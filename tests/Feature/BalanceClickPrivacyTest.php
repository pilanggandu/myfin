<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Category;
use App\Models\RecurringSchedule;
use App\Models\Space;
use App\Models\User;
use App\Services\CreditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BalanceClickPrivacyTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Space $space;

    private Account $account;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->space = $this->user->ensurePersonalSpace();
        $this->account = Account::create([
            'space_id' => $this->space->id,
            'name' => 'Bank BCA Utama',
            'type' => 'bank',
            'opening_balance' => 10000000,
            'opened_on' => '2026-01-01',
            'is_default' => true,
        ]);
    }

    public function test_dashboard_renders_click_to_toggle_balance_script_and_elements(): void
    {
        $response = $this->actingAs($this->user)
            ->withSession(['active_space_id' => $this->space->id])
            ->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('class="privacy-balance"', false);
        $response->assertSee('data-raw-value="Rp 10.000.000"', false);
        $response->assertSee('function toggleSingleBalance(el)', false);
        $response->assertSee('function setupBalanceInteractiveAttributes(el, isMasked)', false);
        $response->assertSee('function syncPrivacyContainer(container)', false);
    }

    public function test_credit_index_wraps_statement_pending_balance_for_privacy_toggle(): void
    {
        $creditAccount = app(CreditService::class)->createCreditAccount($this->space, [
            'name' => 'Mandiri Visa',
            'type' => 'credit_card',
            'credit_limit' => 15000000,
            'billing_cycle_day' => 15,
            'due_day' => 5,
        ]);

        $category = Category::where('space_id', $this->space->id)
            ->where('type', 'expense')
            ->firstOrFail();

        app(CreditService::class)->recordCreditPurchase($this->space, $this->user, [
            'credit_account_id' => $creditAccount->id,
            'category_id' => $category->id,
            'name' => 'Monitor Kerja',
            'total_amount' => 3000000,
            'tenor' => 3,
            'start_date' => now()->toDateString(),
            'first_due_date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($this->user)
            ->withSession(['active_space_id' => $this->space->id])
            ->get(route('credit.index'));

        $response->assertOk();
        $response->assertSee('font-serif font-bold text-sm text-charcoal block privacy-balance', false);
        $response->assertSee('data-raw-value="Rp 1.000.000"', false);
    }

    public function test_recurring_show_only_applies_privacy_balance_to_fixed_amounts(): void
    {
        $category = Category::where('space_id', $this->space->id)
            ->where('type', 'expense')
            ->firstOrFail();

        $fixedSchedule = RecurringSchedule::create([
            'space_id' => $this->space->id,
            'type' => 'expense',
            'name' => 'Internet Rumah',
            'category_id' => $category->id,
            'source_account_id' => $this->account->id,
            'frequency' => 'monthly',
            'anchor_day' => 10,
            'start_date' => '2026-01-10',
            'next_due_date' => '2026-12-10',
            'amount' => 450000,
            'is_variable_amount' => false,
            'status' => 'active',
            'created_by' => $this->user->id,
        ]);

        $variableSchedule = RecurringSchedule::create([
            'space_id' => $this->space->id,
            'type' => 'expense',
            'name' => 'Listrik Pascabayar',
            'category_id' => $category->id,
            'source_account_id' => $this->account->id,
            'frequency' => 'monthly',
            'anchor_day' => 15,
            'start_date' => '2026-01-15',
            'next_due_date' => '2026-12-15',
            'amount' => null,
            'is_variable_amount' => true,
            'status' => 'active',
            'created_by' => $this->user->id,
        ]);

        $fixedResponse = $this->actingAs($this->user)
            ->withSession(['active_space_id' => $this->space->id])
            ->get(route('recurring.show', $fixedSchedule));

        $fixedResponse->assertOk();
        $fixedResponse->assertSee('data-raw-value="Rp 450.000"', false);

        $variableResponse = $this->actingAs($this->user)
            ->withSession(['active_space_id' => $this->space->id])
            ->get(route('recurring.show', $variableSchedule));

        $variableResponse->assertOk();
        $variableResponse->assertSee('Fleksibel (diisi saat konfirmasi)', false);
        $variableResponse->assertDontSee('font-mono font-bold text-sm text-charcoal privacy-balance', false);
    }

    public function test_reports_calendar_renders_privacy_balance_hooks_in_day_modal(): void
    {
        $response = $this->actingAs($this->user)
            ->withSession(['active_space_id' => $this->space->id])
            ->get(route('reports.index', ['tab' => 'daily', 'view' => 'calendar']));

        $response->assertOk();
        $response->assertSee('id="calModalInflow" class="text-sm sm:text-base font-mono font-bold text-forest privacy-balance"', false);
        $response->assertSee('id="calModalOutflow" class="text-sm sm:text-base font-mono font-bold text-red dark:text-rose-400 privacy-balance"', false);
        $response->assertSee('id="calModalNet" class="text-sm sm:text-base font-mono font-bold privacy-balance"', false);
        $response->assertSee('window.syncPrivacyContainer(modal)', false);
    }
}
