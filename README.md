# AI Ads Optimization Advisor API (Laravel)

Backend service for campaign management, metrics, AI analysis generation, and per-user LLM configuration.

## Stack
- Laravel 13
- Sanctum token auth
- PostgreSQL (Supabase)
- L5-Swagger

## Setup
```bash
composer install
cp .env.example .env
php artisan key:generate
```

Set DB config in `.env` (Supabase pooler):
```env
DB_CONNECTION=pgsql
DB_HOST=aws-1-ap-southeast-1.pooler.supabase.com
DB_PORT=6543
DB_DATABASE=postgres
DB_USERNAME=postgres.sibegnubsoemgggykdcd
DB_PASSWORD=YOUR_REAL_PASSWORD
```

Global fallback LLM config (optional):
```env
LLM_BASE_URL=https://api.openai.com/v1
LLM_API_KEY=sk-xxxxx
LLM_DEFAULT_MODEL=gpt-4o-mini
LLM_TIMEOUT=30
```

Run migrations:
```bash
php artisan migrate
```

Run server:
```bash
php artisan serve
```

## Auth
Use Bearer token from login/register response.

## API Endpoints
### Auth
- `POST /api/auth/register`
- `POST /api/auth/login`
- `GET /api/auth/me` (auth)
- `POST /api/auth/logout` (auth)

### Campaigns (auth)
- `GET /api/campaigns`
- `POST /api/campaigns`
- `POST /api/campaigns/bulk`
- `GET /api/campaigns/{campaign}`
- `PUT /api/campaigns/{campaign}`
- `DELETE /api/campaigns/{campaign}`

### Analyses (auth)
- `GET /api/analyses`
- `POST /api/analyses`
- `GET /api/analyses/{analysis}`
- `DELETE /api/analyses/{analysis}`

`POST /api/analyses` payload:
```json
{
  "campaign_id": 1,
  "focus": "turunkan CPA",
  "model": "gpt-4o-mini"
}
```

### LLM Settings (auth)
- `GET /api/settings/llm`
- `PUT /api/settings/llm`

`PUT /api/settings/llm` payload:
```json
{
  "provider": "openai_compatible",
  "api_key": "sk-xxxxx",
  "base_url": "https://api.openai.com/v1",
  "default_model": "gpt-4o-mini",
  "timeout": 30
}
```

## LLM Resolution Priority
When analysis is generated, backend resolves config in this order:
1. User-specific encrypted settings from `user_llm_settings`
2. Global fallback from `.env` (`LLM_*`)
3. Heuristic fallback when provider call fails

## Swagger
Generate docs:
```bash
php artisan l5-swagger:generate
```

Default UI:
- `/api/documentation`

## Frontend Integration
Frontend (`clean-react-hub`) expects:
```env
VITE_API_BASE_URL=http://127.0.0.1:8000/api
```

Auth flow:
1. Frontend calls `/auth/register` or `/auth/login`
2. Backend returns token + user
3. Frontend stores token in `localStorage` key `ada_token`
4. Protected requests send `Authorization: Bearer <token>`

## CORS
If frontend runs on Vite default port (`5173`), ensure backend CORS allows origin `http://127.0.0.1:5173` (and/or `http://localhost:5173`).
