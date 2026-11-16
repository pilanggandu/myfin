<?php

namespace App\Http\Controllers\Transactions;

use App\Http\Controllers\Controller;
use App\Models\Space;
use App\Services\TransactionImportService;
use App\Services\TransactionRuleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TransactionImportController extends Controller
{
    public function __construct(
        protected TransactionImportService $importService,
        protected TransactionRuleService $ruleService
    ) {}

    public function show(Request $request): View
    {
        /** @var Space $space */
        $space = app('currentSpace');

        $accounts = $space->activeAccounts()->orderBy('name')->get();
        $categories = $space->activeCategories()->orderBy('type')->orderBy('name')->get();
        $members = $space->members()->get();
        $activeRulesCount = $space->transactionRules()->active()->count();

        return view('transactions.import', compact(
            'accounts',
            'categories',
            'members',
            'activeRulesCount'
        ));
    }

    public function parse(Request $request): JsonResponse|View
    {
        /** @var Space $space */
        $space = app('currentSpace');

        $request->validate([
            'account_id' => ['required', 'integer', 'exists:accounts,id,space_id,'.$space->id],
            'csv_file' => ['nullable', 'file', 'mimes:csv,txt', 'max:5120'],
            'pasted_csv' => ['nullable', 'string', 'max:500000'],
        ]);

        $content = '';
        if ($request->hasFile('csv_file')) {
            $content = (string) file_get_contents($request->file('csv_file')->getRealPath());
        } elseif ($request->filled('pasted_csv')) {
            $content = (string) $request->input('pasted_csv');
        } else {
            throw ValidationException::withMessages([
                'csv_file' => 'Unggah berkas CSV atau tempelkan teks CSV untuk diproses.',
            ]);
        }

        $accountId = (int) $request->input('account_id');
        $parsed = $this->importService->parseCsvContent($content, $space, $accountId);

        if ($request->wantsJson()) {
            return response()->json($parsed);
        }

        $accounts = $space->activeAccounts()->orderBy('name')->get();
        $categories = $space->activeCategories()->orderBy('type')->orderBy('name')->get();
        $members = $space->members()->get();
        $activeRulesCount = $space->transactionRules()->active()->count();

        return view('transactions.import', array_merge(
            compact('accounts', 'categories', 'members', 'activeRulesCount', 'accountId'),
            ['previewData' => $parsed]
        ));
    }

    public function execute(Request $request): JsonResponse|RedirectResponse
    {
        /** @var Space $space */
        $space = app('currentSpace');

        $request->validate([
            'account_id' => ['required', 'integer', 'exists:accounts,id,space_id,'.$space->id],
            'rows' => ['required', 'array', 'min:1'],
            'rows.*.description' => ['required', 'string', 'max:255'],
            'rows.*.amount' => ['required', 'numeric', 'min:0.01'],
            'rows.*.date' => ['required', 'date'],
            'rows.*.type' => ['required', 'in:expense,income'],
            'rows.*.category_id' => ['nullable', 'integer'],
            'rows.*.member_id' => ['nullable', 'integer'],
            'rows.*.notes' => ['nullable', 'string', 'max:1000'],
            'rows.*.selected' => ['nullable', 'boolean'],
            'rows.*.primary_matched_rule' => ['nullable', 'string'],
        ]);

        $accountId = (int) $request->input('account_id');
        $rows = $request->input('rows');

        $result = $this->importService->executeImport(
            $space,
            $request->user(),
            $rows,
            $accountId
        );

        $message = "Berhasil mengimpor {$result['imported_count']} transaksi.";
        if ($result['matched_rules_count'] > 0) {
            $message .= " ({$result['matched_rules_count']} transaksi otomatis dikategorikan oleh aturan).";
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'result' => $result,
                'redirect_url' => route('transactions.index'),
            ]);
        }

        return redirect()->route('transactions.index')
            ->with('success', $message);
    }

    public function downloadSample(): Response
    {
        $csv = $this->importService->generateSampleCsv();

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="contoh_impor_transaksi.csv"',
        ]);
    }
}
