<?php

namespace Tests\Feature;

use App\Models\OperationHistory;
use App\Models\OperatorToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OperationHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_the_history_page(): void
    {
        $this->get('/history')->assertRedirect(route('login'));
    }

    public function test_owner_can_view_history_attributed_to_the_owner_and_operators(): void
    {
        $user = User::factory()->create(['name' => 'Owner']);
        $operatorToken = OperatorToken::create(['name' => 'Alex']);

        OperationHistory::create([
            'operation' => 'beep',
            'type' => 'duration',
            'value' => 5,
            'user_id' => $user->id,
        ]);

        OperationHistory::create([
            'operation' => 'shock',
            'type' => 'intensity',
            'value' => 20,
            'operator_token_id' => $operatorToken->id,
        ]);

        $response = $this->actingAs($user)->get('/history');

        $response->assertOk();
        $response->assertSee('Owner');
        $response->assertSee('Alex');
    }

    public function test_duration_and_intensity_from_the_same_command_are_shown_as_one_row(): void
    {
        $user = User::factory()->create();

        OperationHistory::create([
            'operation' => 'shock',
            'type' => 'duration',
            'value' => 10,
            'user_id' => $user->id,
        ]);

        OperationHistory::create([
            'operation' => 'shock',
            'type' => 'intensity',
            'value' => 40,
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->get('/history');

        $response->assertOk();
        $response->assertSeeTextInOrder(['10', '40']);
        $this->assertSame(1, substr_count($response->getContent(), '>shock<'));
    }

    public function test_unrelated_commands_from_different_actors_are_not_merged(): void
    {
        $user = User::factory()->create();
        $operatorToken = OperatorToken::create(['name' => 'Alex']);

        OperationHistory::create([
            'operation' => 'beep',
            'type' => 'duration',
            'value' => 5,
            'user_id' => $user->id,
        ]);

        OperationHistory::create([
            'operation' => 'beep',
            'type' => 'duration',
            'value' => 7,
            'operator_token_id' => $operatorToken->id,
        ]);

        $response = $this->actingAs($user)->get('/history');

        $response->assertOk();
        $this->assertSame(2, substr_count($response->getContent(), '>beep<'));
    }

    public function test_a_failed_command_is_shown_as_failed(): void
    {
        $user = User::factory()->create();

        OperationHistory::create([
            'operation' => 'beep',
            'type' => 'duration',
            'value' => 5,
            'succeeded' => false,
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->get('/history');

        $response->assertOk();
        $response->assertSee('Failed');
        $response->assertDontSee('Success');
    }

    public function test_a_successful_command_is_shown_as_success(): void
    {
        $user = User::factory()->create();

        OperationHistory::create([
            'operation' => 'beep',
            'type' => 'duration',
            'value' => 5,
            'succeeded' => true,
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->get('/history');

        $response->assertOk();
        $response->assertSee('Success');
        $response->assertDontSee('Failed');
    }

    public function test_the_devices_a_command_targeted_are_shown(): void
    {
        $user = User::factory()->create();

        OperationHistory::create([
            'operation' => 'shock',
            'type' => 'duration',
            'value' => 10,
            'devices' => ['Living Room', 'Bedroom'],
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->get('/history');

        $response->assertOk();
        $response->assertSee('Living Room, Bedroom');
    }

    public function test_missing_device_history_shows_a_placeholder(): void
    {
        $user = User::factory()->create();

        OperationHistory::create([
            'operation' => 'beep',
            'type' => 'duration',
            'value' => 5,
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->get('/history');

        $response->assertOk();
        $response->assertSeeTextInOrder(['beep', '---']);
    }

    public function test_guests_cannot_prune_history(): void
    {
        $this->post('/history/prune')->assertRedirect(route('login'));
    }

    public function test_the_owner_can_manually_prune_old_history(): void
    {
        $user = User::factory()->create();

        $old = OperationHistory::create(['operation' => 'beep', 'type' => 'duration', 'value' => 5]);
        $old->forceFill(['created_at' => now()->subDays(200)])->save();

        $recent = OperationHistory::create(['operation' => 'beep', 'type' => 'duration', 'value' => 5]);

        config(['pishock.history_retention_days' => 180]);

        $response = $this->actingAs($user)->post(route('history.prune'));

        $response->assertRedirect(route('history.index'));
        $this->assertSoftDeleted($old);
        $this->assertNotSoftDeleted($recent);
    }

    public function test_manual_history_pruning_is_disabled_when_retention_is_zero(): void
    {
        $user = User::factory()->create();

        $old = OperationHistory::create(['operation' => 'beep', 'type' => 'duration', 'value' => 5]);
        $old->forceFill(['created_at' => now()->subDays(2000)])->save();

        config(['pishock.history_retention_days' => 0]);

        $this->actingAs($user)->post(route('history.prune'));

        $this->assertNotSoftDeleted($old);
    }
}
