<?php

namespace App\Http\Controllers\ShortPlans;

use App\Http\Controllers\Controller;
use App\Http\Requests\ShortPlan\RealizeShortPlanRequest;
use App\Http\Requests\ShortPlan\StoreShortPlanItemRequest;
use App\Http\Requests\ShortPlan\StoreShortPlanRequest;
use App\Http\Requests\ShortPlan\UpdateShortPlanRequest;
use App\Models\ShortPlan;
use App\Models\ShortPlanItem;
use App\Models\Space;
use App\Services\TransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ShortPlanController extends Controller
{
    public function __construct(
        protected TransactionService $transactionService
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', ShortPlan::class);

        $space = app('currentSpace');
        $now = $space ? $space->now() : Carbon::now('Asia/Jakarta');

        $status = (string) $request->query('status', 'planned');
        if (! in_array($status, ['planned', 'done', 'cancelled', 'all'], true)) {
            $status = 'planned';
        }

        $priority = (string) $request->query('priority', 'all');
        if (! in_array($priority, ['all', 'high', 'medium', 'low'], true)) {
            $priority = 'all';
        }

        $search = trim((string) $request->query('search', ''));

        $query = $space->shortPlans()
            ->with(['account', 'category', 'member', 'transaction', 'items']);

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        if ($priority !== 'all') {
            $query->where('priority', $priority);
        }

        if ($search !== '') {
            $escapedSearch = addcslashes($search, '%_\\');
            $query->where(function ($q) use ($escapedSearch): void {
                $q->where('title', 'like', '%'.$escapedSearch.'%')
                    ->orWhere('notes', 'like', '%'.$escapedSearch.'%');
            });
        }

        if ($status === 'done') {
            $query->orderByDesc('realized_at')->orderByDesc('id');
        } elseif ($status === 'cancelled') {
            $query->orderByDesc('updated_at')->orderByDesc('id');
        } else {
            $query->orderByRaw("CASE status WHEN 'planned' THEN 0 WHEN 'done' THEN 1 ELSE 2 END ASC")
                ->orderByRaw('CASE WHEN target_date IS NULL THEN 1 ELSE 0 END ASC, target_date ASC')
                ->orderByRaw("CASE priority WHEN 'high' THEN 0 WHEN 'medium' THEN 1 ELSE 2 END ASC")
                ->orderByDesc('id');
        }

        $shortPlans = $query->get();

        $statusCounts = $space->shortPlans()
            ->select('status', DB::raw('COUNT(*) as aggregate_count'))
            ->groupBy('status')
            ->pluck('aggregate_count', 'status');

        $plannedCount = (int) ($statusCounts['planned'] ?? 0);
        $doneCount = (int) ($statusCounts['done'] ?? 0);
        $cancelledCount = (int) ($statusCounts['cancelled'] ?? 0);
        $allCount = $plannedCount + $doneCount + $cancelledCount;

        $activePlansForMetrics = $space->shortPlans()
            ->where('status', 'planned')
            ->get(['id', 'planned_amount', 'target_date', 'priority']);

        $totalPlannedActive = round((float) $activePlansForMetrics->sum('planned_amount'), 2);
        $liquidBalance = (float) $space->totalBalance();
        $coverageDiff = round($liquidBalance - $totalPlannedActive, 2);

        $urgentOrSoonCount = $activePlansForMetrics->filter(function (ShortPlan $plan) use ($now): bool {
            return $plan->priority === 'high' || in_array($plan->dueStatus($now), ['overdue', 'due_today', 'soon'], true);
        })->count();

        $monthStart = $now->copy()->startOfMonth();
        $monthEnd = $now->copy()->endOfMonth();

        $doneThisMonthQuery = $space->shortPlans()
            ->where('status', 'done')
            ->whereBetween('realized_at', [$monthStart, $monthEnd]);

        $doneThisMonthCount = (clone $doneThisMonthQuery)->count();
        $doneThisMonthTotal = round((float) (clone $doneThisMonthQuery)
            ->selectRaw('COALESCE(SUM(COALESCE(actual_amount, planned_amount)), 0) as total_realized')
            ->value('total_realized'), 2);

        $liquidAccounts = $this->liquidAccounts($space);
        $defaultAccount = $liquidAccounts->firstWhere('is_default', true) ?? $liquidAccounts->first();
        $recentAccountIds = $space->recentAccountIds('expense', 5);
        $expenseCategories = $space->activeCategories()->expense()->orderBy('name')->get();
        $recentExpenseCategoryIds = $space->recentCategoryIds('expense', 5);
        $members = $space->customMembers()->active()->orderBy('name')->get();
        $today = $now->toDateString();
        $requestKey = (string) Str::uuid();

        return view('short-plans.index', compact(
            'space',
            'now',
            'today',
            'shortPlans',
            'status',
            'priority',
            'search',
            'plannedCount',
            'doneCount',
            'cancelledCount',
            'allCount',
            'totalPlannedActive',
            'liquidBalance',
            'coverageDiff',
            'urgentOrSoonCount',
            'doneThisMonthCount',
            'doneThisMonthTotal',
            'liquidAccounts',
            'defaultAccount',
            'recentAccountIds',
            'expenseCategories',
            'recentExpenseCategoryIds',
            'members',
            'requestKey'
        ));
    }

    public function create(): View
    {
        Gate::authorize('create', ShortPlan::class);

        $space = app('currentSpace');
        $now = $space ? $space->now() : Carbon::now('Asia/Jakarta');

        $liquidAccounts = $this->liquidAccounts($space);
        $defaultAccount = $liquidAccounts->firstWhere('is_default', true) ?? $liquidAccounts->first();
        $recentAccountIds = $space->recentAccountIds('expense', 5);
        $expenseCategories = $space->activeCategories()->expense()->orderBy('name')->get();
        $recentExpenseCategoryIds = $space->recentCategoryIds('expense', 5);
        $members = $space->customMembers()->active()->orderBy('name')->get();
        $today = $now->toDateString();

        return view('short-plans.create', compact(
            'space',
            'now',
            'today',
            'liquidAccounts',
            'defaultAccount',
            'recentAccountIds',
            'expenseCategories',
            'recentExpenseCategoryIds',
            'members'
        ));
    }

    public function store(StoreShortPlanRequest $request): RedirectResponse
    {
        Gate::authorize('create', ShortPlan::class);

        $space = app('currentSpace');
        $user = $request->user();
        $validated = $request->validated();

        $shortPlan = DB::transaction(function () use ($space, $user, $validated): ShortPlan {
            $plan = ShortPlan::create([
                'space_id' => $space->id,
                'account_id' => $validated['account_id'] ?? null,
                'category_id' => $validated['category_id'] ?? null,
                'member_id' => $validated['member_id'] ?? null,
                'title' => $validated['title'],
                'notes' => $validated['notes'] ?? null,
                'planned_amount' => $validated['planned_amount'],
                'target_date' => $validated['target_date'] ?? null,
                'priority' => $validated['priority'],
                'status' => 'planned',
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            $items = $validated['items'] ?? [];
            foreach ($items as $idx => $item) {
                ShortPlanItem::create([
                    'space_id' => $space->id,
                    'short_plan_id' => $plan->id,
                    'title' => $item['title'],
                    'estimated_amount' => (float) ($item['estimated_amount'] ?? 0),
                    'is_checked' => false,
                    'sort_order' => $idx + 1,
                ]);
            }

            return $plan;
        });

        if ($request->input('redirect_to') === 'index') {
            return redirect()->route('short-plans.index')
                ->with('success', 'Rencana "'.$shortPlan->title.'" berhasil ditambahkan.');
        }

        return redirect()->route('short-plans.show', $shortPlan)
            ->with('success', 'Rencana "'.$shortPlan->title.'" berhasil dibuat.');
    }

    public function show(ShortPlan $shortPlan): View
    {
        $space = $this->authorizePlan($shortPlan, 'view');
        $now = $space->now();

        $shortPlan->load([
            'account',
            'category',
            'member',
            'transaction.entries.account',
            'creator',
            'updater',
            'items',
        ]);

        $liquidAccounts = $this->liquidAccounts($space);
        $defaultAccount = $liquidAccounts->firstWhere('is_default', true) ?? $liquidAccounts->first();
        $recentAccountIds = $space->recentAccountIds('expense', 5);
        $expenseCategories = $space->activeCategories()->expense()->orderBy('name')->get();
        $recentExpenseCategoryIds = $space->recentCategoryIds('expense', 5);
        $members = $space->customMembers()->active()->orderBy('name')->get();
        $today = $now->toDateString();
        $requestKey = (string) Str::uuid();

        return view('short-plans.show', compact(
            'space',
            'now',
            'today',
            'shortPlan',
            'liquidAccounts',
            'defaultAccount',
            'recentAccountIds',
            'expenseCategories',
            'recentExpenseCategoryIds',
            'members',
            'requestKey'
        ));
    }

    public function edit(ShortPlan $shortPlan): View
    {
        $space = $this->authorizePlan($shortPlan, 'update');
        $now = $space->now();

        $liquidAccounts = $this->liquidAccounts($space);
        $recentAccountIds = $space->recentAccountIds('expense', 5);
        $expenseCategories = $space->activeCategories()->expense()->orderBy('name')->get();
        $recentExpenseCategoryIds = $space->recentCategoryIds('expense', 5);
        $members = $space->customMembers()->active()->orderBy('name')->get();

        return view('short-plans.edit', compact(
            'space',
            'now',
            'shortPlan',
            'liquidAccounts',
            'recentAccountIds',
            'expenseCategories',
            'recentExpenseCategoryIds',
            'members'
        ));
    }

    public function update(UpdateShortPlanRequest $request, ShortPlan $shortPlan): RedirectResponse
    {
        $this->authorizePlan($shortPlan, 'update');

        $validated = $request->validated();
        $user = $request->user();

        $updateData = [
            'account_id' => $validated['account_id'] ?? null,
            'category_id' => $validated['category_id'] ?? null,
            'member_id' => $validated['member_id'] ?? null,
            'title' => $validated['title'],
            'notes' => $validated['notes'] ?? null,
            'planned_amount' => $validated['planned_amount'],
            'target_date' => $validated['target_date'] ?? null,
            'priority' => $validated['priority'],
            'updated_by' => $user->id,
        ];

        if (! empty($validated['status'])) {
            $updateData['status'] = $validated['status'];
            if ($validated['status'] === 'done' && ! $shortPlan->realized_at) {
                $updateData['realized_at'] = $shortPlan->space->now();
                $updateData['actual_amount'] = $shortPlan->actual_amount ?? $validated['planned_amount'];
            } elseif ($validated['status'] !== 'done') {
                $updateData['realized_at'] = null;
            }
        }

        $shortPlan->update($updateData);

        return redirect()->route('short-plans.show', $shortPlan)
            ->with('success', 'Rencana "'.$shortPlan->title.'" berhasil diperbarui.');
    }

    public function destroy(ShortPlan $shortPlan): RedirectResponse
    {
        $this->authorizePlan($shortPlan, 'delete');

        $title = $shortPlan->title;
        $shortPlan->delete();

        return redirect()->route('short-plans.index')
            ->with('success', 'Rencana "'.$title.'" berhasil dihapus.');
    }

    public function realize(RealizeShortPlanRequest $request, ShortPlan $shortPlan): RedirectResponse
    {
        $space = $this->authorizePlan($shortPlan, 'update');

        if ($shortPlan->isDone()) {
            return back()->with('warning', 'Rencana "'.$shortPlan->title.'" sudah ditandai selesai sebelumnya.');
        }

        $validated = $request->validated();
        $user = $request->user();
        $recordTransaction = ! empty($validated['record_transaction']);

        $transaction = DB::transaction(function () use ($space, $user, $shortPlan, $validated, $recordTransaction) {
            $recordedTx = null;

            if ($recordTransaction) {
                $actualAmount = (float) $validated['actual_amount'];
                $description = trim((string) ($validated['description'] ?? ''));
                if ($description === '') {
                    $description = $shortPlan->title;
                }

                $recordedTx = $this->transactionService->recordExpense($space, $user, [
                    'account_id' => $validated['account_id'],
                    'category_id' => $validated['category_id'],
                    'amount' => $actualAmount,
                    'transaction_date' => $validated['transaction_date'],
                    'transaction_time' => $space->now()->format('H:i:s'),
                    'description' => $description,
                    'member_id' => $validated['member_id'] ?? $shortPlan->member_id,
                    'request_key' => $validated['request_key'] ?? (string) Str::uuid(),
                ]);

                $shortPlan->update([
                    'status' => 'done',
                    'actual_amount' => $actualAmount,
                    'account_id' => $validated['account_id'],
                    'category_id' => $validated['category_id'],
                    'member_id' => $validated['member_id'] ?? $shortPlan->member_id,
                    'financial_transaction_id' => $recordedTx->id,
                    'realized_at' => $space->now(),
                    'updated_by' => $user->id,
                ]);
            } else {
                $actualAmount = $validated['actual_amount'] !== null
                    ? (float) $validated['actual_amount']
                    : (float) $shortPlan->planned_amount;

                $shortPlan->update([
                    'status' => 'done',
                    'actual_amount' => $actualAmount,
                    'financial_transaction_id' => null,
                    'realized_at' => $space->now(),
                    'updated_by' => $user->id,
                ]);
            }

            return $recordedTx;
        });

        $message = $transaction
            ? 'Rencana "'.$shortPlan->title.'" selesai dan transaksi pengeluaran telah dicatat.'
            : 'Rencana "'.$shortPlan->title.'" berhasil ditandai selesai.';

        if ($request->input('redirect_to') === 'index') {
            return redirect()->route('short-plans.index')->with('success', $message);
        }

        return redirect()->route('short-plans.show', $shortPlan)->with('success', $message);
    }

    public function cancel(Request $request, ShortPlan $shortPlan): RedirectResponse
    {
        $this->authorizePlan($shortPlan, 'update');

        if ($shortPlan->isDone()) {
            return back()->with('warning', 'Rencana yang sudah terealisasi tidak dapat dibatalkan langsung. Buka kembali terlebih dahulu jika diperlukan.');
        }

        $shortPlan->update([
            'status' => 'cancelled',
            'updated_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Rencana "'.$shortPlan->title.'" dibatalkan.');
    }

    public function reopen(Request $request, ShortPlan $shortPlan): RedirectResponse
    {
        $this->authorizePlan($shortPlan, 'update');

        $hadLinkedTx = $shortPlan->financial_transaction_id !== null;

        $shortPlan->update([
            'status' => 'planned',
            'actual_amount' => null,
            'realized_at' => null,
            'financial_transaction_id' => null,
            'updated_by' => $request->user()->id,
        ]);

        $msg = 'Rencana "'.$shortPlan->title.'" diaktifkan kembali.';
        if ($hadLinkedTx) {
            $msg .= ' Transaksi kas sebelumnya tetap tersimpan di buku transaksi.';
        }

        return back()->with('success', $msg);
    }

    public function storeItem(StoreShortPlanItemRequest $request, ShortPlan $shortPlan): RedirectResponse
    {
        $space = $this->authorizePlan($shortPlan, 'update');
        $validated = $request->validated();

        $nextOrder = ((int) $shortPlan->items()->max('sort_order')) + 1;

        ShortPlanItem::create([
            'space_id' => $space->id,
            'short_plan_id' => $shortPlan->id,
            'title' => $validated['title'],
            'estimated_amount' => (float) ($validated['estimated_amount'] ?? 0),
            'is_checked' => false,
            'sort_order' => $nextOrder,
        ]);

        return redirect()->route('short-plans.show', $shortPlan)
            ->with('success', 'Item rincian berhasil ditambahkan.');
    }

    public function toggleItem(Request $request, ShortPlan $shortPlan, ShortPlanItem $item): RedirectResponse|JsonResponse
    {
        $this->authorizePlan($shortPlan, 'update');

        if ((int) $item->short_plan_id !== (int) $shortPlan->id) {
            abort(403);
        }

        $item->update([
            'is_checked' => ! $item->is_checked,
        ]);

        if ($request->expectsJson() || $request->wantsJson()) {
            $items = $shortPlan->items()->get();

            return response()->json([
                'success' => true,
                'item_id' => $item->id,
                'is_checked' => $item->is_checked,
                'checked_count' => $items->where('is_checked', true)->count(),
                'total_count' => $items->count(),
                'checked_amount' => round((float) $items->where('is_checked', true)->sum('estimated_amount'), 2),
                'items_total_amount' => round((float) $items->sum('estimated_amount'), 2),
            ]);
        }

        return redirect()->route('short-plans.show', $shortPlan);
    }

    public function syncAmountFromItems(Request $request, ShortPlan $shortPlan): RedirectResponse
    {
        $this->authorizePlan($shortPlan, 'update');

        $total = $shortPlan->itemsTotalAmount();
        if ($total < 1) {
            return back()->with('warning', 'Total nominal item rincian minimal Rp 1 untuk disinkronkan ke estimasi rencana.');
        }

        $shortPlan->update([
            'planned_amount' => $total,
            'updated_by' => $request->user()->id,
        ]);

        return redirect()->route('short-plans.show', $shortPlan)
            ->with('success', 'Estimasi biaya rencana disinkronkan menjadi Rp '.number_format($total, 0, ',', '.').'.');
    }

    public function destroyItem(ShortPlan $shortPlan, ShortPlanItem $item): RedirectResponse
    {
        $this->authorizePlan($shortPlan, 'update');

        if ((int) $item->short_plan_id !== (int) $shortPlan->id) {
            abort(403);
        }

        $item->delete();

        return redirect()->route('short-plans.show', $shortPlan)
            ->with('success', 'Item rincian dihapus.');
    }

    protected function authorizePlan(ShortPlan $shortPlan, string $ability): Space
    {
        Gate::authorize($ability, $shortPlan);

        $currentSpace = app('currentSpace');
        if (! $currentSpace || (int) $shortPlan->space_id !== (int) $currentSpace->id) {
            abort(403, 'Akses tidak diizinkan untuk rencana di ruang ini.');
        }

        return $currentSpace;
    }

    protected function liquidAccounts(Space $space)
    {
        return $space->activeAccounts()
            ->liquidAssets()
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();
    }
}
