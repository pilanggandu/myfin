<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_login_page(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
        $response->assertSee('Masuk ke Akun Anda');
    }

    public function test_user_can_view_register_page_when_registration_open(): void
    {
        config(['arus.registration_open' => true]);

        $response = $this->get('/register');
        $response->assertStatus(200);
        $response->assertSee('Pendaftaran Akun Baru');
    }

    public function test_registration_redirects_when_registration_is_closed(): void
    {
        config(['arus.registration_open' => false]);

        $response = $this->get('/register');
        $response->assertRedirect('/login');
        $response->assertSessionHas('warning');

        $postResponse = $this->post('/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);
        $postResponse->assertStatus(403);
    }

    public function test_user_can_register_and_automatically_receives_personal_space(): void
    {
        config(['arus.registration_open' => true]);

        $response = $this->post('/register', [
            'name' => 'Budi Pratama',
            'email' => 'budi@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();

        $user = User::where('email', 'budi@example.com')->first();
        $this->assertNotNull($user);

        // Verifikasi pembuatan ruang personal otomatis
        $personalSpace = $user->personalSpace();
        $this->assertNotNull($personalSpace);
        $this->assertEquals('personal', $personalSpace->type);
        $this->assertEquals($user->id, $personalSpace->owner_id);
        $this->assertTrue($personalSpace->hasMember($user));
        $this->assertEquals('owner', $personalSpace->getRoleOf($user));

        // Verifikasi session active_space_id terpasang
        $this->assertEquals($personalSpace->id, session('active_space_id'));
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => Hash::make('Password123!'),
        ]);

        $response = $this->post('/login', [
            'email' => 'user@example.com',
            'password' => 'Password123!',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_user_cannot_login_with_invalid_password(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => Hash::make('Password123!'),
        ]);

        $response = $this->post('/login', [
            'email' => 'user@example.com',
            'password' => 'WrongPassword',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_user_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $response->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_user_can_update_profile(): void
    {
        $user = User::factory()->create([
            'name' => 'Nama Lama',
            'email' => 'lama@example.com',
        ]);

        $response = $this->actingAs($user)->patch('/profile', [
            'name' => 'Nama Baru',
            'email' => 'baru@example.com',
        ]);

        $response->assertRedirect('/profile');
        $response->assertSessionHas('success');

        $user->refresh();
        $this->assertEquals('Nama Baru', $user->name);
        $this->assertEquals('baru@example.com', $user->email);
    }

    public function test_user_can_update_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('OldPassword123!'),
        ]);

        $response = $this->actingAs($user)->put('/profile/password', [
            'current_password' => 'OldPassword123!',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);

        $response->assertRedirect('/profile');
        $response->assertSessionHas('success');

        $user->refresh();
        $this->assertTrue(Hash::check('NewPassword123!', $user->password));
    }
}
