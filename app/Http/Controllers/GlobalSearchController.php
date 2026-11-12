<?php

namespace App\Http\Controllers;

use App\Http\Requests\GlobalSearchRequest;
use App\Services\GlobalSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class GlobalSearchController extends Controller
{
    public function index(GlobalSearchRequest $request, GlobalSearchService $searchService): JsonResponse|RedirectResponse
    {
        $space = app('currentSpace');
        $validated = $request->validated();

        $results = $searchService->search($space, $request->user(), $validated);

        if ($request->expectsJson() || $request->wantsJson() || $request->ajax()) {
            return response()->json($results);
        }

        return redirect()->to($results['transactions_index_url']);
    }
}
