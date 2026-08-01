# MotorDesk Architecture Notes

Stand: 2026-08-01

Diese Datei ist der Einstiegspunkt fuer die technische Zielarchitektur von
MotorDesk. Sie ergaenzt `docs/MOTORDESK_AUDIT.md` und wird schrittweise
konkreter, sobald die Foundation-Phasen umgesetzt werden.

## Produkt- und Lizenzstatus

MotorDesk basiert aktuell auf dem vorhandenen OpensourceERP/LxCars-Codebestand.
Im Projektwurzelverzeichnis fehlt noch eine Root-Lizenzdatei.

Bis die Ursprungslizenz geklaert ist:

- Keine neue `LICENSE` mit geratenem Inhalt anlegen.
- Keine Copyright-/Lizenztexte entfernen.
- kivitendo-Kompatibilitaet in Doku und Codehinweisen erhalten.
- `LxCars` als existierenden Modulnamen nicht pauschal ersetzen.

Sobald die Lizenz geklaert ist, sollen ergaenzt werden:

- `LICENSE`
- optional `NOTICE`
- ein kurzer Lizenzabschnitt in `README.md`

## Laufzeitaufbau

MotorDesk besteht aus:

- Vue 3/Vuetify/Vite Frontend unter `src/`
- PHP-API unter `backend/api/`
- PostgreSQL-Datenbanken fuer Auth- und Company-Daten
- kivitendo-kompatiblen Basisschemata unter `backend/upstall/skr03` und
  `backend/upstall/skr04`
- MotorDesk/CRM/LxCars-Erweiterungen unter `backend/upstall/crm` und
  `backend/upstall/lxcars`
- optionalem SSE-Server unter `backend/sse`
- optionalen lokalen Diensten wie Whisper unter `backend/whisper`

## Konfigurationsprinzip

Installation und Betrieb sollen ueber Root-Dateien auffindbar sein:

- `.env.example`
- `docker-compose.yml`
- `docs/INSTALLATION.md`
- `docs/UPDATE.md`
- `docs/BACKUP_RESTORE.md`

Legacy-Dateien unter `docker/` bleiben erhalten, weil bestehende Skripte und
Installationen darauf zeigen.

## Datenbankprinzip

Die vorhandene Architektur trennt Auth- und Company-Daten. Diese Trennung bleibt
erhalten.

Neue MotorDesk-Tabellen sollen:

- bestehende kivitendo-Tabellen nicht unnoetig veraendern
- ueber eigene Schemata/Tabellen ergaenzen
- Migrationspfade dokumentieren
- bestehende `auth.clients` Mandantenlogik respektieren

## Scanner- und OCR-Prinzip

`fahrzeugschein-scanner.de` bleibt als optionale Integration erlaubt. Fuer kleine
Scanmengen, z.B. 10 Scans, ist das pragmatisch.

MotorDesk soll trotzdem ohne diesen externen Dienst installierbar bleiben:

- Externe OCR nur nach bewusster Konfiguration
- Demo-Provider fuer Tests
- Spaeter lokale OCR als weiterer Provider
- OCR-Ergebnisse als Vorschlaege, nicht als ungepruefte Stammdaten

## Naechste Architekturbausteine

1. Reproduzierbare Installation und Backups
2. PHP-Healthcheck und Smoke-Tests
3. MotorDesk Theme- und Token-System
4. Sichere Firmenbranding-Erweiterung
5. Application Shell neben bestehender Navigation
6. Fahrzeuguebersicht als Referenzseite
7. Generische Dokumentenbasis
