# MotorDesk Update

Stand: 2026-08-01

Diese Datei beschreibt den sicheren Update-Pfad fuer den aktuellen Stand. Sie
setzt voraus, dass die Installation bereits laeuft und dass vor jedem Update ein
Backup erstellt wurde.

## Grundregeln

- Vor jedem Update ein DB- und Datenverzeichnis-Backup erstellen.
- Produktiv keine unbekannten lokalen Dateien in `backend/config/` oder
  `backend/data/` ueberschreiben.
- Bei Docker zuerst den Web-Container neu bauen; DB-Volumes nur bei bewussten
  Migrationen anfassen.
- SQL aus `backend/upstall/` kontrolliert und in der richtigen Datenbank
  ausfuehren.

## Docker-Update

```bash
./scripts/docker.sh backup
git pull
npm ci
npm run build
./scripts/docker.sh destroy-web
./scripts/docker.sh up-web
./scripts/docker.sh status
```

Wenn SQL-Erweiterungen hinzugekommen sind:

```bash
./scripts/docker.sh upstall
```

Hinweis: `destroy-web` loescht nur Web-Container und lokales Web-Image. Das
PostgreSQL-Volume bleibt erhalten.

## Bare-Metal-Update

```bash
git pull
npm ci
npm run build
composer install --working-dir=backend --no-dev --optimize-autoloader
```

Danach je nach Server-Setup:

```bash
sudo systemctl restart apache2
sudo systemctl restart oserp-sse
```

Die Service-Namen koennen in bestehenden Installationen noch
OpensourceERP/oserp enthalten. Vor einer Umbenennung muessen systemd-Units,
Apache-vHosts und Cronjobs einzeln geprueft werden.

## Migrationskontrolle

Vor SQL-Aenderungen:

```bash
./scripts/docker.sh backup
```

Dann im Testsystem pruefen:

```bash
./scripts/docker.sh upstall
npm run check:api
```

## Rollback

Bei Frontend-/PHP-Problemen:

```bash
git checkout <letzter-funktionierender-stand>
npm ci
npm run build
./scripts/docker.sh destroy-web
./scripts/docker.sh up-web
```

Bei Datenbankproblemen muss ein vorheriges Backup zurueckgespielt werden. Siehe
`docs/BACKUP_RESTORE.md`.
