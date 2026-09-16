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
}
