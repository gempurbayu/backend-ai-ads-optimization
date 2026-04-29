<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpsertLlmSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'provider' => ['nullable', 'string', 'max:60'],
            'api_key' => ['required', 'string', 'min:10', 'max:500'],
            'base_url' => ['required', 'url', 'max:255'],
            'default_model' => ['required', 'string', 'max:120'],
            'timeout' => ['nullable', 'integer', 'min:5', 'max:120'],
        ];
    }
}
