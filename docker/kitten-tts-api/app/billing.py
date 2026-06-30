from __future__ import annotations

import sqlite3
from contextlib import contextmanager
from datetime import datetime, timezone
from pathlib import Path
from typing import Any

from app.config import settings


class UsageStore:
    def __init__(self, db_path: str) -> None:
        self.db_path = Path(db_path)
        self.db_path.parent.mkdir(parents=True, exist_ok=True)
        self._init_db()

    def _init_db(self) -> None:
        with self._connect() as conn:
            conn.execute(
                """
                CREATE TABLE IF NOT EXISTS usage_events (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    api_key_hash TEXT NOT NULL,
                    api_key_label TEXT NOT NULL,
                    created_at TEXT NOT NULL,
                    text_length INTEGER NOT NULL,
                    voice TEXT NOT NULL,
                    cost REAL NOT NULL
                )
                """
            )
            conn.commit()

    @contextmanager
    def _connect(self):
        conn = sqlite3.connect(self.db_path)
        conn.row_factory = sqlite3.Row
        try:
            yield conn
        finally:
            conn.close()

    def record_request(
        self,
        api_key_hash: str,
        api_key_label: str,
        text_length: int,
        voice: str,
        cost: float,
    ) -> None:
        with self._connect() as conn:
            conn.execute(
                """
                INSERT INTO usage_events (api_key_hash, api_key_label, created_at, text_length, voice, cost)
                VALUES (?, ?, ?, ?, ?, ?)
                """,
                (
                    api_key_hash,
                    api_key_label,
                    datetime.now(timezone.utc).isoformat(),
                    text_length,
                    voice,
                    cost,
                ),
            )
            conn.commit()

    def get_usage(self, api_key_hash: str) -> dict[str, Any]:
        with self._connect() as conn:
            row = conn.execute(
                """
                SELECT
                    COUNT(*) AS request_count,
                    COALESCE(SUM(cost), 0) AS total_cost,
                    COALESCE(SUM(text_length), 0) AS total_characters
                FROM usage_events
                WHERE api_key_hash = ?
                """,
                (api_key_hash,),
            ).fetchone()

        request_count = int(row["request_count"])
        monthly_count = self._monthly_count(api_key_hash)

        return {
            "request_count": request_count,
            "monthly_request_count": monthly_count,
            "total_cost": round(float(row["total_cost"]), 4),
            "total_characters": int(row["total_characters"]),
            "price_per_request": settings.price_per_request,
            "monthly_quota": settings.monthly_quota,
            "remaining_quota": max(settings.monthly_quota - monthly_count, 0)
            if settings.monthly_quota > 0
            else None,
        }

    def _monthly_count(self, api_key_hash: str) -> int:
        month_prefix = datetime.now(timezone.utc).strftime("%Y-%m")
        with self._connect() as conn:
            row = conn.execute(
                """
                SELECT COUNT(*) AS monthly_count
                FROM usage_events
                WHERE api_key_hash = ? AND created_at LIKE ?
                """,
                (api_key_hash, f"{month_prefix}%"),
            ).fetchone()
        return int(row["monthly_count"])


usage_store = UsageStore(settings.usage_db_path)
