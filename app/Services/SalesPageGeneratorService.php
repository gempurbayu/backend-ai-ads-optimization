<?php

namespace App\Services;

use App\Models\UserLlmSetting;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class SalesPageGeneratorService
{
    public function generate(array $input, int $userId, ?string $model = null): array
    {
        $prompt = [
            'product' => $input,
            'output_json_keys' => [
                'headline','subheadline','description','benefits','features','social_proof','cta_text','cta_subtext',
            ],
            'rules' => ['language' => 'English', 'tone' => 'persuasive, premium, clear', 'benefits_min' => 3, 'features_min' => 4],
        ];

        return $this->requestJson($prompt, $userId, $model);
    }

    public function regenerateSection(array $product, array $currentContent, string $section, int $userId, ?string $model = null): array
    {
        $allowed = ['headline', 'subheadline', 'description', 'benefits', 'features', 'social_proof', 'cta_text', 'cta_subtext'];
        if (!in_array($section, $allowed, true)) {
            throw new RuntimeException('Invalid section to regenerate.');
        }

        $prompt = [
            'product' => $product,
            'current_content' => $currentContent,
            'task' => 'Regenerate only one section and keep others unchanged.',
            'target_section' => $section,
            'output_json_keys' => [$section],
        ];

        return $this->requestJson($prompt, $userId, $model);
    }

    private function requestJson(array $prompt, int $userId, ?string $model): array
    {
        $userSetting = UserLlmSetting::query()->where('user_id', $userId)->first();
        $selectedModel = $model ?: ($userSetting?->default_model ?: config('services.llm.default_model'));
        $baseUrl = $userSetting?->base_url ?: config('services.llm.base_url');
        $timeout = $userSetting?->timeout ?: (int) config('services.llm.timeout', 30);
        $requestTimeout = max(5, min($timeout, 20));

        $apiKey = null;
        if ($userSetting?->api_key_encrypted) {
            try { $apiKey = Crypt::decryptString($userSetting->api_key_encrypted); } catch (\Throwable $e) { Log::warning('Sales page key decrypt failed', ['error' => $e->getMessage()]); }
        }
        $apiKey = $apiKey ?: config('services.llm.api_key');
        if (!$apiKey) throw new RuntimeException('No API key available. Please save API key in Settings first.');

        try {
            $response = Http::timeout($requestTimeout)->connectTimeout(min(8, $requestTimeout))->withToken($apiKey)->post(rtrim($baseUrl, '/').'/chat/completions', [
                'model' => $selectedModel,
                'messages' => [
                    ['role' => 'system', 'content' => 'You are a direct-response copywriter. Return valid JSON only. No markdown.'],
                    ['role' => 'user', 'content' => json_encode($prompt, JSON_UNESCAPED_UNICODE)],
                ],
                'temperature' => 0.35,
            ]);
        } catch (\Throwable $e) {
            throw new RuntimeException('LLM request failed: '.$e->getMessage());
        }

        if (!$response->successful()) {
            $message = data_get($response->json(), 'error.message') ?: 'Unknown LLM provider error.';
            throw new RuntimeException('LLM provider error: '.$message);
        }

        $content = (string) data_get($response->json(), 'choices.0.message.content', '');
        $clean = trim($content);
        $clean = preg_replace('/^```json\s*/i', '', $clean);
        $clean = preg_replace('/^```\s*/i', '', $clean);
        $clean = preg_replace('/\s*```$/', '', $clean);
        $decoded = json_decode($clean, true);
        if (!is_array($decoded)) throw new RuntimeException('LLM returned invalid JSON format for sales page.');

        return [
            'headline' => (string) ($decoded['headline'] ?? ''),
            'subheadline' => (string) ($decoded['subheadline'] ?? ''),
            'description' => (string) ($decoded['description'] ?? ''),
            'benefits' => array_values(array_filter((array) ($decoded['benefits'] ?? []), 'is_string')),
            'features' => array_values(array_filter((array) ($decoded['features'] ?? []), 'is_string')),
            'social_proof' => (string) ($decoded['social_proof'] ?? 'Loved by 1,200+ customers worldwide.'),
            'cta_text' => (string) ($decoded['cta_text'] ?? 'Get Started Today'),
            'cta_subtext' => (string) ($decoded['cta_subtext'] ?? 'No credit card required.'),
            'meta' => ['model' => $selectedModel, 'provider' => 'llm'],
        ];
    }
}
