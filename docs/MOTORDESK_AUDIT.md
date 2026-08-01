# MotorDesk Technical Audit

Datum: 2026-08-01

Status: erste technische Bestandsaufnahme fuer die Umbenennung und Weiterentwicklung von OpensourceERP/LxCars zu MotorDesk. Diese Datei dokumentiert den Ist-Zustand und einen dateibasierten Implementierungsplan. Es wurden keine grossflaechigen Backend-Aenderungen vorgenommen.

## Kurzfazit

Das Projekt ist kein klassischer monolithischer kivitendo-Fork, sondern ein modernes Vue/Vuetify-Frontend mit PHP-API-Schicht fuer kivitendo-kompatible PostgreSQL-Datenbanken. LxCars ist als Feature-Erweiterung fuer Fahrzeug- und Werkstattprozesse integriert.

Der aktuelle lokale Arbeitsordner ist kein Git-Repository. `git status` scheitert mit `fatal: not a git repository`, daher konnte der angeforderte Branch `feature/motordesk-foundation` nicht angelegt werden. Vor echten Entwicklungsphasen muss der Code in ein Git-Repository ueberfuehrt oder aus einem Repository neu ausgecheckt werden.

Der Frontend-Produktionsbuild laeuft nach erfolgreichem Dependency-Install durch. Der Build ueberspringt den API-Healthcheck, weil `php` in der lokalen Windows-Umgebung nicht im PATH vorhanden ist. Docker ist lokal ebenfalls nicht im PATH. Zum Auditzeitpunkt beschrieb die Docker-Dokumentation noch manuelle Datenbank-Schritte und referenzierte nicht vorhandene `backend/db/*.sql` Dateien; diese sichtbaren Doku-Pfade wurden in Phase A korrigiert.

## Reproduzierte Installation und Checks

Ausgefuehrt im Arbeitsordner `C:\Users\noord\Desktop\MotorDesk-OS`.

| Check | Ergebnis |
| --- | --- |
| `git status --short` | Fehlgeschlagen: kein Git-Repository |
| `node --version` | `v24.16.0` |
| `npm --version` | PowerShell blockiert `npm.ps1`; `npm.cmd --version` funktioniert mit `11.13.0` |
| `php -v` | Fehlgeschlagen: `php` nicht gefunden |
| `composer --version` | Fehlgeschlagen: `composer` nicht gefunden |
| `docker --version` | Fehlgeschlagen: `docker` nicht gefunden |
| `npm.cmd ci --cache .npm-cache --no-audit --no-fund` | Erst durch Netzwerk-Freigabe erfolgreich; 293 Pakete installiert |
| `npm.cmd run build` | Erfolgreich nach Freigabe fuer Dateisandbox; Vite baut `dist/` |
| `npm.cmd run check:api` | Fehlgeschlagen, weil `php` nicht gefunden wird |

Build-Hinweise:

- `vite.config.js` fuehrt beim Build `tools/check-api-health.php` aus.
- Ohne PHP wird der API-Healthcheck nur gewarnt und uebersprungen.
- Vite meldet grosse Chunks, besonders `dist/assets/index-*.js` mit ca. 5 MB minifiziert.
- Innerhalb der Dateisandbox scheitert esbuild beim Laden von `vite.config.js` mit `Cannot read directory "../..": Access is denied`; ausserhalb der Sandbox laeuft der Build.

## Phase-A-Fortschritt

Umgesetzt am 2026-08-01:

- Root-`.env.example` fuer neue MotorDesk-Installationen angelegt.
- Root-`docker-compose.yml` fuer Start aus dem Projektwurzelverzeichnis angelegt.
- `scripts/docker.sh` liest zuerst Root-`.env` und nutzt `docker/.env` nur noch als Legacy-Fallback.
- Docker-Defaults fuer Stack, DB-Namen und Session-Cookie auf `motordesk` umgestellt.
- Docker-README, Haupt-README und Demo-Setup-Pfade auf `backend/upstall/...` statt nicht vorhandener `backend/db/...` Pfade aktualisiert.
- Neue Dokumente angelegt: `docs/INSTALLATION.md`, `docs/UPDATE.md`, `docs/BACKUP_RESTORE.md`, `docs/MOTORDESK_ARCHITECTURE.md`.
- Keine `LICENSE` geraten; Lizenzklaerung bleibt offen.

## Phase-B-Fortschritt

Umgesetzt am 2026-08-01:

- Portabler Check-Runner `tools/run-checks.mjs` angelegt.
- `npm run check` ergaenzt: Build plus optionaler API-Healthcheck, wenn PHP vorhanden ist.
- `npm run check:ci` ergaenzt: Build plus verpflichtender API-Healthcheck fuer CI/PHP-Umgebungen.
- `scripts/check.sh` als Linux/CI-Wrapper angelegt.
- Smoke-Test-Grundlage unter `tests/smoke/README.md` dokumentiert.
- Lokaler kompletter Check laeuft ausserhalb der Dateisandbox erfolgreich; PHP fehlt weiterhin lokal, daher wird der API-Healthcheck im lokalen Check uebersprungen.

## Phase-C-Fortschritt

Umgesetzt am 2026-08-01:

- MotorDesk-Designwerte in `src/core/theme/motordesk.tokens.js` angelegt.
- Vuetify-Konfiguration aus `src/main.js` nach `src/core/theme/vuetify.js` ausgelagert.
- Bestehende Theme-Namen `light` und `dark` beibehalten, damit bestehender Code kompatibel bleibt.
- Globale CSS-Variablen in `src/style.css` ergaenzt.
- `useUserPrefs.js` unterstuetzt jetzt `theme_mode = light|dark|system`, den alten `dark_mode`-Wert und `company_default_theme`.
- Store-Helfer `getBrandingConfig()` fuer kommende Firmenbranding-Controls ergaenzt.
- Navbar liest das Firmenlogo ueber `getBrandingConfig()`, ohne das bisherige Verhalten zu aendern.
- Lokale Testanleitung `docs/LOCAL_TESTING.md` angelegt.

## Phase-D-Fortschritt

Umgesetzt am 2026-08-01:

- Neues Komponentenverzeichnis `src/core/components/app-shell/` angelegt.
- `motordesk-shell.vue` als zentrale Shell-Huelle mit Loading-Slot angelegt.
- `motordesk-header.vue`, `motordesk-sidebar.vue` und `theme-switcher.vue` als vorbereitete Bausteine angelegt.
- `src/App.vue` nutzt jetzt `MotorDeskShell` statt direkt `v-main`.
- Bestehende `NavbarView`-Einbindungen in Views bleiben unveraendert, um doppelte App-Bars zu vermeiden.
- Phase-D-Testhinweise in `docs/LOCAL_TESTING.md` ergaenzt.

## Phase-E-Fortschritt

Umgesetzt am 2026-08-01:

- Fahrzeuglisten-Route `/fahrzeug` von Platzhalter auf eine echte LxCars-View umgestellt.
- Neue read-only API-Action `getCars` in `backend/api/lxcars/cars.php` angelegt.
- Pinia-Store-Wrapper `loadCars()` in `src/features/lxcars/stores/lxcars.store.js` ergaenzt.
- Neue View `src/features/lxcars/views/car/car.list.view.vue` mit Suche, Server-Paginierung, Sortierung und Navigation zur Detailansicht angelegt.
- Bestehende Detailroute `/fahrzeug/:id` und Fahrzeuganlage unveraendert gelassen.
- Phase-E-Testhinweise in `docs/LOCAL_TESTING.md` ergaenzt.

## Aktuelle Projektstruktur

| Pfad | Zweck |
| --- | --- |
| `src/` | Vue-3-Frontend, Views, Stores, Router, Komponenten, I18n |
| `src/core/` | Kernmodule: Login, Setup, CRM/Kunden, Faktura, Suche, Config, E-Mail, Kalender, Wiki, Banking-Hub-Anbindung |
| `src/features/lxcars/` | Fahrzeug- und Werkstattmodul: Fahrzeuge, Fahrzeugschein-Scan, Mechaniker-Modus, HU-Serienbrief, Reports |
| `src/features/accounting/` | Buchhaltung, Beleg-Upload, DATEV, Buchungen, Kreditorenmatching |
| `src/features/banking/` | FinTS, Transfers, Kasse, Abgleich |
| `src/features/weroni/` | KI-Assistent mit Dokumentanalyse |
| `backend/api/` | PHP-API-Endpunkte, JSON-Actions, Auth, Session, Datenbankzugriff |
| `backend/upstall/` | SQL-Schemata fuer kivitendo-Basis, CRM- und LxCars-Erweiterungen |
| `backend/templates-default/` | LaTeX/PDF-Belegtemplates |
| `backend/sse/` | Node-SSE-Server fuer Echtzeit-Updates via PostgreSQL `LISTEN/NOTIFY` |
| `backend/whisper/` | Lokaler Whisper-Transkriptionsdienst |
| `backend/services/` | Kamera-/ANPR-nahe Dienste |
| `docker/` | Docker Compose, Web-Dockerfile, Apache-/PHP-Konfiguration, DB-Init-Skripte |
| `install/` | Bare-metal Installer, Apache-vHost, systemd-Service-Vorlagen |
| `scripts/` | Build-, Dev-, Docker-, Backup-nahe Helfer |
| `docs/` | Feature-Dokumentation |
| `dev/` | Migrationsnotizen, SQL-Hilfen, technische Konzepte |
| `promotion/` | Bestehende Logos und Marketing-Assets |
| `_actest/` | Kleines Vite-Testprojekt, kein vollwertiges Testframework |

## Verwendete Technologien

| Bereich | Ist-Zustand |
| --- | --- |
| Programmiersprachen | JavaScript/Vue, PHP, SQL/PLpgSQL, Shell, Python fuer Zusatzdienste |
| Frontend | Vue 3, Vuetify 3, Vite 7, Pinia, Vue Router 4, vue-i18n |
| Backend | PHP 8.1+ API-Skripte, kein Framework wie Laravel/Symfony erkennbar |
| Datenbank | PostgreSQL, kivitendo-kompatible Schemas plus `defaults_oserp`, `employee_config_oserp`, LxCars-Tabellen |
| Build | Vite, npm, API-Healthcheck per PHP-Skript im Build-Hook |
| Echtzeit | Node.js SSE-Server in `backend/sse/sse-server.js` |
| UI/CSS | Vuetify, globale CSS in `src/style.css`, komponentenbezogene Scoped Styles |
| PDF/Belege | kivitendo-/LaTeX-Templates, FPDF/FPDI, ZUGFeRD-Library |
| Uploads | VueFinder/Kundendateimanager, Base64-Uploads in mehreren Modulen, lokale Dateiablage unter `backend/data` |
| KI/OCR heute | Externe APIs fuer Fahrzeugschein-Scan und diverse KI-Funktionen, lokaler Whisper-Dienst fuer Sprache |
| Docker | `docker/docker-compose.yml` mit Web, DB und optional Certbot |

## Lizenz- und Markenbefund

Im Projektwurzelverzeichnis wurde keine `LICENSE`, `COPYING` oder `NOTICE` Datei gefunden. Vor Umbenennung, Veroeffentlichung oder Weitergabe von MotorDesk muss die Ursprungslizenz des Repositories geklaert und als Root-Lizenzdatei aufgenommen werden.

Gefundene Lizenzdateien:

- `backend/lib/fpdf/license.txt`: MIT-Text ohne vollstaendigen Copyright-Header im ausgegebenen Ausschnitt.
- `backend/lib/fpdi/LICENSE.txt`: MIT, Copyright Setasign GmbH & Co. KG.

Direkte npm-Abhaengigkeiten laut `package-lock.json`:

- Ueberwiegend MIT.
- `@mdi/font` und `xlsx` sind Apache-2.0.

Composer-Abhaengigkeiten laut `backend/composer.lock`:

- Ueberwiegend MIT.
- `smalot/pdfparser` ist LGPL-3.0.

Marken-/Namenshinweise:

- Viele Dateien nennen noch `OpensourceERP`, `opensource-erp`, `opensource_erp`, `LxCars` und `kivitendo`.
- `LxCars` ist derzeit Feature-/Modulname und sollte erst nach rechtlicher Pruefung und funktionaler Migration umbenannt werden.
- kivitendo-Kompatibilitaet ist fachlich relevant und darf nicht aus Dokumentation/API-Verhalten entfernt werden.

Sicherheits-/Lizenzrisiko:

- `backend/config/settings.iniBAK` existiert im Arbeitsbaum. Solche Dateien koennen Zugangsdaten enthalten. Nicht auslesen, sondern klaeren, ob echte Secrets enthalten sind, und nicht versioniert fuehren.

## Frontend-Aufbau

Wichtige Einstiegspunkte:

- `src/main.js`: App-Initialisierung, Pinia, Router, i18n, Vuetify-Themes.
- `src/App.vue`: V-App-Huelle und Router-View.
- `src/core/router/index.js`: Routen, Auth-Guard, Berechtigungschecks.
- `src/core/stores/oserp.store.js`: zentrale Session-, Feature-, Config- und CRM-Daten.
- `src/core/components/navbar/navbar.view.vue`: aktuelle globale Kopf-/Menueleiste.
- `src/core/composables/navigation.cards.js`: Navigationskarten/Menues.
- `src/core/composables/useUserPrefs.js`: Dark Mode und Sprache aus Employee-Config.

Ist-Zustand Design:

- Vuetify-Theme `light` und `dark` existiert direkt in `src/main.js`.
- White Mode ist als `defaultTheme: 'light'` aktiv.
- Dark Mode wird aus `employee_config_oserp.key = dark_mode` gelesen.
- Es gibt noch kein separates MotorDesk-Designsystem mit zentralen Tokens fuer Farben, Abstaende, Tabellen, Status, Modals und Navigation.
- Viele Komponenten nutzen Vuetify-Theme-Variablen, aber es gibt auch harte Farben in Scoped Styles und Inline-Styles.
- Das Layout ist aktuell eine App-Bar plus responsive Menues, keine neue feste/einklappbare MotorDesk-Seitenleiste.

Bestehendes Firmenbranding:

- `src/core/views/config/tabs/company.tab.vue` erlaubt Logo-Auswahl.
- Gespeichert wird aktuell ein zugeschnittenes PNG-Data-URL in `defaults_oserp.company_logo` via `saveClientDefault`.
- `src/core/components/navbar/navbar.view.vue` zeigt `company_logo` in der Navbar.
- Es gibt noch keine Logo-Position links/rechts, keine Akzentfarbe, keine sichere serverseitige Logo-Dateispeicherung, keine SVG-Unterstuetzung und keine Backend-Dateityppruefung fuer Firmenlogos.

## Backend-Aufbau

Wichtige Einstiegspunkte:

- `backend/api/inc.php`: laedt Config, Logging, Passwort, DB, Session, Auth und API-Dispatcher.
- `backend/api/config.php`: liest `backend/config/settings.ini`, setzt Konstanten.
- `backend/api/database.php`: PDO-Wrapper, Query-/Update-Helfer.
- `backend/api/session.php`: Session-Wiederherstellung, Permissions, globale `permit()` Hilfen.
- `backend/api/auth.php`: Login, Logout, Mandantenwechsel, Session-Restore.
- `backend/api/api.call.php`: Action-Dispatcher.
- `backend/api/setup/setup.php`: Setup-Wizard Backend.

API-Muster:

- Frontend postet JSON an `/api/.../` mit `action`.
- PHP-Funktionen werden ueber Action-Namen dispatcht.
- Antworten laufen meist ueber `resultInfo(success, text, payload, debug)`.
- Datenbankzugriffe erfolgen ueber vorbereitete PDO-Statements oder ueber Hilfsfunktionen.

Auth und Rechte:

- Login prueft `auth.user`, `auth.clients_users`, setzt Cookie und schreibt `auth.session_oserp`.
- Berechtigungen werden ueber `auth.user_group`, `auth.group_rights`, `auth.clients_groups` ermittelt.
- Frontend prueft ausgewaehlte Routen via `to.meta.permission`.
- Backend prueft kritische Faktura-/Such-/Upload-Funktionen mit `permit()` oder `checkPermissions()`.

## Setup-Assistent

Vorhanden:

- Frontend: `src/core/views/setup/setup.view.vue`.
- Backend: `backend/api/setup/setup.php`.
- Setup erkennt `settings.ini` ueber `setupExists()`.
- Datenbankverbindung kann getestet werden.
- `settings.ini` wird erstellt.
- Nach Abschluss kann offenbar ein Datenbankupdate angestossen werden.

Fehlend oder unvollstaendig gegen Zielbild:

- Administratoranlage ist im geprueften Setup-Code nicht sichtbar.
- Firmenname, Firmenlogo, Sprache, Zeitzone und E-Mail-Konfiguration sind nicht als vollstaendiger Wizard-Fluss umgesetzt.
- Bestehende Installation wird vor Ueberschreiben der `settings.ini` geschuetzt, aber Datenbankinitialisierung/Migration ist nicht komplett automatisiert.
- Docker-Setup umgeht den Wizard teilweise, indem `settings.ini` im Entrypoint erzeugt wird.

## Datenbank und relevante Tabellen

Die Basisschemata liegen unter:

- `backend/upstall/skr03/company_schema.sql`
- `backend/upstall/skr04/company_schema.sql`

CRM-/ERP-Erweiterungen:

- `backend/upstall/crm/auth_schema.sql`
- `backend/upstall/crm/company_schema.sql`

LxCars-Erweiterungen:

- `backend/upstall/lxcars/company_schema.sql`
- `backend/upstall/lxcars/anpr_schema.sql`

Wichtige Tabellen:

| Bereich | Tabellen |
| --- | --- |
| Auth/Session | `auth.user`, `auth.clients`, `auth.clients_users`, `auth.group`, `auth.user_group`, `auth.group_rights`, `auth.clients_groups`, `auth.user_config`, `auth.session_oserp` |
| Kunden/Lieferanten | `customer`, `vendor`, `contacts`, `shipto`, `customer_ext`, `vendor_ext` |
| Belege | `oe`, `ar`, `ap`, `orderitems`, `invoice`, `delivery_orders`, `delivery_order_items` |
| Artikel/Lager | `parts`, `warehouse`, `bin`, `inventory`, `stocktakings` |
| Konfiguration | `defaults`, `defaults_oserp`, `employee_config_oserp`, `features_oserp` |
| Fahrzeug/Werkstatt | `cars_lxcars`, `oe_ext`, `ar_ext`, `fs_scans_lxcars`, `kba_lxcars`, `special_kba_lxcars` |
| Werkstattarbeit | `instructions_lxcars`, `oe_instructions_lxcars`, `oe_defects`, `ar_defects`, `oe_parts_requests_lxcars`, `missing_orders_lxcars` |
| ANPR/Kamera | `anpr_cameras_lxcars`, `anpr_actuators_lxcars`, `anpr_detections_lxcars`, `anpr_health_lxcars`, `camera`, `camera_zone`, `camera_event`, `camera_rule` |
| Dokument-/Buchhaltung | `accounting_documents`, `accounting_bookings`, `accounting_booking_lines`, `vendor_aliases`, `cash_gl_documents` |
| Kommunikation | `calendar_events`, `whatsapp_messages`, `whatsapp_templates`, `voice_notes`, `crmti`, `wiki_pages`, `wiki_revisions` |
| Banking | `bank_account_fints`, `bank_import_log`, `bank_transfer_orders`, `bank_transaction_matches`, `standing_orders`, `sepa_mandates` |

## Vorhandene Fahrzeugfelder

Quelle: `backend/upstall/lxcars/company_schema.sql`, Tabelle `cars_lxcars`.

Kernfelder:

- `c_id`: Fahrzeug-ID
- `c_ow`: Besitzer/Kunde
- `c_ln`: Kennzeichen, eindeutig
- `c_2`: HSN
- `c_3`: TSN
- `c_fin`: FIN, eindeutig
- `c_d`: Erstzulassung bzw. Zulassungsdatum aus Scan-Mapping
- `c_hu`: HU-Datum
- `c_em`: Emissions-/Umweltfeld
- `c_mkb`, `c_t`, `c_st`, `c_wt`, `c_st_l`, `c_wt_l`, `c_mt`, `c_e_id`
- `c_text`: Freitext
- `c_m`: Marke/Hersteller-Code
- `c_color`: Farbe
- `c_gart`, `c_st_z`, `c_wt_z`
- `c_km`: Kilometerstand
- `c_finchk`: FIN-Pruefziffer/-Status
- `c_pb`: Personenbefoerderung erkannt
- `c_hu_notify`: HU-Benachrichtigung
- `kba_id`: Verweis auf `kba_lxcars`
- `scan_detail_id`, `scan_id`, `filename`
- `c_ktype`, `c_ktype_desc`, `installed_engines`

Pruef-/Checkboxfelder:

- `chk_c_ln`, `chk_c_2`, `chk_c_3`, `chk_c_em`, `chk_fin`, `chk_c_hu`, `chk_c_d`
- `c_sk`, `c_zrk`, `c_zrd`, `c_bf`, `c_wd`

KBA-/Zulassungsbescheinigungsfelder:

- `fs_scans_lxcars` speichert zahlreiche Rohfelder der deutschen Zulassungsbescheinigung Teil I, darunter `ez`, `hsn`, `tsn`, `vin`, `registrationnumber`, Halterdaten, `j`, `field_4`, `d1`, `d2_*`, `d3`, `v9`, `p1`, `p2_p4`, `p3`, `f1`, `f2`, Achslasten, Geraeuschwerte, Reifenfelder, `hu`, `creation_date`, `creation_city`, `document_id`, `maker`, `model`, `powerkw`, `ccm`, `fuel`.
- `kba_lxcars` und `special_kba_lxcars` halten Stammdaten/vehicle-type Daten fuer HSN/TSN/D2 und technische Felder.

## Vorhandene Kundenfelder

Quelle: `backend/upstall/skr03/company_schema.sql`, Tabelle `customer`, plus `customer_ext`.

Auszug relevante Felder:

- `id`, `name`, `department_1`, `department_2`
- `street`, `zipcode`, `city`, `country`
- `contact`, `phone`, `fax`, `homepage`, `email`
- `notes`
- `discount`, `taxincluded`, `creditlimit`
- `customernumber`
- `cc`, `bcc`
- `business_id`, `taxnumber`, `ustid`
- `account_number`, `bank_code`, `bank`
- weitere kivitendo-Felder fuer Zahlungsbedingungen, Steuerzone, Sprache, Waehrung, Rechnungs-/Lieferadressen und Custom Variables

`customer_ext` erweitert:

- `customer_id`
- `phone_numbers`, `phone_labels`, `emails` als JSONB
- `keywords`
- `hu_serienbrief_excluded` wird durch LxCars ergaenzt

## Vorhandene Auftrags- und Belegfelder

Basistabelle `oe` fuer Angebote/Auftraege/Lieferscheine-nahe Workflows:

- `id`, `ordnumber`, `transdate`
- `vendor_id`, `customer_id`
- `amount`, `netamount`
- `reqdate`, `taxincluded`, `shippingpoint`
- `notes`, `intnotes`
- `employee_id`, `salesman_id`
- `closed`, `delivered`, `proforma`
- `quonumber`, `cusordnumber`
- `department_id`, `cp_id`, `language_id`, `payment_id`
- `delivery_customer_id`, `delivery_vendor_id`, `shipto_id`
- `taxzone_id`, `globalproject_id`
- Margenfelder und weitere kivitendo-Felder

LxCars-Erweiterung `oe_ext`:

- `oe_id`
- `c_id`
- `km_stand`
- `kfz_ort`
- `gedruckt`
- `intern`
- `bringetermin`
- `fertigstellung`
- `status`
- `kennzeichen`
- `no_whatsapp`
- `asanetwork_sent_at`

Rechnungstabelle `ar`:

- `id`, `invnumber`, `transdate`, `gldate`
- `customer_id`, `amount`, `netamount`, `paid`
- `datepaid`, `duedate`, `deliverydate`
- `invoice`, `storno`, `type`
- `ordnumber`, `quonumber`, `cusordnumber`
- `notes`, `intnotes`, `taxzone_id`, `currency_id`

Positionsdaten `orderitems`:

- `trans_id`, `parts_id`, `description`, `qty`
- `sellprice`, `discount`, `unit`
- `reqdate`, `ship`, `serialnumber`
- `longdescription`, `position`
- Margen-, Preisquellen- und Preisfaktor-Felder

## Dokumenten- und Uploadfunktionen

Vorhandene Funktionen:

- Kundendateimanager: `backend/api/customer_vendor/filemanager.php`, Frontend u.a. `src/core/views/customer-vendor/tabs/files.tab.vue`.
- Fahrzeugscheinbilder: `backend/api/lxcars/scan_images.php`, Frontend `src/features/lxcars/views/car/car.scan.view.vue` und `car-files.dialog.vue`.
- Mechaniker-Foto-Uploads fuer Ersatzteil-/Auftragskontext: `backend/api/lxcars/mechanic.php`.
- Accounting-Belegupload: `backend/api/accounting/invoice_upload.php`, `src/features/accounting/views/accounting.invoice-upload.vue`.
- Kassenbelegupload: `src/features/banking/views/banking.kasse.vue` nutzt `uploadCashDocument`.
- Artikel-/eBay-Bilder: `src/core/views/article/article.edit.view.vue`, `backend/api/ebay/listings.php`.
- Druck/PDF: `backend/api/print/print.php`, `src/core/stores/faktura.store.js`, Templates unter `backend/templates-default/`.

Risiken im Ist-Zustand:

- Es gibt noch keine generische, normalisierte `documents` Tabelle fuer alle Akten.
- Datei-Metadaten wie `checksum`, `page_count`, `ocr_status`, `confirmed_fields`, Audit-Historie und Verknuepfungen ueber mehrere Entitaeten sind nur teilweise bzw. modulbezogen vorhanden.
- Firmenlogo wird als Data-URL in `defaults_oserp` gespeichert, nicht als kontrolliert ausgelieferte Datei.
- Mehrere Uploadpfade arbeiten mit Base64 in JSON. Das ist einfach, aber fuer grosse PDFs und mobile Mehrseitenscans ineffizient.
- Einige Dateien werden unter `backend/data` bzw. ueber spezifische Webhook-/Media-Endpunkte bereitgestellt. Fuer MotorDesk muss jeder Zugriff route-/permission-geprueft sein.

## Scan- und OCR-Funktionen

Vorhanden:

- Fahrzeugschein-Scan-UI mit Upload, Scanliste, KBA-Abgleich und manueller Uebernahme.
- `backend/api/lxcars/cars.php::scanFahrzeugschein()` ruft aktuell `https://api.fahrzeugschein-scanner.de/generic-json` auf, wenn kein Demo-Modus aktiv ist.
- Scan-Details koennen ueber `fahrzeugschein-scanner.de/api/Scans/ScanDetails/...` nachgeladen werden.
- Rohdaten werden in `fs_scans_lxcars` gespeichert.
- Bilder/Crops werden temporaer unter `backend/tmp/{scan_id}` und final unter `fmDataDir()/fahrzeuge/{c_id}/fahrzeugschein/` abgelegt.
- Demo-Modus kann Scan-Daten aus `backend/api/demo/data/demo_fs_scan.csv` simulieren.
- ANPR fuer Kennzeichenerkennung existiert als separater Dienst-/Tabellenkomplex.
- Lokaler Whisper-Dienst existiert fuer Sprache, nicht fuer Dokument-OCR.

Zielanpassung nach Nutzerhinweis:

- `fahrzeugschein-scanner.de` darf als optionale, konfigurierte Integration erhalten bleiben, z.B. fuer geringe Scanmengen wie 10 Scans.
- MotorDesk sollte trotzdem ohne diese Cloud-Integration installierbar und nutzbar bleiben.
- Langfristig sollte eine Provider-Abstraktion entstehen: `external_fahrzeugschein_scanner` optional, `local_ocr` lokal, `demo` fuer Testdaten.
- Kein Fahrzeugschein oder personenbezogenes Dokument darf ohne bewusste Konfiguration an externe Dienste gesendet werden.

## Docker- und Installationsbefund

Vorhanden:

- `docker/docker-compose.yml`: Services `web`, `db`, optional `certbot`.
- `docker/web/Dockerfile`: Multi-Stage Build mit Node 25, PHP 8.3 FPM, Apache, Composer, SSE-Deps.
- `docker/web/entrypoint.sh`: erzeugt `backend/config/settings.ini`, setzt Rechte, startet PHP-FPM, optional SSL, SSE und Apache.
- `scripts/docker.sh`: Helfer fuer Start/Stop/Reset/Backup/Demo.
- `install/install.sh`: umfangreicher Bare-metal Installer fuer Debian/Ubuntu-nahe Systeme.

Abweichungen vom Ziel:

- Es gibt keine Root-`docker-compose.yml`; die Compose-Datei liegt unter `docker/`.
- Es gibt keine Root-`.env.example`; sie liegt unter `docker/.env.example`.
- Die Doku beschreibt noch manuelle DB-Anlage und manuelles Schema-Laden.
- Zum Auditzeitpunkt referenzierte `docker/README.md` `backend/db/auth_schema.sql` und `backend/db/company_schema.sql`, die nicht existieren. Phase A hat die sichtbaren Setup-Pfade auf `backend/upstall/.../auth_schema.sql` und `backend/upstall/.../company_schema.sql` korrigiert.
- Docker war in der lokalen Umgebung nicht ausfuehrbar, weil `docker` nicht gefunden wurde.
- Der Passwort-Platzhalter ist kein echtes Passwort; das ist korrekt, sollte aber bei automatischem Start validiert werden.

## Tests

Gefunden:

- Keine `test`, `vitest`, `playwright`, `phpunit` oder vergleichbare Test-Scripts in `package.json`.
- `_actest/` ist ein kleines Vite-Testprojekt, nicht die Kern-App-Test-Suite.
- `install/test-vm.sh` testet Bare-metal Installation in einer VM-nahen Umgebung.
- `backend/api/developer-tools/test-parser.php` ist Developer-Tool-nahe Funktionalitaet.
- `tools/check-api-health.php` ist der wichtigste aktuelle API-Syntax-/Include-Healthcheck, benoetigt aber PHP.

Fehlend gegen Ziel:

- Automatisierte Smoke-Tests fuer Login, Kunden, Fahrzeuge, Auftrag, Rechnung, Berechtigung und Upload.
- E2E-Tests fuer mobile Navigation, Theme-Wechsel und Firmenbranding.
- Docker-Starttest fuer frische Installation.

## Technische Risiken

1. Kein Git-Repository im aktuellen Arbeitsordner: Branching, Commits und saubere Diff-Kontrolle sind blockiert.
2. Root-Lizenz fehlt: Veroeffentlichung/Rebranding ist rechtlich nicht sauber dokumentiert.
3. Lokale Umgebung unvollstaendig: PHP, Composer und Docker fehlen im PATH.
4. Docker-Setup ist nicht One-Command-ready, Datenbanken muessen laut Doku manuell vorbereitet werden.
5. Docker-Doku referenzierte zum Auditzeitpunkt nicht vorhandene Schema-Pfade; Phase A hat die sichtbaren Setup-Pfade auf `backend/upstall/...` korrigiert.
6. API-Healthcheck wird im erfolgreichen Build uebersprungen, solange PHP fehlt.
7. Externe OCR/KI-Dienste sind an mehreren Stellen vorhanden. Sie muessen optional bleiben und als Datenabfluss kenntlich sein.
8. Firmenlogo-Upload speichert Data-URLs in Config statt Dateien mit MIME-/Groessen-/SVG-Sicherheitspruefung.
9. Generische Dokumentenakte fehlt; vorhandene Uploads sind modulbezogen.
10. Grosser Frontend-Hauptchunk kann Ladezeit und mobile Performance belasten.
11. `settings.iniBAK` im Arbeitsbaum kann Secrets enthalten.
12. Viele Produktnamen sind hart in Code, Doku, Config, Service-Namen und Templates verteilt.

## Notwendige Aenderungen

Kurzfristig:

- Git-Arbeitsbasis wiederherstellen und Branch `feature/motordesk-foundation` anlegen.
- Root-Lizenz und NOTICE/Copyright-Situation klaeren.
- `docs/MOTORDESK_AUDIT.md` als Grundlage versionieren.
- Installation dokumentieren und Docker-Pfade korrigieren.
- Root-`docker-compose.yml` und Root-`.env.example` vorbereiten oder Doku konsequent auf `docker/` ausrichten.
- API-Healthcheck in einer PHP-faehigen Umgebung ausfuehren.
- `settings.iniBAK` aus produktiven/versionierten Artefakten entfernen, falls es echte Secrets enthaelt.

Design/Foundation:

- MotorDesk-Designsystem in eigenen Dateien aufbauen, z.B. `src/core/theme/motordesk.tokens.js` und `src/core/theme/vuetify.js`.
- Globale CSS-Variablen/Tokens in `src/style.css` ergaenzen.
- `src/main.js` entlasten und Theme-Konfiguration auslagern.
- `useUserPrefs.js` erweitern: gespeicherte Auswahl pro Benutzer, Browser-Fallback, Systempraeferenz nur ohne gespeicherte Auswahl.
- Firmenbranding erweitern: `company_logo_file_id`, `company_logo_position`, `company_accent_color`, `company_default_theme`.
- Neues Application-Shell-Konzept neben bestehender Navbar einfuehren, nicht bestehende Routen/Funktionen entfernen.

Dokumente/OCR:

- Generische Dokumentenmetadaten-Tabelle planen, ohne bestehende Dateimanager zu brechen.
- Upload-Service mit sicheren Dateinamen, MIME-Pruefung, Checksummen und kontrolliertem Download-Endpunkt entwerfen.
- Fahrzeugschein-Scanner als Provider-System planen: optional externer Anbieter, spaeter lokaler OCR-Dienst.
- OCR-Ergebnisse nur als Vorschlaege mit Vergleich alt/neu speichern.

## Empfohlene Reihenfolge

1. Git-Repository/Branch und Lizenz klaeren.
2. Installation stabilisieren: Root-Compose, `.env.example`, Doku, Schema-Seeding.
3. PHP/Composer/Docker-Checks in einer passenden Umgebung ausfuehren.
4. Smoke-Test-Grundlage schaffen.
5. MotorDesk-Tokens und Theme-Service einfuehren.
6. Firmenbranding sicher erweitern.
7. Application Shell parallel zur bestehenden Navigation integrieren.
8. Fahrzeuguebersicht als Referenzseite modernisieren, mit echten Daten und bestehenden Berechtigungen.
9. Generische Dokumentenkomponente und Metadatenmodell vorbereiten.
10. Fahrzeugschein-OCR-Provider abstrahieren; `fahrzeugschein-scanner.de` optional behalten, lokale OCR spaeter ergaenzen.

## Redesign-relevante Dateien

| Datei | Geplante Rolle |
| --- | --- |
| `src/main.js` | Theme-Setup auslagern, MotorDesk defaultTheme |
| `src/style.css` | Globale Design Tokens und App-Basisstyles |
| `src/App.vue` | Shell-Huelle vorbereiten |
| `src/core/router/index.js` | Routen unveraendert erhalten, Shell-Meta ggf. ergaenzen |
| `src/core/stores/oserp.store.js` | Branding-/Theme-Config lesbar machen |
| `src/core/composables/useUserPrefs.js` | Theme-Persistenz und Browser-Fallback |
| `src/core/components/navbar/navbar.view.vue` | Bestehende Navigation sichern, spaeter in Shell ueberfuehren |
| `src/core/composables/navigation.cards.js` | Grundlage fuer Sidebar-Menues |
| `src/core/views/config/tabs/company.tab.vue` | Firmenbranding erweitern |
| `src/features/lxcars/views/car/car.edit.view.vue` | Fahrzeugakte, Scanner-Button, Dokumente |
| `src/features/lxcars/views/car/car.scan.view.vue` | Bestehenden Fahrzeugschein-Scan erhalten und Provider-Umschaltung vorbereiten |
| `src/features/lxcars/stores/lxcars.store.js` | Fahrzeug-/Scan-API-Client |
| `src/core/views/order-search/order-search.view.vue` | Wahrscheinliche Basis fuer Fahrzeug-/Auftragsuebersichten |

## Dateien, die nicht unnoetig veraendert werden duerfen

| Datei/Pfad | Grund |
| --- | --- |
| `backend/upstall/skr03/company_schema.sql` | kivitendo-Basisschema, hoher Migrations-/Kompatibilitaetswert |
| `backend/upstall/skr04/company_schema.sql` | kivitendo-Basisschema, hoher Migrations-/Kompatibilitaetswert |
| `backend/api/faktura/faktura.php` | Belegprozesse, Nummernkreise, Berechtigungen |
| `backend/api/customer_vendor/customer_vendor.php` | Kunden-/Lieferanten-Stammdaten |
| `backend/api/database.php` | Zentraler DB-Layer |
| `backend/api/session.php` | Auth-/Permission-Kern |
| `backend/api/auth.php` | Login, Session, Mandantenwechsel |
| `backend/templates-default/` | Belegausgabe/PDF-Verhalten |
| `backend/upstall/lxcars/company_schema.sql` | Bestehende Fahrzeug-/Werkstattdaten |
| `backend/api/lxcars/cars.php` | Fahrzeuglogik und aktueller Scanner |
| `backend/api/lxcars/scan_images.php` | Bestehende Scanbildablage |
| `backend/config/settings.ini*` | Secrets/Installation, nie blind ueberschreiben |

## Konkreter dateibasierter Implementierungsplan

### Phase A: Repository und Installation

1. Git wiederherstellen:
   - Aktion: Projekt aus Ursprung neu klonen oder `git init` nur nach Entscheidung.
   - Zielbranch: `feature/motordesk-foundation`.

2. Lizenzdateien ergaenzen:
   - Neue Datei: `LICENSE` nur nach Klaerung der Ursprungslizenz.
   - Neue Datei: `NOTICE` falls erforderlich.
   - Dokumentation: `docs/MOTORDESK_ARCHITECTURE.md` mit Lizenzhinweis.

3. Docker-Schnellstart korrigieren:
   - Neue/angepasste Datei: `docker-compose.yml` im Root oder dokumentierter Wrapper.
   - Neue Datei: `.env.example` im Root, ohne echte Secrets.
   - Aendern: `docker/README.md`, `README.md`.
   - Aendern: `scripts/docker.sh`, falls Root-Compose unterstuetzt werden soll.
   - Pruefen: `docker/db/init/*.sh`, um DB-Anlage/Schemata automatisch zu seed-en.

4. Installationsdoku:
   - Neue Datei: `docs/INSTALLATION.md`
   - Neue Datei: `docs/UPDATE.md`
   - Neue Datei: `docs/BACKUP_RESTORE.md`

### Phase B: Tests und Healthchecks

1. PHP-Healthcheck reproduzierbar machen:
   - Pruefen: `tools/check-api-health.php`
   - CI/Script: `scripts/check.sh` oder npm script erweitern.

2. Smoke-Test-Konzept:
   - Neue Tests z.B. unter `tests/smoke/`.
   - Ziele: Login, Kunden, Fahrzeug, Auftrag, Rechnung, Berechtigung, Upload.
   - Playwright erst einfuehren, wenn Testdaten/Docker-Seed stabil sind.

### Phase C: MotorDesk Designsystem

1. Theme auslagern:
   - Neue Datei: `src/core/theme/motordesk.tokens.js`
   - Neue Datei: `src/core/theme/vuetify.js`
   - Aendern: `src/main.js`

2. Persistenz:
   - Aendern: `src/core/composables/useUserPrefs.js`
   - Aendern: `src/core/stores/oserp.store.js`
   - Optional Backend: `backend/api/oserp_config/defaults.php` nur falls neue Keys validiert werden sollen.

3. Branding:
   - Aendern: `src/core/views/config/tabs/company.tab.vue`
   - Neue Backend-Actions fuer sicheren Logo-Upload, z.B. `backend/api/company/branding.php` oder integrierter `oserp_config` Endpunkt.
   - Neue DB-Keys in `defaults_oserp`: `company_logo_file_id`, `company_logo_position`, `company_accent_color`, `company_default_theme`, `navigation_density`.

### Phase D: Application Shell

1. Neue Shell-Komponenten:
   - Neue Datei: `src/core/components/app-shell/motordesk-shell.vue`
   - Neue Datei: `src/core/components/app-shell/motordesk-sidebar.vue`
   - Neue Datei: `src/core/components/app-shell/motordesk-header.vue`
   - Neue Datei: `src/core/components/app-shell/theme-switcher.vue`

2. Integration:
   - Aendern: `src/App.vue`
   - Bestehende `NavbarView` zunaechst erhalten oder schrittweise einbetten.
   - Keine Route und kein Menue entfernen.

### Phase E: Fahrzeuguebersicht

1. Ist-Zustand klaeren:
   - Aktuell zeigt Route `CarView.routes.manageCars` auf `NotFoundView`.
   - Fahrzeugdaten liegen in `backend/api/lxcars/cars.php`.

2. Referenzseite:
   - Neue Datei: `src/features/lxcars/views/car/car.list.view.vue`
   - Aendern: `src/core/router/index.js`
   - Aendern: `src/features/lxcars/stores/lxcars.store.js`
   - Backend nur erweitern, wenn kein passender Listen-Endpunkt vorhanden ist.

### Phase F: Dokumentenbasis und Scanner

1. Dokumentenmodell:
   - Neue Migration unter `backend/upstall/...` erst nach Schema-Konzept.
   - Tabellenentwurf: `documents_oserp`, `document_links_oserp`, `document_events_oserp`.

2. Upload-Komponente:
   - Neue Datei: `src/core/components/documents/document-upload-dialog.vue`
   - Neue Datei: `src/core/components/documents/document-preview.vue`
   - Neue API: kontrollierter Upload/Download-Endpunkt.

3. Fahrzeugschein-Provider:
   - Aendern: `backend/api/lxcars/cars.php`, Provider-Fassade statt direkter API-Call.
   - Externen Provider `fahrzeugschein-scanner.de` optional behalten.
   - Spaeter: lokaler OCR-Service mit Tesseract/PaddleOCR/OpenCV.

## Offene Fragen

1. Welche Ursprungslizenz gilt fuer den kompletten Codebestand?
2. Soll `LxCars` als Modulname langfristig bleiben oder ebenfalls durch MotorDesk-Begriffe ersetzt werden?
3. Ist `backend/config/settings.iniBAK` ein versehentlich kopiertes Secret?
4. Soll der Docker-Schnellstart eine leere Demo-/Setup-Datenbank erzeugen oder zwingend einen kivitendo-Dump importieren?
5. Welcher Fahrzeugschein-Scanner-Modus soll Standard sein: optional externer Dienst aus, optional externer Dienst an, oder Demo/Lokal?
