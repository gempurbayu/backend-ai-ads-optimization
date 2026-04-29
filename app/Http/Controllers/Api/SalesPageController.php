<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SalesPage;
use App\Services\SalesPageGeneratorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class SalesPageController extends Controller
{
    public function __construct(private readonly SalesPageGeneratorService $generator)
    {
    }

    public function index(Request $request): JsonResponse
    {
        return response()->json(SalesPage::query()->where('user_id', $request->user()->id)->latest()->get());
    }

    public function generate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'features' => ['required', 'array', 'min:1'],
            'features.*' => ['required', 'string', 'max:255'],
            'audience' => ['required', 'string', 'max:255'],
            'price' => ['required', 'string', 'max:120'],
            'unique_selling_points' => ['nullable', 'string', 'max:2000'],
            'model' => ['nullable', 'string', 'max:120'],
            'template' => ['nullable', 'in:aurora,slate,minimal'],
        ]);

        try {
            $generated = $this->generator->generate($data, $request->user()->id, $request->input('model'));
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $template = $data['template'] ?? 'aurora';
        $html = $this->renderHtml($data['name'], $data['price'], $data['audience'], $generated, $template);

        $page = SalesPage::query()->create([
            'user_id' => $request->user()->id,
            'name' => $data['name'],
            'description' => $data['description'],
            'features_input' => $data['features'],
            'audience' => $data['audience'],
            'price' => $data['price'],
            'unique_selling_points' => $data['unique_selling_points'] ?? null,
            'template' => $template,
            'content' => $generated,
            'export_html' => $html,
        ]);

        return response()->json($page, 201);
    }

    public function export(Request $request, SalesPage $salesPage)
    {
        abort_if($salesPage->user_id !== $request->user()->id, 403);
        $html = $salesPage->export_html ?: $this->renderHtml($salesPage->name, $salesPage->price, $salesPage->audience, (array) $salesPage->content, $salesPage->template ?: 'aurora');
        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="sales-page-'.$salesPage->id.'.html"',
        ]);
    }

    public function regenerate(Request $request, SalesPage $salesPage): JsonResponse
    {
        abort_if($salesPage->user_id !== $request->user()->id, 403);
        $data = $request->validate(['section' => ['required', 'string'], 'model' => ['nullable', 'string', 'max:120']]);

        try {
            $newContent = $this->generator->regenerateSection([
                'name' => $salesPage->name,
                'description' => $salesPage->description,
                'features' => $salesPage->features_input,
                'audience' => $salesPage->audience,
                'price' => $salesPage->price,
                'unique_selling_points' => $salesPage->unique_selling_points,
            ], (array) $salesPage->content, $data['section'], $request->user()->id, $data['model'] ?? null);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $merged = (array) $salesPage->content;
        $merged[$data['section']] = $newContent[$data['section']] ?? ($merged[$data['section']] ?? null);
        $salesPage->update([
            'content' => $merged,
            'export_html' => $this->renderHtml($salesPage->name, $salesPage->price, $salesPage->audience, $merged, $salesPage->template ?: 'aurora'),
        ]);

        return response()->json($salesPage->fresh());
    }

    public function show(Request $request, SalesPage $salesPage): JsonResponse
    {
        abort_if($salesPage->user_id !== $request->user()->id, 403);
        return response()->json($salesPage);
    }

    public function update(Request $request, SalesPage $salesPage): JsonResponse
    {
        abort_if($salesPage->user_id !== $request->user()->id, 403);
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'content' => ['sometimes', 'array'],
            'template' => ['sometimes', 'in:aurora,slate,minimal'],
        ]);

        $salesPage->update($data);
        $salesPage->update([
            'export_html' => $this->renderHtml($salesPage->name, $salesPage->price, $salesPage->audience, (array) $salesPage->content, $salesPage->template ?: 'aurora'),
        ]);

        return response()->json($salesPage->fresh());
    }

    public function destroy(Request $request, SalesPage $salesPage): JsonResponse
    {
        abort_if($salesPage->user_id !== $request->user()->id, 403);
        $salesPage->delete();
        return response()->json(['message' => 'Sales page deleted']);
    }

    private function renderHtml(string $name, string $price, string $audience, array $c, string $template): string
    {
        $theme = match ($template) {
            'slate' => [
                'bg' => '#020617',
                'fg' => '#f1f5f9',
                'badge' => '#7dd3fc',
                'sub' => '#cbd5e1',
                'body' => '#cbd5e1',
                'card' => '#1e293bcc',
                'proofBg' => '#1e293bcc',
                'proofBorder' => '#475569',
                'ctaBg' => '#38bdf8',
                'ctaFg' => '#0f172a',
                'ctaSub' => '#cbd5e1',
            ],
            'minimal' => [
                'bg' => '#ffffff',
                'fg' => '#0f172a',
                'badge' => '#64748b',
                'sub' => '#334155',
                'body' => '#334155',
                'card' => '#f1f5f9',
                'proofBg' => '#f8fafc',
                'proofBorder' => '#e2e8f0',
                'ctaBg' => '#0f172a',
                'ctaFg' => '#ffffff',
                'ctaSub' => '#64748b',
            ],
            default => [
                'bg' => 'linear-gradient(135deg,#020617 0%,#1e1b4b 45%,#3b0764 100%)',
                'fg' => '#ffffff',
                'badge' => '#c7d2fe',
                'sub' => '#e0e7ff',
                'body' => '#c7d2fe',
                'card' => '#ffffff1a',
                'proofBg' => '#ffffff1a',
                'proofBorder' => '#ffffff33',
                'ctaBg' => '#ffffff',
                'ctaFg' => '#0f172a',
                'ctaSub' => '#c7d2fe',
            ],
        };

        $benefits = (array) ($c['benefits'] ?? []);
        $benefitCards = implode('', array_map(
            fn ($b) => '<div style="border-radius:12px;padding:16px;background:'.$theme['card'].';">'.htmlspecialchars((string) $b).'</div>',
            $benefits
        ));

        return '<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>'.htmlspecialchars($name).' - Sales Page</title>
</head>
<body style="margin:0;font-family:Inter,Arial,sans-serif;background:#f1f5f9;">
  <main style="max-width:1040px;margin:24px auto;padding:0 16px;">
    <section style="overflow:hidden;border-radius:24px;box-shadow:0 20px 40px rgba(2,6,23,.15);background:'.$theme['bg'].';color:'.$theme['fg'].';">
      <div style="max-width:896px;margin:0 auto;padding:64px 24px;">
        <p style="margin:0;letter-spacing:.2em;font-size:12px;text-transform:uppercase;color:'.$theme['badge'].';">FOR '.strtoupper(htmlspecialchars($audience)).'</p>
        <h1 style="margin:12px 0 0;font-size:56px;line-height:1.1;font-weight:800;">'.htmlspecialchars((string) ($c['headline'] ?? '')).'</h1>
        <p style="margin:12px 0 0;font-size:32px;color:'.$theme['sub'].';">'.htmlspecialchars((string) ($c['subheadline'] ?? '')).'</p>
        <p style="margin:20px 0 0;max-width:768px;line-height:1.65;color:'.$theme['body'].';">'.htmlspecialchars((string) ($c['description'] ?? '')).'</p>

        <div style="margin-top:32px;display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px;">
          '.$benefitCards.'
        </div>

        <div style="margin-top:32px;border:1px solid '.$theme['proofBorder'].';border-radius:16px;padding:20px;background:'.$theme['proofBg'].';">
          <p style="margin:0;font-size:14px;">'.htmlspecialchars((string) ($c['social_proof'] ?? '')).'</p>
        </div>

        <div style="margin-top:32px;display:flex;align-items:center;gap:16px;flex-wrap:wrap;">
          <button style="border:0;border-radius:12px;padding:12px 24px;font-weight:700;background:'.$theme['ctaBg'].';color:'.$theme['ctaFg'].';">'.htmlspecialchars((string) ($c['cta_text'] ?? 'Get Started')).'</button>
          <p style="margin:0;font-size:14px;color:'.$theme['ctaSub'].';">'.htmlspecialchars((string) ($c['cta_subtext'] ?? '')).'</p>
        </div>
      </div>
    </section>
  </main>
</body>
</html>';
    }
}
