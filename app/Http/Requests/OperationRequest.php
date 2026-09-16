<?php

namespace App\Http\Requests;

use App\Enums\ControlTypes;
use Illuminate\Foundation\Http\FormRequest;

class OperationRequest extends FormRequest
{
    /**
     * @return array<string, array>
    */
    public function rules(): array
    {
        return [
            'operation' => ['required', 'string', 'in:' . implode(',', ControlTypes::$types)],
            'duration' => ['required', 'integer', 'min:1', 'max:100'],
            'intensity' => ['nullable', 'integer', 'min:1', 'max:100'],
            'deviceShareCodes' => ['required', 'array', 'min:1'],
            'deviceShareCodes.*' => ['string'],
        ];
    }
}
