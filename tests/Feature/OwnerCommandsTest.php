<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class OwnerCommandsTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_create_creates_the_single_owner_account(): void
    {
        $this->artisan('owner:create', [
            'name' => 'Owner',
            'email' => 'owner@example.com',
            'password' => 'a-strong-password',
        ])->assertSuccessful();

        $this->assertDatabaseHas('users', ['email' => 'owner@example.com']);
        $this->assertTrue(Hash::check('a-strong-password', User::first()->password));
    }

    public function test_owner_create_refuses_when_an_owner_already_exists(): void
    {
        User::factory()->create();

        $this->artisan('owner:create', [
            'name' => 'Another Owner',
            'email' => 'another@example.com',
            'password' => 'a-strong-password',
        ])->assertFailed();

        $this->assertDatabaseCount('users', 1);
    }

    public function test_owner_reset_password_changes_the_existing_owners_password(): void
    {
        $user = User::factory()->create();

        $this->artisan('owner:reset-password', ['password' => 'a-new-password'])
            ->assertSuccessful();

        $this->assertTrue(Hash::check('a-new-password', $user->fresh()->password));
    }

    public function test_owner_reset_password_fails_when_no_owner_exists(): void
    {
        $this->artisan('owner:reset-password', ['password' => 'a-new-password'])
            ->assertFailed();
    }
}
