<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Category;
use App\Models\Member;
use App\Models\Space;
use App\Models\TransactionRule;
use App\Models\User;
use App\Services\TransactionRuleService;
use App\Services\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionRuleTest extends TestCase
{
    use RefreshDatabase;

    private User $userA;

    private User $userB;

    private Space $spaceA;

    private Space $spaceB;

    private Account $accountA;

    private Category $foodCategory;

    private Category $salaryCategory;

    private Member $memberGilang;

    private TransactionRuleService $ruleService;

    private TransactionService $transactionService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userA = User::factory()->create(['name' => 'Gilang User A', 'email' => 'userA@example.com']);
        $this->userB = User::factory()->create(['name' => 'Other User B', 'email' => 'userB@example.com']);

        $this->spaceA = Space::create([
            'name' => 'Ruang Personal A',
            'type' => 'personal',
            'owner_id' => $this->userA->id,
            'timezone' => 'Asia/Jakarta',
        ]);
        $this->spaceA->members()->attach($this->userA->id, ['role' => 'owner', 'status' => 'active']);

        $this->spaceB = Space::create([
            'name' => 'Ruang Personal B',
            'type' => 'personal',
            'owner_id' => $this->userB->id,
            'timezone' => 'Asia/Jakarta',
        ]);
        $this->spaceB->members()->attach($this->userB->id, ['role' => 'owner', 'status' => 'active']);

        $this->accountA = Account::create([
            'space_id' => $this->spaceA->id,
            'name' => 'BCA Utama',
            'type' => 'bank',
            'opening_balance' => 5000000,
            'opened_on' => '2026-01-01',
        ]);

        $this->foodCategory = Category::create([
            'space_id' => $this->spaceA->id,
            'name' => 'Makanan & Minuman',
            'type' => 'expense',
        ]);

        $this->salaryCategory = Category::create([
            'space_id' => $this->spaceA->id,
            'name' => 'Gaji Bulanan',
            'type' => 'income',
        ]);

        $this->memberGilang = Member::create([
            'space_id' => $this->spaceA->id,
            'name' => 'Gilang Pratama',
            'color' => '#10b981',
        ]);

        $this->ruleService = app(TransactionRuleService::class);
        $this->transactionService = app(TransactionService::class);
    }

    public function test_user_can_view_transaction_rules_index(): void
    {
        TransactionRule::create([
            'space_id' => $this->spaceA->id,
            'name' => 'Aturan Kopi Rutin',
            'is_active' => true,
            'priority' => 10,
            'type_criterion' => 'expense',
            'keyword' => 'kopi',
            'keyword_operator' => 'contains',
            'amount_operator' => 'any',
            'action_category_id' => $this->foodCategory->id,
            'created_by' => $this->userA->id,
        ]);

        $response = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->get(route('transaction-rules.index'));

        $response->assertStatus(200);
        $response->assertSee('Aturan Otomatis Transaksi');
        $response->assertSee('Aturan Kopi Rutin');
    }

    public function test_user_can_create_transaction_rule(): void
    {
        $response = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->post(route('transaction-rules.store'), [
                'name' => 'Auto Kopi Kenangan',
                'priority' => 5,
                'is_active' => '1',
                'type_criterion' => 'expense',
                'keyword' => 'kenangan',
                'keyword_operator' => 'contains',
                'amount_operator' => 'greater_than_or_equal',
                'amount_min' => '20.000',
                'action_category_id' => $this->foodCategory->id,
                'action_member_id' => $this->memberGilang->id,
                'action_note' => 'Kopi santai kerja',
            ]);

        $response->assertRedirect(route('transaction-rules.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('transaction_rules', [
            'space_id' => $this->spaceA->id,
            'name' => 'Auto Kopi Kenangan',
            'keyword' => 'kenangan',
            'keyword_operator' => 'contains',
            'amount_operator' => 'greater_than_or_equal',
            'amount_min' => 20000,
            'action_category_id' => $this->foodCategory->id,
            'action_member_id' => $this->memberGilang->id,
            'action_note' => 'Kopi santai kerja',
        ]);
    }

    public function test_rule_keyword_operators_match_accurately(): void
    {
        // 1. Operator contains
        $ruleContains = new TransactionRule([
            'keyword' => 'starbucks',
            'keyword_operator' => 'contains',
            'amount_operator' => 'any',
            'type_criterion' => 'all',
        ]);
        $this->assertTrue($this->ruleService->evaluateRule($ruleContains, 'Beli Starbucks Reserve', 65000));
        $this->assertFalse($this->ruleService->evaluateRule($ruleContains, 'Beli Kopi Kenangan', 30000));

        // 2. Operator starts_with
        $ruleStartsWith = new TransactionRule([
            'keyword' => 'gaji',
            'keyword_operator' => 'starts_with',
            'amount_operator' => 'any',
            'type_criterion' => 'all',
        ]);
        $this->assertTrue($this->ruleService->evaluateRule($ruleStartsWith, 'Gaji Bulan November', 8500000));
        $this->assertFalse($this->ruleService->evaluateRule($ruleStartsWith, 'Transfer Bonus dan Gaji', 1000000));

        // 3. Operator ends_with
        $ruleEndsWith = new TransactionRule([
            'keyword' => 'pln',
            'keyword_operator' => 'ends_with',
            'amount_operator' => 'any',
            'type_criterion' => 'all',
        ]);
        $this->assertTrue($this->ruleService->evaluateRule($ruleEndsWith, 'Tagihan Listrik PLN', 250000));
        $this->assertFalse($this->ruleService->evaluateRule($ruleEndsWith, 'PLN Token Prabayar', 250000));

        // 4. Operator exact
        $ruleExact = new TransactionRule([
            'keyword' => 'Netflix',
            'keyword_operator' => 'exact',
            'amount_operator' => 'any',
            'type_criterion' => 'all',
        ]);
        $this->assertTrue($this->ruleService->evaluateRule($ruleExact, 'netflix', 186000));
        $this->assertFalse($this->ruleService->evaluateRule($ruleExact, 'Langganan Netflix', 186000));

        // 5. Operator regex
        $ruleRegex = new TransactionRule([
            'keyword' => '^Trf\\s+Payroll',
            'keyword_operator' => 'regex',
            'amount_operator' => 'any',
            'type_criterion' => 'all',
        ]);
        $this->assertTrue($this->ruleService->evaluateRule($ruleRegex, 'Trf Payroll PT ABC', 10000000));
        $this->assertFalse($this->ruleService->evaluateRule($ruleRegex, 'Biaya Admin Trf Payroll', 6500));
    }

    public function test_rule_amount_conditions_match_accurately(): void
    {
        // 1. greater_than
        $ruleGt = new TransactionRule([
            'keyword' => null,
            'keyword_operator' => 'contains',
            'amount_operator' => 'greater_than',
            'amount_min' => 100000,
            'type_criterion' => 'all',
        ]);
        $this->assertTrue($this->ruleService->evaluateRule($ruleGt, 'Makan Mewah', 150000));
        $this->assertFalse($this->ruleService->evaluateRule($ruleGt, 'Makan Hemat', 100000));

        // 2. equals
        $ruleEq = new TransactionRule([
            'keyword' => null,
            'keyword_operator' => 'contains',
            'amount_operator' => 'equals',
            'amount_min' => 54000,
            'type_criterion' => 'all',
        ]);
        $this->assertTrue($this->ruleService->evaluateRule($ruleEq, 'Langganan Spotify', 54000));
        $this->assertFalse($this->ruleService->evaluateRule($ruleEq, 'Langganan Lain', 60000));

        // 3. between
        $ruleBetween = new TransactionRule([
            'keyword' => null,
            'keyword_operator' => 'contains',
            'amount_operator' => 'between',
            'amount_min' => 50000,
            'amount_max' => 100000,
            'type_criterion' => 'all',
        ]);
        $this->assertTrue($this->ruleService->evaluateRule($ruleBetween, 'Belanja Mart', 75000));
        $this->assertTrue($this->ruleService->evaluateRule($ruleBetween, 'Belanja Mart', 50000));
        $this->assertTrue($this->ruleService->evaluateRule($ruleBetween, 'Belanja Mart', 100000));
        $this->assertFalse($this->ruleService->evaluateRule($ruleBetween, 'Belanja Mart', 49000));
        $this->assertFalse($this->ruleService->evaluateRule($ruleBetween, 'Belanja Mart', 101000));
    }

    public function test_rule_simulator_returns_expected_actions(): void
    {
        TransactionRule::create([
            'space_id' => $this->spaceA->id,
            'name' => 'Auto Makan Restoran',
            'is_active' => true,
            'priority' => 10,
            'type_criterion' => 'expense',
            'keyword' => 'restoran',
            'keyword_operator' => 'contains',
            'amount_operator' => 'greater_than',
            'amount_min' => 50000,
            'action_category_id' => $this->foodCategory->id,
            'action_member_id' => $this->memberGilang->id,
            'action_note' => 'Makan malam santai',
            'stop_processing' => true,
            'created_by' => $this->userA->id,
        ]);

        $response = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->postJson(route('transaction-rules.simulate'), [
                'description' => 'Makan di Restoran Padang Pagi Sore',
                'amount' => 125000,
                'type' => 'expense',
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'matched' => true,
            'matched_count' => 1,
            'result' => [
                'category_id' => $this->foodCategory->id,
                'category_name' => $this->foodCategory->name,
                'member_id' => $this->memberGilang->id,
                'member_name' => $this->memberGilang->name,
                'notes' => 'Makan malam santai',
            ],
        ]);
    }

    public function test_user_can_toggle_rule_active_status(): void
    {
        $rule = TransactionRule::create([
            'space_id' => $this->spaceA->id,
            'name' => 'Aturan Belanja',
            'is_active' => true,
            'priority' => 1,
            'type_criterion' => 'expense',
            'keyword' => 'superindo',
            'keyword_operator' => 'contains',
            'amount_operator' => 'any',
            'action_category_id' => $this->foodCategory->id,
            'created_by' => $this->userA->id,
        ]);

        $response = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->postJson(route('transaction-rules.toggle-active', $rule));

        $response->assertStatus(200);
        $response->assertJson(['success' => true, 'is_active' => false]);
        $this->assertFalse($rule->fresh()->is_active);
    }

    public function test_retroactive_rule_application_updates_existing_transactions(): void
    {
        // 1. Buat transaksi yang belum memiliki kategori
        $tx = $this->transactionService->recordExpense($this->spaceA, $this->userA, [
            'account_id' => $this->accountA->id,
            'category_id' => $this->foodCategory->id, // Awalnya food
            'amount' => 35000,
            'transaction_date' => '2026-11-10',
            'description' => 'Kopi Kenangan Senayan City',
        ]);
        // Set ke null untuk menguji retroactive uncategorized matching
        $tx->update(['category_id' => null, 'notes' => null]);

        // 2. Buat aturan
        $rule = TransactionRule::create([
            'space_id' => $this->spaceA->id,
            'name' => 'Aturan Retroactive Kopi',
            'is_active' => true,
            'priority' => 10,
            'type_criterion' => 'expense',
            'keyword' => 'kenangan',
            'keyword_operator' => 'contains',
            'amount_operator' => 'any',
            'action_category_id' => $this->foodCategory->id,
            'action_note' => 'Otomatis diisi aturan retro',
            'created_by' => $this->userA->id,
        ]);

        $response = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->post(route('transaction-rules.apply-existing', $rule), [
                'only_uncategorized' => '1',
            ]);

        $response->assertRedirect(route('transaction-rules.index'));
        $response->assertSessionHas('success');

        $this->assertEquals($this->foodCategory->id, $tx->fresh()->category_id);
        $this->assertEquals('Otomatis diisi aturan retro', $tx->fresh()->notes);
    }

    public function test_user_cannot_access_or_modify_other_spaces_rules(): void
    {
        $ruleB = TransactionRule::create([
            'space_id' => $this->spaceB->id,
            'name' => 'Aturan Rahasia Ruang B',
            'is_active' => true,
            'priority' => 0,
            'type_criterion' => 'all',
            'keyword' => 'rahasia',
            'keyword_operator' => 'contains',
            'amount_operator' => 'any',
            'action_note' => 'Rahasia B',
            'created_by' => $this->userB->id,
        ]);

        // User A mencoba mengakses atau mengubah aturan milik User B di Ruang B
        $response = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->put(route('transaction-rules.update', $ruleB), [
                'name' => 'Hacked Name',
                'type_criterion' => 'all',
                'keyword_operator' => 'contains',
                'amount_operator' => 'any',
                'action_note' => 'Hacked',
            ]);

        $response->assertStatus(403);
    }
}
