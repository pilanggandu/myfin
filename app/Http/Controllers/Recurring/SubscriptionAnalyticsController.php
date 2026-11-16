<?php

namespace App\Http\Controllers\Recurring;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Category;
use App\Models\Member;
use App\Services\SubscriptionAnalyticsService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubscriptionAnalyticsController extends Controller
{
    public function __construct(
        protected SubscriptionAnalyticsService $analyticsService
    ) {}

    public function index(Request $request): View
    {
        $space = app('currentSpace');
        $tz = $space->timezone ?? 'Asia/Jakarta';
        $today = Carbon::now($tz)->startOfDay();

        $filters = [
            'status' => $request->query('status', 'active'),
            'frequency' => $request->query('frequency'),
            'category_id' => $request->query('category_id') ? (int) $request->query('category_id') : null,
            'source_account_id' => $request->query('source_account_id') ? (int) $request->query('source_account_id') : null,
            'member_id' => $request->query('member_id') ? (int) $request->query('member_id') : null,
            'has_price_hike' => $request->boolean('has_price_hike'),
            'search' => $request->query('search'),
        ];

        $tab = $request->query('tab', 'category');

        $analytics = $this->analyticsService->getAnalytics($space, $filters, $today);

        $categories = Category::where('space_id', $space->id)
            ->expense()
            ->active()
            ->orderBy('name')
            ->get();

        $accounts = Account::where('space_id', $space->id)
            ->active()
            ->expenseSources()
            ->orderBy('name')
            ->get();

        $members = Member::where('space_id', $space->id)
            ->active()
            ->orderBy('name')
            ->get();

        return view('recurring.subscriptions', compact(
            'space',
            'today',
            'filters',
            'tab',
            'analytics',
            'categories',
            'accounts',
            'members'
        ));
    }
}
