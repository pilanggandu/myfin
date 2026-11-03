<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserSeederTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test UserSeeder creates default users with verified email and personal spaces.
     */
    public function test_user_seeder_creates_expected_users(): void
    {
        $this->seed(UserSeeder::class);

        $this->assertDatabaseHas('users', [
            'email' => 'admin@arus.local',
            'name' => 'Administrator',
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'user@arus.local',
            'name' => 'Pengguna Demo',
        ]);

        $admin = User::where('email', 'admin@arus.local')->first();
        $this->assertNotNull($admin);
        $this->assertTrue(Hash::check('Password123!', $admin->password));
        $this->assertNotNull($admin->email_verified_at);

        $adminSpace = $admin->personalSpace();
        $this->assertNotNull($adminSpace);
        $this->assertEquals('Keuangan Pribadi', $adminSpace->name);
        $this->assertGreaterThan(0, $adminSpace->categories()->count());

        $demoUser = User::where('email', 'user@arus.local')->first();
        $this->assertNotNull($demoUser);
        $this->assertTrue(Hash::check('Password123!', $demoUser->password));
        $this->assertNotNull($demoUser->personalSpace());
    }

    /**
     * Test UserSeeder can be run multiple times safely without duplication.
     */
    public function test_user_seeder_is_idempotent(): void
    {
        $this->seed(UserSeeder::class);
        $this->seed(UserSeeder::class);

        $this->assertSame(1, User::where('email', 'admin@arus.local')->count());
        $this->assertSame(1, User::where('email', 'user@arus.local')->count());
    }

    /**
     * Test DatabaseSeeder triggers UserSeeder correctly.
     */
    public function test_database_seeder_runs_user_seeder(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseHas('users', [
            'email' => 'admin@arus.local',
        ]);
        $this->assertDatabaseHas('users', [
            'email' => 'user@arus.local',
        ]);
    }
}
