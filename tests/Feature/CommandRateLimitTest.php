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

    private const LIMIT = 3;

    protected function setUp(): void
    {
        parent::setUp();

        // Fixed regardless of the app's configured default, and kept low
        // so these tests don't need dozens of (slow, real) outbound requests.
        config(['pishock.commands_per_minute' => self::LIMIT]);
    }

    private function sendBeep(string $url, string $shareCode): TestResponse
    {
        return $this->post($url, [
            'operation' => 'beep',
            'duration' => 5,
            'deviceShareCodes' => [$shareCode],
        ]);
    }

    public function test_an_operator_is_throttled_after_the_configured_limit(): void
    {
        $device = Device::create(['device_name' => 'Test Shocker', 'share_code' => 'ABC123']);
        $operatorToken = OperatorToken::create(['name' => 'Alex']);
        $url = route('pishock.operate.send', $operatorToken->token);

        for ($i = 0; $i < self::LIMIT; $i++) {
            $this->sendBeep($url, $device->share_code)->assertRedirect();
        }

        $this->assertDatabaseCount('operation_history', self::LIMIT);

        $response = $this->sendBeep($url, $device->share_code);

        $response->assertRedirect();
        $this->assertDatabaseCount('operation_history', self::LIMIT);
    }

    public function test_separate_operator_tokens_have_independent_rate_limits(): void
    {
        $device = Device::create(['device_name' => 'Test Shocker', 'share_code' => 'ABC123']);
        $alex = OperatorToken::create(['name' => 'Alex']);
        $sam = OperatorToken::create(['name' => 'Sam']);

        $alexUrl = route('pishock.operate.send', $alex->token);
        for ($i = 0; $i < self::LIMIT + 1; $i++) {
            $this->sendBeep($alexUrl, $device->share_code);
        }

        // Alex hit the limit, so only LIMIT of Alex's LIMIT+1 attempts landed.
        $this->assertDatabaseCount('operation_history', self::LIMIT);

        // Sam's bucket is untouched by Alex's activity.
        $this->sendBeep(route('pishock.operate.send', $sam->token), $device->share_code);

        $this->assertDatabaseCount('operation_history', self::LIMIT + 1);
    }

    public function test_the_owner_is_rate_limited_separately_from_operators(): void
    {
        $device = Device::create(['device_name' => 'Test Shocker', 'share_code' => 'ABC123']);
        $user = User::factory()->create();
        $operatorToken = OperatorToken::create(['name' => 'Alex']);

        $operatorUrl = route('pishock.operate.send', $operatorToken->token);
        for ($i = 0; $i < self::LIMIT + 1; $i++) {
            $this->sendBeep($operatorUrl, $device->share_code);
        }

        $this->assertDatabaseCount('operation_history', self::LIMIT);

        $this->actingAs($user)->sendBeep('/pishock', $device->share_code);

        $this->assertDatabaseCount('operation_history', self::LIMIT + 1);
    }
}
