<?php

namespace App\Http\Controllers;

use App\Enums\ControlTypes;
use App\Enums\Operations;
use App\Http\Requests\OperationRequest;
use App\Models\Device;
use App\Models\OperationHistory;
use App\Models\OperatorToken;
use App\Models\Settings;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PishockController extends Controller
{
    protected string $baseUrl = 'https://do.pishock.com/api/apioperate';

    protected string $username;

    protected string $apiKey;

    protected string $name = 'Pishock interface';

    private array $devices;

    public function __construct()
    {
        $this->username = config('pishock.username');
        $this->apiKey = config('pishock.apikey');
        $this->devices = Device::all()->pluck('device_name', 'share_code')->toArray();
    }

    /**
     * The owner's own control panel.
     *
     * @return View
     */
    public function index(): View
    {
        return $this->renderControls();
    }

    /**
     * A named operator's control panel, reached via their share link.
     *
     * @param string $token
     * @return View
     */
    public function operate(string $token): View
    {
        $operator = OperatorToken::active()->where('token', $token)->firstOrFail();

        return $this->renderControls($operator);
    }

    /**
     * @param OperatorToken|null $operator
     * @return View
     */
    protected function renderControls(?OperatorToken $operator = null): View
    {
        return view('pishock', [
            'devices' => $this->devices,
            'maxValues' => $this->buildMaxValues(),
            'operator' => $operator,
        ]);
    }

    /**
     * The current max values, polled by the control panel so operators
     * see a change the owner makes without needing to reload the page.
     *
     * @return JsonResponse
     */
    public function maxValues(): JsonResponse
    {
        return response()->json($this->buildMaxValues());
    }

    /**
     * @return array<string, array<string, int>>
     */
    protected function buildMaxValues(): array
    {
        $maxValues = [];

        foreach (Settings::all() as $setting) {
            $maxValues[$setting->operation][$setting->type] = $setting->max_value;
        }

        return $maxValues;
    }

    /**
     * @param OperationRequest $request
     * @return RedirectResponse
     */
    public function sendCommand(OperationRequest $request): RedirectResponse
    {
        return $this->handleCommand($request);
    }

    /**
     * @param OperationRequest $request
     * @param string $token
     * @return RedirectResponse
     */
    public function sendCommandAs(OperationRequest $request, string $token): RedirectResponse
    {
        $operator = OperatorToken::active()->where('token', $token)->firstOrFail();

        return $this->handleCommand($request, $operator);
    }

    /**
     * @param OperationRequest $request
     * @param OperatorToken|null $operator
     * @return RedirectResponse
     */
    protected function handleCommand(OperationRequest $request, ?OperatorToken $operator = null): RedirectResponse
    {
        $operation = $request->input('operation');
        $devices = $request->input('deviceShareCodes');

        $duration = $this->clampToConfiguredMax($operation, 'duration', (int) $request->input('duration'));
        $intensity = $request->filled('intensity')
            ? $this->clampToConfiguredMax($operation, 'intensity', (int) $request->input('intensity'))
            : null;

        $outcome = match ($operation) {
            'shock' => $this->sendRequest(Operations::SHOCK, $duration, $devices, $intensity),
            'vibrate' => $this->sendRequest(Operations::VIBRATE, $duration, $devices, $intensity),
            'beep' => $this->sendRequest(Operations::BEEP, $duration, $devices),
            default => ['message' => 'Invalid operation', 'succeeded' => false],
        };

        $this->recordHistory($operation, 'duration', $duration, $outcome['succeeded'], $operator);
        if ($intensity !== null) {
            $this->recordHistory($operation, 'intensity', $intensity, $outcome['succeeded'], $operator);
        }

        return redirect()->back()->with('response', $outcome['message']);
    }

    /**
     * Caps a requested value at the owner-configured max for this operation/type,
     * so the max-value setting can't be bypassed by posting the form directly.
     */
    protected function clampToConfiguredMax(string $operation, string $type, int $value): int
    {
        $max = Settings::where('operation', $operation)->where('type', $type)->value('max_value');

        return $max !== null ? min($value, $max) : $value;
    }

    protected function recordHistory(string $operation, string $type, int $value, bool $succeeded, ?OperatorToken $operator = null): void
    {
        OperationHistory::create([
            'operation' => $operation,
            'type' => $type,
            'value' => $value,
            'succeeded' => $succeeded,
            'user_id' => $operator ? null : Auth::id(),
            'operator_token_id' => $operator?->id,
        ]);
    }

    /**
     * Sends the operation to each device independently, so one device
     * failing (bad share code, offline, etc.) doesn't lose the results
     * of the others. Overall "succeeded" is true only if every device did.
     *
     * @param string $operation
     * @param int $duration
     * @param array $deviceShareCodes
     * @param int|null $intensity
     * @return array{message: string, succeeded: bool}
     */
    protected function sendRequest(string $operation, int $duration, array $deviceShareCodes, ?int $intensity = null): array
    {
        $client = new Client();
        $results = [];
        $succeeded = true;

        foreach ($deviceShareCodes as $deviceCode) {
            $deviceName = $this->devices[$deviceCode] ?? $deviceCode;

            $params = [
                'Username' => $this->username,
                'Name' => $this->name,
                'Code' => $deviceCode,
                'Apikey' => $this->apiKey,
                'Op' => $operation,
                'Duration' => $duration,
            ];

            if ($intensity !== null) {
                $params['Intensity'] = $intensity;
            }

            $outcome = $this->sendToDevice($client, $params);
            $succeeded = $succeeded && $outcome['success'];
            $results[] = "{$deviceName}: " . $outcome['message'];
        }

        return [
            'message' => implode(' | ', $results),
            'succeeded' => $succeeded,
        ];
    }

    /**
     * @param Client $client
     * @param array $params
     * @return array{success: bool, message: string}
     */
    protected function sendToDevice(Client $client, array $params): array
    {
        // Never log $params: it contains the PiShock API key.
        $context = ['operation' => $params['Op'], 'device' => $params['Code']];

        try {
            $response = $client->post($this->baseUrl, [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'body' => json_encode($params),
            ]);

            return ['success' => true, 'message' => trim($response->getBody()->getContents()) ?: 'Sent.'];
        } catch (ConnectException $e) {
            Log::error('PiShock request failed: could not reach the API.', $context);

            return ['success' => false, 'message' => 'Could not reach PiShock, please try again.'];
        } catch (RequestException $e) {
            $status = $e->getResponse()?->getStatusCode();
            Log::error('PiShock request was rejected.', $context + ['status' => $status]);

            return [
                'success' => false,
                'message' => $status
                    ? "PiShock rejected the request (HTTP {$status})."
                    : 'PiShock rejected the request.',
            ];
        } catch (GuzzleException $e) {
            Log::error('PiShock request failed unexpectedly.', $context);

            return ['success' => false, 'message' => 'Something went wrong sending this command.'];
        }
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function updateMaxValues(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'operation' => ['required', 'string', 'in:' . implode(',', ControlTypes::$types)],
            'type' => ['required', 'string', 'in:duration,intensity'],
            'max_value' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        Settings::updateOrCreate(
            ['operation' => $validated['operation'], 'type' => $validated['type']],
            ['max_value' => $validated['max_value']]
        );

        return response()->json([
            'operation' => $validated['operation'],
            'type' => $validated['type'],
            'maxValue' => $validated['max_value'],
        ]);
    }
}
