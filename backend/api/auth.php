<?php
// backend/api/auth.php

/**
 * Prüft ob der aktuelle Benutzer neue Firmen anlegen darf
 *
 * @param string $login Login-Name des Benutzers
 * @return bool
 */
function canUserCreateCompany($login, $auth = null) {
    $adminUsers = array_map('trim', explode(',', COMPANY_ADMIN_USERS));
    if (in_array($login, $adminUsers, true)) {
        return true;
    }

    if ($auth instanceof ApiSession && $auth->getClientId()) {
        try {
            mdAuthEnsureClientMetadata($auth);
            $client = $auth->getOne(
                'SELECT is_system FROM auth.clients WHERE id = :client_id',
                [':client_id' => intval($auth->getClientId())]
            );
            if (!empty($client['is_system']) && $auth->checkPermissions(['admin'], false)) {
                return true;
            }
        } catch (\Throwable $e) {
            return false;
        }
    }

    return false;
}

function mdAuthNormalizeCompanyNumber($value) {
    return preg_replace('/[^0-9]+/', '', (string)$value);
}

function mdAuthFallbackCompanyNumber($id) {
    return (string)(2199 + intval($id));
}

function mdAuthEnsureClientMetadata($auth) {
    $auth->execute("ALTER TABLE auth.clients ADD COLUMN IF NOT EXISTS company_number text");
    $auth->execute("ALTER TABLE auth.clients ADD COLUMN IF NOT EXISTS is_system boolean NOT NULL DEFAULT false");
    $auth->execute("ALTER TABLE auth.clients ADD COLUMN IF NOT EXISTS master_data_locked boolean NOT NULL DEFAULT true");
    $auth->execute("ALTER TABLE auth.clients ADD COLUMN IF NOT EXISTS verification_status text NOT NULL DEFAULT 'pending'");
    $auth->execute("ALTER TABLE auth.clients ADD COLUMN IF NOT EXISTS setup_status text NOT NULL DEFAULT 'needs_review'");
    $auth->execute("UPDATE auth.clients SET is_system = false WHERE is_system IS NULL");
    $auth->execute("
        WITH only_client AS (
            SELECT id
            FROM auth.clients
            WHERE (SELECT COUNT(*) FROM auth.clients) = 1
            LIMIT 1
        )
        UPDATE auth.clients
        SET is_system = true,
            company_number = '0',
            master_data_locked = true,
            verification_status = 'verified',
            setup_status = 'operator',
            is_default = true,
            mtime = now()
        WHERE id IN (SELECT id FROM only_client)
          AND (
              company_number IS NULL
              OR btrim(company_number) = ''
              OR company_number = '2200'
              OR company_number ~ '^MD-[0-9]+$'
          )
    ");
    $auth->execute("
        UPDATE auth.clients
        SET company_number = (2199 + substring(company_number FROM '^MD-0*([0-9]+)$')::integer)::text
        WHERE company_number ~ '^MD-[0-9]+$'
          AND COALESCE(is_system, false) = false
    ");
    $auth->execute("
        UPDATE auth.clients
        SET company_number = (2199 + id)::text
        WHERE (company_number IS NULL OR btrim(company_number) = '')
          AND COALESCE(is_system, false) = false
    ");
    $auth->execute("UPDATE auth.clients SET master_data_locked = true WHERE master_data_locked IS NULL");
    $auth->execute("UPDATE auth.clients SET verification_status = 'pending' WHERE verification_status IS NULL OR verification_status = ''");
    $auth->execute("UPDATE auth.clients SET setup_status = 'needs_review' WHERE setup_status IS NULL OR setup_status = ''");
    $auth->execute("CREATE UNIQUE INDEX IF NOT EXISTS auth_clients_company_number_key ON auth.clients (company_number)");
}

function mdAuthNextCompanyNumber($auth) {
    mdAuthEnsureClientMetadata($auth);
    $row = $auth->getOne("
        SELECT GREATEST(COALESCE(MAX(company_number::integer) + 1, 2200), 2200) AS next_num
        FROM auth.clients
        WHERE company_number ~ '^[0-9]+$'
          AND COALESCE(is_system, false) = false
    ");
    return (string)intval($row['next_num'] ?? 2200);
}

function mdAuthResolveClientId($auth, $value): int {
    mdAuthEnsureClientMetadata($auth);
    $raw = trim((string)$value);
    $companyNumber = mdAuthNormalizeCompanyNumber($raw);

    if ($companyNumber !== '') {
        $client = $auth->getOne(
            'SELECT id FROM auth.clients WHERE company_number = :company_number',
            [':company_number' => $companyNumber]
        );
        if ($client) {
            return intval($client['id']);
        }
    }

    if (preg_match('/^[0-9]+$/', $raw)) {
        $client = $auth->getOne(
            'SELECT id FROM auth.clients WHERE id = :id',
            [':id' => intval($raw)]
        );
        if ($client) {
            return intval($client['id']);
        }
    }

    throw new ApiError('CLIENT_NOT_FOUND', 'Firma mit dieser Firmennummer wurde nicht gefunden');
}

function mdAuthEnsureUserClientAdminAccess($auth, int $clientId, int $userId) {
    $group = $auth->getOne(
        'INSERT INTO auth."group" (name, mtime)
         VALUES (:name, now())
         ON CONFLICT (name) DO UPDATE SET mtime = now()
         RETURNING id',
        [':name' => 'Administratoren']
    );
    $groupId = intval($group['id']);

    $auth->execute(
        'INSERT INTO auth.clients_users (client_id, user_id)
         VALUES (:client_id, :user_id)
         ON CONFLICT DO NOTHING',
        [':client_id' => $clientId, ':user_id' => $userId]
    );
    $auth->execute(
        'INSERT INTO auth.clients_groups (client_id, group_id)
         VALUES (:client_id, :group_id)
         ON CONFLICT DO NOTHING',
        [':client_id' => $clientId, ':group_id' => $groupId]
    );
    $auth->execute(
        'INSERT INTO auth.user_group (user_id, group_id)
         VALUES (:user_id, :group_id)
         ON CONFLICT DO NOTHING',
        [':user_id' => $userId, ':group_id' => $groupId]
    );

    foreach (['admin', 'special_access', 'customer_vendor_edit', 'customer_vendor_all_edit', 'invoice_edit', 'sales_order_edit', 'sales_quotation_edit', 'sales_delivery_order_edit'] as $right) {
        $auth->execute(
            'INSERT INTO auth.group_rights (group_id, "right", granted)
             VALUES (:group_id, :right, true)
             ON CONFLICT (group_id, "right") DO UPDATE SET granted = true',
            [':group_id' => $groupId, ':right' => $right]
        );
    }
}

/**
 * Stellt eine direkte Verbindung zur Firmen-Datenbank eines Mandanten her.
 */
function mdAuthCompanyDbForClient($auth, int $clientId): ApiDatabase {
    $client = $auth->getOne(
        'SELECT dbhost, dbport, dbname, dbuser, dbpasswd
         FROM auth.clients
         WHERE id = :client_id',
        [':client_id' => $clientId]
    );

    if (!$client) {
        throw new ApiError('CLIENT_NOT_FOUND', 'Firma wurde nicht gefunden');
    }

    return new ApiDatabase(connectPDO(
        $client['dbhost'],
        $client['dbport'],
        $client['dbname'],
        $client['dbuser'],
        $client['dbpasswd']
    ));
}

function mdAuthEnsureCompanyEmployeeForUser($auth, int $clientId, int $userId, string $login): void {
    $user = $auth->getOne(
        'SELECT name, email FROM auth."user" WHERE id = :user_id',
        [':user_id' => $userId]
    ) ?: [];

    try {
        $company = mdAuthCompanyDbForClient($auth, $clientId);
        $company->execute(
            "INSERT INTO employee (login, name, sales, deleted, deleted_email, itime)
             VALUES (:login, :name, true, false, :email, now())
             ON CONFLICT (login) DO UPDATE SET
                 name = COALESCE(NULLIF(:name_update, ''), employee.name),
                 deleted = false,
                 sales = true,
                 deleted_email = COALESCE(NULLIF(:email_update, ''), employee.deleted_email),
                 mtime = now()",
            [
                ':login' => $login,
                ':name' => trim($user['name'] ?? '') ?: $login,
                ':email' => trim($user['email'] ?? ''),
                ':name_update' => trim($user['name'] ?? ''),
                ':email_update' => trim($user['email'] ?? ''),
            ]
        );
    } catch (\Throwable $e) {
        writeLog('Panel-Wechsel: Mitarbeiter konnte nicht vorbereitet werden: ' . $e->getMessage(), true, DLOG_ERR);
        throw new ApiError('CLIENT_DATABASE_NOT_READY', 'Das Panel ist noch nicht vollstaendig vorbereitet. Bitte Datenbank-Update im Admin Hub ausfuehren.');
    }
}

/**
 * Laedt die Liste aller verfuegbaren Mandanten
 *
 * @param array $data Eingabedaten (wird nicht verwendet)
 * @return void Gibt JSON mit Mandantenliste aus
 */
function getClients($data) {
    $session = DbhAuth::begin();
    mdAuthEnsureClientMetadata($session);

    $query = <<<SQL
        SELECT json_agg(clients) AS clients
        FROM (
            SELECT
                id AS code,
                name,
                company_number,
                company_number || ' - ' || name AS login_label,
                is_default,
                is_system
            FROM auth.clients
            WHERE COALESCE(is_system, false) = false
            ORDER BY company_number, name
        ) AS clients
    SQL;

    $result = $session->getOne($query, array());
    $clients = json_decode($result['clients'] ?? '[]', true) ?: [];

    resultInfo(true, '', [
        'clients'  => $clients,
        'is_demo'  => defined('DEMO_MODE') && DEMO_MODE
    ]);
}

/**
 * Lädt die Benutzerkonfiguration aus der auth-Datenbank
 *
 * @param bool $json Wenn true, wird das Ergebnis als JSON zurückgegeben
 * @return array|string Benutzerdaten als Array oder JSON-String
 */
function getAuthUserData($json = false) {
    $auth = DbhAuth::begin();
    $userId = $auth->getUserId();

    if ($json) {
        $query = <<<SQL
            SELECT json_object_agg(cfg_key, cfg_value) AS user_data
            FROM auth.user_config
            WHERE user_id = :user_id;
        SQL;
        return $auth->get($query, [':user_id' => $userId]);
    } else {
        $query = <<<SQL
            SELECT *
            FROM auth.user_config
            WHERE user_id = :user_id
        SQL;
    }

    $result = $auth->getAll($query, [':user_id' => $userId]);
    $userData = array();
    foreach ($result as $row) {
        $userData[$row['cfg_key']] = $row['cfg_value'];
    }
    $result = $userData;
    return $result;
}

/**
 * Authentifiziert einen Benutzer und erstellt eine Session
 *
 * @param array $data Array mit 'username', 'password', 'client' und optional 'remember_me'
 * @return void Gibt JSON mit Login-Daten und CV-Daten aus
 * @throws ApiError Bei fehlenden Argumenten, ungültigem Benutzer oder falschem Passwort
 * @testdata {"username": "demo", "password": "demo", "client": 1, "remember_me": false}
 */
function login($data) {
    if (!isset($data['username']) || !isset($data['password']) || !isset($data['client'])) {
        resultInfo(false, "MISSING_ARGUMENTS");
        return;
    }

    $auth = DbhAuth::begin();
    mdAuthEnsureClientMetadata($auth);

    $username = $data['username'];
    $query = <<<SQL
        SELECT *
        FROM auth.user
        WHERE login = :username
    SQL;
    $context = $auth->getOne($query, [':username' => $username]);

    if (!$context) {
        throw new ApiError("USER_NOT_FOUND", "Benutzer nicht gefunden");
    }

    $storedHash = $context['password'];
    $userId = $context['id'];
    $clientId = mdAuthResolveClientId($auth, $data['client']);
    $cleartextPassword = $data['password'];
    $rememberMe = !empty($data['remember_me']);

    $auth->setUserId($userId);
    $auth->setClientId($clientId);

    $query = <<<SQL
        SELECT name, company_number, is_system
        FROM auth.clients_users cu
        JOIN auth.clients c ON cu.client_id = c.id
        WHERE client_id = :client_id
        AND user_id = :user_id
    SQL;
    $clientName = $auth->getAll($query, [
        ':client_id' => $clientId,
        ':user_id' => $userId
    ]);

    if (empty($clientName)) {
        throw new ApiError("USER_NOT_ASSIGNED_TO_CLIENT", "Benutzer nicht dem Mandanten zugeordnet");
    }

    $loginData = array();

    if (verify_password($username, $cleartextPassword, $storedHash)) {
        if (!$auth->hasSession()) {
            $sessionId = bin2hex(random_bytes(16));
            $auth->setCookie($sessionId);
        } else {
            $sessionId = $auth->getCookie();
        }

        // Ohne remember_me: reiner Session-Cookie (stirbt mit dem Browser)
        // Mit remember_me: persistenter Cookie, der in restoreSession() bei Aktivitaet mit-gesliden wird
        $cookieOptions = ['path' => '/', 'samesite' => COOKIE_SAME_SITE, 'httponly' => true];
        if ($rememberMe) {
            $cookieOptions['expires'] = time() + 120 * 3600;
        }
        setcookie(SESSION_COOKIE, $sessionId, $cookieOptions);

        $query = <<<SQL
            INSERT INTO auth.session_oserp (session_id, user_id, client_id, remember_me)
            VALUES (:session_id, :user_id, :client_id, :remember_me)
            ON CONFLICT (session_id) DO UPDATE SET
                user_id = :user_id,
                client_id = :client_id,
                active = NOW(),
                remember_me = :remember_me
        SQL;
        $auth->execute($query, [
            ':session_id' => $sessionId,
            ':user_id' => $userId,
            ':client_id' => $clientId,
            ':remember_me' => $rememberMe,
        ]);

        $dbhCompany = DbhCompany::begin();
        $query = <<<SQL
            SELECT id
            FROM employee
            WHERE login = :username
        SQL;
        $employeeId = $dbhCompany->getOne($query, [':username' => $username]);

        $loginData = array(
            "login" => $context['login'],
            "client" => $clientName[0]['name'],
            "company_number" => $clientName[0]['company_number'],
            "is_system_client" => !empty($clientName[0]['is_system']),
            "user_id" => $userId,
            "client_id" => $clientId,
            "employee_id" => $employeeId,
            "auth_user_data" => getAuthUserData(),
            "permissions" => $auth->fetchAllPermissions(),
            "auth_groups" => $auth->fetchClientGroups(),
            "is_demo" => defined('DEMO_MODE') && DEMO_MODE,
            "demo_inactivity_minutes" => defined('DEMO_INACTIVITY_MINUTES') ? DEMO_INACTIVITY_MINUTES : 20,
            "can_create_company" => canUserCreateCompany($context['login'], $auth)
        );
    } else {
        throw new ApiError("WRONG_PASSWORD", "Falsches Passwort");
    }

    require __DIR__ . '/customer_vendor/customer_vendor.php';
    getCV($data, $loginData);
}

/**
 * Gibt die Git-Commit-Hashes des lokalen und Remote-Repos zurück
 *
 * @return array ['local' => string|false, 'remote' => string|false]
 */
function getGitHashes() {
    $local = shell_exec('git rev-parse HEAD 2>/dev/null');
    $remote = shell_exec('git ls-remote origin -h refs/heads/main | cut -f1 2>/dev/null');
    return [
        'local' => $local ? trim($local) : false,
        'remote' => $remote ? trim($remote) : false
    ];
}

/**
 * Stellt eine bestehende Session wieder her
 *
 * @param array $data Eingabedaten (kann Filter für CV-Daten enthalten)
 * @return void Gibt JSON mit Session-Daten und CV-Daten aus
 * @throws ApiError Bei ungültiger Session
 */
function restoreSession($data) {

    if (!setupExists()) {
        resultInfo(false, 'SETUP_REQUIRED', null, 'Setup not completed', false);
        return;
    }

    $session = DbhAuth::begin();
    mdAuthEnsureClientMetadata($session);
    $sessionId = $session->getCookie();

    // Abgelaufene Sessions werden vom DB-Trigger cleanup_session_oserp geputzt (120h Inaktivitaet).
    $query = <<<SQL
        SELECT
            user_id,
            client_id,
            remember_me,
            u.login,
            c.name AS client_name,
            c.company_number,
            c.is_system
        FROM auth.session_oserp s
        JOIN auth.user u ON s.user_id = u.id
        JOIN auth.clients c ON s.client_id = c.id
        WHERE s.session_id = :session_id
    SQL;
    $context = $session->getOne($query, [':session_id' => $sessionId]);

    if (!$context) {
        throw new ApiError("INVALID_SESSION", 'Ungültige Sitzung');
    }

    // Session-Aktivitaet aktualisieren (Sliding Window)
    $session->execute(
        "UPDATE auth.session_oserp SET active = NOW() WHERE session_id = :session_id",
        [':session_id' => $sessionId]
    );

    // Cookie-Lifetime fuer "Angemeldet bleiben"-Sessions mit-sliden
    if (!empty($context['remember_me'])) {
        setcookie(SESSION_COOKIE, $sessionId, [
            'expires'  => time() + 120 * 3600,
            'path'     => '/',
            'samesite' => COOKIE_SAME_SITE,
            'httponly' => true,
        ]);
    }

    // Session-Daten setzen (falls noch nicht geschehen)
    if (!$session->getUserId()) {
        $session->setUserId($context['user_id']);
        $session->setClientId($context['client_id']);
    }

    $loginData = array(
        "user_id" => $context['user_id'],
        "client_id" => $context['client_id'],
        "login" => $context['login'],
        "client" => $context['client_name'],
        "company_number" => $context['company_number'],
        "is_system_client" => !empty($context['is_system']),
        "auth_user_data" => getAuthUserData(),
        "permissions" => $session->fetchAllPermissions(),
        "auth_groups" => $session->fetchClientGroups(),
        "is_demo" => defined('DEMO_MODE') && DEMO_MODE,
        "demo_inactivity_minutes" => defined('DEMO_INACTIVITY_MINUTES') ? DEMO_INACTIVITY_MINUTES : 20,
        "can_create_company" => canUserCreateCompany($context['login'], $session)
    );

    require __DIR__ . '/customer_vendor/customer_vendor.php';
    getCV($data, $loginData);
}

/**
 * Wechselt den Mandanten in der bestehenden Session
 *
 * @param int $data['client'] Neue Mandanten-ID
 * @testdata {"client": 1}
 */
function switchClient($data) {
    if (!isset($data['client'])) {
        resultInfo(false, 'MISSING_ARGUMENTS');
        return;
    }

    $auth = DbhAuth::begin();
    mdAuthEnsureClientMetadata($auth);
    $sessionId = $auth->getCookie();

    // Aktuelle Session prüfen
    $query = <<<SQL
        SELECT user_id, u.login
        FROM auth.session_oserp s
        JOIN auth.user u ON s.user_id = u.id
        WHERE s.session_id = :session_id
    SQL;
    $context = $auth->getOne($query, [':session_id' => $sessionId]);

    if (!$context) {
        throw new ApiError('INVALID_SESSION', 'Ungültige Sitzung');
    }

    $userId = $context['user_id'];
    $clientId = (int)$data['client'];

    // Prüfen ob Benutzer dem neuen Mandanten zugeordnet ist
    $query = <<<SQL
        SELECT name, company_number, is_system
        FROM auth.clients_users cu
        JOIN auth.clients c ON cu.client_id = c.id
        WHERE client_id = :client_id
        AND user_id = :user_id
    SQL;
    $clientName = $auth->getAll($query, [
        ':client_id' => $clientId,
        ':user_id' => $userId
    ]);

    if (empty($clientName)) {
        if (!canUserCreateCompany($context['login'], $auth)) {
            throw new ApiError('USER_NOT_ASSIGNED_TO_CLIENT', 'Benutzer nicht der Firma zugeordnet');
        }

        $targetClient = $auth->getOne(
            'SELECT name, company_number, is_system FROM auth.clients WHERE id = :client_id',
            [':client_id' => $clientId]
        );
        if (!$targetClient) {
            throw new ApiError('CLIENT_NOT_FOUND', 'Firma wurde nicht gefunden');
        }

        mdAuthEnsureUserClientAdminAccess($auth, $clientId, intval($userId));
        $clientName = [$targetClient];
    }

    // Session auf neuen Mandanten umschreiben
    $auth->setUserId($userId);
    $auth->setClientId($clientId);

    $auth->execute(
        "UPDATE auth.session_oserp SET client_id = :client_id, active = NOW() WHERE session_id = :session_id",
        [':client_id' => $clientId, ':session_id' => $sessionId]
    );

    mdAuthEnsureCompanyEmployeeForUser($auth, $clientId, intval($userId), $context['login']);

    $dbhCompany = DbhCompany::begin();
    $employeeRow = $dbhCompany->getOne(
        "SELECT id FROM employee WHERE login = :login",
        [':login' => $context['login']]
    );

    $loginData = array(
        'login' => $context['login'],
        'client' => $clientName[0]['name'],
        'company_number' => $clientName[0]['company_number'],
        'is_system_client' => !empty($clientName[0]['is_system']),
        'user_id' => $userId,
        'client_id' => $clientId,
        'employee_id' => isset($employeeRow['id']) ? intval($employeeRow['id']) : null,
        'auth_user_data' => getAuthUserData(),
        'permissions' => $auth->fetchAllPermissions(),
        'auth_groups' => $auth->fetchClientGroups(),
        'is_demo' => defined('DEMO_MODE') && DEMO_MODE,
        'demo_inactivity_minutes' => defined('DEMO_INACTIVITY_MINUTES') ? DEMO_INACTIVITY_MINUTES : 20,
        'can_create_company' => canUserCreateCompany($context['login'], $auth)
    );

    require __DIR__ . '/customer_vendor/customer_vendor.php';
    getCV($data, $loginData);
}

/**
 * Beendet die aktuelle Session
 *
 * @param array $data Eingabedaten (wird nicht verwendet)
 * @return void Gibt JSON mit Erfolgs-Status aus
 * @testdata {}
 */
function logout($data) {
    $session = DbhAuth::begin();
    $sessionId = $session->getCookie();

    $session->execute(
        "DELETE FROM auth.session_oserp WHERE session_id = :session_id",
        [':session_id' => $sessionId]
    );

    setcookie(SESSION_COOKIE, '', [
        'expires'  => time() - 3600,
        'path'     => '/',
        'samesite' => COOKIE_SAME_SITE,
        'httponly' => true,
    ]);

    $session->setUserId(null);

    resultInfo(true);
}
