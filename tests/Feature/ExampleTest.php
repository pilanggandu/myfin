<?php

namespace Tests\Feature;

use App\Models\Space;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Tamu tanpa autentikasi dialihkan ke halaman masuk.
     */
    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/login');
    }

    /**
     * Pengguna terautentikasi dapat membuka dashboard.
     */
    public function test_authenticated_user_can_access_dashboard(): void
    {
        $user = User::factory()->create();
        $space = Space::create([
            'name' => 'Keuangan Pribadi',
            'type' => 'personal',
            'owner_id' => $user->id,
            'timezone' => 'Asia/Jakarta',
        ]);
        $space->members()->attach($user->id, ['role' => 'owner', 'status' => 'active']);

        $response = $this->actingAs($user)->get('/');

        $response->assertStatus(200);
    }
}
