<?php

namespace App\Services;

use App\Models\Campaign;

class AiAnalysisService
{
    public function generate(Campaign $campaign, ?string $focus = null): array
    {
        $metrics = app(MetricsService::class)->calculate($campaign->toArray());

        $issues = [];
        if ($metrics['ctr'] < 1.0) {
            $issues[] = 'CTR rendah, indikasi creative atau targeting kurang relevan.';
        }
        if ($metrics['roas'] < 1.5) {
            $issues[] = 'ROAS di bawah target, butuh optimasi budget dan conversion funnel.';
        }
        if ($metrics['cpa'] > 150000) {
            $issues[] = 'CPA tinggi, periksa audience overlap dan kualitas landing page.';
        }

        $actionItems = [
            'Alihkan 15-25% budget dari ad set ROAS terendah ke ROAS tertinggi.',
            'Uji 2-3 variasi creative baru dengan angle value proposition berbeda.',
            'Persempit audience ke segmen dengan CVR tertinggi dan exclude low intent.',
            'Optimalkan landing page speed + CTA untuk menurunkan drop-off.',
        ];

        $summary = sprintf(
            'Campaign %s di platform %s menghasilkan CTR %.2f%%, CPC %.2f, CPA %.2f, dan ROAS %.2f.',
            $campaign->name,
            $campaign->platform,
            $metrics['ctr'],
            $metrics['cpc'],
            $metrics['cpa'],
            $metrics['roas']
        );

        $result = [
            'performance_summary' => $summary,
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
