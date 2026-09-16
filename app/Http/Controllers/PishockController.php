<?php

namespace App\Http\Controllers;

use App\Enums\ControlTypes;
use App\Enums\Operations;
use App\Http\Requests\OperationRequest;
use App\Models\Device;
use App\Models\Settings;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
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
     * @return View
     */
    public function index(): View
    {
        $settings = Settings::all();
        $maxValues = [];

        foreach ($settings as $setting) {
            $maxValues[$setting->operation][$setting->type] = $setting->max_value;
        }

        return view('pishock', ['devices' => $this->devices, 'maxValues' => $maxValues]);
    }

    /**
     * @param OperationRequest $request
     * @return RedirectResponse
     */
    public function sendCommand(OperationRequest $request): RedirectResponse
    {
        $operation = $request->input('operation');
        $duration = $request->input('duration');
        $intensity = $request->input('intensity');
        $devices = $request->input('deviceShareCodes');

        $response = match ($operation) {
            'shock' => $this->sendRequest(Operations::SHOCK, $duration, $devices, $intensity),
            'vibrate' => $this->sendRequest(Operations::VIBRATE, $duration, $devices, $intensity),
            'beep' => $this->sendRequest(Operations::BEEP, $duration, $devices),
            default => 'Invalid operation',
        };

        return redirect()->back()->with('response', $response);
    }

    /**
     * @param string $operation
     * @param int $duration
     * @param array $deviceShareCodes
     * @param int|null $intensity
     * @return string|null
     */
    protected function sendRequest(string $operation, int $duration, array $deviceShareCodes, ?int $intensity = null): ?string
    {
        try {
            $client = new Client();

            $responses = [];

            foreach ($deviceShareCodes as $deviceCode) {
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

                $response = $client->post($this->baseUrl, [
                    'headers' => [
                        'Content-Type' => 'application/json',
                    ],
                    'body' => json_encode($params),
                ]);

                $responses[] = $response->getBody()->getContents();
            }

            return implode(', ', $responses);
        } catch (GuzzleException $e) {
            Log::error($e);
        }
        return null;
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
