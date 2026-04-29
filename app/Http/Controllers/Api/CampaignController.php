<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CampaignRequest;
use App\Models\Campaign;
use App\Services\MetricsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CampaignController extends Controller
{
    public function __construct(private readonly MetricsService $metricsService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $campaigns = Campaign::query()
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get();

        return response()->json($campaigns);
    }

    public function store(CampaignRequest $request): JsonResponse
    {
        $data = $request->validated();
        $exists = Campaign::query()
            ->where('user_id', $request->user()->id)
            ->where('name', $data['name'])
            ->where('platform', $data['platform'])
            ->whereDate('date_start', $data['date_start'])
            ->whereDate('date_end', $data['date_end'])
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'campaign' => ['Duplicate campaign detected: same name, platform, and date range already exists.'],
            ]);
        }

        $campaign = Campaign::query()->create([
            ...$data,
            'user_id' => $request->user()->id,
        ]);

        return response()->json($campaign, 201);
    }

    public function bulkStore(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'campaigns' => ['required', 'array', 'min:1'],
            'campaigns.*.name' => ['required', 'string', 'max:255'],
            'campaigns.*.platform' => ['required', 'in:Facebook,Google,TikTok'],
            'campaigns.*.impressions' => ['required', 'integer', 'min:0'],
            'campaigns.*.clicks' => ['required', 'integer', 'min:0'],
            'campaigns.*.conversions' => ['required', 'integer', 'min:0'],
            'campaigns.*.cost' => ['required', 'numeric', 'min:0'],
            'campaigns.*.revenue' => ['required', 'numeric', 'min:0'],
            'campaigns.*.date_start' => ['required', 'date'],
            'campaigns.*.date_end' => ['required', 'date', 'after_or_equal:campaigns.*.date_start'],
        ]);

        $rows = collect($validated['campaigns'])->values();

        $existingKeys = Campaign::query()
            ->where('user_id', $request->user()->id)
            ->get(['name', 'platform', 'date_start', 'date_end'])
            ->map(fn ($c) => strtolower(trim($c->name)).'|'.$c->platform.'|'.substr((string) $c->date_start, 0, 10).'|'.substr((string) $c->date_end, 0, 10))
            ->flip();

        $seenInCsv = collect();
        $insertRows = [];
        $skipped = [];

        foreach ($rows as $idx => $row) {
            $key = strtolower(trim($row['name'])).'|'.$row['platform'].'|'.$row['date_start'].'|'.$row['date_end'];
            $label = $row['name'].' ('.$row['platform'].', '.$row['date_start'].' → '.$row['date_end'].')';

            if ($seenInCsv->has($key)) {
                $skipped[] = [
                    'index' => $idx + 1,
                    'campaign' => $label,
                    'reason' => 'duplicate_in_csv',
                ];
                continue;
            }

            $seenInCsv->put($key, true);

            if ($existingKeys->has($key)) {
                $skipped[] = [
                    'index' => $idx + 1,
                    'campaign' => $label,
                    'reason' => 'duplicate_in_database',
                ];
                continue;
            }

            $insertRows[] = [
                ...$row,
                'user_id' => $request->user()->id,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (!empty($insertRows)) {
            Campaign::query()->insert($insertRows);
        }

        return response()->json([
            'message' => 'Bulk upload processed',
            'inserted_count' => count($insertRows),
            'skipped_count' => count($skipped),
            'skipped' => $skipped,
        ], 201);
    }

    public function show(Request $request, Campaign $campaign): JsonResponse
    {
        abort_if($campaign->user_id !== $request->user()->id, 403);

        return response()->json([
            'campaign' => $campaign,
            'metrics' => $this->metricsService->calculate($campaign->toArray()),
        ]);
    }

    public function update(CampaignRequest $request, Campaign $campaign): JsonResponse
    {
        abort_if($campaign->user_id !== $request->user()->id, 403);

        $campaign->update($request->validated());

        return response()->json($campaign);
    }

    public function destroy(Request $request, Campaign $campaign): JsonResponse
    {
        abort_if($campaign->user_id !== $request->user()->id, 403);

        $campaign->delete();

        return response()->json(['message' => 'Campaign deleted']);
    }
}
