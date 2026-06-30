from __future__ import annotations

from fastapi import Depends, FastAPI, HTTPException, Response, status
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel, Field

from app.auth import ApiKeyContext, require_api_key
from app.billing import usage_store
from app.config import settings
from app.tts import VOICES, generate_wav_bytes

app = FastAPI(title=settings.app_name, version="1.0.0")

app.add_middleware(
    CORSMiddleware,
    allow_origins=[origin.strip() for origin in settings.cors_origins.split(",") if origin.strip()],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)


class GenerateRequest(BaseModel):
    text: str = Field(min_length=1, max_length=5000)
    voice: str = Field(default="Bella")
    speed: float = Field(default=1.0, ge=0.5, le=2.0)


@app.get("/health")
def health() -> dict[str, str]:
    return {"status": "ok", "service": settings.app_name}


@app.get("/v1/health")
def health_v1() -> dict[str, str]:
    return health()


@app.get("/v1/usage")
def usage(api_key: ApiKeyContext = Depends(require_api_key)) -> dict:
    return usage_store.get_usage(api_key.key_hash)


@app.post("/v1/generate")
def generate(
    payload: GenerateRequest,
    api_key: ApiKeyContext = Depends(require_api_key),
) -> Response:
    if payload.voice not in VOICES:
        raise HTTPException(
            status_code=status.HTTP_400_BAD_REQUEST,
            detail=f"Invalid voice. Choose from: {', '.join(VOICES)}",
        )

    try:
        wav_bytes = generate_wav_bytes(
            payload.text,
            payload.voice,
            payload.speed,
        )
    except Exception as exc:  # noqa: BLE001
        raise HTTPException(
            status_code=status.HTTP_500_INTERNAL_SERVER_ERROR,
            detail=str(exc),
        ) from exc

    cost = settings.price_per_request
    usage_store.record_request(
        api_key_hash=api_key.key_hash,
        api_key_label=api_key.label,
        text_length=len(payload.text),
        voice=payload.voice,
        cost=cost,
    )

    usage = usage_store.get_usage(api_key.key_hash)

    return Response(
        content=wav_bytes,
        media_type="audio/wav",
        headers={
            "X-Price-Per-Request": str(cost),
            "X-Request-Cost": str(cost),
            "X-Total-Requests": str(usage["request_count"]),
            "X-Total-Cost": str(usage["total_cost"]),
            "X-Monthly-Requests": str(usage["monthly_request_count"]),
        },
    )
