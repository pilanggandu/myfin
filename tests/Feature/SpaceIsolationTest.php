<?php

namespace Tests\Feature;

use App\Models\Space;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpaceIsolationTest extends TestCase
{
    use RefreshDatabase;

    private User $userA;
    private User $userB;
    private Space $spaceA;
    private Space $spaceB;

    protected function setUp(): void
    {
        parent::setUp();

        // Buat User A dan ruang personalnya
        $this->userA = User::factory()->create(['name' => 'User A', 'email' => 'a@example.com']);
        $this->spaceA = Space::create([
            'name' => 'Ruang Personal A',
            'type' => 'personal',
            'owner_id' => $this->userA->id,
            'timezone' => 'Asia/Jakarta',
        ]);
        $this->spaceA->members()->attach($this->userA->id, ['role' => 'owner', 'status' => 'active']);

        // Buat User B dan ruang personalnya
        $this->userB = User::factory()->create(['name' => 'User B', 'email' => 'b@example.com']);
        $this->spaceB = Space::create([
            'name' => 'Ruang Personal B',
            'type' => 'personal',
            'owner_id' => $this->userB->id,
            'timezone' => 'Asia/Jakarta',
        ]);
        $this->spaceB->members()->attach($this->userB->id, ['role' => 'owner', 'status' => 'active']);
    }

    public function test_user_a_cannot_switch_to_user_b_space(): void
    {
        // User A mencoba berpindah ke space milik User B secara manipulasi URL/ID
        $response = $this->actingAs($this->userA)->post("/spaces/{$this->spaceB->id}/switch");

        $response->assertStatus(403);
    }

    public function test_user_a_cannot_edit_user_b_space(): void
    {
        // User A mencoba membuka halaman edit space B
        $response = $this->actingAs($this->userA)->get("/spaces/{$this->spaceB->id}/edit");

        $response->assertStatus(403);
    }

    public function test_user_a_cannot_update_user_b_space(): void
    {
        // User A mencoba mengirim request pembaruan ke space B
        $response = $this->actingAs($this->userA)->put("/spaces/{$this->spaceB->id}", [
            'name' => 'Diretas oleh A',
            'timezone' => 'Asia/Jakarta',
        ]);

        $response->assertStatus(403);

        $this->spaceB->refresh();
        $this->assertEquals('Ruang Personal B', $this->spaceB->name);
    }

    public function test_user_a_cannot_delete_user_b_space(): void
    {
        // User A mencoba menghapus space B
        $response = $this->actingAs($this->userA)->delete("/spaces/{$this->spaceB->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('spaces', ['id' => $this->spaceB->id]);
    }

    public function test_user_cannot_delete_personal_space(): void
    {
        // User A mencoba menghapus ruang personal miliknya sendiri
        $response = $this->actingAs($this->userA)->delete("/spaces/{$this->spaceA->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('spaces', ['id' => $this->spaceA->id]);
    }

    public function test_non_owner_member_cannot_delete_shared_space(): void
    {
        // Buat shared space milik User A, User B adalah member biasa
        $sharedSpace = Space::create([
            'name' => 'Ruang Bersama',
            'type' => 'shared',
            'owner_id' => $this->userA->id,
            'timezone' => 'Asia/Jakarta',
        ]);
        $sharedSpace->members()->attach($this->userA->id, ['role' => 'owner', 'status' => 'active']);
        $sharedSpace->members()->attach($this->userB->id, ['role' => 'member', 'status' => 'active']);

        // User B mencoba menghapus ruang bersama
        $response = $this->actingAs($this->userB)->delete("/spaces/{$sharedSpace->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('spaces', ['id' => $sharedSpace->id]);

        // Sedangkan User A (owner) berhasil menghapus
        $deleteResponse = $this->actingAs($this->userA)->delete("/spaces/{$sharedSpace->id}");
        $deleteResponse->assertRedirect('/spaces');
        $this->assertDatabaseMissing('spaces', ['id' => $sharedSpace->id]);
    }

    public function test_manipulated_session_space_id_is_safely_reset_by_middleware(): void
    {
        // Simulasikan session dimanipulasi dengan ID space milik User B
        $response = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceB->id])
            ->get('/dashboard');

        $response->assertStatus(200);

        // Middleware EnsureActiveSpace mereset session kembali ke space A milik User A
        $this->assertEquals($this->spaceA->id, session('active_space_id'));
    }

    public function test_user_cannot_create_duplicate_personal_space_at_database_level(): void
    {
        $this->expectException(UniqueConstraintViolationException::class);

        Space::create([
            'name' => 'Ruang Personal Duplikat',
            'type' => 'personal',
            'owner_id' => $this->userA->id,
            'timezone' => 'Asia/Jakarta',
        ]);
    }

    public function test_user_can_have_multiple_shared_spaces_at_database_level(): void
    {
        $shared1 = Space::create([
            'name' => 'Shared 1',
            'type' => 'shared',
            'owner_id' => $this->userA->id,
            'timezone' => 'Asia/Jakarta',
        ]);

        $shared2 = Space::create([
            'name' => 'Shared 2',
            'type' => 'shared',
            'owner_id' => $this->userA->id,
            'timezone' => 'Asia/Jakarta',
        ]);

        $this->assertDatabaseHas('spaces', ['id' => $shared1->id]);
        $this->assertDatabaseHas('spaces', ['id' => $shared2->id]);
    }

    public function test_ensure_personal_space_is_atomic_and_idempotent(): void
    {
        $newUser = User::factory()->create(['email' => 'unique_new@example.com']);

        $this->assertEquals(0, Space::where('owner_id', $newUser->id)->count());

        $space1 = $newUser->ensurePersonalSpace();
        $this->assertNotNull($space1);
        $this->assertEquals('personal', $space1->type);
        $this->assertTrue($space1->members()->where('users.id', $newUser->id)->wherePivot('role', 'owner')->exists());

        $space2 = $newUser->ensurePersonalSpace();
        $this->assertEquals($space1->id, $space2->id);
        $this->assertEquals(1, Space::where('owner_id', $newUser->id)->where('type', 'personal')->count());
    }

    public function test_middleware_creates_personal_space_atomically_when_user_has_none(): void
    {
        $newUser = User::factory()->create(['email' => 'middleware_check@example.com']);

        $response = $this->actingAs($newUser)->get('/dashboard');
        $response->assertStatus(200);

        $createdSpace = Space::where('owner_id', $newUser->id)->where('type', 'personal')->first();
        $this->assertNotNull($createdSpace);
        $this->assertEquals($createdSpace->id, session('active_space_id'));
        $this->assertTrue($createdSpace->members()->where('users.id', $newUser->id)->wherePivot('role', 'owner')->wherePivot('status', 'active')->exists());

        $secondResponse = $this->actingAs($newUser)->get('/dashboard');
        $secondResponse->assertStatus(200);
        $this->assertEquals(1, Space::where('owner_id', $newUser->id)->where('type', 'personal')->count());
    }

    public function test_user_can_view_spaces_index_with_responsive_layouts(): void
    {
        $response = $this->actingAs($this->userA)->get('/spaces');

        $response->assertStatus(200);
        $response->assertSee('Daftar Ruang Terdaftar');
        $response->assertSee('Peran Anda');
        $response->assertSee('md:hidden');
        $response->assertSee('hidden md:block');
    }
}
