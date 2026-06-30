from __future__ import annotations

import hashlib
from dataclasses import dataclass

from fastapi import Header, HTTPException, status

from app.billing import usage_store
from app.config import settings


@dataclass(frozen=True)
class ApiKeyContext:
    raw_key: str
    label: str
    key_hash: str


def _parse_api_keys(raw: str) -> dict[str, str]:
    keys: dict[str, str] = {}
    for entry in raw.split(","):
        entry = entry.strip()
        if not entry:
            continue
        if ":" in entry:
            key, label = entry.split(":", 1)
            keys[key.strip()] = label.strip() or "default"
        else:
            keys[entry] = "default"
    return keys


API_KEYS = _parse_api_keys(settings.api_keys)


def hash_api_key(api_key: str) -> str:
    return hashlib.sha256(api_key.encode("utf-8")).hexdigest()


def require_api_key(x_api_key: str | None = Header(default=None)) -> ApiKeyContext:
    if not x_api_key:
        raise HTTPException(
            status_code=status.HTTP_401_UNAUTHORIZED,
            detail="Missing X-API-Key header.",
        )

    label = API_KEYS.get(x_api_key)
    if not label:
        raise HTTPException(
            status_code=status.HTTP_401_UNAUTHORIZED,
            detail="Invalid API key.",
        )

    key_hash = hash_api_key(x_api_key)

    if settings.monthly_quota > 0:
        usage = usage_store.get_usage(key_hash)
        if usage["monthly_request_count"] >= settings.monthly_quota:
            raise HTTPException(
                status_code=status.HTTP_402_PAYMENT_REQUIRED,
                detail="Monthly request quota exceeded.",
            )

    return ApiKeyContext(raw_key=x_api_key, label=label, key_hash=key_hash)
