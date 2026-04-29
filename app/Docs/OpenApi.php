<?php

namespace App\Docs;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'AI Ads Optimization Advisor API',
    description: 'REST API for authentication, campaign management, metrics, and AI analysis history.'
)]
#[OA\Server(url: 'http://127.0.0.1:8000', description: 'Local development server')]
#[OA\SecurityScheme(
    securityScheme: 'sanctum',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'Token'
)]

#[OA\Post(
    path: '/api/auth/register',
    tags: ['Auth'],
    summary: 'Register user',
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['name', 'email', 'password'],
            properties: [
                new OA\Property(property: 'name', type: 'string', example: 'Jane Marketer'),
                new OA\Property(property: 'email', type: 'string', format: 'email', example: 'jane@company.com'),
                new OA\Property(property: 'password', type: 'string', format: 'password', example: 'secret123'),
            ]
        )
    ),
    responses: [new OA\Response(response: 201, description: 'Register success')]
)]
#[OA\Post(
    path: '/api/auth/login',
    tags: ['Auth'],
    summary: 'Login user',
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['email', 'password'],
            properties: [
                new OA\Property(property: 'email', type: 'string', format: 'email'),
                new OA\Property(property: 'password', type: 'string', format: 'password'),
            ]
        )
    ),
    responses: [new OA\Response(response: 200, description: 'Login success')]
)]
#[OA\Get(path: '/api/auth/me', tags: ['Auth'], summary: 'Get current user', security: [['sanctum' => []]], responses: [new OA\Response(response: 200, description: 'Current user')])]
#[OA\Post(path: '/api/auth/logout', tags: ['Auth'], summary: 'Logout user', security: [['sanctum' => []]], responses: [new OA\Response(response: 200, description: 'Logout success')])]

#[OA\Get(path: '/api/campaigns', tags: ['Campaigns'], summary: 'List campaigns', security: [['sanctum' => []]], responses: [new OA\Response(response: 200, description: 'Campaign list')])]
#[OA\Post(
    path: '/api/campaigns',
    tags: ['Campaigns'],
    summary: 'Create campaign',
    security: [['sanctum' => []]],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['name', 'platform', 'impressions', 'clicks', 'conversions', 'cost', 'revenue', 'date_start', 'date_end'],
            properties: [
                new OA\Property(property: 'name', type: 'string', example: 'Q2 Retargeting'),
                new OA\Property(property: 'platform', type: 'string', enum: ['Facebook', 'Google', 'TikTok']),
                new OA\Property(property: 'impressions', type: 'integer', example: 120000),
                new OA\Property(property: 'clicks', type: 'integer', example: 2800),
                new OA\Property(property: 'conversions', type: 'integer', example: 140),
                new OA\Property(property: 'cost', type: 'number', format: 'float', example: 18000000),
                new OA\Property(property: 'revenue', type: 'number', format: 'float', example: 42000000),
                new OA\Property(property: 'date_start', type: 'string', format: 'date', example: '2026-04-01'),
                new OA\Property(property: 'date_end', type: 'string', format: 'date', example: '2026-04-30'),
            ]
        )
    ),
    responses: [new OA\Response(response: 201, description: 'Campaign created')]
)]
#[OA\Post(path: '/api/campaigns/bulk', tags: ['Campaigns'], summary: 'Bulk create campaigns', security: [['sanctum' => []]], responses: [new OA\Response(response: 201, description: 'Bulk upload success')])]
#[OA\Get(path: '/api/campaigns/{campaign}', tags: ['Campaigns'], summary: 'Show campaign', security: [['sanctum' => []]], parameters: [new OA\Parameter(name: 'campaign', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Campaign detail')])]
#[OA\Put(path: '/api/campaigns/{campaign}', tags: ['Campaigns'], summary: 'Update campaign', security: [['sanctum' => []]], parameters: [new OA\Parameter(name: 'campaign', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Campaign updated')])]
#[OA\Delete(path: '/api/campaigns/{campaign}', tags: ['Campaigns'], summary: 'Delete campaign', security: [['sanctum' => []]], parameters: [new OA\Parameter(name: 'campaign', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Campaign deleted')])]

#[OA\Get(path: '/api/analyses', tags: ['Analyses'], summary: 'List analyses history', security: [['sanctum' => []]], responses: [new OA\Response(response: 200, description: 'Analysis list')])]
#[OA\Post(path: '/api/analyses', tags: ['Analyses'], summary: 'Generate analysis', security: [['sanctum' => []]], responses: [new OA\Response(response: 201, description: 'Analysis created')])]
#[OA\Get(path: '/api/analyses/{analysis}', tags: ['Analyses'], summary: 'Show analysis detail', security: [['sanctum' => []]], parameters: [new OA\Parameter(name: 'analysis', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Analysis detail')])]
#[OA\Delete(path: '/api/analyses/{analysis}', tags: ['Analyses'], summary: 'Delete analysis', security: [['sanctum' => []]], parameters: [new OA\Parameter(name: 'analysis', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Analysis deleted')])]
class OpenApi
{
}
