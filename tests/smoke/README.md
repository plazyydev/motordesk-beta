# MotorDesk Smoke Tests

Stand: 2026-08-01

Diese Datei beschreibt die erste Smoke-Test-Schicht fuer Phase B. Noch werden
keine Browser-Testabhaengigkeiten eingefuehrt; zuerst muessen Docker-Seed,
Testdaten und PHP-Healthcheck reproduzierbar sein.

## Ziel

Smoke-Tests sollen schnell zeigen, ob eine Installation grundsaetzlich benutzbar
ist. Sie ersetzen keine fachlichen Tests und keine Migrationspruefung.

## Geplante Reihenfolge

1. Build laeuft: `npm run build`
2. PHP-API-Healthcheck laeuft: `npm run check:api`
3. Docker-Stack startet mit Testdaten
4. Login ist moeglich
5. Kundenliste laedt
6. Fahrzeugliste laedt
7. Fahrzeugdetail laedt
8. Auftrag kann geoeffnet werden
9. Rechnungsansicht kann geoeffnet werden
10. Upload-Endpunkt nimmt eine kleine Testdatei an und liefert sie berechtigt aus

## Aktuelle Kommandos

Lokal:

```bash
npm run check
```

CI oder vollstaendige PHP-Umgebung:

```bash
npm run check:ci
```

`npm run check` ueberspringt den API-Healthcheck, wenn PHP lokal fehlt.
`npm run check:ci` schlaegt in diesem Fall fehl.

## Noch offen

- Testdaten-Dump fuer Auth- und Company-Datenbank festlegen
- Playwright oder vergleichbares E2E-Tool erst nach stabilem Docker-Seed
  einfuehren
- Upload-Testdateien ohne personenbezogene Daten bereitstellen
- Berechtigungsrollen fuer Smoke-Tests dokumentieren
