<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveSpace
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();
            $activeSpaceId = session('active_space_id');
            $currentSpace = null;

            if ($activeSpaceId) {
                $currentSpace = $user->activeSpaces()->where('spaces.id', $activeSpaceId)->first();
            }

            if (! $currentSpace) {
                $currentSpace = $user->personalSpace();

                if (! $currentSpace) {
                    $currentSpace = $user->ensurePersonalSpace();
                }

                if (! $currentSpace) {
                    $currentSpace = $user->activeSpaces()->first();
                }

                if ($currentSpace) {
                    session(['active_space_id' => $currentSpace->id]);
                }
            }

            if ($currentSpace) {
                app()->instance('currentSpace', $currentSpace);
                View::share('currentSpace', $currentSpace);
                View::share('userSpaces', $user->activeSpaces()->orderBy('type', 'desc')->orderBy('name')->get());
                View::share('quickAccounts', $currentSpace->activeAccounts()->orderByDesc('is_default')->orderBy('name')->get());
                View::share('quickRecentAccountIds', $currentSpace->recentAccountIds(limit: 5));
                View::share('quickIncomeCategories', $currentSpace->activeCategories()->income()->orderBy('name')->get());
                View::share('quickExpenseCategories', $currentSpace->activeCategories()->expense()->orderBy('name')->get());
                View::share('quickRecentIncomeCategoryIds', $currentSpace->recentCategoryIds('income', 5));
                View::share('quickRecentExpenseCategoryIds', $currentSpace->recentCategoryIds('expense', 5));
                View::share('quickMembers', $currentSpace->customMembers()->active()->orderBy('name')->get(['id', 'name']));
            }
        }

        return $next($request);
    }
}
