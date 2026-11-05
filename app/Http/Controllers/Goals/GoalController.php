<?php

namespace App\Http\Controllers\Goals;

use App\Http\Controllers\Controller;
use App\Http\Requests\Goal\ContributeGoalRequest;
use App\Http\Requests\Goal\StoreGoalRequest;
use App\Http\Requests\Goal\UpdateGoalRequest;
use App\Http\Requests\Goal\WithdrawGoalRequest;
use App\Models\Goal;
use App\Models\GoalContribution;
use App\Services\TransactionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class GoalController extends Controller
{
    public function __construct(
        protected TransactionService $transactionService
    ) {}

    public function index(Request $request): View
    {
        $space = app('currentSpace');
        $now = $space ? $space->now() : Carbon::now('Asia/Jakarta');

        $status = $request->query('status', 'active');

        $query = $space ? $space->goals()->with(['account', 'category']) : Goal::query();

        if ($status === 'active') {
            $query->where('status', 'active');
        } elseif ($status === 'achieved') {
            $query->where('status', 'achieved');
        } elseif ($status === 'paused') {
            $query->where('status', 'paused');
        }

        $goals = $query->orderBy('status')
            ->orderBy('target_date', 'asc')
            ->orderByDesc('id')
            ->get();

        $allGoals = $space ? $space->goals()->get() : collect();
        $totalTargetAmount = (float) $allGoals->where('status', 'active')->sum('target_amount');
        $totalCurrentAmount = (float) $allGoals->where('status', 'active')->sum('current_amount');
        $achievedCount = $allGoals->where('status', 'achieved')->count();
        $activeCount = $allGoals->where('status', 'active')->count();

        $overallProgress = $totalTargetAmount > 0
            ? min(100.0, round(($totalCurrentAmount / $totalTargetAmount) * 100, 1))
            : 0.0;

        return view('goals.index', compact(
            'space',
            'now',
            'goals',
            'status',
            'totalTargetAmount',
            'totalCurrentAmount',
            'overallProgress',
            'achievedCount',
            'activeCount'
        ));
    }

    public function create(): View
    {
        $space = app('currentSpace');
        $now = $space ? $space->now() : Carbon::now('Asia/Jakarta');

        $cashAccounts = $space
            ? $space->accounts()->whereNull('archived_at')->whereIn('type', ['bank', 'cash', 'e-wallet', 'investment'])->orderBy('name')->get()
            : collect();

        $categories = $space
            ? $space->categories()->whereNull('archived_at')->orderBy('name')->get()
            : collect();

        return view('goals.create', compact('space', 'now', 'cashAccounts', 'categories'));
    }

    public function store(StoreGoalRequest $request): RedirectResponse
    {
        $space = app('currentSpace');
        $user = $request->user();
        $validated = $request->validated();

        $goal = DB::transaction(function () use ($space, $user, $validated) {
            $goal = Goal::create([
                'space_id' => $space->id,
                'account_id' => $validated['account_id'] ?? null,
                'category_id' => $validated['category_id'] ?? null,
                'name' => $validated['name'],
                'notes' => $validated['notes'] ?? null,
                'target_amount' => $validated['target_amount'],
                'current_amount' => 0,
                'start_date' => $validated['start_date'],
                'target_date' => $validated['target_date'] ?? null,
                'icon' => $validated['icon'] ?? '🎯',
                'color' => $validated['color'] ?? '#174C40',
                'status' => 'active',
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            $initialDeposit = (float) ($validated['initial_deposit'] ?? 0);
            if ($initialDeposit > 0) {
                $deduct = ! empty($validated['deduct_from_account']) && ! empty($validated['account_id']);
                $financialTxId = null;

                if ($deduct) {
                    $catId = $goal->category_id ?: $space->categories()->expense()->first()?->id;
                    if ($catId) {
                        $tx = $this->transactionService->recordExpense($space, $user, [
                            'account_id' => $validated['account_id'],
                            'category_id' => $catId,
                            'amount' => $initialDeposit,
                            'transaction_date' => $goal->start_date->toDateString(),
                            'transaction_time' => now()->format('H:i:s'),
                            'description' => 'Setoran Awal Target: '.$goal->name,
                            'request_key' => (string) Str::uuid(),
                        ]);
                        $financialTxId = $tx->id;
                    }
                }

                GoalContribution::create([
                    'space_id' => $space->id,
                    'goal_id' => $goal->id,
                    'user_id' => $user->id,
                    'member_id' => $space->customMembers()->where('name', $user->name)->first()?->id,
                    'financial_transaction_id' => $financialTxId,
                    'type' => 'deposit',
                    'amount' => $initialDeposit,
                    'contribution_date' => $goal->start_date,
                    'notes' => 'Setoran awal pembukaan target',
                    'created_by' => $user->id,
                ]);

                $goal->syncAmount();
            }

            return $goal;
        });

        return redirect()->route('goals.show', $goal)->with('success', 'Target tabungan "'.$goal->name.'" berhasil dibuat!');
    }

    public function show(Goal $goal): View
    {
        $space = app('currentSpace');
        if ($goal->space_id !== $space?->id) {
            abort(403, 'Akses tidak diizinkan untuk target ini.');
        }

        $now = $space ? $space->now() : Carbon::now('Asia/Jakarta');

        $goal->load(['account', 'category', 'creator', 'contributions.user', 'contributions.member']);

        $cashAccounts = $space
            ? $space->accounts()->whereNull('archived_at')->whereIn('type', ['bank', 'cash', 'e-wallet', 'investment'])->orderBy('name')->get()
            : collect();

        $members = $space
            ? $space->customMembers()->active()->orderBy('name')->get()
            : collect();

        // Breakdown per kontributor (User / Member) untuk Ruang Bersama
        $memberBreakdown = DB::table('goal_contributions')
            ->leftJoin('members', 'goal_contributions.member_id', '=', 'members.id')
            ->leftJoin('users', 'goal_contributions.user_id', '=', 'users.id')
            ->where('goal_contributions.goal_id', $goal->id)
            ->where('goal_contributions.type', 'deposit')
            ->groupBy('goal_contributions.member_id', 'goal_contributions.user_id', 'members.name', 'members.color', 'users.name')
            ->select(
                DB::raw('COALESCE(members.name, users.name, "Anggota") as contributor_name'),
                DB::raw('COALESCE(members.color, "#174C40") as contributor_color'),
                DB::raw('SUM(goal_contributions.amount) as total_contributed'),
                DB::raw('COUNT(goal_contributions.id) as contribution_count')
            )
            ->orderByDesc('total_contributed')
            ->get();

        $totalDeposited = (float) $goal->contributions->where('type', 'deposit')->sum('amount');

        $memberBreakdown = $memberBreakdown->map(function ($item) use ($totalDeposited) {
            $amt = (float) $item->total_contributed;
            $pct = $totalDeposited > 0 ? round(($amt / $totalDeposited) * 100, 1) : 0;

            return (object) [
                'name' => $item->contributor_name,
                'color' => $item->contributor_color,
                'amount' => $amt,
                'count' => (int) $item->contribution_count,
                'percentage' => $pct,
            ];
        });

        return view('goals.show', compact('space', 'now', 'goal', 'cashAccounts', 'members', 'memberBreakdown'));
    }

    public function edit(Goal $goal): View
    {
        $space = app('currentSpace');
        if ($goal->space_id !== $space?->id) {
            abort(403);
        }

        $cashAccounts = $space
            ? $space->accounts()->whereNull('archived_at')->whereIn('type', ['bank', 'cash', 'e-wallet', 'investment'])->orderBy('name')->get()
            : collect();

        $categories = $space
            ? $space->categories()->whereNull('archived_at')->orderBy('name')->get()
            : collect();

        return view('goals.edit', compact('space', 'goal', 'cashAccounts', 'categories'));
    }

    public function update(UpdateGoalRequest $request, Goal $goal): RedirectResponse
    {
        $space = app('currentSpace');
        if ($goal->space_id !== $space?->id) {
            abort(403);
        }

        $validated = $request->validated();
        $user = $request->user();

        $goal->update([
            'account_id' => $validated['account_id'] ?? null,
            'category_id' => $validated['category_id'] ?? null,
            'name' => $validated['name'],
            'notes' => $validated['notes'] ?? null,
            'target_amount' => $validated['target_amount'],
            'start_date' => $validated['start_date'],
            'target_date' => $validated['target_date'] ?? null,
            'icon' => $validated['icon'] ?? $goal->icon,
            'color' => $validated['color'] ?? $goal->color,
            'status' => $validated['status'],
            'updated_by' => $user->id,
        ]);

        $goal->syncAmount();

        return redirect()->route('goals.show', $goal)->with('success', 'Target tabungan berhasil diperbarui.');
    }

    public function destroy(Goal $goal): RedirectResponse
    {
        $space = app('currentSpace');
        if ($goal->space_id !== $space?->id) {
            abort(403);
        }

        $name = $goal->name;
        $goal->delete();

        return redirect()->route('goals.index')->with('success', 'Target tabungan "'.$name.'" berhasil dihapus.');
    }

    public function contribute(ContributeGoalRequest $request, Goal $goal): RedirectResponse
    {
        $space = app('currentSpace');
        if ($goal->space_id !== $space?->id) {
            abort(403);
        }

        $validated = $request->validated();
        $user = $request->user();
        $amount = (float) $validated['amount'];

        DB::transaction(function () use ($space, $user, $goal, $validated, $amount) {
            $deduct = ! empty($validated['deduct_from_account']) && ! empty($validated['account_id']);
            $financialTxId = null;

            if ($deduct) {
                $catId = $goal->category_id ?: $space->categories()->expense()->first()?->id;
                if ($catId) {
                    $tx = $this->transactionService->recordExpense($space, $user, [
                        'account_id' => $validated['account_id'],
                        'category_id' => $catId,
                        'amount' => $amount,
                        'transaction_date' => $validated['contribution_date'],
                        'transaction_time' => now()->format('H:i:s'),
                        'description' => 'Setoran Target: '.$goal->name,
                        'member_id' => $validated['member_id'] ?? null,
                        'request_key' => (string) Str::uuid(),
                    ]);
                    $financialTxId = $tx->id;
                }
            }

            GoalContribution::create([
                'space_id' => $space->id,
                'goal_id' => $goal->id,
                'user_id' => $user->id,
                'member_id' => $validated['member_id'] ?? null,
                'financial_transaction_id' => $financialTxId,
                'type' => 'deposit',
                'amount' => $amount,
                'contribution_date' => $validated['contribution_date'],
                'notes' => $validated['notes'] ?? 'Setoran tabungan',
                'created_by' => $user->id,
            ]);

            $goal->syncAmount();
        });

        $goal->refresh();
        $message = 'Berhasil menabung Rp '.number_format($amount, 0, ',', '.').' ke target "'.$goal->name.'"!';
        if ($goal->isAchieved()) {
            $message .= ' 🎉 Selamat, target tabungan telah tercapai!';
        }

        return redirect()->route('goals.show', $goal)->with('success', $message);
    }

    public function withdraw(WithdrawGoalRequest $request, Goal $goal): RedirectResponse
    {
        $space = app('currentSpace');
        if ($goal->space_id !== $space?->id) {
            abort(403);
        }

        $validated = $request->validated();
        $user = $request->user();
        $amount = (float) $validated['amount'];

        if ($amount > $goal->current_amount) {
            throw ValidationException::withMessages([
                'amount' => 'Nominal penarikan tidak boleh melebihi saldo tabungan terkumpul saat ini (Rp '.number_format($goal->current_amount, 0, ',', '.').').',
            ]);
        }

        DB::transaction(function () use ($space, $user, $goal, $validated, $amount) {
            $depositReal = ! empty($validated['deposit_to_account']) && ! empty($validated['account_id']);
            $financialTxId = null;

            if ($depositReal) {
                $catId = $space->categories()->income()->first()?->id;
                if ($catId) {
                    $tx = $this->transactionService->recordIncome($space, $user, [
                        'account_id' => $validated['account_id'],
                        'category_id' => $catId,
                        'amount' => $amount,
                        'transaction_date' => $validated['withdrawal_date'],
                        'transaction_time' => now()->format('H:i:s'),
                        'description' => 'Pencairan Target: '.$goal->name,
                        'member_id' => $validated['member_id'] ?? null,
                        'request_key' => (string) Str::uuid(),
                    ]);
                    $financialTxId = $tx->id;
                }
            }

            GoalContribution::create([
                'space_id' => $space->id,
                'goal_id' => $goal->id,
                'user_id' => $user->id,
                'member_id' => $validated['member_id'] ?? null,
                'financial_transaction_id' => $financialTxId,
                'type' => 'withdrawal',
                'amount' => $amount,
                'contribution_date' => $validated['withdrawal_date'],
                'notes' => $validated['notes'] ?? 'Pencairan tabungan',
                'created_by' => $user->id,
            ]);

            $goal->syncAmount();
        });

        return redirect()->route('goals.show', $goal)->with('success', 'Berhasil mencairkan Rp '.number_format($amount, 0, ',', '.').' dari target "'.$goal->name.'".');
    }

    public function toggleStatus(Goal $goal): RedirectResponse
    {
        $space = app('currentSpace');
        if ($goal->space_id !== $space?->id) {
            abort(403);
        }

        if ($goal->status === 'paused') {
            $goal->status = $goal->current_amount >= $goal->target_amount ? 'achieved' : 'active';
            $msg = 'Target tabungan diaktifkan kembali.';
        } elseif ($goal->status === 'active') {
            $goal->status = 'paused';
            $msg = 'Target tabungan dijeda.';
        } else {
            $goal->status = 'active';
            $msg = 'Status target tabungan diperbarui.';
        }

        $goal->save();

        return redirect()->route('goals.show', $goal)->with('success', $msg);
    }
}
