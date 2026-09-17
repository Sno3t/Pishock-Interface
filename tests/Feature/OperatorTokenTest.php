<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\OperationHistory;
use App\Models\OperatorToken;
use App\Models\Settings;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OperatorTokenTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_the_owner_control_panel(): void
    {
        $response = $this->get('/pishock');

        $response->assertRedirect(route('login'));
    }

    public function test_an_active_operator_token_can_view_the_control_panel(): void
    {
        Device::create(['device_name' => 'Test Shocker', 'share_code' => 'ABC123']);
        $operatorToken = OperatorToken::create(['name' => 'Alex']);

        $response = $this->get(route('pishock.operate', $operatorToken->token));

        $response->assertOk();
        $response->assertSee('Alex');
    }

    public function test_a_revoked_operator_token_cannot_view_the_control_panel(): void
    {
        $operatorToken = OperatorToken::create(['name' => 'Alex']);
        $operatorToken->revoke();

        $response = $this->get(route('pishock.operate', $operatorToken->token));

        $response->assertNotFound();
    }

    public function test_an_invalid_operator_token_returns_not_found(): void
    {
        $response = $this->get('/pishock/does-not-exist');

        $response->assertNotFound();
    }

    public function test_an_expired_operator_token_cannot_view_the_control_panel(): void
    {
        $operatorToken = OperatorToken::create(['name' => 'Alex', 'expires_at' => now()->subMinute()]);

        $response = $this->get(route('pishock.operate', $operatorToken->token));

        $response->assertNotFound();
    }

    public function test_an_expired_operator_token_cannot_send_commands(): void
    {
        $device = Device::create(['device_name' => 'Test Shocker', 'share_code' => 'ABC123']);
        $operatorToken = OperatorToken::create(['name' => 'Alex', 'expires_at' => now()->subMinute()]);

        $response = $this->post(route('pishock.operate.send', $operatorToken->token), [
            'operation' => 'beep',
            'duration' => 5,
            'deviceShareCodes' => [$device->share_code],
        ]);

        $response->assertNotFound();
    }

    public function test_a_token_with_a_future_expiry_can_still_be_used(): void
    {
        Device::create(['device_name' => 'Test Shocker', 'share_code' => 'ABC123']);
        $operatorToken = OperatorToken::create(['name' => 'Alex', 'expires_at' => now()->addDay()]);

        $response = $this->get(route('pishock.operate', $operatorToken->token));

        $response->assertOk();
        $response->assertSee('Alex');
    }

    public function test_an_operator_link_can_be_created_with_an_expiry(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('operators.store'), [
            'name' => 'Alex',
            'expires_at' => now()->addDay()->format('Y-m-d\TH:i'),
        ]);

        $response->assertRedirect(route('operators.index'));
        $this->assertNotNull(OperatorToken::where('name', 'Alex')->first()->expires_at);
    }

    public function test_an_operator_link_expiry_must_be_in_the_future(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('operators.store'), [
            'name' => 'Alex',
            'expires_at' => now()->subDay()->format('Y-m-d\TH:i'),
        ]);

        $response->assertSessionHasErrors('expires_at');
    }

    public function test_an_operator_can_send_a_command_and_it_is_attributed_to_them(): void
    {
        $device = Device::create(['device_name' => 'Test Shocker', 'share_code' => 'ABC123']);
        $operatorToken = OperatorToken::create(['name' => 'Alex']);

        $response = $this->post(route('pishock.operate.send', $operatorToken->token), [
            'operation' => 'beep',
            'duration' => 5,
            'deviceShareCodes' => [$device->share_code],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('operation_history', [
            'operation' => 'beep',
            'value' => 5,
            // No real PISHOCK_API_KEY is configured in tests, so this
            // genuinely fails against PiShock's API - exercising the
            // real failure path, not just asserting a hardcoded value.
            'succeeded' => false,
            'operator_token_id' => $operatorToken->id,
            'user_id' => null,
        ]);

        $this->assertSame(['Test Shocker'], OperationHistory::first()->devices);
    }

    public function test_operator_commands_are_clamped_to_the_configured_max(): void
    {
        $device = Device::create(['device_name' => 'Test Shocker', 'share_code' => 'ABC123']);
        $operatorToken = OperatorToken::create(['name' => 'Alex']);
        Settings::create(['operation' => 'beep', 'type' => 'duration', 'max_value' => 10]);

        $this->post(route('pishock.operate.send', $operatorToken->token), [
            'operation' => 'beep',
            'duration' => 90,
            'deviceShareCodes' => [$device->share_code],
        ]);

        $this->assertDatabaseHas('operation_history', [
            'operation' => 'beep',
            'value' => 10,
            'operator_token_id' => $operatorToken->id,
        ]);
    }

    public function test_a_revoked_operator_token_cannot_send_commands(): void
    {
        $device = Device::create(['device_name' => 'Test Shocker', 'share_code' => 'ABC123']);
        $operatorToken = OperatorToken::create(['name' => 'Alex']);
        $operatorToken->revoke();

        $response = $this->post(route('pishock.operate.send', $operatorToken->token), [
            'operation' => 'beep',
            'duration' => 5,
            'deviceShareCodes' => [$device->share_code],
        ]);

        $response->assertNotFound();
    }

    public function test_operators_cannot_update_max_values(): void
    {
        $response = $this->post(route('updateMaxValues'), [
            'operation' => 'beep',
            'type' => 'duration',
            'max_value' => 99,
        ]);

        $response->assertRedirect(route('login'));
    }

    public function test_guests_cannot_manage_devices(): void
    {
        $this->get('/devices')->assertRedirect(route('login'));
    }

    public function test_registration_is_disabled(): void
    {
        $this->get('/register')->assertNotFound();
        $this->post('/register')->assertNotFound();
    }

    public function test_guests_cannot_prune_operator_links(): void
    {
        $this->post('/operators/prune')->assertRedirect(route('login'));
    }

    public function test_the_owner_can_manually_archive_every_revoked_or_expired_operator_link(): void
    {
        $user = User::factory()->create();
        $justRevoked = OperatorToken::create(['name' => 'JustRevoked']);
        $justRevoked->revoke();
        $justExpired = OperatorToken::create(['name' => 'JustExpired', 'expires_at' => now()->subMinute()]);
        $old = OperatorToken::create(['name' => 'Old']);
        $old->forceFill(['revoked_at' => now()->subDays(40)])->save();
        $active = OperatorToken::create(['name' => 'Active']);

        $response = $this->actingAs($user)->post(route('operators.prune'));

        $response->assertRedirect(route('operators.index'));
        $this->assertSoftDeleted($justRevoked);
        $this->assertSoftDeleted($justExpired);
        $this->assertSoftDeleted($old);
        $this->assertNotSoftDeleted($active);
    }

    public function test_manual_pruning_ignores_the_configured_retention_period(): void
    {
        $user = User::factory()->create();
        $justRevoked = OperatorToken::create(['name' => 'JustRevoked']);
        $justRevoked->revoke();

        // A retention period of 0 disables the scheduled job, but a manual
        // click is an explicit action and should still archive right away.
        config(['pishock.operator_token_retention_days' => 0]);

        $this->actingAs($user)->post(route('operators.prune'));

        $this->assertSoftDeleted($justRevoked);
    }
}
