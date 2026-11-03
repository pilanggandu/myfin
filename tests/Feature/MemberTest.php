<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\Space;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberTest extends TestCase
{
    use RefreshDatabase;

    private User $userA;
    private User $userB;
    private Space $spaceA;
    private Space $spaceB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userA = User::factory()->create(['name' => 'User A', 'email' => 'a@example.com']);
        $this->spaceA = Space::create([
            'name' => 'Ruang Personal A',
            'type' => 'personal',
            'owner_id' => $this->userA->id,
            'timezone' => 'Asia/Jakarta',
        ]);
        $this->spaceA->members()->attach($this->userA->id, ['role' => 'owner', 'status' => 'active']);

        $this->userB = User::factory()->create(['name' => 'User B', 'email' => 'b@example.com']);
        $this->spaceB = Space::create([
            'name' => 'Ruang Personal B',
            'type' => 'personal',
            'owner_id' => $this->userB->id,
            'timezone' => 'Asia/Jakarta',
        ]);
        $this->spaceB->members()->attach($this->userB->id, ['role' => 'owner', 'status' => 'active']);
    }

    public function test_can_list_members_in_active_space(): void
    {
        Member::create([
            'space_id' => $this->spaceA->id,
            'name' => 'Family Member',
            'color' => '#0f4c3a',
        ]);

        $response = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->get('/members');

        $response->assertStatus(200)
            ->assertSee('Family Member');
    }

    public function test_cannot_see_members_from_other_space(): void
    {
        Member::create([
            'space_id' => $this->spaceB->id,
            'name' => 'Secret Member B',
            'color' => '#1e3a8a',
        ]);

        $response = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->get('/members');

        $response->assertStatus(200)
            ->assertDontSee('Secret Member B');
    }

    public function test_can_create_member(): void
    {
        $response = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->post('/members', [
                'name' => 'Parent',
                'color' => '#0f4c3a',
                'notes' => 'Tagihan rutin orang tua',
            ]);

        $response->assertRedirect('/members');
        $this->assertDatabaseHas('members', [
            'space_id' => $this->spaceA->id,
            'name' => 'Parent',
            'color' => '#0f4c3a',
            'notes' => 'Tagihan rutin orang tua',
        ]);
    }

    public function test_can_update_member(): void
    {
        $member = Member::create([
            'space_id' => $this->spaceA->id,
            'name' => 'Nama Lama',
            'color' => '#0f4c3a',
        ]);

        $response = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->put("/members/{$member->id}", [
                'name' => 'Nama Baru',
                'color' => '#1e3a8a',
                'notes' => 'Catatan baru',
            ]);

        $response->assertRedirect('/members');
        $this->assertDatabaseHas('members', [
            'id' => $member->id,
            'name' => 'Nama Baru',
            'color' => '#1e3a8a',
        ]);
    }

    public function test_cannot_update_member_of_another_space(): void
    {
        $memberB = Member::create([
            'space_id' => $this->spaceB->id,
            'name' => 'Member B',
            'color' => '#0f4c3a',
        ]);

        $response = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->put("/members/{$memberB->id}", [
                'name' => 'Hacked Name',
            ]);

        $response->assertStatus(403);
    }

    public function test_can_archive_and_unarchive_member(): void
    {
        $member = Member::create([
            'space_id' => $this->spaceA->id,
            'name' => 'Arsip Test',
            'color' => '#0f4c3a',
        ]);

        $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->delete("/members/{$member->id}");

        $member->refresh();
        $this->assertTrue($member->isArchived());

        $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->delete("/members/{$member->id}");

        $member->refresh();
        $this->assertFalse($member->isArchived());
    }
}
