<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\OperatorToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class CommandRateLimitTest extends TestCase
{
    use RefreshDatabase;

    private function sendBeep(string $url, string $shareCode): TestResponse
    {
        return $this->post($url, [
            'operation' => 'beep',
            'duration' => 5,
            'deviceShareCodes' => [$shareCode],
        ]);
    }

    public function test_an_operator_is_throttled_after_ten_commands_in_a_minute(): void
    {
        $device = Device::create(['device_name' => 'Test Shocker', 'share_code' => 'ABC123']);
        $operatorToken = OperatorToken::create(['name' => 'Alex']);
        $url = route('pishock.operate.send', $operatorToken->token);

        for ($i = 0; $i < 10; $i++) {
            $this->sendBeep($url, $device->share_code)->assertRedirect();
        }

        $this->assertDatabaseCount('operation_history', 10);

        $response = $this->sendBeep($url, $device->share_code);

        $response->assertRedirect();
        $this->assertDatabaseCount('operation_history', 10);
    }

    public function test_separate_operator_tokens_have_independent_rate_limits(): void
    {
        $device = Device::create(['device_name' => 'Test Shocker', 'share_code' => 'ABC123']);
        $alex = OperatorToken::create(['name' => 'Alex']);
        $sam = OperatorToken::create(['name' => 'Sam']);

        $alexUrl = route('pishock.operate.send', $alex->token);
        for ($i = 0; $i < 11; $i++) {
            $this->sendBeep($alexUrl, $device->share_code);
        }

        // Alex hit the 10/minute limit, so only 10 of Alex's 11 attempts landed.
        $this->assertDatabaseCount('operation_history', 10);

        // Sam's bucket is untouched by Alex's activity.
        $this->sendBeep(route('pishock.operate.send', $sam->token), $device->share_code);

        $this->assertDatabaseCount('operation_history', 11);
    }

    public function test_the_owner_is_rate_limited_separately_from_operators(): void
    {
        $device = Device::create(['device_name' => 'Test Shocker', 'share_code' => 'ABC123']);
        $user = User::factory()->create();
        $operatorToken = OperatorToken::create(['name' => 'Alex']);

        $operatorUrl = route('pishock.operate.send', $operatorToken->token);
        for ($i = 0; $i < 11; $i++) {
            $this->sendBeep($operatorUrl, $device->share_code);
        }

        $this->assertDatabaseCount('operation_history', 10);

        $this->actingAs($user)->sendBeep('/pishock', $device->share_code);

        $this->assertDatabaseCount('operation_history', 11);
    }
}
