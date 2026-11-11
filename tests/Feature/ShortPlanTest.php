<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Category;
use App\Models\FinancialTransaction;
use App\Models\Member;
use App\Models\ShortPlan;
use App\Models\ShortPlanItem;
use App\Models\Space;
use App\Models\User;
use App\Policies\ShortPlanPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ShortPlanTest extends TestCase
{
    use RefreshDatabase;

    private User $userA;

    private User $userB;

    private Space $spaceA;

    private Space $spaceB;

    private Account $bca;

    private Account $creditAccount;

    private Category $expenseCat;

    private Category $incomeCat;

    private Member $memberA;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userA = User::factory()->create([
            'name' => 'User A',
            'email' => 'a@example.com',
            'password' => Hash::make('secret123'),
        ]);
        $this->spaceA = Space::create([
            'name' => 'Ruang Personal A',
            'type' => 'personal',
            'owner_id' => $this->userA->id,
            'timezone' => 'Asia/Jakarta',
        ]);
        $this->spaceA->members()->attach($this->userA->id, ['role' => 'owner', 'status' => 'active']);

        $this->userB = User::factory()->create([
            'name' => 'User B',
            'email' => 'b@example.com',
            'password' => Hash::make('secret123'),
        ]);
        $this->spaceB = Space::create([
            'name' => 'Ruang Personal B',
            'type' => 'personal',
            'owner_id' => $this->userB->id,
            'timezone' => 'Asia/Jakarta',
        ]);
        $this->spaceB->members()->attach($this->userB->id, ['role' => 'owner', 'status' => 'active']);

        $this->bca = Account::create([
            'space_id' => $this->spaceA->id,
            'name' => 'Bank BCA',
            'type' => 'bank',
            'opening_balance' => 10000000,
            'opened_on' => '2026-01-01',
            'is_default' => true,
        ]);

        $this->creditAccount = Account::create([
            'space_id' => $this->spaceA->id,
            'name' => 'Kartu Kredit BCA',
            'type' => 'credit_card',
            'opening_balance' => 0,
            'opened_on' => '2026-01-01',
        ]);

        $this->expenseCat = Category::create([
            'space_id' => $this->spaceA->id,
            'name' => 'Belanja Harian',
            'type' => 'expense',
        ]);

        $this->incomeCat = Category::create([
            'space_id' => $this->spaceA->id,
            'name' => 'Gaji',
            'type' => 'income',
        ]);

        $this->memberA = Member::create([
            'space_id' => $this->spaceA->id,
            'name' => 'Keluarga',
            'color' => '#0B4A8B',
        ]);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('short-plans.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_user_can_view_short_plans_index_and_empty_state(): void
    {
        $response = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->get(route('short-plans.index'));

        $response->assertOk();
        $response->assertSee('Short Plan');
        $response->assertSee('Belum Ada Short Plan');
        $response->assertSee('Buat Rencana Pertama');
    }

    public function test_user_can_create_short_plan_with_items(): void
    {
        $response = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->post(route('short-plans.store'), [
                'title' => 'Servis Motor Berkala',
                'planned_amount' => '350.000',
                'target_date' => '2026-11-20',
                'priority' => 'high',
                'account_id' => $this->bca->id,
                'category_id' => $this->expenseCat->id,
                'member_id' => $this->memberA->id,
                'notes' => 'Servis di bengkel resmi',
                'items' => [
                    ['title' => 'Ganti Oli Mesin', 'estimated_amount' => '85.000'],
                    ['title' => 'Kampas Rem Depan', 'estimated_amount' => '120.000'],
                ],
            ]);

        $plan = ShortPlan::first();
        $this->assertNotNull($plan);
        $this->assertSame('Servis Motor Berkala', $plan->title);
        $this->assertEquals(350000, (float) $plan->planned_amount);
        $this->assertSame('high', $plan->priority);
        $this->assertSame('planned', $plan->status);
        $this->assertSame($this->spaceA->id, $plan->space_id);
        $this->assertSame(2, $plan->items()->count());

        $response->assertRedirect(route('short-plans.show', $plan));
    }

    public function test_store_short_plan_validates_required_and_space_scoped_inputs(): void
    {
        $spaceBAccount = Account::create([
            'space_id' => $this->spaceB->id,
            'name' => 'Mandiri B',
            'type' => 'bank',
            'opening_balance' => 500000,
            'opened_on' => '2026-01-01',
        ]);

        $emptyResponse = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->post(route('short-plans.store'), [
                'priority' => 'medium',
            ]);

        $emptyResponse->assertSessionHasErrors([
            'title' => 'Nama rencana wajib diisi.',
            'planned_amount' => 'Estimasi biaya wajib diisi.',
        ]);

        $badRefResponse = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->post(route('short-plans.store'), [
                'title' => 'Rencana Tidak Valid',
                'planned_amount' => '100.000',
                'priority' => 'medium',
                'account_id' => $spaceBAccount->id,
                'category_id' => $this->incomeCat->id,
            ]);

        $badRefResponse->assertSessionHasErrors(['account_id', 'category_id']);

        $liabilityResponse = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->post(route('short-plans.store'), [
                'title' => 'Pakai Kartu Kredit',
                'planned_amount' => '100.000',
                'priority' => 'medium',
                'account_id' => $this->creditAccount->id,
            ]);

        $liabilityResponse->assertSessionHasErrors(['account_id']);
        $this->assertSame(0, ShortPlan::count());
    }

    public function test_user_can_manage_checklist_items_and_sync_amount(): void
    {
        $plan = ShortPlan::create([
            'space_id' => $this->spaceA->id,
            'title' => 'Belanja Dapur',
            'planned_amount' => 100000,
            'priority' => 'medium',
            'status' => 'planned',
            'created_by' => $this->userA->id,
            'updated_by' => $this->userA->id,
        ]);

        $addRes = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->post(route('short-plans.items.store', $plan), [
                'title' => 'Beras 5kg',
                'estimated_amount' => '75.000',
            ]);

        $addRes->assertRedirect(route('short-plans.show', $plan));

        $item1 = ShortPlanItem::firstOrFail();
        ShortPlanItem::create([
            'space_id' => $this->spaceA->id,
            'short_plan_id' => $plan->id,
            'title' => 'Minyak Goreng',
            'estimated_amount' => 40000,
            'is_checked' => false,
            'sort_order' => 2,
        ]);

        $toggleRes = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->postJson(route('short-plans.items.toggle', [$plan, $item1]));

        $toggleRes->assertOk()
            ->assertJson([
                'success' => true,
                'is_checked' => true,
                'checked_count' => 1,
                'total_count' => 2,
                'checked_amount' => 75000,
                'items_total_amount' => 115000,
            ]);

        $syncRes = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->post(route('short-plans.sync-amount', $plan));

        $syncRes->assertRedirect(route('short-plans.show', $plan));
        $this->assertEquals(115000, (float) $plan->fresh()->planned_amount);

        $delRes = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->delete(route('short-plans.items.destroy', [$plan, $item1]));

        $delRes->assertRedirect(route('short-plans.show', $plan));
        $this->assertSame(1, $plan->items()->count());
    }

    public function test_user_can_realize_short_plan_and_record_expense_transaction(): void
    {
        $plan = ShortPlan::create([
            'space_id' => $this->spaceA->id,
            'account_id' => $this->bca->id,
            'category_id' => $this->expenseCat->id,
            'member_id' => $this->memberA->id,
            'title' => 'Beli Sepatu Kerja',
            'planned_amount' => 500000,
            'priority' => 'high',
            'status' => 'planned',
            'created_by' => $this->userA->id,
            'updated_by' => $this->userA->id,
        ]);

        $response = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->post(route('short-plans.realize', $plan), [
                'record_transaction' => '1',
                'actual_amount' => '450.000',
                'transaction_date' => '2026-11-11',
                'account_id' => $this->bca->id,
                'category_id' => $this->expenseCat->id,
                'description' => 'Beli Sepatu Kerja (Diskon)',
            ]);

        $response->assertRedirect(route('short-plans.show', $plan));

        $plan->refresh();
        $this->assertSame('done', $plan->status);
        $this->assertEquals(450000, (float) $plan->actual_amount);
        $this->assertNotNull($plan->realized_at);
        $this->assertNotNull($plan->financial_transaction_id);

        $tx = FinancialTransaction::find($plan->financial_transaction_id);
        $this->assertNotNull($tx);
        $this->assertSame('expense', $tx->type);
        $this->assertEquals(450000, (float) $tx->totalAmount());
        $this->assertSame($this->memberA->id, $tx->member_id);

        // Balance reduced from 10.000.000 to 9.550.000
        $this->assertEquals(9550000, $this->bca->fresh()->currentBalance());

        // Realizing an already-done plan must not duplicate the transaction
        $secondResponse = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->post(route('short-plans.realize', $plan), [
                'record_transaction' => '1',
                'actual_amount' => '450.000',
                'transaction_date' => '2026-11-11',
                'account_id' => $this->bca->id,
                'category_id' => $this->expenseCat->id,
            ]);

        $secondResponse->assertSessionHas('warning');
        $this->assertSame(1, FinancialTransaction::count());
    }

    public function test_user_can_mark_short_plan_done_without_recording_transaction(): void
    {
        $plan = ShortPlan::create([
            'space_id' => $this->spaceA->id,
            'title' => 'Perpanjang STNK',
            'planned_amount' => 300000,
            'priority' => 'medium',
            'status' => 'planned',
            'created_by' => $this->userA->id,
            'updated_by' => $this->userA->id,
        ]);

        $response = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->post(route('short-plans.realize', $plan), [
                'record_transaction' => '0',
                'actual_amount' => '280.000',
                'redirect_to' => 'index',
            ]);

        $response->assertRedirect(route('short-plans.index'));

        $plan->refresh();
        $this->assertSame('done', $plan->status);
        $this->assertEquals(280000, (float) $plan->actual_amount);
        $this->assertNull($plan->financial_transaction_id);
        $this->assertSame(0, FinancialTransaction::count());
        $this->assertEquals(10000000, $this->bca->fresh()->currentBalance());
    }

    public function test_realize_requires_account_and_category_when_recording_transaction(): void
    {
        $plan = ShortPlan::create([
            'space_id' => $this->spaceA->id,
            'title' => 'Beli Kursi Kerja',
            'planned_amount' => 800000,
            'priority' => 'low',
            'status' => 'planned',
            'created_by' => $this->userA->id,
            'updated_by' => $this->userA->id,
        ]);

        $response = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->post(route('short-plans.realize', $plan), [
                'record_transaction' => '1',
                'actual_amount' => '800.000',
            ]);

        $response->assertSessionHasErrors([
            'transaction_date' => 'Tanggal transaksi realisasi wajib diisi.',
            'account_id' => 'Rekening sumber wajib dipilih untuk mencatat transaksi.',
            'category_id' => 'Kategori pengeluaran wajib dipilih untuk mencatat transaksi.',
        ]);

        $this->assertSame('planned', $plan->fresh()->status);
    }

    public function test_user_can_update_cancel_reopen_and_delete_short_plan(): void
    {
        $plan = ShortPlan::create([
            'space_id' => $this->spaceA->id,
            'title' => 'Beli Keyboard',
            'planned_amount' => 600000,
            'priority' => 'low',
            'status' => 'planned',
            'created_by' => $this->userA->id,
            'updated_by' => $this->userA->id,
        ]);

        $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->put(route('short-plans.update', $plan), [
                'title' => 'Beli Keyboard Mekanikal',
                'planned_amount' => '750.000',
                'priority' => 'high',
                'status' => 'planned',
                'target_date' => '2026-11-25',
            ])
            ->assertRedirect(route('short-plans.show', $plan));

        $plan->refresh();
        $this->assertSame('Beli Keyboard Mekanikal', $plan->title);
        $this->assertEquals(750000, (float) $plan->planned_amount);
        $this->assertSame('high', $plan->priority);

        $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->post(route('short-plans.cancel', $plan))
            ->assertRedirect();
        $this->assertSame('cancelled', $plan->fresh()->status);

        $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->post(route('short-plans.reopen', $plan))
            ->assertRedirect();
        $this->assertSame('planned', $plan->fresh()->status);

        $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->delete(route('short-plans.destroy', $plan))
            ->assertRedirect(route('short-plans.index'));
        $this->assertDatabaseMissing('short_plans', ['id' => $plan->id]);
    }

    public function test_filtering_and_search_on_index(): void
    {
        ShortPlan::create([
            'space_id' => $this->spaceA->id,
            'title' => 'Servis AC Kamar',
            'planned_amount' => 200000,
            'priority' => 'high',
            'status' => 'planned',
            'created_by' => $this->userA->id,
        ]);

        ShortPlan::create([
            'space_id' => $this->spaceA->id,
            'title' => 'Langganan Buku',
            'planned_amount' => 100000,
            'priority' => 'low',
            'status' => 'done',
            'actual_amount' => 100000,
            'realized_at' => Carbon::now('Asia/Jakarta'),
            'created_by' => $this->userA->id,
        ]);

        $filtered = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->get(route('short-plans.index', [
                'status' => 'planned',
                'priority' => 'high',
                'search' => 'AC',
            ]));

        $filtered->assertOk();
        $filtered->assertSee('Servis AC Kamar');
        $filtered->assertDontSee('Langganan Buku');

        $emptyFilter = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->get(route('short-plans.index', [
                'status' => 'planned',
                'search' => 'TidakAdaSamaSekali',
            ]));

        $emptyFilter->assertOk();
        $emptyFilter->assertSee('Tidak Ada Rencana yang Cocok');
        $emptyFilter->assertSee('Reset Filter');
    }

    public function test_dashboard_displays_active_short_plans_summary(): void
    {
        ShortPlan::create([
            'space_id' => $this->spaceA->id,
            'account_id' => $this->bca->id,
            'category_id' => $this->expenseCat->id,
            'title' => 'Beli Kado Ulang Tahun',
            'planned_amount' => 400000,
            'priority' => 'high',
            'status' => 'planned',
            'created_by' => $this->userA->id,
        ]);

        $response = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Short Plan');
        $response->assertSee('Beli Kado Ulang Tahun');
        $response->assertSee('Rp 400.000');
    }

    public function test_space_isolation_prevents_cross_space_access_and_policy_enforces_membership(): void
    {
        $planA = ShortPlan::create([
            'space_id' => $this->spaceA->id,
            'title' => 'Rencana Rahasia A',
            'planned_amount' => 250000,
            'priority' => 'medium',
            'status' => 'planned',
            'created_by' => $this->userA->id,
        ]);

        $itemA = ShortPlanItem::create([
            'space_id' => $this->spaceA->id,
            'short_plan_id' => $planA->id,
            'title' => 'Item Rahasia',
            'estimated_amount' => 50000,
        ]);

        $this->actingAs($this->userB)
            ->withSession(['active_space_id' => $this->spaceB->id])
            ->get(route('short-plans.show', $planA))
            ->assertForbidden();

        $this->actingAs($this->userB)
            ->withSession(['active_space_id' => $this->spaceB->id])
            ->post(route('short-plans.realize', $planA), [
                'record_transaction' => '0',
            ])
            ->assertForbidden();

        $this->actingAs($this->userB)
            ->withSession(['active_space_id' => $this->spaceB->id])
            ->post(route('short-plans.items.toggle', [$planA, $itemA]))
            ->assertForbidden();

        $this->actingAs($this->userB)
            ->withSession(['active_space_id' => $this->spaceB->id])
            ->delete(route('short-plans.destroy', $planA))
            ->assertForbidden();

        // Policy matrix check
        $policy = new ShortPlanPolicy;
        $this->assertTrue($policy->view($this->userA, $planA));
        $this->assertTrue($policy->update($this->userA, $planA));
        $this->assertTrue($policy->delete($this->userA, $planA));
        $this->assertFalse($policy->view($this->userB, $planA));
        $this->assertFalse($policy->update($this->userB, $planA));
        $this->assertFalse($policy->delete($this->userB, $planA));

        // Active space isolation even if user belongs to both spaces
        $sharedSpace = Space::create([
            'name' => 'Ruang Bersama',
            'type' => 'shared',
            'owner_id' => $this->userA->id,
            'timezone' => 'Asia/Jakarta',
        ]);
        $sharedSpace->members()->attach($this->userA->id, ['role' => 'owner', 'status' => 'active']);

        $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $sharedSpace->id])
            ->get(route('short-plans.show', $planA))
            ->assertForbidden();
    }

    public function test_data_clean_service_handles_short_plans(): void
    {
        $planA = ShortPlan::create([
            'space_id' => $this->spaceA->id,
            'account_id' => $this->bca->id,
            'category_id' => $this->expenseCat->id,
            'title' => 'Plan Untuk Dibersihkan',
            'planned_amount' => 150000,
            'priority' => 'medium',
            'status' => 'planned',
            'created_by' => $this->userA->id,
        ]);

        ShortPlanItem::create([
            'space_id' => $this->spaceA->id,
            'short_plan_id' => $planA->id,
            'title' => 'Sub Item',
            'estimated_amount' => 50000,
        ]);

        $planB = ShortPlan::create([
            'space_id' => $this->spaceB->id,
            'title' => 'Plan Space B Tetap Ada',
            'planned_amount' => 200000,
            'priority' => 'low',
            'status' => 'planned',
            'created_by' => $this->userB->id,
        ]);

        $this->actingAs($this->userA)
            ->post('/profile/clean-data', [
                'space_id' => $this->spaceA->id,
                'password' => 'secret123',
                'all' => 1,
            ])
            ->assertRedirect('/profile');

        $this->assertDatabaseMissing('short_plans', ['id' => $planA->id]);
        $this->assertSame(0, ShortPlanItem::where('space_id', $this->spaceA->id)->count());
        $this->assertDatabaseHas('short_plans', ['id' => $planB->id]);
    }

    public function test_create_and_edit_pages_render_and_escape_special_characters(): void
    {
        $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->get(route('short-plans.create'))
            ->assertOk()
            ->assertSee('Buat Short Plan Baru');

        $plan = ShortPlan::create([
            'space_id' => $this->spaceA->id,
            'title' => "Belanja Jum'at <script>alert(1)</script>",
            'planned_amount' => 125000,
            'priority' => 'medium',
            'status' => 'planned',
            'created_by' => $this->userA->id,
        ]);

        $indexResponse = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->get(route('short-plans.index'));

        $indexResponse->assertOk();
        $indexResponse->assertSee("Belanja Jum'at");
        $indexResponse->assertDontSee('<script>alert(1)</script>', false);

        $showResponse = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->get(route('short-plans.show', $plan));

        $showResponse->assertOk();
        $showResponse->assertDontSee('<script>alert(1)</script>', false);

        $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->get(route('short-plans.edit', $plan))
            ->assertOk()
            ->assertSee('Edit Short Plan')
            ->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_cleaning_transactions_only_preserves_short_plan_and_unlinks_transaction(): void
    {
        $plan = ShortPlan::create([
            'space_id' => $this->spaceA->id,
            'account_id' => $this->bca->id,
            'category_id' => $this->expenseCat->id,
            'title' => 'Servis Rutin',
            'planned_amount' => 200000,
            'priority' => 'medium',
            'status' => 'planned',
            'created_by' => $this->userA->id,
        ]);

        $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->post(route('short-plans.realize', $plan), [
                'record_transaction' => '1',
                'actual_amount' => '200.000',
                'transaction_date' => '2026-11-11',
                'account_id' => $this->bca->id,
                'category_id' => $this->expenseCat->id,
            ]);

        $plan->refresh();
        $this->assertNotNull($plan->financial_transaction_id);

        $this->actingAs($this->userA)
            ->post('/profile/clean-data', [
                'space_id' => $this->spaceA->id,
                'password' => 'secret123',
                'transactions' => 1,
            ])
            ->assertRedirect('/profile');

        $plan->refresh();
        $this->assertNull($plan->financial_transaction_id);
        $this->assertDatabaseHas('short_plans', ['id' => $plan->id]);
    }
}
