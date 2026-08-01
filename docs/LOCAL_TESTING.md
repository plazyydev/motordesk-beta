# MotorDesk lokal testen

Stand: 2026-08-01

Diese Anleitung ist fuer schnelle Rueckmeldungen gedacht: Was kannst du lokal
klicken, welche Befehle sind sinnvoll, und welche Fehler bedeuten nur, dass ein
lokaler Dienst fehlt.

## Schnellster Frontend-Test

Unter Windows/PowerShell:

```powershell
npm.cmd run dev -- --host 127.0.0.1
```

Dann im Browser oeffnen:

```text
http://127.0.0.1:5173
```

Hinweis: Der Vite-Devserver proxyt API-Aufrufe nach `http://localhost:8000` und
SSE nach `http://localhost:3001`. Wenn dort kein PHP-/Apache-Backend bzw. kein
SSE-Server laeuft, sind Login/API-Fehler erwartbar. Frontend-Layout, Theme und
Build-Fehler kann man trotzdem frueh sehen.

## Projektchecks

Schneller lokaler Check:

```powershell
npm.cmd run check
```

Wenn PHP lokal fehlt, wird der API-Healthcheck uebersprungen. Das ist auf dieser
Windows-Umgebung aktuell normal.

CI-/Server-Check mit verpflichtendem PHP:

```powershell
npm.cmd run check:ci
```

Dieser Befehl muss fehlschlagen, wenn `php` nicht im PATH ist.

## Nur Syntax des Check-Runners testen

```powershell
npm.cmd run check -- --skip-build
```

Das ist schnell und prueft, ob der Runner selbst startet.

## Phase C gezielt testen

1. App im Browser oeffnen.
2. Browser-DevTools oeffnen.
3. In der Console pruefen:

```js
document.documentElement.dataset.themeMode
```

Ohne gespeicherte Benutzer- oder Firmenkonfiguration sollte dort `system`
stehen. Die sichtbare App folgt dann der System-/Browser-Einstellung.

4. Windows Hell/Dunkel umschalten oder Browser neu laden.
5. Nach Login in der Benutzerkonfiguration den bestehenden Dark-Mode-Schalter
   testen. Dieser nutzt weiterhin `dark_mode` und bleibt kompatibel.

## Phase D gezielt testen

Die neue Application Shell ist aktuell eine technische Huelle. Die bestehende
Navbar bleibt in den einzelnen Views, damit keine doppelten App-Bars entstehen.

Bitte pruefen:

1. Startseite/Setup/Login zeigt weiter genau eine obere Leiste.
2. Nach Navigation zwischen Ansichten bleibt der Ladebildschirm weg.
3. Auf kleinen Breiten bleibt die bestehende mobile Navbar-Zeile benutzbar.
4. Keine Seite bekommt ploetzlich zusaetzliche Aussenabstaende oder horizontales
   Scrollen.
5. In den DevTools duerfen keine Vue-Warnungen zu fehlenden Shell-Komponenten
   erscheinen.

## Phase E gezielt testen

Die neue Fahrzeuguebersicht liegt unter:

```text
http://127.0.0.1:5173/fahrzeug
```

Mit laufendem PHP-/Apache-Backend sollte die Tabelle Fahrzeuge laden. Ohne
Backend ist auf dieser Seite ein API-/Proxy-Fehler erwartbar; Layout, Routing
und Build-Overlay lassen sich trotzdem pruefen.

Bitte pruefen:

1. `/fahrzeug` zeigt die neue Liste und nicht mehr die 404-Platzhalterseite.
2. Die Buttons `Neu` und `Scan` fuehren zu den bestehenden Fahrzeug-Anlagepfaden.
3. Suche, Sortierung und Seitenwechsel loesen neue Tabellenladungen aus.
4. Klick auf eine Zeile oder das Oeffnen-Icon fuehrt zu `/fahrzeug/<id>`.
5. Bei fehlendem Backend bleibt die Seite sichtbar und zeigt eine Fehlermeldung
   statt eines weissen Bildschirms.

## Was du melden solltest

- Weisser Bildschirm oder Build-Overlay im Browser
- Console-Fehler mit Datei/Zeile aus `src/`
- Theme schaltet nicht zwischen hell/dunkel
- Buttons, Tabellen oder Dialoge sehen durch die neuen Vuetify-Defaults
  sichtbar kaputt aus
- `/fahrzeug` zeigt weiter 404 oder laedt die Tabelle trotz laufendem Backend
  nicht
- Login/API-Fehler nur dann, wenn dein PHP-/Docker-Backend wirklich laeuft

## Bekannte lokale Einschraenkungen

- `php` ist hier nicht im PATH; `npm run check:api` kann deshalb lokal nicht
  laufen.
- `docker` ist hier nicht im PATH; Docker-Start kann in dieser Umgebung nicht
  verifiziert werden.
- Innerhalb der Codex-Dateisandbox kann esbuild den Vite-Build nicht laden. Der
  Build laeuft ausserhalb der Sandbox erfolgreich.
