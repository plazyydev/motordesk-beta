# MotorDesk Installation

Stand: 2026-08-01

Diese Anleitung beschreibt den aktuellen Installationsstand. MotorDesk besteht
aus Vue/Vite im Frontend, PHP-API-Skripten und PostgreSQL-Datenbanken mit
kivitendo-kompatiblem Schema plus MotorDesk/LxCars-Erweiterungen.

## Voraussetzungen

Lokal fuer Entwicklung:

- Node.js 20.19 oder neuer
- npm
- PHP 8.1 oder neuer mit PostgreSQL-Erweiterung
- Composer fuer PHP-Abhaengigkeiten
- PostgreSQL, wenn ohne Docker entwickelt wird

Fuer Docker:

- Docker Engine
- Docker Compose v2

Hinweis fuer Windows/PowerShell: Falls `npm` durch die Execution Policy
blockiert wird, funktioniert meist `npm.cmd`.

## Frontend lokal starten

```bash
npm ci
npm run dev
```

Die Vite-Entwicklungsumgebung laeuft standardmaessig unter
`http://localhost:5173`.

Produktionsbuild:

```bash
npm run build
```

Der API-Healthcheck benoetigt PHP:

```bash
npm run check:api
```

Projektchecks:

```bash
npm run check
```

`npm run check` fuehrt den Build aus und ueberspringt den API-Healthcheck, wenn
PHP lokal nicht im PATH liegt. Fuer CI oder eine vollstaendige PHP-Umgebung:

```bash
npm run check:ci
```

Fuer schnelle manuelle Tests siehe `docs/LOCAL_TESTING.md`.

## Docker: Root-Schnellstart

Ab Phase A gibt es einen Einstieg aus dem Projektwurzelverzeichnis:

```bash
cp .env.example .env
nano .env
docker compose up -d --build
```

Mindestens `POSTGRES_PASSWORD` muss geaendert werden. Fuer einen echten
Serverbetrieb muessen ausserdem `DOMAIN` und `CERTBOT_EMAIL` gesetzt werden.

Der normale Docker-Start erzeugt Container, Volumes und die Laufzeitkonfiguration.
Fuer eine frische Installation ohne vorhandene Dumps kann danach der Bootstrap
ausgefuehrt werden.

```bash
ADMIN_PASSWORD='ein-starkes-passwort' bash scripts/bootstrap-fresh.sh
```

Das Skript erstellt `DB_AUTH_NAME` und `DB_COMPANY_NAME`, laedt das SKR04-Schema
plus CRM/LxCars/ANPR-Erweiterungen und legt den ersten Admin-Benutzer an. Der
Kontenrahmen kann vor dem Start mit `MOTORDESK_CHART=skr03` auf SKR03 geaendert
werden.

## Docker-Helfer

Das zentrale Skript bleibt:

```bash
./scripts/docker.sh help
```

Das Skript liest zuerst `.env` im Projektwurzelverzeichnis. Als Legacy-Fallback
wird weiterhin `docker/.env` akzeptiert.

Haeufige Kommandos:

```bash
./scripts/docker.sh up-db
./scripts/docker.sh up-web
./scripts/docker.sh up-all
./scripts/docker.sh status
./scripts/docker.sh logs web
```

Fresh-Install ohne Dumps:

```bash
ADMIN_PASSWORD='ein-starkes-passwort' bash scripts/bootstrap-fresh.sh
```

## Bestehende Datenbank-Dumps einspielen

Wenn ein Auth- und ein Company-Dump vorhanden sind:

```bash
./scripts/docker.sh reset auth.sql company.sql
```

Das Kommando setzt den Docker-Stack neu auf, erstellt die Datenbanken, spielt
beide Dumps ein, patcht `auth.clients` fuer die Docker-Netzwerkadresse und baut
den Web-Container.

Unterstuetzt werden auch gzip-Dateien:

```bash
./scripts/docker.sh reset auth.sql.gz company.sql.gz
```

## Schema-Erweiterungen

Die vorhandenen SQL-Dateien liegen unter `backend/upstall/`, nicht unter
`backend/db/`.

Das Helferkommando:

```bash
./scripts/docker.sh upstall
```

laedt alle gefundenen `auth_schema.sql` und `company_schema.sql` Dateien aus
`backend/upstall/` in die in `.env` konfigurierten Datenbanken.

## Demo-Overlay

Das Demo-Overlay nutzt `docker/docker-compose.demo.yml` und die Init-Skripte in
`docker/db/init/`. Es erwartet absolute Pfade zu Demo-Dumps:

```bash
DEMO_AUTH_DUMP=/absolute/path/to/auth.sql
DEMO_COMPANY_DUMP=/absolute/path/to/company.sql
DEMO_SCAN_DATA=/absolute/path/to/demo-scan-data
```

Start:

```bash
./scripts/docker.sh demo-up
```

Die Demo-Datenbank liegt im tmpfs und kann durch einen DB-Restart neu eingespielt
werden.

## Bare-Metal-Installation

Die bestehende Bare-Metal-Installation liegt weiterhin unter `install/`.

```bash
sudo ./install/install.sh
```

Diese Route ist deutlich naeher am bestehenden OpensourceERP/kivitendo-Betrieb.
Vor produktiver Nutzung sollten PHP, Composer, PostgreSQL, Apache, SSE-Server,
Templates, Backups und Secrets einzeln geprueft werden.

## Bekannte Einschraenkungen

- Im aktuellen Arbeitsordner fehlt `.git`; Branches und Commits sind deshalb
  lokal nicht moeglich.
- Eine Root-Lizenzdatei fehlt noch und muss vor Veroeffentlichung geklaert
  werden.
- `php`, `composer` und `docker` muessen lokal im PATH vorhanden sein, wenn die
  entsprechenden Checks ausgefuehrt werden sollen.
- `backend/config/settings.iniBAK` muss auf echte Secrets geprueft werden und
  darf nicht versehentlich versioniert werden.
