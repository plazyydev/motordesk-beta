# Prompt zum lokalen Weiterbauen

Diesen Text in eine neue Claude-Code-Sitzung im Repo `motordesk-beta` einfügen.
Vorher: `git fetch origin && git checkout feature/ankauf-kalkulation`.

---

Du arbeitest im Repo `motordesk-beta` auf dem Branch `feature/ankauf-kalkulation`.
Lies zuerst `CLAUDE.md` und `dev/programmierstil-richtlinien.md` und halte dich daran
(Logik in die Datenbank, ein Ajax-Call pro Vorgang, Store als einzige Datenquelle,
Prepared Statements, `@testdata` an jeder API-Funktion, vue-i18n für jeden Text,
echte Umlaute, nie gendern).

## Ziel

Ich will mein eigenes Fahrzeug-Verkaufssystem (Vorbild: Audaris) für meinen privaten
Fuhrpark. Autos kommen und gehen, ich verkaufe sie teils privat, teils gewerblich.
Später kommt eine Werkstatt dazu. Das System soll aus zwei Bereichen bestehen,
die zusammen in einer Oberfläche leben:

- **Verkauf**: Fahrzeuge, Ankauf und Kalkulation, Inserate, Preisschilder, Exposés,
  Termine, Interessenten.
- **Werkstatt**: Aufträge, Mechaniker-Modus, HU-Serienbrief, Mängel (vorhandene
  LxCars-Funktionen, werden schrittweise in die neue Oberfläche überführt).

Alles muss am Handy und am Tablet genauso bedienbar sein wie am Rechner.
Die Oberfläche (Design und Struktur) wird komplett neu gebaut. Die alte
OpenSource-ERP-/LxCars-Oberfläche bleibt nur erreichbar, bis ihre Funktionen
ersetzt sind. Die Backend-Basis (Login, Benutzer, Dateien, Kalender, PostgreSQL)
wird weiterverwendet.

## Schon fertig (siehe `docs/design-previews/`)

- Tabellen und DB-Funktionen in `backend/upstall/lxcars/company_schema.sql`:
  `car_purchases`, `car_price_history`, `car_exposes`, Nummernkreise
  (`FZ-0001` Fahrzeugnummer, `AN-0001` Angebotsnummer, Präfix aus `defaults_oserp`),
  `car_purchases_calc()`, `car_purchase_save()`, `car_document_data()`,
  `car_expose_create()`, `fleet_dashboard()` (Kennzahlen, Handlungsbedarf, Bestand).
- API in `backend/api/lxcars/purchases.php`, Store-Funktionen in
  `src/features/lxcars/stores/lxcars.store.js`.
- Ansicht `/ankauf` (`src/features/lxcars/views/purchase/`): Tabelle, Suche, Filter,
  Formular mit Kalkulation, Fotos hochladen, Preisschild und Exposé öffnen.
- Dokumente (`src/features/lxcars/components/`): Preisschild (2 Seiten) und Exposé
  (Titelseite, Ausstattung, Technik/Verbrauch, Fotoseiten), A4, druckfreundlich
  (weißer Grund), Logo per Schalter an/aus, leere Angaben erscheinen nicht.
- Fotos liegen unter `fahrzeuge/{c_id}/fotos/` und werden über `vfPreview` ausgeliefert.

## Design-Vorgaben

- Farben aus meinem Logo: Anthrazit `#2B2B2F` und Rot `#C8102E`. Primär ist Anthrazit,
  Rot nur als Akzent (aktive Navigation, Preis, Hervorhebungen).
- Nicht zu rund und nicht zu kantig: Radius 4 bis 6 px. Keine Pillen-Chips,
  keine Android-Optik (keine Großbuchstaben-Buttons, keine Schatten-Karten).
- Flach, 1 px Linien, viel Weißraum, Zahlen mit tabellarischen Ziffern.
- Dokumente (Preisschild, Exposé) bleiben hell, weil sie gedruckt werden.
- Mobil zuerst denken: Seitenleiste am Rechner, Icon-Leiste am Tablet,
  untere Navigationsleiste am Handy.

## Nächste Schritte (in dieser Reihenfolge)

1. **Neuer Rahmen** (Shell) mit Umschalter Verkauf/Werkstatt, Seitenleiste,
   mobiler Leiste, Benutzermenü. Theme-Overrides in `src/style.css`
   und `src/core/theme/`.
2. **Neue Startseite** (Route `/`) für den Verkauf mit Daten aus `fleet_dashboard()`:
   Kennzahlen, Fahrzeuge im Bestand als Karten mit Titelfoto, Handlungsbedarf
   (HU fällig, lange Standzeit, Preis lange nicht angepasst), nächste Termine aus dem
   vorhandenen Kalender. Die alte Startseite bleibt unter dem Menü-Pfad erreichbar.
3. **Ankauf und Kalkulation** in den neuen Rahmen und das neue Design setzen.
4. **Fahrzeug anlegen** mit KBA-Auswahl direkt im Ankauf-Formular (HSN/TSN, Liste der
   Varianten mit Baujahr, Auswahl befüllt die Felder). Zusätzlich Motorcode, Lackcode
   und Lackname. Lookup-Funktionen existieren in `backend/api/lxcars/cars.php`
   (`lookupKba`, `lookupKbaByHsn`, `lookupKbaFuzzy`).
5. **Titel- und Beschreibungsgenerator** für Kleinanzeigen und mobile.de
   (Vorlagen in der DB, Kopieren-Button). Vorhanden: `backend/api/lxcars/sales_text.php`.
6. **Terminkalender** und **Benutzerverwaltung** in der neuen Oberfläche.
7. Werkstatt-Bereich schrittweise überführen.

## Lokale Einrichtung

```bash
npm install
cd backend && composer install && cd ..
```

Datenbank: lokale PostgreSQL, Schema über das Update-System einspielen
(`updateSchema` mit Feature `lxcars`, siehe `backend/upstall/README.md`).
Frische Entwicklungs-DB siehe `scripts/bootstrap-fresh.sh` (verwendet Docker; die SQL-Schritte
lassen sich auch direkt mit `psql` ausführen). Danach `npm run dev` und PHP-Server
auf Port 8000 (`php -S 127.0.0.1:8000 -t backend`).

## Bekannte Stolpersteine

- Das Paket `xlsx` kommt von `cdn.sheetjs.com` und ist in manchen Netzen gesperrt.
- `package-lock.json` ist nicht synchron mit `package.json`, `npm ci` schlägt fehl.
- Beim KBA-Import verletzen einige Zeilen (HSN `-`) die Regel `kba_lxcars_hsn_check`.
- `backend/upstall/lxcars/company_data/tuev_defect_catalog.csv` hat einen fehlerhaften
  Zeilenumbruch in der Kopfzeile.
- `tools/fix-ws.sh` ist interaktiv. Alternativ vor dem Commit `git diff --check`.
- Ein Neuladen der Seite hängt bei „Bitte warten…“, wenn der SSE-Server (Port 3001) fehlt.

## Arbeitsweise

Kleine, saubere Commits. Nach jedem sichtbaren Schritt einen Bildschirmfoto-Vergleich
machen und mir zeigen, bevor es weitergeht. Mobile Ansicht (390 px Breite) immer mitprüfen.
