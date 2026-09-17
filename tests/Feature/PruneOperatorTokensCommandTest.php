<?php

namespace Tests\Feature;

use App\Models\OperatorToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PruneOperatorTokensCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_archives_operator_links_revoked_longer_than_the_retention_period(): void
    {
        $old = OperatorToken::create(['name' => 'Old']);
        $old->forceFill(['revoked_at' => now()->subDays(40)])->save();

        $this->artisan('operators:prune', ['--days' => 30])->assertSuccessful();

        $this->assertSoftDeleted($old);
    }

    public function test_it_archives_operator_links_expired_longer_than_the_retention_period(): void
    {
        $old = OperatorToken::create(['name' => 'Old', 'expires_at' => now()->subDays(40)]);

        $this->artisan('operators:prune', ['--days' => 30])->assertSuccessful();

        $this->assertSoftDeleted($old);
    }

    public function test_it_keeps_recently_revoked_operator_links(): void
    {
        $recent = OperatorToken::create(['name' => 'Recent']);
        $recent->forceFill(['revoked_at' => now()->subDays(5)])->save();

        $this->artisan('operators:prune', ['--days' => 30])->assertSuccessful();

        $this->assertNotSoftDeleted($recent);
    }

    public function test_it_keeps_active_operator_links(): void
    {
        $active = OperatorToken::create(['name' => 'Active']);

        $this->artisan('operators:prune', ['--days' => 30])->assertSuccessful();

        $this->assertNotSoftDeleted($active);
    }

    public function test_it_is_disabled_when_retention_is_zero(): void
    {
        $old = OperatorToken::create(['name' => 'Old']);
        $old->forceFill(['revoked_at' => now()->subDays(400)])->save();

        $this->artisan('operators:prune', ['--days' => 0])->assertSuccessful();

        $this->assertNotSoftDeleted($old);
    }

    public function test_archived_operator_links_no_longer_appear_on_the_operators_page(): void
    {
        $user = \App\Models\User::factory()->create();
        $old = OperatorToken::create(['name' => 'GoneNow']);
        $old->forceFill(['revoked_at' => now()->subDays(40)])->save();

        $this->artisan('operators:prune', ['--days' => 30]);

        $response = $this->actingAs($user)->get('/operators');

        $response->assertDontSee('GoneNow');
        $this->assertDatabaseHas('operator_tokens', ['name' => 'GoneNow']);
    }
}
