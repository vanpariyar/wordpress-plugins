from pydantic_settings import BaseSettings, SettingsConfigDict


class Settings(BaseSettings):
    model_config = SettingsConfigDict(env_file=".env", extra="ignore")

    app_name: str = "KittenTTS API"
    host: str = "0.0.0.0"
    port: int = 8080
    kitten_model: str = "KittenML/kitten-tts-nano-0.8-int8"
    api_keys: str = "dev-secret-key:development"
    price_per_request: float = 0.01
    monthly_quota: int = 0
    usage_db_path: str = "/data/usage.db"
    cors_origins: str = "*"


settings = Settings()
