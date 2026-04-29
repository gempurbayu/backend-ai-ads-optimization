<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AnalysisRequest;
use App\Models\Analysis;
use App\Models\Campaign;
use App\Services\AiAnalysisService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class AnalysisController extends Controller
{
    public function __construct(private readonly AiAnalysisService $aiAnalysisService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $analyses = Analysis::query()
            ->with('campaign:id,name,platform')
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get();

        return response()->json($analyses);
    }

    public function store(AnalysisRequest $request): JsonResponse
    {
        $campaign = Campaign::query()
            ->where('id', $request->integer('campaign_id'))
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        try {
            $generated = $this->aiAnalysisService->generate(
                campaign: $campaign,
                focus: $request->input('focus'),
                model: $request->input('model')
            );
        } catch (RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }

        $analysis = Analysis::query()->create([
            'user_id' => $request->user()->id,
            'campaign_id' => $campaign->id,
            'result' => $generated['result'],
            'summary' => $generated['summary'],
            'action_items' => $generated['action_items'],
            'meta' => $generated['meta'],
        ]);

        return response()->json($analysis->load('campaign:id,name,platform'), 201);
    }

    public function show(Request $request, Analysis $analysis): JsonResponse
    {
        abort_if($analysis->user_id !== $request->user()->id, 403);

        return response()->json($analysis->load('campaign'));
    }

    public function destroy(Request $request, Analysis $analysis): JsonResponse
    {
        abort_if($analysis->user_id !== $request->user()->id, 403);

        $analysis->delete();

        return response()->json(['message' => 'Analysis deleted']);
    }
}
