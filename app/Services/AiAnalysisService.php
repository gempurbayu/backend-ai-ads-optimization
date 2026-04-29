<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\UserLlmSetting;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class AiAnalysisService
{
    public function generate(Campaign $campaign, ?string $focus = null, ?string $model = null): array
    {
        $metrics = app(MetricsService::class)->calculate($campaign->toArray());

        $userSetting = UserLlmSetting::query()->where('user_id', $campaign->user_id)->first();
        $selectedModel = $model ?: ($userSetting?->default_model ?: config('services.llm.default_model'));
        $baseUrl = $userSetting?->base_url ?: config('services.llm.base_url');
        $timeout = $userSetting?->timeout ?: (int) config('services.llm.timeout', 30);

        $apiKey = null;
        if ($userSetting?->api_key_encrypted) {
            try {
                $apiKey = Crypt::decryptString($userSetting->api_key_encrypted);
            } catch (\Throwable $e) {
                Log::warning('LLM key decrypt failed, fallback to global key', ['error' => $e->getMessage()]);
                $apiKey = null;
            }
        }
        $apiKey = $apiKey ?: config('services.llm.api_key');

        if ($apiKey) {
            $llm = $this->generateFromLlm($campaign, $metrics, $focus, $selectedModel, $baseUrl, $apiKey, $timeout);
            if ($llm !== null) {
                return [
                    ...$llm,
                    'meta' => [
                        ...($llm['meta'] ?? []),
                        'focus' => $focus,
                        'metrics' => $metrics,
                        'model' => $selectedModel,
                        'provider' => 'llm',
                    ],
                ];
            }

            throw new RuntimeException('LLM generation failed. Check API key, model, or provider base URL in Settings.');
        }

        $fallback = $this->generateHeuristic($campaign, $metrics, $focus);
        $fallback['meta']['model'] = $selectedModel;
        $fallback['meta']['provider'] = 'heuristic_fallback';
        $fallback['meta']['llm_debug'] = $this->buildDebugMeta($apiKey, $baseUrl, $selectedModel, $timeout);

        return $fallback;
    }

    private function generateFromLlm(Campaign $campaign, array $metrics, ?string $focus, string $model, string $baseUrl, string $apiKey, int $timeout): ?array
    {
        $prompt = [
            'campaign' => [
                'name' => $campaign->name,
                'platform' => $campaign->platform,
                'impressions' => $campaign->impressions,
                'clicks' => $campaign->clicks,
                'conversions' => $campaign->conversions,
                'cost' => $campaign->cost,
                'revenue' => $campaign->revenue,
                'date_start' => $campaign->date_start,
                'date_end' => $campaign->date_end,
            ],
            'metrics' => $metrics,
            'focus' => $focus,
            'output_format' => [
                'summary' => 'string',
                'underperforming_signals' => 'string[]',
                'optimization_suggestions' => 'string[]',
                'prioritized_action_items' => 'string[]',
            ],
        ];

        $response = Http::timeout($timeout)
            ->withToken($apiKey)
            ->post(rtrim($baseUrl, '/').'/chat/completions', [
                'model' => $model,
                'messages' => [
                    ['role' => 'system', 'content' => 'You are a senior performance marketing analyst. Respond in English only. Return valid JSON only with keys: summary, underperforming_signals, optimization_suggestions, prioritized_action_items.'],
                    ['role' => 'user', 'content' => json_encode($prompt, JSON_UNESCAPED_UNICODE)],
                ],
                'temperature' => 0.3,
            ]);

        if (!$response->successful()) {
            $bodyJson = $response->json();
            $providerMessage = data_get($bodyJson, 'error.message');
            $providerCode = data_get($bodyJson, 'error.code');

            Log::warning('LLM request failed', [
                'status' => $response->status(),
                'body' => $response->body(),
                'base_url' => $baseUrl,
                'model' => $model,
            ]);

            $safeMessage = is_string($providerMessage) && $providerMessage !== ''
                ? $providerMessage
                : 'Unknown LLM provider error.';

            if (is_string($providerCode) && $providerCode !== '') {
                throw new RuntimeException("LLM provider error ({$providerCode}): {$safeMessage}");
            }

            throw new RuntimeException("LLM provider error: {$safeMessage}");
        }

        $content = data_get($response->json(), 'choices.0.message.content');
        if (!is_string($content) || $content === '') {
            Log::warning('LLM response missing content', ['json' => $response->json()]);
            throw new RuntimeException('LLM response is missing content.');
        }

        $clean = trim($content);
        $clean = preg_replace('/^```json\s*/i', '', $clean);
        $clean = preg_replace('/^```\s*/i', '', $clean);
        $clean = preg_replace('/\s*```$/', '', $clean);

        $decoded = json_decode($clean, true);
        if (!is_array($decoded)) {
            Log::warning('LLM JSON decode failed', ['content' => $content]);
            throw new RuntimeException('LLM response format is invalid JSON.');
        }

        $summary = (string) ($decoded['summary'] ?? 'Analysis generated');
        $actions = array_values(array_filter((array) ($decoded['prioritized_action_items'] ?? []), fn ($v) => is_string($v) && $v !== ''));

        return [
            'summary' => $summary,
            'result' => json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            'action_items' => array_slice($actions, 0, 5),
            'meta' => [
                'raw_underperforming' => $decoded['underperforming_signals'] ?? [],
                'raw_optimizations' => $decoded['optimization_suggestions'] ?? [],
            ],
        ];
    }

    private function buildDebugMeta(mixed $apiKey, string $baseUrl, string $model, int $timeout): array
    {
        return [
            'has_api_key' => !empty($apiKey),
            'base_url' => $baseUrl,
            'model' => $model,
            'timeout' => $timeout,
        ];
    }

    private function generateHeuristic(Campaign $campaign, array $metrics, ?string $focus): array
    {
        $issues = [];
        if ($metrics['ctr'] < 1.0) $issues[] = 'Low CTR indicates creative or targeting may be misaligned.';
        if ($metrics['roas'] < 1.5) $issues[] = 'ROAS is below target; budget allocation and funnel optimization are recommended.';
        if ($metrics['cpa'] > 150000) $issues[] = 'High CPA detected; review audience overlap and landing page quality.';

        $actionItems = [
            'Reallocate 15–25% of budget from the lowest-ROAS ad sets to the highest-performing ones.',
            'Test 2–3 new creative variations with distinct value proposition angles.',
            'Narrow audience targeting to highest-CVR segments and exclude low-intent audiences.',
            'Optimize landing page speed and CTA clarity to reduce drop-off.',
        ];

        $summary = sprintf('Campaign %s on %s delivered CTR %.2f%%, CPC %.2f, CPA %.2f, and ROAS %.2f.', $campaign->name, $campaign->platform, $metrics['ctr'], $metrics['cpc'], $metrics['cpa'], $metrics['roas']);

        $result = [
            'summary' => $summary,
            'underperforming_signals' => $issues,
            'optimization_suggestions' => $actionItems,
            'prioritized_action_items' => array_slice($actionItems, 0, 3),
            'focus' => $focus,
            'metrics' => $metrics,
        ];

        return [
            'summary' => $summary,
            'result' => json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            'action_items' => $result['prioritized_action_items'],
            'meta' => ['focus' => $focus, 'metrics' => $metrics],
        ];
    }
}
