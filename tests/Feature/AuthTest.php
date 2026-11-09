<?php

namespace Tests\Feature;

use App\Mail\PasswordResetOtpMail;
use App\Mail\RegistrationOtpMail;
use App\Models\EmailOtp;
use App\Models\PasswordResetOtp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_login_page(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
        $response->assertSee('Masuk ke Akun Anda');
        $response->assertSee('Masuk dengan Google');
    }

    public function test_user_can_view_register_page_when_registration_open(): void
    {
        config(['arus.registration_open' => true]);

        $response = $this->get('/register');
        $response->assertStatus(200);
        $response->assertSee('Pendaftaran Akun Baru');
        $response->assertSee('Daftar dengan Google');
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

    public function test_user_registration_sends_email_otp_and_redirects_to_verification_page(): void
    {
        Mail::fake();
        config(['arus.registration_open' => true]);

        $response = $this->post('/register', [
            'name' => 'Budi Pratama',
            'email' => 'budi@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertRedirect(route('register.otp.show'));
        $this->assertGuest();

        $otp = EmailOtp::where('email', 'budi@example.com')->first();
        $this->assertNotNull($otp);
        $this->assertEquals('Budi Pratama', $otp->name);
        $this->assertEquals(6, strlen($otp->otp_code));

        Mail::assertSent(RegistrationOtpMail::class, function ($mail) {
            return $mail->hasTo('budi@example.com');
        });

        // Verifikasi halaman input OTP
        $otpPage = $this->withSession(['registration_email' => 'budi@example.com'])
            ->get('/register/verify-otp');
        $otpPage->assertOk();
        $otpPage->assertSee('Masukkan Kode OTP');
        $otpPage->assertSee('budi@example.com');
    }

    public function test_user_can_verify_email_otp_and_automatically_receives_personal_space(): void
    {
        EmailOtp::create([
            'email' => 'budi@example.com',
            'name' => 'Budi Pratama',
            'password' => Hash::make('Password123!'),
            'otp_code' => '123456',
            'attempts' => 0,
            'expires_at' => now()->addMinutes(10),
        ]);

        $response = $this->withSession(['registration_email' => 'budi@example.com'])
            ->post('/register/verify-otp', [
                'email' => 'budi@example.com',
                'otp' => '123456',
            ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();

        $user = User::where('email', 'budi@example.com')->first();
        $this->assertNotNull($user);
        $this->assertNotNull($user->email_verified_at);

        // Verifikasi pembuatan ruang personal otomatis
        $personalSpace = $user->personalSpace();
        $this->assertNotNull($personalSpace);
        $this->assertEquals('personal', $personalSpace->type);
        $this->assertEquals($user->id, $personalSpace->owner_id);
        $this->assertTrue($personalSpace->hasMember($user));
        $this->assertEquals('owner', $personalSpace->getRoleOf($user));

        // Verifikasi OTP telah dihapus
        $this->assertDatabaseMissing('email_otps', ['email' => 'budi@example.com']);

        // Verifikasi session active_space_id terpasang
        $this->assertEquals($personalSpace->id, session('active_space_id'));
    }

    public function test_user_cannot_verify_with_invalid_otp(): void
    {
        EmailOtp::create([
            'email' => 'budi@example.com',
            'name' => 'Budi Pratama',
            'password' => Hash::make('Password123!'),
            'otp_code' => '123456',
            'attempts' => 0,
            'expires_at' => now()->addMinutes(10),
        ]);

        $response = $this->post('/register/verify-otp', [
            'email' => 'budi@example.com',
            'otp' => '999999',
        ]);

        $response->assertSessionHasErrors('otp');
        $this->assertGuest();

        $otp = EmailOtp::where('email', 'budi@example.com')->first();
        $this->assertEquals(1, $otp->attempts);
    }

    public function test_user_can_resend_email_otp_after_cooldown(): void
    {
        Mail::fake();

        $otp = EmailOtp::create([
            'email' => 'budi@example.com',
            'name' => 'Budi Pratama',
            'password' => Hash::make('Password123!'),
            'otp_code' => '111111',
            'attempts' => 2,
            'expires_at' => now()->addMinutes(10),
        ]);

        // Simulasikan sudah lewat 65 detik
        $response = $this->withSession([
            'registration_email' => 'budi@example.com',
            'otp_sent_at' => now()->subSeconds(65)->timestamp,
        ])->post('/register/resend-otp', [
            'email' => 'budi@example.com',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $otp->refresh();
        $this->assertNotEquals('111111', $otp->otp_code);
        $this->assertEquals(0, $otp->attempts);

        Mail::assertSent(RegistrationOtpMail::class);
    }

    public function test_google_sso_redirect_works_when_configured(): void
    {
        config([
            'services.google.client_id' => 'mock-client-id',
            'services.google.client_secret' => 'mock-client-secret',
        ]);

        $provider = Mockery::mock('Laravel\Socialite\Two\GoogleProvider');
        $provider->shouldReceive('redirect')->andReturn(redirect('https://accounts.google.com/o/oauth2/auth'));
        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        $response = $this->get(route('auth.google.redirect'));
        $response->assertRedirect('https://accounts.google.com/o/oauth2/auth');
    }

    public function test_google_sso_callback_creates_and_authenticates_new_user(): void
    {
        config([
            'services.google.client_id' => 'mock-client-id',
            'services.google.client_secret' => 'mock-client-secret',
            'arus.registration_open' => true,
        ]);

        $abstractUser = Mockery::mock(SocialiteUser::class);
        $abstractUser->shouldReceive('getId')->andReturn('google-uid-999');
        $abstractUser->shouldReceive('getName')->andReturn('Google User');
        $abstractUser->shouldReceive('getEmail')->andReturn('googleuser@example.com');
        $abstractUser->shouldReceive('getAvatar')->andReturn('https://avatar.example.com/photo.jpg');

        $provider = Mockery::mock('Laravel\Socialite\Two\GoogleProvider');
        $provider->shouldReceive('user')->andReturn($abstractUser);
        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        $response = $this->get(route('auth.google.callback'));

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();

        $user = User::where('google_id', 'google-uid-999')->first();
        $this->assertNotNull($user);
        $this->assertEquals('googleuser@example.com', $user->email);
        $this->assertNotNull($user->personalSpace());
    }

    public function test_google_sso_callback_links_existing_user(): void
    {
        config([
            'services.google.client_id' => 'mock-client-id',
            'services.google.client_secret' => 'mock-client-secret',
        ]);

        $existing = User::factory()->create([
            'email' => 'existing@example.com',
            'google_id' => null,
        ]);

        $abstractUser = Mockery::mock(SocialiteUser::class);
        $abstractUser->shouldReceive('getId')->andReturn('google-uid-777');
        $abstractUser->shouldReceive('getName')->andReturn('Existing User');
        $abstractUser->shouldReceive('getEmail')->andReturn('existing@example.com');
        $abstractUser->shouldReceive('getAvatar')->andReturn('https://avatar.example.com/new.jpg');

        $provider = Mockery::mock('Laravel\Socialite\Two\GoogleProvider');
        $provider->shouldReceive('user')->andReturn($abstractUser);
        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        $response = $this->get(route('auth.google.callback'));

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($existing);

        $existing->refresh();
        $this->assertEquals('google-uid-777', $existing->google_id);
    }

    public function test_authenticated_user_can_bind_google_account(): void
    {
        config([
            'services.google.client_id' => 'mock-client-id',
            'services.google.client_secret' => 'mock-client-secret',
        ]);

        $user = User::factory()->create([
            'email' => 'myaccount@example.com',
            'google_id' => null,
            'has_password' => true,
        ]);

        $abstractUser = Mockery::mock(SocialiteUser::class);
        $abstractUser->shouldReceive('getId')->andReturn('google-uid-newbind');
        $abstractUser->shouldReceive('getName')->andReturn('Google User Name');
        $abstractUser->shouldReceive('getEmail')->andReturn('googleemail@gmail.com');
        $abstractUser->shouldReceive('getAvatar')->andReturn('https://avatar.example.com/bind.jpg');

        $provider = Mockery::mock('Laravel\Socialite\Two\GoogleProvider');
        $provider->shouldReceive('user')->andReturn($abstractUser);
        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        $response = $this->actingAs($user)
            ->withSession(['google_auth_action' => 'bind'])
            ->get(route('auth.google.callback'));

        $response->assertRedirect(route('profile.edit'));
        $response->assertSessionHas('success');

        $user->refresh();
        $this->assertEquals('google-uid-newbind', $user->google_id);
    }

    public function test_authenticated_user_cannot_bind_google_account_if_already_taken(): void
    {
        config([
            'services.google.client_id' => 'mock-client-id',
            'services.google.client_secret' => 'mock-client-secret',
        ]);

        User::factory()->create([
            'email' => 'other@example.com',
            'google_id' => 'google-uid-taken',
        ]);

        $user = User::factory()->create([
            'email' => 'myaccount@example.com',
            'google_id' => null,
            'has_password' => true,
        ]);

        $abstractUser = Mockery::mock(SocialiteUser::class);
        $abstractUser->shouldReceive('getId')->andReturn('google-uid-taken');
        $abstractUser->shouldReceive('getName')->andReturn('Other User');
        $abstractUser->shouldReceive('getEmail')->andReturn('othergoogle@gmail.com');
        $abstractUser->shouldReceive('getAvatar')->andReturn(null);

        $provider = Mockery::mock('Laravel\Socialite\Two\GoogleProvider');
        $provider->shouldReceive('user')->andReturn($abstractUser);
        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        $response = $this->actingAs($user)
            ->withSession(['google_auth_action' => 'bind'])
            ->get(route('auth.google.callback'));

        $response->assertRedirect(route('profile.edit'));
        $response->assertSessionHas('error');

        $user->refresh();
        $this->assertNull($user->google_id);
    }

    public function test_user_can_unlink_google_when_has_password(): void
    {
        $user = User::factory()->create([
            'google_id' => 'google-uid-unlink',
            'has_password' => true,
        ]);

        $response = $this->actingAs($user)
            ->post(route('profile.google.unlink'));

        $response->assertRedirect(route('profile.edit'));
        $response->assertSessionHas('success');

        $user->refresh();
        $this->assertNull($user->google_id);
    }

    public function test_user_cannot_unlink_google_without_password(): void
    {
        $user = User::factory()->create([
            'google_id' => 'google-uid-unlink',
            'has_password' => false,
        ]);

        $response = $this->actingAs($user)
            ->post(route('profile.google.unlink'));

        $response->assertRedirect(route('profile.edit'));
        $response->assertSessionHas('error');

        $user->refresh();
        $this->assertEquals('google-uid-unlink', $user->google_id);
    }

    public function test_sso_user_can_create_password_without_current_password(): void
    {
        $user = User::factory()->create([
            'has_password' => false,
            'password' => Hash::make('random-temp'),
        ]);

        $response = $this->actingAs($user)
            ->put(route('profile.password'), [
                'password' => 'MyNewPassword123!',
                'password_confirmation' => 'MyNewPassword123!',
            ]);

        $response->assertRedirect(route('profile.edit'));
        $response->assertSessionHas('success');

        $user->refresh();
        $this->assertTrue($user->has_password);
        $this->assertTrue(Hash::check('MyNewPassword123!', $user->password));
    }

    public function test_standard_user_requires_current_password_to_update_password(): void
    {
        $user = User::factory()->create([
            'has_password' => true,
            'password' => Hash::make('OldPassword123!'),
        ]);

        $failResponse = $this->actingAs($user)
            ->put(route('profile.password'), [
                'password' => 'MyNewPassword123!',
                'password_confirmation' => 'MyNewPassword123!',
            ]);

        $failResponse->assertSessionHasErrors('current_password');

        $successResponse = $this->actingAs($user)
            ->put(route('profile.password'), [
                'current_password' => 'OldPassword123!',
                'password' => 'MyNewPassword123!',
                'password_confirmation' => 'MyNewPassword123!',
            ]);

        $successResponse->assertRedirect(route('profile.edit'));
        $successResponse->assertSessionHas('success');

        $user->refresh();
        $this->assertTrue(Hash::check('MyNewPassword123!', $user->password));
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

    public function test_user_can_view_forgot_password_page(): void
    {
        $response = $this->get('/forgot-password');
        $response->assertStatus(200);
        $response->assertSee('Lupa Kata Sandi?');
        $response->assertSee('Kirim Kode OTP Reset');
    }

    public function test_user_can_request_password_reset_otp(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'email' => 'budi@example.com',
            'name' => 'Budi Pratama',
        ]);

        $response = $this->post('/forgot-password', [
            'email' => 'budi@example.com',
        ]);

        $response->assertRedirect('/forgot-password/verify-otp');
        $response->assertSessionHas('password_reset_email', 'budi@example.com');
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('password_reset_otps', [
            'email' => 'budi@example.com',
            'attempts' => 0,
        ]);

        Mail::assertSent(PasswordResetOtpMail::class, function ($mail) {
            return $mail->hasTo('budi@example.com') && strlen($mail->otpCode) === 6;
        });
    }

    public function test_password_reset_request_fails_for_unregistered_email(): void
    {
        Mail::fake();

        $response = $this->post('/forgot-password', [
            'email' => 'tidakterdaftar@example.com',
        ]);

        $response->assertSessionHasErrors('email');
        Mail::assertNothingSent();
    }

    public function test_user_can_verify_otp_and_reset_password(): void
    {
        $user = User::factory()->create([
            'email' => 'budi@example.com',
            'password' => Hash::make('OldPassword123!'),
        ]);

        PasswordResetOtp::create([
            'email' => 'budi@example.com',
            'otp_code' => '654321',
            'attempts' => 0,
            'expires_at' => now()->addMinutes(10),
        ]);

        $response = $this->withSession(['password_reset_email' => 'budi@example.com'])
            ->post('/forgot-password/verify-otp', [
                'email' => 'budi@example.com',
                'otp' => '654321',
                'password' => 'NewSecretPassword123!',
                'password_confirmation' => 'NewSecretPassword123!',
            ]);

        $response->assertRedirect('/login');
        $response->assertSessionHas('success');

        $user->refresh();
        $this->assertTrue(Hash::check('NewSecretPassword123!', $user->password));

        $this->assertDatabaseMissing('password_reset_otps', [
            'email' => 'budi@example.com',
        ]);
    }

    public function test_password_reset_otp_verification_fails_with_wrong_code(): void
    {
        $user = User::factory()->create(['email' => 'budi@example.com']);

        $otp = PasswordResetOtp::create([
            'email' => 'budi@example.com',
            'otp_code' => '654321',
            'attempts' => 0,
            'expires_at' => now()->addMinutes(10),
        ]);

        $response = $this->withSession(['password_reset_email' => 'budi@example.com'])
            ->post('/forgot-password/verify-otp', [
                'email' => 'budi@example.com',
                'otp' => '000000',
                'password' => 'NewSecretPassword123!',
                'password_confirmation' => 'NewSecretPassword123!',
            ]);

        $response->assertSessionHasErrors('otp');
        $otp->refresh();
        $this->assertEquals(1, $otp->attempts);
    }

    public function test_password_reset_otp_resend_respects_cooldown(): void
    {
        Mail::fake();

        User::factory()->create(['email' => 'budi@example.com']);

        PasswordResetOtp::create([
            'email' => 'budi@example.com',
            'otp_code' => '111111',
            'attempts' => 0,
            'expires_at' => now()->addMinutes(10),
        ]);

        // Attempt resend immediately (cooldown active)
        $response = $this->withSession([
            'password_reset_email' => 'budi@example.com',
            'password_reset_otp_sent_at' => now()->timestamp,
        ])->post('/forgot-password/resend-otp', [
            'email' => 'budi@example.com',
        ]);

        $response->assertSessionHas('warning');
        Mail::assertNothingSent();

        // Simulate 61 seconds later
        $response = $this->withSession([
            'password_reset_email' => 'budi@example.com',
            'password_reset_otp_sent_at' => now()->subSeconds(65)->timestamp,
        ])->post('/forgot-password/resend-otp', [
            'email' => 'budi@example.com',
        ]);

        $response->assertSessionHas('success');
        Mail::assertSent(PasswordResetOtpMail::class);
    }
}
