<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CampaignRequest;
use App\Models\Campaign;
use App\Services\MetricsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
        $campaign = Campaign::query()->create([
            ...$request->validated(),
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

        $payload = collect($validated['campaigns'])->map(fn (array $row) => [
            ...$row,
            'user_id' => $request->user()->id,
            'created_at' => now(),
            'updated_at' => now(),
        ])->all();

        Campaign::query()->insert($payload);

        return response()->json(['message' => 'Bulk upload success'], 201);
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
