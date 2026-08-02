# MotorDesk Docker Setup

Diese Datei beschreibt die Docker-Dateien unter `docker/`. Fuer den schnellen
Einstieg vom Projektwurzelverzeichnis siehe auch `docs/INSTALLATION.md`.

## Ueberblick

Der Stack besteht aus:

| Service | Aufgabe |
| --- | --- |
| `web` | Apache, PHP 8.3 FPM, gebautes Vue-Frontend, PHP-API, optional SSL |
| `db` | PostgreSQL 16 |
| `certbot` | optionaler Zertifikatserneuerer im Profil `ssl` |

Container-, Volume- und Netzwerk-Namen werden ueber `STACK_NAME` gesetzt.
Neue Installationen nutzen standardmaessig `motordesk`.

## Empfohlener Einstieg

Vom Projektwurzelverzeichnis:

```bash
cp .env.example .env
nano .env
./scripts/docker.sh up-all
```

Das Skript liest zuerst `.env` im Projektwurzelverzeichnis. Der alte Pfad
`docker/.env` bleibt als Fallback erhalten:

```bash
cp docker/.env.example docker/.env
```

## Direkter Docker-Compose-Aufruf

Vom Projektwurzelverzeichnis:

```bash
cp .env.example .env
docker compose build --no-cache
docker compose up -d --no-build
```

Oder mit der Compose-Datei unter `docker/`:

```bash
docker compose --env-file .env -f docker/docker-compose.yml build --no-cache
docker compose --env-file .env -f docker/docker-compose.yml up -d --no-build
```

## Datenbankstatus

Der normale Stack startet PostgreSQL und Web. Eine fachlich nutzbare Datenbank
wird aktuell nicht vollautomatisch aus einer leeren Installation erzeugt.

Empfohlen ist das Einspielen vorhandener Dumps:

```bash
./scripts/docker.sh reset auth.sql company.sql
```

Auch gzip-komprimierte Dumps werden akzeptiert:

```bash
./scripts/docker.sh reset auth.sql.gz company.sql.gz
```

Das Kommando:

- setzt den Stack nach Bestaetigung neu auf
- erstellt Auth- und Company-Datenbank
- spielt beide Dumps ein
- patcht `auth.clients` auf Host `db`
- baut den Web-Container neu

## Schema-Erweiterungen

Die SQL-Dateien liegen unter `backend/upstall/`.

```bash
./scripts/docker.sh upstall
```

Dieses Kommando sucht `auth_schema.sql` und `company_schema.sql` unter
`backend/upstall/` und spielt sie in die konfigurierten Datenbanken ein.

Veraltete Pfade wie `backend/db/auth_schema.sql` und
`backend/db/company_schema.sql` existieren nicht.

## Demo-Overlay

Das Demo-Overlay liegt in `docker/docker-compose.demo.yml` und nutzt
`docker/db/init/`.

In `.env` muessen absolute Pfade gesetzt werden:

```bash
DEMO_AUTH_DUMP=/absolute/path/to/auth.sql
DEMO_COMPANY_DUMP=/absolute/path/to/company.sql
DEMO_SCAN_DATA=/absolute/path/to/demo-scan-data
```

Start:

```bash
./scripts/docker.sh demo-up
```

Die Demo-Datenbank liegt im tmpfs. Ein DB-Restart spielt den Seed neu ein:

```bash
./scripts/docker.sh demo-restart-db
```

## Wichtige Variablen

| Variable | Beschreibung | Beispiel |
| --- | --- | --- |
| `STACK_NAME` | Praefix fuer Container, Volumes und Netzwerk | `motordesk` |
| `DOMAIN` | Domain ohne `https://` | `erp.example.com` |
| `CERTBOT_EMAIL` | E-Mail fuer Let's Encrypt | `admin@example.com` |
| `POSTGRES_USER` | PostgreSQL-User, wegen Schema-Owner aktuell `postgres` | `postgres` |
| `POSTGRES_PASSWORD` | Starkes Datenbankpasswort | `CHANGE_ME_STRONG_PASSWORD` |
| `DB_AUTH_NAME` | Auth-Datenbank | `motordesk_auth` |
| `DB_COMPANY_NAME` | Company-Datenbank | `motordesk_company` |
| `WEB_HTTP_PORT` | HTTP-Port auf dem Host | `8080` |
| `WEB_HTTPS_PORT` | HTTPS-Port auf dem Host | `8443` |

## Kommandos

```bash
./scripts/docker.sh help
./scripts/docker.sh up-db
./scripts/docker.sh up-web
./scripts/docker.sh up-all
./scripts/docker.sh status
./scripts/docker.sh logs web
./scripts/docker.sh shell web
./scripts/docker.sh backup
```

Destruktive Kommandos fragen interaktiv nach:

```bash
./scripts/docker.sh destroy-web
./scripts/docker.sh destroy-db
./scripts/docker.sh destroy-all
```

## SSL

Fuer Let's Encrypt muessen `DOMAIN` und `CERTBOT_EMAIL` in `.env` auf echte
Werte gesetzt werden. Port 80 und 443 muessen vom Internet erreichbar sein.

Der Web-Container prueft beim Start, ob ein Zertifikat existiert, und richtet SSL
ein, wenn die Konfiguration nicht mehr auf den Beispielwerten steht.

## Backup und Restore

Backup:

```bash
./scripts/docker.sh backup
```

Restore:

```bash
./scripts/docker.sh reset auth.sql.gz company.sql.gz
```

Weitere Details stehen in `docs/BACKUP_RESTORE.md`.

## Troubleshooting

Status:

```bash
./scripts/docker.sh status
```

Logs:

```bash
./scripts/docker.sh logs
./scripts/docker.sh logs web
./scripts/docker.sh logs db
```

Shell:

```bash
./scripts/docker.sh shell web
./scripts/docker.sh shell db
```

Port-Konflikte:

```bash
# Alternative Ports in .env setzen:
WEB_HTTP_PORT=9080
WEB_HTTPS_PORT=9443
DB_EXTERNAL_PORT=5434
```
