<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\OperatorToken;
use App\Models\Settings;
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
            'operator_token_id' => $operatorToken->id,
            'user_id' => null,
        ]);
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
}
