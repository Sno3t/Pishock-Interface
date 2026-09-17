<?php

namespace Tests\Feature;

use App\Models\OperationHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PruneOperationHistoryCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_archives_history_older_than_the_retention_period(): void
    {
        $old = OperationHistory::create(['operation' => 'beep', 'type' => 'duration', 'value' => 5]);
        $old->forceFill(['created_at' => now()->subDays(200)])->save();

        $this->artisan('history:prune', ['--days' => 180])->assertSuccessful();

        $this->assertSoftDeleted($old);
    }

    public function test_it_keeps_recent_history(): void
    {
        $recent = OperationHistory::create(['operation' => 'beep', 'type' => 'duration', 'value' => 5]);

        $this->artisan('history:prune', ['--days' => 180])->assertSuccessful();

        $this->assertNotSoftDeleted($recent);
    }

    public function test_it_is_disabled_when_retention_is_zero(): void
    {
        $old = OperationHistory::create(['operation' => 'beep', 'type' => 'duration', 'value' => 5]);
        $old->forceFill(['created_at' => now()->subDays(2000)])->save();

        $this->artisan('history:prune', ['--days' => 0])->assertSuccessful();

        $this->assertNotSoftDeleted($old);
    }

    public function test_archived_history_no_longer_appears_on_the_history_page(): void
    {
        $user = User::factory()->create();
        $old = OperationHistory::create(['operation' => 'shock', 'type' => 'duration', 'value' => 5, 'user_id' => $user->id]);
        $old->forceFill(['created_at' => now()->subDays(200)])->save();

        $this->artisan('history:prune', ['--days' => 180]);

        $response = $this->actingAs($user)->get('/history');

        $response->assertSee('No commands have been sent yet.');
        $this->assertDatabaseHas('operation_history', ['id' => $old->id]);
    }
}
