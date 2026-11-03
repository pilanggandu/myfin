<?php

namespace Tests\Feature;

use App\Models\Currency;
use App\Models\Space;
use App\Models\User;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CurrencyTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Space $space;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['name' => 'Budi Finansial', 'email' => 'budi@example.com']);
        $this->space = Space::create([
            'name' => 'Ruang Personal Budi',
            'type' => 'personal',
            'owner_id' => $this->user->id,
            'timezone' => 'Asia/Jakarta',
        ]);
        $this->space->members()->attach($this->user->id, ['role' => 'owner', 'status' => 'active']);

        $this->seed(CurrencySeeder::class);
    }

    public function test_user_can_view_currency_index_page(): void
    {
        $response = $this->actingAs($this->user)
            ->withSession(['active_space_id' => $this->space->id])
            ->get(route('currencies.index'));

        $response->assertOk();
        $response->assertSee('Master Mata Uang');
        $response->assertSee('IDR');
        $response->assertSee('USD');
        $response->assertSee('Sinkronkan Kurs API');
    }

    public function test_user_can_create_new_currency(): void
    {
        $response = $this->actingAs($this->user)
            ->withSession(['active_space_id' => $this->space->id])
            ->post(route('currencies.store'), [
                'code' => 'CHF',
                'name' => 'Swiss Franc',
                'symbol' => 'CHF',
                'exchange_rate' => 19500.0,
                'decimal_digits' => 2,
                'is_active' => 1,
            ]);

        $response->assertRedirect(route('currencies.index'));
        $this->assertDatabaseHas('currencies', [
            'code' => 'CHF',
            'name' => 'Swiss Franc',
            'decimal_digits' => 2,
        ]);
    }

    public function test_user_cannot_create_duplicate_currency_code(): void
    {
        $response = $this->actingAs($this->user)
            ->withSession(['active_space_id' => $this->space->id])
            ->post(route('currencies.store'), [
                'code' => 'USD',
                'name' => 'Dollar Duplikat',
                'symbol' => '$',
                'exchange_rate' => 16000.0,
                'decimal_digits' => 2,
            ]);

        $response->assertSessionHasErrors(['code']);
    }

    public function test_user_can_update_currency(): void
    {
        $usd = Currency::where('code', 'USD')->firstOrFail();

        $response = $this->actingAs($this->user)
            ->withSession(['active_space_id' => $this->space->id])
            ->put(route('currencies.update', $usd), [
                'code' => 'USD',
                'name' => 'United States Dollar (Updated)',
                'symbol' => 'US$',
                'exchange_rate' => 16300.0,
                'decimal_digits' => 2,
                'is_active' => 1,
            ]);

        $response->assertRedirect(route('currencies.index'));
        $this->assertDatabaseHas('currencies', [
            'id' => $usd->id,
            'name' => 'United States Dollar (Updated)',
            'symbol' => 'US$',
            'exchange_rate' => 16300.0,
        ]);
    }

    public function test_user_can_toggle_currency_active_status(): void
    {
        $jpy = Currency::where('code', 'JPY')->firstOrFail();
        $this->assertTrue($jpy->is_active);

        $response = $this->actingAs($this->user)
            ->withSession(['active_space_id' => $this->space->id])
            ->post(route('currencies.toggleActive', $jpy));

        $response->assertRedirect(route('currencies.index'));
        $this->assertFalse($jpy->fresh()->is_active);
    }

    public function test_user_cannot_deactivate_default_currency(): void
    {
        $idr = Currency::where('code', 'IDR')->firstOrFail();
        $this->assertTrue($idr->is_default);

        $response = $this->actingAs($this->user)
            ->withSession(['active_space_id' => $this->space->id])
            ->post(route('currencies.toggleActive', $idr));

        $response->assertSessionHasErrors(['is_active']);
        $this->assertTrue($idr->fresh()->is_active);
    }

    public function test_user_can_set_default_currency(): void
    {
        $idr = Currency::where('code', 'IDR')->firstOrFail();
        $usd = Currency::where('code', 'USD')->firstOrFail();

        $this->assertTrue($idr->is_default);
        $this->assertFalse($usd->is_default);

        $response = $this->actingAs($this->user)
            ->withSession(['active_space_id' => $this->space->id])
            ->post(route('currencies.setDefault', $usd));

        $response->assertRedirect(route('currencies.index'));
        $this->assertTrue($usd->fresh()->is_default);
        $this->assertFalse($idr->fresh()->is_default);
    }

    public function test_user_cannot_delete_default_currency(): void
    {
        $idr = Currency::where('code', 'IDR')->firstOrFail();

        $response = $this->actingAs($this->user)
            ->withSession(['active_space_id' => $this->space->id])
            ->delete(route('currencies.destroy', $idr));

        $response->assertSessionHasErrors(['currency']);
        $this->assertDatabaseHas('currencies', ['code' => 'IDR']);
    }

    public function test_user_can_delete_non_default_currency(): void
    {
        $jpy = Currency::where('code', 'JPY')->firstOrFail();

        $response = $this->actingAs($this->user)
            ->withSession(['active_space_id' => $this->space->id])
            ->delete(route('currencies.destroy', $jpy));

        $response->assertRedirect(route('currencies.index'));
        $this->assertDatabaseMissing('currencies', ['code' => 'JPY']);
    }

    public function test_sync_rates_from_public_api(): void
    {
        Http::fake([
            'api.frankfurter.dev/*' => Http::response([
                'amount' => 1.0,
                'base' => 'USD',
                'date' => '2026-11-03',
                'rates' => [
                    'IDR' => 16500.0,
                    'EUR' => 0.90,
                    'SGD' => 1.30,
                    'JPY' => 150.0,
                ],
            ], 200),
        ]);

        $response = $this->actingAs($this->user)
            ->withSession(['active_space_id' => $this->space->id])
            ->post(route('currencies.sync'));

        $response->assertRedirect(route('currencies.index'));
        $response->assertSessionHas('success');

        $usd = Currency::where('code', 'USD')->firstOrFail();
        $this->assertEquals(16500.0, (float) $usd->exchange_rate);
        $this->assertNotNull($usd->last_synced_at);

        $idr = Currency::where('code', 'IDR')->firstOrFail();
        $this->assertEquals(1.0, (float) $idr->exchange_rate);
    }

    public function test_convert_currency_endpoint(): void
    {
        $response = $this->actingAs($this->user)
            ->withSession(['active_space_id' => $this->space->id])
            ->postJson(route('currencies.convert'), [
                'amount' => 10,
                'from' => 'USD',
                'to' => 'IDR',
            ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'from' => 'USD',
            'to' => 'IDR',
        ]);
        $this->assertGreaterThan(0, $response->json('result'));
    }
}
