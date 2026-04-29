<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpsertLlmSettingRequest;
use App\Models\UserLlmSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;

class LlmSettingController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $setting = UserLlmSetting::query()->where('user_id', $request->user()->id)->first();

        if (!$setting) {
            return response()->json([
                'provider' => 'openai_compatible',
                'base_url' => config('services.llm.base_url'),
                'default_model' => config('services.llm.default_model'),
                'timeout' => config('services.llm.timeout', 30),
                'has_api_key' => false,
            ]);
        }

        return response()->json([
            'provider' => $setting->provider,
            'base_url' => $setting->base_url,
            'default_model' => $setting->default_model,
            'timeout' => $setting->timeout,
            'has_api_key' => !empty($setting->api_key_encrypted),
        ]);
    }

    public function upsert(UpsertLlmSettingRequest $request): JsonResponse
    {
        $data = $request->validated();

        UserLlmSetting::query()->updateOrCreate(
            ['user_id' => $request->user()->id],
            [
                'provider' => $data['provider'] ?? 'openai_compatible',
                'api_key_encrypted' => Crypt::encryptString($data['api_key']),
                'base_url' => $data['base_url'],
                'default_model' => $data['default_model'],
                'timeout' => $data['timeout'] ?? 30,
            ]
        );

        return response()->json(['message' => 'LLM settings saved']);
    }

    public function models(Request $request): JsonResponse
    {
        $setting = UserLlmSetting::query()->where('user_id', $request->user()->id)->first();

        $baseUrl = $setting?->base_url ?: config('services.llm.base_url');
        $apiKey = null;

        if ($setting?->api_key_encrypted) {
            try {
                $apiKey = Crypt::decryptString($setting->api_key_encrypted);
            } catch (\Throwable) {
                $apiKey = null;
            }
        }

        $apiKey = $apiKey ?: config('services.llm.api_key');

        if (!$apiKey) {
            return response()->json(['message' => 'No API key configured.'], 422);
        }

        $response = Http::timeout((int) config('services.llm.timeout', 30))
            ->withToken($apiKey)
            ->get(rtrim($baseUrl, '/').'/models');

        if (!$response->successful()) {
            $bodyJson = $response->json();
            $providerMessage = data_get($bodyJson, 'error.message') ?: 'Failed to fetch model list.';
            $providerCode = data_get($bodyJson, 'error.code');

            $message = is_string($providerCode) && $providerCode !== ''
                ? "LLM provider error ({$providerCode}): {$providerMessage}"
                : "LLM provider error: {$providerMessage}";

            return response()->json(['message' => $message], $response->status());
        }

        $models = collect((array) data_get($response->json(), 'data', []))
            ->map(fn ($item) => data_get($item, 'id'))
            ->filter(fn ($id) => is_string($id) && $id !== '')
            ->values();

        return response()->json(['models' => $models]);
    }
}
