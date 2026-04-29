# AI Ads Optimization Advisor API (Laravel)

Backend service for campaign management, metrics, and AI analysis history.

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

## Sample Campaign Payload
```json
{
  "name": "Q2 Retargeting",
  "platform": "Facebook",
  "impressions": 120000,
  "clicks": 2800,
  "conversions": 140,
  "cost": 18000000,
  "revenue": 42000000,
  "date_start": "2026-04-01",
  "date_end": "2026-04-30"
}
```

## Swagger
Generate docs:
```bash
php artisan l5-swagger:generate
```

Default UI:
- `/api/documentation`

> Note: Controllers are implemented and ready. Add OpenAPI annotations on controllers for richer schema output if needed.
