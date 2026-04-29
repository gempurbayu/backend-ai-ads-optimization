<?php

namespace App\Docs;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'AI Ads Optimization Advisor API',
    description: 'REST API for authentication, campaign management, metrics, and AI analysis history.'
)]
#[OA\Server(
    url: 'http://127.0.0.1:8000',
    description: 'Local development server'
)]
#[OA\SecurityScheme(
    securityScheme: 'sanctum',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'Token',
    description: 'Use Sanctum bearer token from /api/auth/login'
)]
#[OA\PathItem(path: '/api')]
class OpenApi
{
}
