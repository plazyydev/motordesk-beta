#!/usr/bin/env bash
# Bootstrap a fresh MotorDesk Docker database without requiring existing dumps.

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
ENV_FILE="${MOTORDESK_ENV_FILE:-$PROJECT_ROOT/.env}"
COMPOSE_FILE="$PROJECT_ROOT/docker-compose.yml"

info() { printf '[INFO] %s\n' "$*"; }
success() { printf '[OK]   %s\n' "$*"; }
warn() { printf '[WARN] %s\n' "$*"; }
error() { printf '[ERR]  %s\n' "$*" >&2; }

if [[ ! -f "$ENV_FILE" ]]; then
    error ".env nicht gefunden: $ENV_FILE"
    echo "Erstelle sie zuerst mit: cp .env.example .env"
    exit 1
fi

set -a
# shellcheck source=/dev/null
source "$ENV_FILE"
set +a

: "${STACK_NAME:=motordesk}"
: "${POSTGRES_USER:=postgres}"
: "${POSTGRES_PASSWORD:?POSTGRES_PASSWORD fehlt in .env}"
: "${DB_AUTH_NAME:=motordesk_auth}"
: "${DB_COMPANY_NAME:=motordesk_company}"
: "${WEB_HTTP_PORT:=8080}"
: "${DOMAIN:=localhost}"
: "${MOTORDESK_CHART:=skr04}"
: "${CLIENT_NAME:=MotorDesk}"
: "${ADMIN_LOGIN:=admin}"
: "${ADMIN_PASSWORD:=admin}"
: "${ADMIN_NAME:=Administrator}"

case "$MOTORDESK_CHART" in
    skr03|skr04) ;;
    *)
        error "MOTORDESK_CHART muss skr03 oder skr04 sein."
        exit 1
        ;;
esac

if ! command -v docker >/dev/null 2>&1; then
    error "docker wurde nicht gefunden."
    exit 1
fi

if ! command -v sha256sum >/dev/null 2>&1; then
    error "sha256sum wurde nicht gefunden."
    exit 1
fi

detect_compose_project() {
    local explicit_project="${MOTORDESK_COMPOSE_PROJECT:-}"
    local detected_project
    local db_container="${STACK_NAME}-db"

    if [[ -n "$explicit_project" ]]; then
        printf '%s\n' "$explicit_project"
        return
    fi

    detected_project="$(
        docker inspect \
            -f '{{ index .Config.Labels "com.docker.compose.project" }}' \
            "$db_container" 2>/dev/null || true
    )"

    if [[ -n "$detected_project" && "$detected_project" != "<no value>" ]]; then
        printf '%s\n' "$detected_project"
        return
    fi

    printf '%s\n' "$STACK_NAME"
}

COMPOSE_PROJECT="$(detect_compose_project)"

if [[ "$COMPOSE_PROJECT" != "$STACK_NAME" ]]; then
    warn "Nutze vorhandenes Docker-Compose-Projekt '$COMPOSE_PROJECT' fuer Container ${STACK_NAME}-db."
fi

dc() {
    docker compose -p "$COMPOSE_PROJECT" -f "$COMPOSE_FILE" --env-file "$ENV_FILE" "$@"
}

psql_db() {
    local db_name="$1"
    shift
    dc exec -T db psql -v ON_ERROR_STOP=1 -U "$POSTGRES_USER" -d "$db_name" "$@"
}

wait_for_db() {
    local retries=60
    info "Warte auf PostgreSQL..."
    until dc exec -T db pg_isready -U "$POSTGRES_USER" -q >/dev/null 2>&1; do
        retries=$((retries - 1))
        if [[ "$retries" -le 0 ]]; then
            error "PostgreSQL ist nicht rechtzeitig bereit geworden."
            exit 1
        fi
        sleep 1
    done
    success "PostgreSQL ist bereit."
}

database_exists() {
    local db_name="$1"
    local exists
    exists="$(
        dc exec -T db psql \
            -v ON_ERROR_STOP=1 \
            -U "$POSTGRES_USER" \
            -d postgres \
            -v db_name="$db_name" \
            -tA <<'SQL' | tr -d '[:space:]'
SELECT EXISTS (SELECT 1 FROM pg_database WHERE datname = :'db_name');
SQL
    )"
    [[ "$exists" == "t" ]]
}

ensure_database() {
    local db_name="$1"
    if database_exists "$db_name"; then
        success "Datenbank existiert: $db_name"
        return
    fi

    info "Erstelle Datenbank: $db_name"
    dc exec -T db createdb -U "$POSTGRES_USER" "$db_name"
}

table_exists() {
    local db_name="$1"
    local schema_name="$2"
    local table_name="$3"
    local exists
    exists="$(
        psql_db "$db_name" \
            -v schema_name="$schema_name" \
            -v table_name="$table_name" \
            -tA <<'SQL' | tr -d '[:space:]'
SELECT EXISTS (
    SELECT 1
    FROM information_schema.tables
    WHERE table_schema = :'schema_name'
      AND table_name = :'table_name'
);
SQL
    )"
    [[ "$exists" == "t" ]]
}

load_sql_file() {
    local db_name="$1"
    local sql_file="$2"
    local rel_path

    if [[ ! -f "$sql_file" ]]; then
        error "SQL-Datei nicht gefunden: $sql_file"
        exit 1
    fi

    rel_path="${sql_file#"$PROJECT_ROOT/"}"
    info "Lade $rel_path in $db_name"
    dc exec -T db psql -v ON_ERROR_STOP=1 -U "$POSTGRES_USER" -d "$db_name" < "$sql_file"
}

load_sql_from_marker() {
    local db_name="$1"
    local sql_file="$2"
    local marker="$3"
    local rel_path

    if [[ ! -f "$sql_file" ]]; then
        error "SQL-Datei nicht gefunden: $sql_file"
        exit 1
    fi

    if ! grep -Fq "$marker" "$sql_file"; then
        error "Marker nicht gefunden in $sql_file: $marker"
        exit 1
    fi

    rel_path="${sql_file#"$PROJECT_ROOT/"}"
    info "Lade $rel_path ab Marker: $marker"
    awk -v marker="$marker" 'index($0, marker) { found = 1 } found { print }' "$sql_file" \
        | dc exec -T db psql -v ON_ERROR_STOP=1 -U "$POSTGRES_USER" -d "$db_name"
}

ensure_company_compatibility() {
    info "Pruefe Company-Kompatibilitaetsspalten."
    psql_db "$DB_COMPANY_NAME" <<'SQL'
ALTER TABLE customer ADD COLUMN IF NOT EXISTS phone3 text;
ALTER TABLE vendor ADD COLUMN IF NOT EXISTS phone3 text;
SQL
}

seed_admin_employee() {
    info "Lege Admin-Mitarbeiter in $DB_COMPANY_NAME an/aktualisiere ihn."
    psql_db "$DB_COMPANY_NAME" \
        -v admin_login="$ADMIN_LOGIN" \
        -v admin_name="$ADMIN_NAME" <<'SQL'
INSERT INTO employee (login, name, sales, deleted)
SELECT :'admin_login', :'admin_name', true, false
WHERE NOT EXISTS (
    SELECT 1
    FROM employee
    WHERE login = :'admin_login'
);

UPDATE employee
SET name = :'admin_name',
    sales = true,
    deleted = false,
    mtime = now()
WHERE login = :'admin_login';
SQL
}

seed_auth_database() {
    local admin_hash
    admin_hash="${ADMIN_PASSWORD_HASH:-}"
    if [[ -z "$admin_hash" ]]; then
        admin_hash="{SHA256S}$(printf '%s' "${ADMIN_LOGIN}${ADMIN_PASSWORD}" | sha256sum | awk '{print $1}')"
    fi

    info "Lege Auth-Basis, Mandant, Gruppe und Admin-Benutzer an."
    dc exec -T db psql \
        -v ON_ERROR_STOP=1 \
        -U "$POSTGRES_USER" \
        -d "$DB_AUTH_NAME" \
        -v admin_login="$ADMIN_LOGIN" \
        -v admin_hash="$admin_hash" \
        -v admin_name="$ADMIN_NAME" \
        -v client_name="$CLIENT_NAME" \
        -v company_db="$DB_COMPANY_NAME" \
        -v db_user="$POSTGRES_USER" \
        -v db_pass="$POSTGRES_PASSWORD" \
        < "$PROJECT_ROOT/backend/upstall/bootstrap/auth_base.sql"
}

info "Starte/aktualisiere DB-Container."
dc up -d db
wait_for_db

ensure_database "$DB_AUTH_NAME"
ensure_database "$DB_COMPANY_NAME"

if table_exists "$DB_COMPANY_NAME" public employee; then
    success "Company-Basisschema ist bereits vorhanden."
else
    load_sql_file "$DB_COMPANY_NAME" "$PROJECT_ROOT/backend/upstall/$MOTORDESK_CHART/company_schema.sql"
fi

ensure_company_compatibility

CRM_SCHEMA_FILE="$PROJECT_ROOT/backend/upstall/crm/company_schema.sql"
if table_exists "$DB_COMPANY_NAME" public ebay_listings; then
    success "CRM-Erweiterung ist bereits vollstaendig vorhanden."
elif table_exists "$DB_COMPANY_NAME" public features_oserp; then
    warn "CRM-Erweiterung ist unvollstaendig; setze den Import fort."
    load_sql_from_marker \
        "$DB_COMPANY_NAME" \
        "$CRM_SCHEMA_FILE" \
        "DROP TRIGGER IF EXISTS trg_customer_backfill_crmti ON customer;"
else
    load_sql_file "$DB_COMPANY_NAME" "$CRM_SCHEMA_FILE"
fi

if table_exists "$DB_COMPANY_NAME" public cars_lxcars; then
    success "LxCars-Erweiterung ist bereits vorhanden."
else
    load_sql_file "$DB_COMPANY_NAME" "$PROJECT_ROOT/backend/upstall/lxcars/company_schema.sql"
fi

if table_exists "$DB_COMPANY_NAME" public anpr_cameras_lxcars; then
    success "ANPR-Erweiterung ist bereits vorhanden."
else
    load_sql_file "$DB_COMPANY_NAME" "$PROJECT_ROOT/backend/upstall/lxcars/anpr_schema.sql"
fi

seed_admin_employee
seed_auth_database

info "Starte/aktualisiere Web-Container."
dc up -d --build web

success "Fresh Bootstrap abgeschlossen."
echo ""
echo "URL:      http://${DOMAIN}:${WEB_HTTP_PORT}"
echo "Login:    ${ADMIN_LOGIN}"
echo "Passwort: ${ADMIN_PASSWORD}"
echo ""
warn "Aendere das Admin-Passwort nach dem ersten Login."
