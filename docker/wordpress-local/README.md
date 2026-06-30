# Local WordPress for plugin development

```bash
docker compose up -d
```

- **WordPress:** http://localhost:8888
- **Admin:** `admin` / `admin123`

The `post-to-speech` plugin is mounted from `../../plugins/post-to-speech`.

## With KittenTTS API

Start the API service first:

```bash
cd ../kitten-tts-api
docker compose up -d
```

In WordPress **Settings → Post to Speech**:

- Mode: **API**
- API URL: `http://host.docker.internal:8080/`
- API key: `dev-secret-key`

## Stop

```bash
docker compose down
```
