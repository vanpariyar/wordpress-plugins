# Local WordPress for plugin development

```bash
docker compose up -d
```

- **WordPress:** http://localhost:8888
- **Admin:** `admin` / `admin123`
- **Debug log:** `docker/wordpress-local/wp-content/debug.log` (on your machine)
- **Fatal log:** `docker/wordpress-local/wp-content/pts-fatal.log`

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

## Troubleshooting

If you see **500** or "There has been a critical error on this website":

1. **Recreate containers** after changing `docker-compose.yml` (required for volume mounts):

```bash
cd docker/wordpress-local
docker compose down
docker compose up -d --force-recreate
```

2. **Reload WordPress** (fixes corrupted `wp-config.php`):

```bash
cd docker/wordpress-local
chmod +x reload.sh
./reload.sh
```

2. **Full reset** if reload did not help:

```bash
./reset.sh
```

3. Check logs:

```bash
cat wp-content/debug.log
cat wp-content/pts-fatal.log
cat ../../plugins/post-to-speech/local-fatal.log
```

4. Ensure block assets exist:

```bash
cd ../../plugins/post-to-speech
npm install && npm run build
```
