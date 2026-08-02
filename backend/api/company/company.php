<?php
// backend/api/company/company.php

/**
 * Quoted einen PostgreSQL-Bezeichner (Tabelle, DB, User)
 *
 * @param string $name Bezeichner
 * @return string Gequoteter Bezeichner
 */
function quoteIdentifier($name) {
    return '"' . str_replace('"', '""', $name) . '"';
}

/**
 * Legt eine neue Firmendatenbank an
 *
 * 1. Prüft Berechtigung (admin_users aus settings.ini)
 * 2. Erstellt die PostgreSQL-Datenbank via CREATE DATABASE
 * 3. Spielt das SKR-Schema via Upstall-Parser ein
 * 4. Registriert die Firma in auth.clients
 * 5. Führt CRM-Upstall auf der neuen Datenbank aus
 *
 * @param string $data['companyName'] Firmenname
 * @param string $data['dbName'] Datenbankname
 * @param string $data['skr'] Kontenrahmen: "skr03" oder "skr04"
 * @testdata {"companyName": "Testfirma", "dbName": "oserp_testfirma", "skr": "skr03"}
 */
function createCompany($data) {
    // ── 1. Berechtigung prüfen ──
    $auth = DbhAuth::begin();
    $auth->fetchSessionData();
    mdAuthEnsureClientMetadata($auth);
    $login = $auth->getLogin();

    if (!canUserCreateCompany($login, $auth)) {
        resultInfo(false, 'PERMISSION_DENIED', 'Keine Berechtigung zum Anlegen von Firmen');
        return;
    }

    // ── 2. Parameter validieren ──
    $companyName = trim($data['companyName'] ?? '');
    $dbName = trim($data['dbName'] ?? '');
    $skr = trim($data['skr'] ?? '');
    $companyNumber = mdAuthNormalizeCompanyNumber($data['companyNumber'] ?? '');
    if ($companyNumber === '') {
        $companyNumber = mdAuthNextCompanyNumber($auth);
    }

    if ($companyName === '' || $dbName === '') {
        resultInfo(false, 'VALIDATION_ERROR', 'Firmenname und Datenbankname sind erforderlich');
        return;
    }

    if (!in_array($skr, ['skr03', 'skr04'], true)) {
        resultInfo(false, 'VALIDATION_ERROR', 'Kontenrahmen muss skr03 oder skr04 sein');
        return;
    }

    // Datenbankname: nur lowercase, Ziffern, Unterstriche
    if (!preg_match('/^[a-z][a-z0-9_]*$/', $dbName)) {
        resultInfo(false, 'VALIDATION_ERROR', 'Datenbankname darf nur Kleinbuchstaben, Ziffern und Unterstriche enthalten und muss mit einem Buchstaben beginnen');
        return;
    }

    if (!preg_match('/^[0-9]+$/', $companyNumber) || intval($companyNumber) < 2200) {
        resultInfo(false, 'VALIDATION_ERROR', 'Firmennummer muss eine Zahl ab 2200 sein');
        return;
    }

    // Prüfe ob Firmenname schon existiert
    $existing = $auth->getOne(
        "SELECT id FROM auth.clients WHERE name = :name",
        [':name' => $companyName]
    );
    if ($existing) {
        resultInfo(false, 'COMPANY_NAME_EXISTS', "Firmenname '$companyName' bereits vergeben");
        return;
    }

    $existingNumber = $auth->getOne(
        "SELECT id FROM auth.clients WHERE company_number = :company_number",
        [':company_number' => $companyNumber]
    );
    if ($existingNumber) {
        resultInfo(false, 'COMPANY_NUMBER_EXISTS', "Firmennummer '$companyNumber' bereits vergeben");
        return;
    }

    // ── 3. DB-Credentials von aktueller Firma holen ──
    $credentials = $auth->getOne(
        "SELECT c.dbhost, c.dbport, c.dbuser, c.dbpasswd
         FROM auth.session_oserp s
         JOIN auth.clients c ON s.client_id = c.id
         WHERE s.session_id = :session_id",
        [':session_id' => $auth->getCookie()]
    );

    if (!$credentials) {
        resultInfo(false, 'SESSION_ERROR', 'Aktuelle Firmen-Credentials konnten nicht ermittelt werden');
        return;
    }

    $dbHost = $credentials['dbhost'];
    $dbPort = $credentials['dbport'];
    $dbUser = $credentials['dbuser'];
    $dbPass = $credentials['dbpasswd'];

    // ── 4. Prüfe ob Firmenname oder Datenbank schon existiert ──
    $authPdo = $auth->getPDO();
    try {
        $checkStmt = $authPdo->prepare("SELECT 1 FROM pg_database WHERE datname = :dbname");
        $checkStmt->execute([':dbname' => $dbName]);
        if ($checkStmt->fetch()) {
            resultInfo(false, 'DATABASE_EXISTS', "Datenbank '$dbName' existiert bereits");
            return;
        }
    } catch (PDOException $e) {
        resultInfo(false, 'DATABASE_ERROR', $e->getMessage());
        return;
    }

    // ── 5. Datenbank erstellen ──
    // CREATE DATABASE kann nicht in einer Transaktion ausgeführt werden
    $quotedDbName = quoteIdentifier($dbName);
    $quotedDbUser = quoteIdentifier($dbUser);
    try {
        $authPdo->exec("CREATE DATABASE {$quotedDbName} OWNER {$quotedDbUser}");
        writeLog("Datenbank '$dbName' erstellt", true, DLOG_INF);
    } catch (PDOException $e) {
        resultInfo(false, 'DATABASE_ERROR', $e->getMessage());
        return;
    }

    // ── 6. Schema direkt einspielen (leere DB, korrekte Reihenfolge aus pg_dump) ──
    try {
        $newDbPdo = connectPDO($dbHost, $dbPort, $dbName, $dbUser, $dbPass);

        // SKR-Schema einspielen
        $skrSchemaFile = __DIR__ . '/../../upstall/' . $skr . '/company_schema.sql';
        if (!file_exists($skrSchemaFile)) {
            throw new Exception("Schema-Datei nicht gefunden: $skrSchemaFile");
        }

        $schemaSql = file_get_contents($skrSchemaFile);
        $newDbPdo->exec($schemaSql);
        writeLog("SKR-Schema '$skr' in '$dbName' eingespielt", true, DLOG_INF);

        // ── 7. CRM-Upstall auf neuer DB ausführen (inkrementell) ──
        $newDb = new ApiDatabase($newDbPdo);
        require_once __DIR__.'/../update/update.php';

        $crmSchemaFile = __DIR__ . '/../../upstall/crm/company_schema.sql';
        $lxcarsSchemaFile = __DIR__ . '/../../upstall/lxcars/company_schema.sql';
        $schemaFiles = [$crmSchemaFile];
        if (file_exists($lxcarsSchemaFile)) {
            $schemaFiles[] = $lxcarsSchemaFile;
        }
        $csvFiles = ['auth' => [], 'company' => []];

        $crmCsvDir = __DIR__ . '/../../upstall/crm/company_data/';
        if (is_dir($crmCsvDir)) {
            $csvFilesFound = glob($crmCsvDir . '*.csv');
            foreach ($csvFilesFound as $csvFile) {
                $csvFiles['company'][] = $csvFile;
            }
        }

        $lxcarsCsvDir = __DIR__ . '/../../upstall/lxcars/company_data/';
        if (is_dir($lxcarsCsvDir)) {
            $csvFilesFound = glob($lxcarsCsvDir . '*.csv');
            foreach ($csvFilesFound as $csvFile) {
                $csvFiles['company'][] = $csvFile;
            }
        }

        $upstallResult = updateDatabaseSchema($schemaFiles, $csvFiles, false, $newDb);
        if (!$upstallResult['success']) {
            $errors = implode('; ', $upstallResult['errors'] ?? []);
            writeLog("MotorDesk-Upstall fuer '$dbName' fehlgeschlagen: " . $errors, true, DLOG_ERR);
            throw new Exception("MotorDesk-Schema konnte nicht vollstaendig erstellt werden: " . $errors);
        }
        writeLog("MotorDesk-Upstall auf '$dbName' ausgefuehrt", true, DLOG_INF);

        // ── 8. Vollständigen DATEV-Kontenrahmen ergänzen (idempotent, + Reparatur fehlender taxkeys) ──
        // Der Seed-Dump enthält nur einen reduzierten Starter-Kontenrahmen; hier wird er auf den
        // vollen SKR03/SKR04-Standard gebracht. Schlägt der Import fehl, bleibt die Firma mit dem
        // Basis-Kontenrahmen nutzbar (Warnung statt Abbruch).
        require_once __DIR__.'/../accounting/chart_import.php';
        try {
            $chartReport = importChartMaster($newDb, $skr, ['mode' => 'fix', 'dry_run' => false]);
            writeLog("Kontenrahmen '$skr' ergänzt: {$chartReport['summary']['added']} neu, "
                . "{$chartReport['summary']['repaired_taxkeys']} repariert, "
                . "{$chartReport['summary']['conflicts']} Konflikte", true, DLOG_INF);
        } catch (\Throwable $chartEx) {
            writeLog("Kontenrahmen-Import für '$dbName' fehlgeschlagen: " . $chartEx->getMessage(), true, DLOG_WRN);
        }

    } catch (Exception $e) {
        // Datenbank wieder löschen bei Fehler
        try {
            $authPdo->exec("DROP DATABASE IF EXISTS {$quotedDbName}");
        } catch (PDOException $dropEx) {
            writeLog("Konnte fehlgeschlagene DB '$dbName' nicht löschen: " . $dropEx->getMessage(), true, DLOG_ERR);
        }
        resultInfo(false, 'SCHEMA_ERROR', $e->getMessage());
        return;
    }

    // ── 8. Firma in auth.clients registrieren + Benutzer zuordnen ──
    try {
        $newClientId = $auth->getOne(
            "INSERT INTO auth.clients (
                 name, company_number, dbhost, dbport, dbname, dbuser, dbpasswd,
                 is_system, master_data_locked, verification_status, setup_status
             )
             VALUES (
                 :name, :company_number, :dbhost, :dbport, :dbname, :dbuser, :dbpasswd,
                 false, true, 'pending', 'needs_review'
             )
             RETURNING id",
            [
                ':name' => $companyName,
                ':company_number' => $companyNumber,
                ':dbhost' => $dbHost,
                ':dbport' => $dbPort,
                ':dbname' => $dbName,
                ':dbuser' => $dbUser,
                ':dbpasswd' => $dbPass
            ]
        );
        writeLog("Firma '$companyName' in auth.clients registriert (DB: $dbName)", true, DLOG_INF);

        // Aktuellen Benutzer der neuen Firma zuordnen
        $userId = $auth->getUserId();
        $clientId = $auth->getClientId();
        $newCid = $newClientId['id'];
        $auth->execute(
            "INSERT INTO auth.clients_users (client_id, user_id) VALUES (:client_id, :user_id)",
            [':client_id' => $newCid, ':user_id' => $userId]
        );
        writeLog("Benutzer '$login' (ID: $userId) der Firma '$companyName' zugeordnet", true, DLOG_INF);

        // Gruppen des Benutzers aus aktueller Firma für neue Firma übernehmen
        $auth->execute(
            "INSERT INTO auth.clients_groups (client_id, group_id)
             SELECT :new_client_id, cg.group_id
             FROM auth.clients_groups cg
             JOIN auth.user_group ug ON cg.group_id = ug.group_id
             WHERE cg.client_id = :current_client_id
             AND ug.user_id = :user_id",
            [':new_client_id' => $newCid, ':current_client_id' => $clientId, ':user_id' => $userId]
        );
        writeLog("Berechtigungsgruppen für Firma '$companyName' übernommen", true, DLOG_INF);
    } catch (PDOException $e) {
        resultInfo(false, 'AUTH_ERROR', $e->getMessage());
        return;
    }

    resultInfo(true, 'Firma erfolgreich angelegt', [
        'companyName' => $companyName,
        'companyNumber' => $companyNumber,
        'dbName' => $dbName,
        'skr' => $skr
    ]);
}
