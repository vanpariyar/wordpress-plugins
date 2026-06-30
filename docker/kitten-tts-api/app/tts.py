from __future__ import annotations

import io
from functools import lru_cache

import soundfile as sf
from kittentts import KittenTTS

from app.config import settings

VOICES = ["Bella", "Jasper", "Luna", "Bruno", "Rosie", "Hugo", "Kiki", "Leo"]


@lru_cache(maxsize=1)
def get_model() -> KittenTTS:
    return KittenTTS(settings.kitten_model)


def generate_wav_bytes(text: str, voice: str, speed: float) -> bytes:
    if voice not in VOICES:
        raise ValueError(f"Unsupported voice '{voice}'.")

    model = get_model()
    audio = model.generate(text, voice=voice, speed=speed, clean_text=True)
    buffer = io.BytesIO()
    sf.write(buffer, audio, 24000, format="WAV")
    return buffer.getvalue()
