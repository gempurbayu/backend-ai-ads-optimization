<?php

namespace App\Services;

class MetricsService
{
    public function calculate(array $campaign): array
    {
        $impressions = max((int) ($campaign['impressions'] ?? 0), 0);
        $clicks = max((int) ($campaign['clicks'] ?? 0), 0);
        $conversions = max((int) ($campaign['conversions'] ?? 0), 0);
        $cost = max((float) ($campaign['cost'] ?? 0), 0);
        $revenue = max((float) ($campaign['revenue'] ?? 0), 0);

        $ctr = $impressions > 0 ? ($clicks / $impressions) * 100 : 0;
        $cpc = $clicks > 0 ? $cost / $clicks : 0;
        $cpa = $conversions > 0 ? $cost / $conversions : 0;
        $roas = $cost > 0 ? $revenue / $cost : 0;

        return [
            'ctr' => round($ctr, 4),
            'cpc' => round($cpc, 4),
            'cpa' => round($cpa, 4),
            'roas' => round($roas, 4),
        ];
    }
}
