<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Category;
use App\Models\Space;
use App\Models\User;
use App\Services\CreditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CreditFacilityTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Space $space;

    protected Account $bankAccount;

    protected Category $category;

    protected CreditService $creditService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->space = Space::create([
            'name' => 'Ruang Pribadi',
            'type' => 'personal',
            'owner_id' => $this->user->id,
            'timezone' => 'Asia/Jakarta',
        ]);
        $this->space->spaceMembers()->create([
            'user_id' => $this->user->id,
            'role' => 'owner',
            'status' => 'active',
        ]);

        $this->bankAccount = Account::create([
            'space_id' => $this->space->id,
            'name' => 'Bank Mandiri',
            'type' => 'bank',
            'opening_balance' => 10000000,
            'opened_on' => '2026-01-01',
            'is_default' => true,
        ]);

        $this->category = Category::create([
            'space_id' => $this->space->id,
            'name' => 'Belanja Elektronik',
            'type' => 'expense',
            'is_active' => true,
        ]);

        $this->creditService = app(CreditService::class);
    }

    public function test_can_create_credit_facility_via_http(): void
    {
        $response = $this->actingAs($this->user)
            ->withSession(['current_space_id' => $this->space->id])
            ->post(route('credit.facilities.store'), [
                'name' => 'BCA Combined Facility',
                'total_limit' => 30000000,
                'notes' => 'Fasilitas limit gabungan BCA Batman & SQ',
            ]);

        $response->assertRedirect(route('credit.index', ['tab' => 'accounts']));
        $this->assertDatabaseHas('credit_facilities', [
            'space_id' => $this->space->id,
            'name' => 'BCA Combined Facility',
            'total_limit' => 30000000,
        ]);
    }

    public function test_can_update_credit_facility_via_http(): void
    {
        $facility = $this->creditService->createCreditFacility($this->space, [
            'name' => 'BCA Combined',
            'total_limit' => 30000000,
        ]);

        $response = $this->actingAs($this->user)
            ->withSession(['current_space_id' => $this->space->id])
            ->put(route('credit.facilities.update', $facility), [
                'name' => 'BCA Combined Limit Baru',
                'total_limit' => 45000000,
            ]);

        $response->assertRedirect(route('credit.index', ['tab' => 'accounts']));
        $this->assertDatabaseHas('credit_facilities', [
            'id' => $facility->id,
            'name' => 'BCA Combined Limit Baru',
            'total_limit' => 45000000,
        ]);
    }

    public function test_deleting_facility_resets_cards_to_standalone_limits(): void
    {
        $facility = $this->creditService->createCreditFacility($this->space, [
            'name' => 'BCA Combined',
            'total_limit' => 30000000,
        ]);

        $card = $this->creditService->createCreditAccount($this->space, [
            'name' => 'BCA Everyday',
            'type' => 'credit_card',
            'credit_facility_id' => $facility->id,
            'sub_limit' => 10000000,
        ]);

        $response = $this->actingAs($this->user)
            ->withSession(['current_space_id' => $this->space->id])
            ->delete(route('credit.facilities.destroy', $facility));

        $response->assertRedirect(route('credit.index', ['tab' => 'accounts']));
        $this->assertDatabaseMissing('credit_facilities', ['id' => $facility->id]);

        $card->refresh();
        $this->assertNull($card->credit_facility_id);
        $this->assertEquals(10000000, $card->credit_limit);
        $this->assertNull($card->sub_limit);
    }

    public function test_cards_share_pool_limit_correctly(): void
    {
        $facility = $this->creditService->createCreditFacility($this->space, [
            'name' => 'BCA Combined',
            'total_limit' => 30000000,
        ]);

        $cardA = $this->creditService->createCreditAccount($this->space, [
            'name' => 'BCA Everyday',
            'type' => 'credit_card',
            'credit_facility_id' => $facility->id,
        ]);

        $cardB = $this->creditService->createCreditAccount($this->space, [
            'name' => 'BCA KrisFlyer',
            'type' => 'credit_card',
            'credit_facility_id' => $facility->id,
        ]);

        // Both initially have 30jt available
        $this->assertEquals(30000000, $cardA->availableLimit());
        $this->assertEquals(30000000, $cardB->availableLimit());
        $this->assertEquals(30000000, $facility->availableLimit());

        // Card A spends 12.000.000 (cicilan 3 bulan)
        $this->creditService->recordCreditPurchase($this->space, $this->user, [
            'credit_account_id' => $cardA->id,
            'category_id' => $this->category->id,
            'name' => 'Laptop Baru',
            'total_amount' => 12000000,
            'tenor' => 3,
            'interest_rate' => 0,
            'start_date' => now()->toDateString(),
        ]);

        $cardA->refresh();
        $cardB->refresh();
        $facility->refresh();

        // Card A used 12jt, available drops to 18jt
        $this->assertEquals(12000000, $cardA->currentLiability());
        $this->assertEquals(0, $cardB->currentLiability());
        $this->assertEquals(12000000, $facility->currentLiability());

        // Both cards and facility now share remaining 18jt limit!
        $this->assertEquals(18000000, $facility->availableLimit());
        $this->assertEquals(18000000, $cardA->availableLimit());
        $this->assertEquals(18000000, $cardB->availableLimit());
    }

    public function test_card_with_sub_limit_is_capped_by_sub_limit_and_facility_pool(): void
    {
        $facility = $this->creditService->createCreditFacility($this->space, [
            'name' => 'BCA Combined',
            'total_limit' => 30000000,
        ]);

        $primaryCard = $this->creditService->createCreditAccount($this->space, [
            'name' => 'BCA Utama',
            'type' => 'credit_card',
            'credit_facility_id' => $facility->id,
        ]);

        $supplementaryCard = $this->creditService->createCreditAccount($this->space, [
            'name' => 'BCA Suplemen Anak',
            'type' => 'credit_card',
            'credit_facility_id' => $facility->id,
            'sub_limit' => 5000000, // Capped at 5jt
        ]);

        // Supplementary card max available is 5jt
        $this->assertEquals(5000000, $supplementaryCard->availableLimit());
        $this->assertEquals(30000000, $primaryCard->availableLimit());

        // Primary spends 28jt
        $this->creditService->recordCreditPurchase($this->space, $this->user, [
            'credit_account_id' => $primaryCard->id,
            'category_id' => $this->category->id,
            'name' => 'Belanja Besar',
            'total_amount' => 28000000,
            'tenor' => 1,
            'interest_rate' => 0,
            'start_date' => now()->toDateString(),
        ]);

        $facility->refresh();
        $supplementaryCard->refresh();

        // Facility only has 2jt left, so supplementary card available limit is now capped to 2jt!
        $this->assertEquals(2000000, $facility->availableLimit());
        $this->assertEquals(2000000, $supplementaryCard->availableLimit());
    }

    public function test_purchase_exceeding_shared_available_limit_throws_validation_error(): void
    {
        $facility = $this->creditService->createCreditFacility($this->space, [
            'name' => 'BCA Combined',
            'total_limit' => 20000000,
        ]);

        $cardA = $this->creditService->createCreditAccount($this->space, [
            'name' => 'BCA Everyday',
            'type' => 'credit_card',
            'credit_facility_id' => $facility->id,
        ]);

        $cardB = $this->creditService->createCreditAccount($this->space, [
            'name' => 'BCA KrisFlyer',
            'type' => 'credit_card',
            'credit_facility_id' => $facility->id,
        ]);

        // Card A spends 15jt
        $this->creditService->recordCreditPurchase($this->space, $this->user, [
            'credit_account_id' => $cardA->id,
            'category_id' => $this->category->id,
            'name' => 'Belanja 1',
            'total_amount' => 15000000,
            'tenor' => 1,
            'interest_rate' => 0,
            'start_date' => now()->toDateString(),
        ]);

        // Card B tries to spend 10jt (only 5jt left in facility)
        $this->expectException(ValidationException::class);
        $this->creditService->recordCreditPurchase($this->space, $this->user, [
            'credit_account_id' => $cardB->id,
            'category_id' => $this->category->id,
            'name' => 'Belanja 2',
            'total_amount' => 10000000,
            'tenor' => 1,
            'interest_rate' => 0,
            'start_date' => now()->toDateString(),
        ]);
    }

    public function test_space_total_credit_limit_does_not_double_count(): void
    {
        // 1 standalone card with 10jt limit
        $this->creditService->createCreditAccount($this->space, [
            'name' => 'Mandiri Standalone',
            'type' => 'credit_card',
            'credit_limit' => 10000000,
        ]);

        // 1 facility with 30jt limit containing 3 cards
        $facility = $this->creditService->createCreditFacility($this->space, [
            'name' => 'BCA Combined',
            'total_limit' => 30000000,
        ]);

        $this->creditService->createCreditAccount($this->space, [
            'name' => 'BCA 1',
            'type' => 'credit_card',
            'credit_facility_id' => $facility->id,
        ]);

        $this->creditService->createCreditAccount($this->space, [
            'name' => 'BCA 2',
            'type' => 'credit_card',
            'credit_facility_id' => $facility->id,
        ]);

        $this->creditService->createCreditAccount($this->space, [
            'name' => 'BCA 3',
            'type' => 'credit_card',
            'credit_facility_id' => $facility->id,
            'sub_limit' => 5000000,
        ]);

        // Total space limit should be 10jt (standalone) + 30jt (facility) = 40jt, NOT 10jt + 3x30jt!
        $this->assertEquals(40000000, $this->space->totalCreditLimit());
    }

    public function test_credit_index_page_renders_with_facilities_successfully(): void
    {
        $facility = $this->creditService->createCreditFacility($this->space, [
            'name' => 'BCA Combined Facility',
            'total_limit' => 30000000,
        ]);

        $this->creditService->createCreditAccount($this->space, [
            'name' => 'BCA Everyday Card',
            'type' => 'credit_card',
            'credit_facility_id' => $facility->id,
        ]);

        $response = $this->actingAs($this->user)
            ->withSession(['current_space_id' => $this->space->id])
            ->get(route('credit.index', ['tab' => 'accounts']));

        $response->assertOk();
        $response->assertSee('BCA Combined Facility');
        $response->assertSee('BCA Everyday Card');
        $response->assertSee('Limit Gabungan: BCA Combined Facility');
    }
}
