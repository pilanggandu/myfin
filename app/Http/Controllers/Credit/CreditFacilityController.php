<?php

namespace App\Http\Controllers\Credit;

use App\Http\Controllers\Controller;
use App\Http\Requests\Credit\StoreCreditFacilityRequest;
use App\Http\Requests\Credit\UpdateCreditFacilityRequest;
use App\Models\CreditFacility;
use App\Services\CreditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CreditFacilityController extends Controller
{
    public function __construct(
        protected CreditService $creditService
    ) {}

    public function store(StoreCreditFacilityRequest $request): RedirectResponse
    {
        $space = app('currentSpace');
        Gate::authorize('create', CreditFacility::class);

        $facility = $this->creditService->createCreditFacility($space, $request->validated());

        return redirect()->route('credit.index', ['tab' => 'accounts'])
            ->with('alert', [
                'type' => 'success',
                'message' => "Fasilitas limit gabungan \"{$facility->name}\" berhasil ditambahkan.",
            ]);
    }

    public function update(UpdateCreditFacilityRequest $request, CreditFacility $facility): RedirectResponse
    {
        Gate::authorize('update', $facility);

        $this->creditService->updateCreditFacility($facility, $request->validated());

        return redirect()->route('credit.index', ['tab' => 'accounts'])
            ->with('alert', [
                'type' => 'success',
                'message' => "Fasilitas limit gabungan \"{$facility->name}\" berhasil diperbarui.",
            ]);
    }

    public function destroy(Request $request, CreditFacility $facility): RedirectResponse
    {
        Gate::authorize('delete', $facility);

        $name = $facility->name;
        $this->creditService->deleteCreditFacility($facility);

        return redirect()->route('credit.index', ['tab' => 'accounts'])
            ->with('alert', [
                'type' => 'success',
                'message' => "Fasilitas limit gabungan \"{$name}\" berhasil dihapus. Kartu anggota kini menggunakan limit mandiri.",
            ]);
    }
}
