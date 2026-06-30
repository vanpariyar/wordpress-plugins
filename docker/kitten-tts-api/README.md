# KittenTTS API Service

Self-hosted [KittenTTS](https://github.com/KittenML/KittenTTS) API with API-key authentication and pay-per-request usage tracking. Designed to pair with the **post-to-speech** WordPress plugin in API mode.

## Features

- `POST /v1/generate` — synthesize speech, returns `audio/wav`
- `GET /v1/usage` — request count and billed total per API key
- `GET /health` / `GET /v1/health` — health check
- API key auth via `X-API-Key` header
- SQLite usage ledger with per-request cost
- Optional monthly quota per key (`MONTHLY_QUOTA`)
- Response headers: `X-Request-Cost`, `X-Total-Requests`, `X-Total-Cost`

## Quick start

```bash
cd docker/kitten-tts-api
cp .env.example .env
docker compose up --build
```

The API listens on [http://localhost:8080](http://localhost:8080).

## Generate audio

```bash
curl -X POST http://localhost:8080/v1/generate \
  -H "Content-Type: application/json" \
  -H "X-API-Key: dev-secret-key" \
  -d '{"text":"Hello from KittenTTS API.","voice":"Bella","speed":1.0}' \
  --output speech.wav
```

## Check usage (billing)

```bash
curl http://localhost:8080/v1/usage \
  -H "X-API-Key: dev-secret-key"
```

Example response:

```json
{
  "request_count": 12,
  "monthly_request_count": 12,
  "total_cost": 0.12,
  "total_characters": 842,
  "price_per_request": 0.01,
  "monthly_quota": 0,
  "remaining_quota": null
}
```

## Pay-per-request model

Each successful `/v1/generate` call:

1. Validates the API key
2. Generates audio with KittenTTS
3. Records a usage event with `PRICE_PER_REQUEST`
4. Returns billing headers on the WAV response

Use different API keys per customer (`API_KEYS=key:customer-name`) and bill using `GET /v1/usage`.

## WordPress plugin setup

1. Run this Docker service (or deploy to your cloud)
2. In WordPress go to **Settings → Post to Speech**
3. Set **Mode** to **API (external KittenTTS service)**
4. Set **API base URL** to `http://localhost:8080/` (or your public URL)
5. Set **API key** to one of the keys from `.env`
6. Set **Price per request** for editor display (optional)

The WordPress site calls your API server-side — the API key is never sent to the browser.

## Environment variables

| Variable | Default | Description |
|----------|---------|-------------|
| `KITTEN_MODEL` | `KittenML/kitten-tts-nano-0.8-int8` | Hugging Face model ID |
| `API_KEYS` | `dev-secret-key:development` | Comma-separated `key:label` pairs |
| `PRICE_PER_REQUEST` | `0.01` | Cost recorded per generation |
| `MONTHLY_QUOTA` | `0` | Max requests per key per month (`0` = unlimited) |
| `USAGE_DB_PATH` | `/data/usage.db` | SQLite database path |
| `CORS_ORIGINS` | `*` | Allowed CORS origins |

## Production notes

- Put the service behind HTTPS (nginx, Caddy, or a cloud load balancer)
- Use strong random API keys
- Persist the `/data` volume for usage history
- First request downloads the model and may take longer
- Scale horizontally only after sharing model cache or pre-baking the image

## API reference

### `POST /v1/generate`

**Headers:** `X-API-Key`, `Content-Type: application/json`, `Accept: audio/wav`

**Body:**

```json
{
  "text": "Hello world",
  "voice": "Bella",
  "speed": 1.0
}
```

**Voices:** Bella, Jasper, Luna, Bruno, Rosie, Hugo, Kiki, Leo

### `GET /v1/usage`

**Headers:** `X-API-Key`

Returns cumulative usage for the authenticated key.
