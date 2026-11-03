<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Space;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTest extends TestCase
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

    public function test_user_can_create_category(): void
    {
        $response = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->post('/categories', [
                'name' => 'Investasi Saham',
                'type' => 'income',
            ]);

        $response->assertRedirect('/categories');
        $this->assertDatabaseHas('categories', [
            'space_id' => $this->spaceA->id,
            'name' => 'Investasi Saham',
            'type' => 'income',
        ]);
    }

    public function test_duplicate_category_name_in_same_type_and_space_fails_validation(): void
    {
        Category::create([
            'space_id' => $this->spaceA->id,
            'name' => 'Makan',
            'type' => 'expense',
        ]);

        $response = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->post('/categories', [
                'name' => 'Makan',
                'type' => 'expense',
            ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_same_category_name_in_different_space_is_allowed(): void
    {
        Category::create([
            'space_id' => $this->spaceA->id,
            'name' => 'Makan',
            'type' => 'expense',
        ]);

        $response = $this->actingAs($this->userB)
            ->withSession(['active_space_id' => $this->spaceB->id])
            ->post('/categories', [
                'name' => 'Makan',
                'type' => 'expense',
            ]);

        $response->assertRedirect('/categories');
        $this->assertDatabaseHas('categories', [
            'space_id' => $this->spaceB->id,
            'name' => 'Makan',
            'type' => 'expense',
        ]);
    }

    public function test_user_cannot_update_category_from_another_space(): void
    {
        $categoryB = Category::create([
            'space_id' => $this->spaceB->id,
            'name' => 'Rahasia B',
            'type' => 'expense',
        ]);

        $response = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->put("/categories/{$categoryB->id}", [
                'name' => 'Ubah Kategori',
            ]);

        $response->assertStatus(403);
    }
}
