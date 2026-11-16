<?php

namespace App\Http\Controllers\Transactions;

use App\Http\Controllers\Controller;
use App\Http\Requests\Transaction\StoreTransactionRuleRequest;
use App\Http\Requests\Transaction\UpdateTransactionRuleRequest;
use App\Models\Space;
use App\Models\TransactionRule;
use App\Services\ActivityLogService;
use App\Services\TransactionRuleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class TransactionRuleController extends Controller
{
    public function __construct(
        protected TransactionRuleService $ruleService,
        protected ActivityLogService $activityLog
    ) {}

    public function index(Request $request): View
    {
        /** @var Space $space */
        $space = app('currentSpace');

        $query = $space->transactionRules()
            ->with(['account', 'actionCategory', 'actionMember', 'creator'])
            ->ordered();

        if ($request->filled('type')) {
            $query->where('type_criterion', $request->input('type'));
        }

        if ($request->filled('status')) {
            $status = $request->input('status');
            if ($status === 'active') {
                $query->where('is_active', true);
            } elseif ($status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        if ($request->filled('search')) {
            $search = '%'.$request->input('search').'%';
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', $search)
                    ->orWhere('keyword', 'like', $search)
                    ->orWhere('action_description', 'like', $search)
                    ->orWhere('action_note', 'like', $search);
            });
        }

        $rules = $query->get();

        $stats = [
            'total' => $space->transactionRules()->count(),
            'active' => $space->transactionRules()->active()->count(),
            'inactive' => $space->transactionRules()->where('is_active', false)->count(),
        ];

        $categories = $space->activeCategories()->orderBy('type')->orderBy('name')->get();
        $members = $space->members()->get();
        $accounts = $space->activeAccounts()->orderBy('name')->get();

        return view('transaction-rules.index', compact(
            'rules',
            'stats',
            'categories',
            'members',
            'accounts'
        ));
    }

    public function store(StoreTransactionRuleRequest $request): RedirectResponse
    {
        /** @var Space $space */
        $space = app('currentSpace');

        $data = $request->validated();
        $data['space_id'] = $space->id;
        $data['created_by'] = $request->user()->id;

        $rule = TransactionRule::create($data);

        $this->activityLog->log(
            module: 'transaction_rule',
            action: 'created',
            description: "Membuat aturan otomatis transaksi '{$rule->name}'",
            space: $space,
            actor: $request->user(),
            subject: $rule,
            subjectLabel: $rule->name,
            properties: [
                'keyword' => $rule->keyword,
                'keyword_operator' => $rule->keyword_operator,
                'amount_operator' => $rule->amount_operator,
                'type_criterion' => $rule->type_criterion,
            ]
        );

        return redirect()->route('transaction-rules.index')
            ->with('success', "Aturan transaksi '{$rule->name}' berhasil dibuat.");
    }

    public function show(TransactionRule $rule): JsonResponse
    {
        Gate::authorize('view', $rule);

        return response()->json([
            'rule' => $rule->load(['account', 'actionCategory', 'actionMember']),
        ]);
    }

    public function update(UpdateTransactionRuleRequest $request, TransactionRule $rule): RedirectResponse
    {
        Gate::authorize('update', $rule);

        $data = $request->validated();
        $data['updated_by'] = $request->user()->id;

        $rule->update($data);

        $this->activityLog->log(
            module: 'transaction_rule',
            action: 'updated',
            description: "Memperbarui aturan transaksi '{$rule->name}'",
            space: $rule->space,
            actor: $request->user(),
            subject: $rule,
            subjectLabel: $rule->name,
            properties: [
                'priority' => $rule->priority,
                'is_active' => $rule->is_active,
            ]
        );

        return redirect()->route('transaction-rules.index')
            ->with('success', "Aturan transaksi '{$rule->name}' berhasil diperbarui.");
    }

    public function destroy(TransactionRule $rule): RedirectResponse
    {
        Gate::authorize('delete', $rule);

        $name = $rule->name;
        $space = $rule->space;

        $rule->delete();

        $this->activityLog->log(
            module: 'transaction_rule',
            action: 'deleted',
            description: "Menghapus aturan transaksi '{$name}'",
            space: $space,
            actor: request()->user(),
            subjectLabel: $name,
        );

        return redirect()->route('transaction-rules.index')
            ->with('success', "Aturan transaksi '{$name}' telah dihapus.");
    }

    public function toggleActive(TransactionRule $rule): JsonResponse|RedirectResponse
    {
        Gate::authorize('toggleActive', $rule);

        $rule->update([
            'is_active' => ! $rule->is_active,
            'updated_by' => request()->user()->id,
        ]);

        $statusText = $rule->is_active ? 'diaktifkan' : 'dinonaktifkan';

        $this->activityLog->log(
            module: 'transaction_rule',
            action: 'toggled',
            description: "Mengubah status aturan '{$rule->name}' menjadi {$statusText}",
            space: $rule->space,
            actor: request()->user(),
            subject: $rule,
            subjectLabel: $rule->name,
            properties: ['is_active' => $rule->is_active]
        );

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'is_active' => $rule->is_active,
                'message' => "Aturan '{$rule->name}' berhasil {$statusText}.",
            ]);
        }

        return redirect()->back()
            ->with('success', "Aturan '{$rule->name}' berhasil {$statusText}.");
    }

    public function simulate(Request $request): JsonResponse
    {
        /** @var Space $space */
        $space = app('currentSpace');

        $validated = $request->validate([
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0'],
            'type' => ['nullable', 'in:expense,income'],
            'account_id' => ['nullable', 'integer'],
        ]);

        $result = $this->ruleService->simulate(
            $space,
            (string) $validated['description'],
            (float) $validated['amount'],
            $validated['type'] ?? null,
            ! empty($validated['account_id']) ? (int) $validated['account_id'] : null
        );

        return response()->json($result);
    }

    public function applyExisting(Request $request, TransactionRule $rule): RedirectResponse
    {
        Gate::authorize('applyExisting', $rule);

        /** @var Space $space */
        $space = app('currentSpace');

        $onlyUncategorized = $request->boolean('only_uncategorized', true);
        $updatedCount = $this->ruleService->applyToExisting($space, $rule, $onlyUncategorized);

        $msg = $updatedCount > 0
            ? "Berhasil menerapkan aturan '{$rule->name}' ke {$updatedCount} transaksi lama."
            : "Tidak ada transaksi lama yang cocok atau membutuhkan pembaruan dari aturan '{$rule->name}'.";

        return redirect()->route('transaction-rules.index')
            ->with('success', $msg);
    }
}
