# MotorDesk Backup und Restore

Stand: 2026-08-01

Backups muessen mindestens die Auth-Datenbank, die Company-Datenbank und die
Dateiablage enthalten. In Docker liegen Daten in benannten Volumes; ohne Docker
liegen sie je nach Installation unter `backend/data`, `backend/log`,
`backend/backups` und lokalen Serverpfaden.

## Docker-Backup

```bash
./scripts/docker.sh backup
```

Das erzeugt gzip-komprimierte Dumps in `backups/`.

Zusaetzlich sollten die Docker-Volumes fuer Dateiablage und Backups gesichert
werden:

- `${STACK_NAME}-app-data`
- `${STACK_NAME}-app-backups`
- `${STACK_NAME}-app-logs`, falls Logs revisionsrelevant sind
- `${STACK_NAME}-ssl-certs`, wenn Let's Encrypt-Zertifikate erhalten bleiben
  sollen

## Manuelles DB-Backup

Im DB-Container:

```bash
docker exec motordesk-db pg_dump -U postgres --clean --if-exists -d motordesk_auth > auth.sql
docker exec motordesk-db pg_dump -U postgres --clean --if-exists -d motordesk_company > company.sql
```

Die Container- und Datenbanknamen koennen abweichen, wenn `STACK_NAME`,
`DB_AUTH_NAME` oder `DB_COMPANY_NAME` in `.env` anders gesetzt sind.

## Restore mit Docker-Helfer

Aus zwei Dumps:

```bash
./scripts/docker.sh reset auth.sql company.sql
```

Aus gzip-komprimierten Dumps:

```bash
./scripts/docker.sh reset auth.sql.gz company.sql.gz
```

Das Kommando loescht den bestehenden Docker-Stack inklusive DB-Volume nach
Bestaetigung und baut ihn aus den Dumps neu auf.

## Dateiablage wiederherstellen

Die DB allein reicht nicht aus, wenn Belege, Kundenakten, Fahrzeugscheinbilder,
Uploads oder Templates in der Dateiablage liegen.

Pruefe vor dem Restore:

- Welche Volumes im Stack existieren: `docker volume ls`
- Welcher `STACK_NAME` aktiv ist
- Ob `backend/templates/` ein eigenes kundenspezifisches Repository oder ein
  lokales Verzeichnis ist
- Ob `backend/config/settings.ini` produktive Secrets enthaelt

## Restore-Reihenfolge

1. Stack stoppen.
2. DB- und Datei-Backups sichern, bevor etwas geloescht wird.
3. Datenbanken aus Dumps wiederherstellen.
4. Dateiablage/Volumes wiederherstellen.
5. `auth.clients` auf die Zielumgebung pruefen.
6. Web-Container neu bauen/starten.
7. Login, Kunden, Fahrzeuge, Belege, Uploads und PDF-Ausgabe testen.

## Was nie ungeprueft ins Backup gehoert

- Alte `settings.iniBAK` Dateien mit echten Zugangsdaten
- Lokale API-Schluessel aus Entwicklerumgebungen
- Demo-Scans mit personenbezogenen Daten, sofern sie nicht ausdruecklich dafuer
  freigegeben sind
- Temporaere OCR-/Upload-Dateien aus `backend/tmp/`
