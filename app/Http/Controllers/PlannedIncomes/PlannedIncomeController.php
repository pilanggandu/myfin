<?php

namespace App\Http\Controllers\PlannedIncomes;

use App\Http\Controllers\Controller;
use App\Http\Requests\PlannedIncome\RealizePlannedIncomeRequest;
use App\Http\Requests\PlannedIncome\StorePlannedIncomeRequest;
use App\Http\Requests\PlannedIncome\UpdatePlannedIncomeRequest;
use App\Models\PlannedIncome;
use App\Models\Space;
use App\Services\ActivityLogService;
use App\Services\TransactionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class PlannedIncomeController extends Controller
{
    public function __construct(
        protected ActivityLogService $activityLog,
        protected TransactionService $transactionService
    ) {}

    public function store(StorePlannedIncomeRequest $request): RedirectResponse
    {
        /** @var Space $space */
        $space = app('currentSpace');
        $validated = $request->validated();

        $plannedIncome = PlannedIncome::create([
            'space_id' => $space->id,
            'title' => $validated['title'],
            'amount' => $validated['amount'],
            'expected_date' => $validated['expected_date'],
            'account_id' => $validated['account_id'] ?? null,
            'category_id' => $validated['category_id'] ?? null,
            'member_id' => $validated['member_id'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'status' => 'planned',
            'created_by' => $request->user()->id,
        ]);

        $this->activityLog->log(
            module: 'planned_income',
            action: 'created',
            description: "Menambahkan rencana pemasukan '{$plannedIncome->title}' sebesar ".$this->activityLog->formatCurrency($plannedIncome->amount),
            space: $space,
            actor: $request->user(),
            subject: $plannedIncome,
            subjectLabel: $plannedIncome->title,
            properties: [
                'amount' => $plannedIncome->amount,
                'expected_date' => $plannedIncome->expected_date?->toDateString(),
            ],
            request: $request
        );

        return redirect()->route('projections.index', ['tab' => 'planned_incomes'])
            ->with('success', "Rencana pemasukan '{$plannedIncome->title}' berhasil ditambahkan.");
    }

    public function update(UpdatePlannedIncomeRequest $request, PlannedIncome $plannedIncome): RedirectResponse
    {
        Gate::authorize('update', $plannedIncome);

        $space = $plannedIncome->space;
        $validated = $request->validated();
        $before = $plannedIncome->only(['title', 'amount', 'expected_date', 'account_id', 'category_id', 'notes']);

        $plannedIncome->update([
            'title' => $validated['title'],
            'amount' => $validated['amount'],
            'expected_date' => $validated['expected_date'],
            'account_id' => $validated['account_id'] ?? null,
            'category_id' => $validated['category_id'] ?? null,
            'member_id' => $validated['member_id'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'updated_by' => $request->user()->id,
        ]);

        $after = $plannedIncome->only(['title', 'amount', 'expected_date', 'account_id', 'category_id', 'notes']);

        $diff = $this->activityLog->buildDiff($before, $after, [
            'title' => 'Judul',
            'amount' => 'Nominal',
            'expected_date' => 'Tanggal Perkiraan',
            'notes' => 'Catatan',
        ]);

        $this->activityLog->log(
            module: 'planned_income',
            action: 'updated',
            description: "Memperbarui rencana pemasukan '{$plannedIncome->title}'",
            space: $space,
            actor: $request->user(),
            subject: $plannedIncome,
            subjectLabel: $plannedIncome->title,
            properties: $diff,
            request: $request
        );

        return redirect()->route('projections.index', ['tab' => 'planned_incomes'])
            ->with('success', "Rencana pemasukan '{$plannedIncome->title}' berhasil diperbarui.");
    }

    public function destroy(Request $request, PlannedIncome $plannedIncome): RedirectResponse
    {
        Gate::authorize('delete', $plannedIncome);

        $space = $plannedIncome->space;
        $title = $plannedIncome->title;
        $amount = $plannedIncome->amount;

        $plannedIncome->delete();

        $this->activityLog->log(
            module: 'planned_income',
            action: 'deleted',
            description: "Menghapus rencana pemasukan '{$title}' sebesar ".$this->activityLog->formatCurrency($amount),
            space: $space,
            actor: $request->user(),
            subjectLabel: $title,
            request: $request
        );

        return redirect()->route('projections.index', ['tab' => 'planned_incomes'])
            ->with('success', "Rencana pemasukan '{$title}' berhasil dihapus.");
    }

    public function realize(RealizePlannedIncomeRequest $request, PlannedIncome $plannedIncome): RedirectResponse
    {
        Gate::authorize('realize', $plannedIncome);

        $space = $plannedIncome->space;
        $validated = $request->validated();
        $actualAmount = (float) $validated['actual_amount'];
        $recordTransaction = $request->boolean('record_transaction');

        $transaction = DB::transaction(function () use ($space, $request, $plannedIncome, $actualAmount, $recordTransaction, $validated) {
            $recordedTx = null;

            if ($recordTransaction) {
                $recordedTx = $this->transactionService->recordIncome(
                    space: $space,
                    actor: $request->user(),
                    data: [
                        'account_id' => $validated['account_id'],
                        'category_id' => $validated['category_id'],
                        'amount' => $actualAmount,
                        'transaction_date' => $validated['received_date'] ?? now()->toDateString(),
                        'transaction_time' => now()->format('H:i:s'),
                        'description' => 'Realisasi: '.$plannedIncome->title,
                        'member_id' => $plannedIncome->member_id,
                    ]
                );
            }

            $plannedIncome->update([
                'status' => 'received',
                'actual_amount' => $actualAmount,
                'account_id' => $validated['account_id'] ?? $plannedIncome->account_id,
                'category_id' => $validated['category_id'] ?? $plannedIncome->category_id,
                'received_at' => now(),
                'financial_transaction_id' => $recordedTx?->id,
                'updated_by' => $request->user()->id,
            ]);

            $txSuffix = $recordedTx ? " dan transaksi kas (#{$recordedTx->id}) berhasil dicatat." : '.';

            $this->activityLog->log(
                module: 'planned_income',
                action: 'realized',
                description: "Merealisasikan rencana pemasukan '{$plannedIncome->title}' sebesar ".$this->activityLog->formatCurrency($actualAmount).$txSuffix,
                space: $space,
                actor: $request->user(),
                subject: $plannedIncome,
                subjectLabel: $plannedIncome->title,
                properties: array_filter([
                    'actual_amount' => $actualAmount,
                    'record_transaction' => $recordTransaction,
                    'transaction_id' => $recordedTx?->id,
                ], fn (mixed $v): bool => $v !== null),
                request: $request
            );

            return $recordedTx;
        });

        $msg = $transaction
            ? "Rencana pemasukan '{$plannedIncome->title}' berhasil direalisasikan dan transaksi kas telah tercatat."
            : "Rencana pemasukan '{$plannedIncome->title}' berhasil ditandai sebagai telah diterima.";

        return redirect()->route('projections.index', ['tab' => 'planned_incomes'])
            ->with('success', $msg);
    }

    public function cancel(Request $request, PlannedIncome $plannedIncome): RedirectResponse
    {
        Gate::authorize('update', $plannedIncome);

        $space = $plannedIncome->space;
        $plannedIncome->update([
            'status' => 'cancelled',
            'updated_by' => $request->user()->id,
        ]);

        $this->activityLog->log(
            module: 'planned_income',
            action: 'cancelled',
            description: "Membatalkan rencana pemasukan '{$plannedIncome->title}'",
            space: $space,
            actor: $request->user(),
            subject: $plannedIncome,
            subjectLabel: $plannedIncome->title,
            request: $request
        );

        return redirect()->route('projections.index', ['tab' => 'planned_incomes'])
            ->with('success', "Rencana pemasukan '{$plannedIncome->title}' telah dibatalkan.");
    }

    public function reopen(Request $request, PlannedIncome $plannedIncome): RedirectResponse
    {
        Gate::authorize('update', $plannedIncome);

        $space = $plannedIncome->space;
        $hadTx = $plannedIncome->financial_transaction_id !== null;

        $plannedIncome->update([
            'status' => 'planned',
            'actual_amount' => null,
            'received_at' => null,
            'financial_transaction_id' => null,
            'updated_by' => $request->user()->id,
        ]);

        $this->activityLog->log(
            module: 'planned_income',
            action: 'reopened',
            description: "Mengaktifkan kembali rencana pemasukan '{$plannedIncome->title}'",
            space: $space,
            actor: $request->user(),
            subject: $plannedIncome,
            subjectLabel: $plannedIncome->title,
            request: $request
        );

        $msg = "Rencana pemasukan '{$plannedIncome->title}' kembali aktif.";
        if ($hadTx) {
            $msg .= ' Transaksi kas yang tercatat sebelumnya tetap tersimpan di buku transaksi.';
        }

        return redirect()->route('projections.index', ['tab' => 'planned_incomes'])
            ->with('success', $msg);
    }
}
