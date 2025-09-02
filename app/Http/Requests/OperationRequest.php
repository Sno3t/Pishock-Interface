<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OperationRequest extends FormRequest
{
    /**
     * @return array<string, array>
    */
    public function rules(): array
    {
        return [
            'operation' => ['required', 'string'],
            'duration' => ['required', 'int','max:100'],
            'intensity' => ['string', 'max:100'],
        ];
    }
}
