<template>
  <v-app>
    <v-main class="admin-shell">
      <header class="admin-topbar">
        <a class="admin-brand" href="/">
          <span class="admin-brand__mark">MD</span>
          <span>MotorDesk Admin</span>
        </a>
        <div class="admin-topbar__actions">
          <v-chip size="small" variant="tonal" color="primary">
            {{ sessionLabel }}
          </v-chip>
          <v-btn href="/" variant="tonal" color="primary" prepend-icon="mdi-arrow-left">
            App
          </v-btn>
          <v-btn icon="mdi-logout" variant="text" title="Logout" @click="logout" />
        </div>
      </header>

      <section class="admin-layout">
        <aside class="admin-side">
          <div>
            <p class="admin-kicker">Betreiber</p>
            <h1>Admin</h1>
          </div>

          <div class="admin-stat">
            <span>Firmen</span>
            <strong>{{ clients.length }}</strong>
          </div>
          <div class="admin-stat">
            <span>Benutzer</span>
            <strong>{{ users.length }}</strong>
          </div>
          <div class="admin-stat">
            <span>Einladungen</span>
            <strong>{{ invites.length }}</strong>
          </div>
          <div class="admin-stat">
            <span>Admin-Recht</span>
            <strong>{{ canCreateCompany ? 'Aktiv' : 'Nicht aktiv' }}</strong>
          </div>
        </aside>

        <main class="admin-content">
          <v-alert v-if="errorMessage" type="error" variant="tonal" closable class="mb-4" @click:close="errorMessage = ''">
            {{ errorMessage }}
          </v-alert>

          <v-alert v-if="inviteResult" type="success" variant="tonal" closable class="mb-4" @click:close="inviteResult = null">
            <div class="invite-result">
              <strong>Zugang angelegt</strong>
              <span>Login: {{ inviteResult.credentials.login }}</span>
              <span v-if="inviteResult.credentials.password">Passwort: {{ inviteResult.credentials.password }}</span>
              <span>{{ inviteResult.email_delivery.message }}</span>
              <v-btn size="small" variant="tonal" color="primary" @click="copyInvite(inviteResult)">
                <v-icon start>mdi-content-copy</v-icon>
                Kopieren
              </v-btn>
            </div>
          </v-alert>

          <div class="admin-section-head">
            <div>
              <p class="admin-kicker">Mandanten</p>
              <h2>Firmen</h2>
            </div>
            <div class="admin-actions">
              <v-btn
                color="primary"
                variant="tonal"
                prepend-icon="mdi-account-plus"
                :disabled="!canCreateCompany || clients.length === 0"
                @click="openInviteDialog()"
              >
                Admin einladen
              </v-btn>
              <v-btn
                color="primary"
                variant="flat"
                prepend-icon="mdi-domain-plus"
                :disabled="!canCreateCompany"
                @click="openCreateCompanyDialog"
              >
                Neue Firma
              </v-btn>
            </div>
          </div>

          <div v-if="loading" class="admin-loading">
            <v-progress-circular indeterminate color="primary" />
          </div>

          <div v-else class="company-grid">
            <v-card
              v-for="client in clients"
              :key="client.code"
              class="company-card"
              variant="outlined"
              @click="switchClient(client)"
            >
              <v-card-text>
                <div class="company-card__top">
                  <v-icon color="primary">mdi-domain</v-icon>
                  <v-chip v-if="client.name === activeClientName" size="x-small" color="primary" variant="flat">
                    Aktiv
                  </v-chip>
                </div>
                <h3>{{ client.name }}</h3>
                <p>{{ client.dbname || client.code }}</p>
                <div class="company-card__meta">
                  <span>{{ client.assigned_users || 0 }} Benutzer</span>
                  <v-btn
                    size="small"
                    variant="text"
                    color="primary"
                    @click.stop="openInviteDialog(client)"
                  >
                    Einladen
                  </v-btn>
                </div>
              </v-card-text>
            </v-card>
          </div>

          <div class="admin-section-head mt-8">
            <div>
              <p class="admin-kicker">Zugaenge</p>
              <h2>Einladungen</h2>
            </div>
          </div>

          <div class="invite-list">
            <div v-if="!invites.length" class="empty-state">
              Noch keine Einladungen.
            </div>
            <div v-for="invite in invites" v-else :key="invite.id" class="invite-row">
              <v-icon color="primary">{{ invite.role === 'setup_agent' ? 'mdi-account-hard-hat' : 'mdi-shield-account' }}</v-icon>
              <div>
                <strong>{{ invite.name || invite.email }}</strong>
                <span>{{ invite.email }} · {{ invite.client_name || 'Firma geloescht' }}</span>
              </div>
              <v-chip size="small" variant="tonal" :color="invite.email_sent ? 'success' : 'warning'">
                {{ invite.email_sent ? 'Mail gesendet' : 'Mail offen' }}
              </v-chip>
            </div>
          </div>

          <div class="admin-section-head mt-8">
            <div>
              <p class="admin-kicker">Verwaltung</p>
              <h2>Bereiche</h2>
            </div>
          </div>

          <div class="tool-grid">
            <a v-for="tool in tools" :key="tool.href" class="tool-card" :href="tool.href">
              <v-icon color="primary">{{ tool.icon }}</v-icon>
              <span>{{ tool.title }}</span>
            </a>
          </div>

          <div class="admin-section-head mt-8">
            <div>
              <p class="admin-kicker">Vorlagen</p>
              <h2>Editor & Dokumente</h2>
            </div>
            <v-chip size="small" color="primary" variant="tonal">Naechste Phase</v-chip>
          </div>

          <div class="editor-grid">
            <div v-for="editor in templateEditors" :key="editor.title" class="editor-card">
              <v-icon color="primary">{{ editor.icon }}</v-icon>
              <div>
                <strong>{{ editor.title }}</strong>
                <span>{{ editor.text }}</span>
              </div>
            </div>
          </div>
        </main>
      </section>

      <v-dialog v-model="showCreateCompanyDialog" max-width="520" persistent>
        <v-card>
          <v-card-title class="bg-primary text-white">
            <v-icon start>mdi-domain-plus</v-icon>
            Neue Firma
          </v-card-title>
          <v-card-text class="pa-4">
            <v-text-field
              ref="createCompanyNameRef"
              v-model="createCompanyName"
              label="Firmenname"
              variant="outlined"
              density="comfortable"
              class="mb-3"
              :error-messages="createCompanyError"
              @update:model-value="handleCompanyNameInput"
              @keydown.enter="canSubmitCompany && doCreateCompany()"
            />
            <v-text-field
              v-model="createCompanyDbName"
              label="Datenbankname"
              variant="outlined"
              density="comfortable"
              class="mb-3"
              hint="Nur Kleinbuchstaben, Zahlen und Unterstriche"
              persistent-hint
              @keydown.enter="canSubmitCompany && doCreateCompany()"
            />
            <v-select
              v-model="createCompanySkr"
              :items="skrOptions"
              label="Kontenrahmen"
              variant="outlined"
              density="comfortable"
            />
          </v-card-text>
          <v-card-actions>
            <v-btn @click="closeCreateCompanyDialog">Abbrechen</v-btn>
            <v-spacer />
            <v-btn
              color="primary"
              variant="flat"
              :loading="createCompanyLoading"
              :disabled="!canSubmitCompany"
              @click="doCreateCompany"
            >
              <v-icon start>mdi-check</v-icon>
              Anlegen
            </v-btn>
          </v-card-actions>
        </v-card>
      </v-dialog>

      <v-dialog v-model="showInviteDialog" max-width="560" persistent>
        <v-card>
          <v-card-title class="bg-primary text-white">
            <v-icon start>mdi-account-plus</v-icon>
            Admin-Zugang einladen
          </v-card-title>
          <v-card-text class="pa-4">
            <v-select
              v-model="inviteClientId"
              :items="clients"
              item-title="name"
              item-value="code"
              label="Firma"
              variant="outlined"
              density="comfortable"
              class="mb-3"
            />
            <v-text-field
              ref="inviteEmailRef"
              v-model="inviteEmail"
              label="E-Mail"
              type="email"
              variant="outlined"
              density="comfortable"
              class="mb-3"
              :error-messages="inviteError"
              @update:model-value="inviteError = ''"
            />
            <v-text-field
              v-model="inviteName"
              label="Name"
              variant="outlined"
              density="comfortable"
              class="mb-3"
            />
            <v-text-field
              v-model="inviteLogin"
              label="Login optional"
              variant="outlined"
              density="comfortable"
              class="mb-3"
              hint="Leer lassen, wenn aus der E-Mail automatisch erzeugt werden soll."
              persistent-hint
            />
            <v-select
              v-model="inviteRole"
              :items="roleOptions"
              label="Rolle"
              variant="outlined"
              density="comfortable"
              class="mb-3"
            />
            <v-switch
              v-model="inviteSendEmail"
              color="primary"
              inset
              label="Einladung direkt per E-Mail senden, wenn SMTP konfiguriert ist"
              hide-details
              class="mb-2"
            />
            <v-switch
              v-model="inviteResetPassword"
              color="primary"
              inset
              label="Bei bestehendem Benutzer neues Startpasswort setzen"
              hide-details
            />
          </v-card-text>
          <v-card-actions>
            <v-btn @click="closeInviteDialog">Abbrechen</v-btn>
            <v-spacer />
            <v-btn
              color="primary"
              variant="flat"
              :loading="inviteLoading"
              :disabled="!canSubmitInvite"
              @click="doCreateInvite"
            >
              <v-icon start>mdi-send</v-icon>
              Zugang anlegen
            </v-btn>
          </v-card-actions>
        </v-card>
      </v-dialog>
    </v-main>
  </v-app>
</template>

<script setup>
import axios from 'axios'
import { computed, nextTick, onMounted, ref } from 'vue'
import { oserpStore } from '@/core/stores/oserp.store.js'
import { AuthStatus } from '@/core/constants/auth.js'

const oserp = oserpStore()

const loading = ref(true)
const errorMessage = ref('')
const clients = ref([])
const users = ref([])
const invites = ref([])
const inviteResult = ref(null)

const showCreateCompanyDialog = ref(false)
const createCompanyNameRef = ref(null)
const createCompanyName = ref('')
const createCompanyDbName = ref('')
const createCompanySkr = ref('skr03')
const createCompanyError = ref('')
const createCompanyLoading = ref(false)

const showInviteDialog = ref(false)
const inviteEmailRef = ref(null)
const inviteClientId = ref(null)
const inviteEmail = ref('')
const inviteName = ref('')
const inviteLogin = ref('')
const inviteRole = ref('company_admin')
const inviteSendEmail = ref(true)
const inviteResetPassword = ref(true)
const inviteLoading = ref(false)
const inviteError = ref('')

const skrOptions = [
  { title: 'SKR03', value: 'skr03' },
  { title: 'SKR04', value: 'skr04' },
]

const roleOptions = [
  { title: 'Firmen-Admin', value: 'company_admin' },
  { title: 'Setup-Mitarbeiter', value: 'setup_agent' },
]

const tools = [
  { title: 'Firmenkonfiguration', icon: 'mdi-domain-cog', href: '/system/mandantenkonfiguration' },
  { title: 'Benutzer', icon: 'mdi-account-cog', href: '/benutzer/konfiguration' },
  { title: 'Personal', icon: 'mdi-account-group', href: '/personal' },
  { title: 'Fahrzeuge', icon: 'mdi-car-multiple', href: '/fahrzeug' },
  { title: 'Systemupdate', icon: 'mdi-update', href: '/system/aktualisierung' },
  { title: 'Developer Tools', icon: 'mdi-wrench', href: '/system/developer-tools' },
]

const templateEditors = [
  { title: 'Rechnungen', icon: 'mdi-file-document-edit', text: 'Briefkopf, Logo, Fusszeilen und PDF-Vorlagen vorbereiten.' },
  { title: 'Auftraege & Angebote', icon: 'mdi-clipboard-text', text: 'Texte, Positionen, Standardlayout und Uploads zentral pflegen.' },
  { title: 'E-Mail-Texte', icon: 'mdi-email-edit', text: 'Vorlagen fuer Versand, Erinnerungen und Freigabe-Mails einstellen.' },
]

const activeClientName = computed(() => oserp.session.client || '')
const canCreateCompany = computed(() => !!oserp.session.can_create_company)
const canSubmitCompany = computed(() => createCompanyName.value.trim() !== '' && createCompanyDbName.value.trim() !== '')
const canSubmitInvite = computed(() => !!inviteClientId.value && inviteEmail.value.trim() !== '')
const sessionLabel = computed(() => oserp.session.user ? `${oserp.session.user} - ${activeClientName.value}` : 'Nicht angemeldet')

onMounted(async () => {
  await boot()
})

async function boot() {
  loading.value = true
  errorMessage.value = ''
  try {
    const status = await oserp.restoreSession()
    if (status !== AuthStatus.AUTHENTICATED) {
      window.location.href = '/login?redirect=/admin.html'
      return
    }
    await reloadAdminOverview()
  } catch (error) {
    errorMessage.value = readError(error, 'Admin-Daten konnten nicht geladen werden.')
  } finally {
    loading.value = false
  }
}

async function adminPost(payload) {
  const { data } = await axios.post('/api/admin/', payload)
  if (!data.success) {
    throw new Error(data.debug || data.text || 'ADMIN_API_ERROR')
  }
  return data.payload || {}
}

async function reloadAdminOverview() {
  const overview = await adminPost({ action: 'getAdminOverview' })
  clients.value = overview.clients || []
  users.value = overview.users || []
  invites.value = overview.invites || []
}

async function switchClient(client) {
  if (!client?.code || client.name === activeClientName.value) return
  loading.value = true
  errorMessage.value = ''
  try {
    await oserp.switchClient(client.code)
    await reloadAdminOverview()
  } catch (error) {
    errorMessage.value = readError(error, 'Firma konnte nicht gewechselt werden.')
  } finally {
    loading.value = false
  }
}

function openCreateCompanyDialog() {
  createCompanyName.value = ''
  createCompanyDbName.value = ''
  createCompanySkr.value = 'skr03'
  createCompanyError.value = ''
  showCreateCompanyDialog.value = true
  nextTick(() => createCompanyNameRef.value?.focus())
}

function closeCreateCompanyDialog() {
  showCreateCompanyDialog.value = false
  createCompanyName.value = ''
  createCompanyDbName.value = ''
  createCompanyError.value = ''
}

function handleCompanyNameInput() {
  createCompanyError.value = ''
  if (!createCompanyDbName.value) {
    createCompanyDbName.value = slugCompanyName(createCompanyName.value)
  }
}

function slugCompanyName(value) {
  const normalized = String(value || '')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '_')
    .replace(/^_+|_+$/g, '')
  return normalized ? `motordesk_${normalized}` : ''
}

async function doCreateCompany() {
  createCompanyLoading.value = true
  createCompanyError.value = ''
  try {
    const companyName = createCompanyName.value.trim()
    await oserp.createCompany(companyName, createCompanyDbName.value.trim(), createCompanySkr.value)
    closeCreateCompanyDialog()
    await reloadAdminOverview()
  } catch (error) {
    createCompanyError.value = readError(error, 'Firma konnte nicht angelegt werden.')
  } finally {
    createCompanyLoading.value = false
  }
}

function openInviteDialog(client = null) {
  inviteClientId.value = client?.code || clients.value[0]?.code || null
  inviteEmail.value = ''
  inviteName.value = ''
  inviteLogin.value = ''
  inviteRole.value = 'company_admin'
  inviteSendEmail.value = true
  inviteResetPassword.value = true
  inviteError.value = ''
  showInviteDialog.value = true
  nextTick(() => inviteEmailRef.value?.focus())
}

function closeInviteDialog() {
  showInviteDialog.value = false
  inviteError.value = ''
}

async function doCreateInvite() {
  inviteLoading.value = true
  inviteError.value = ''
  inviteResult.value = null
  try {
    const payload = await adminPost({
      action: 'createAdminInvite',
      client_id: inviteClientId.value,
      email: inviteEmail.value.trim(),
      name: inviteName.value.trim(),
      login: inviteLogin.value.trim(),
      role: inviteRole.value,
      send_email: inviteSendEmail.value,
      reset_password: inviteResetPassword.value,
    })
    inviteResult.value = payload
    closeInviteDialog()
    await reloadAdminOverview()
  } catch (error) {
    inviteError.value = readError(error, 'Einladung konnte nicht angelegt werden.')
  } finally {
    inviteLoading.value = false
  }
}

async function copyInvite(result) {
  const credentials = result.credentials || {}
  const text = [
    'MotorDesk Zugang',
    `Login: ${credentials.login || ''}`,
    credentials.password ? `Passwort: ${credentials.password}` : '',
    `Admin-Panel: ${credentials.admin_url || '/admin.html'}`,
  ].filter(Boolean).join('\n')
  try {
    await navigator.clipboard.writeText(text)
  } catch (error) {
    console.warn('Clipboard unavailable', error)
  }
}

async function logout() {
  try {
    await oserp.logout()
  } finally {
    window.location.href = '/login?redirect=/admin.html'
  }
}

function readError(error, fallback) {
  return error?.response?.data?.debug || error?.response?.data?.text || error?.message || error?.code || fallback
}
</script>

<style scoped>
.admin-shell {
  min-height: 100vh;
  background: var(--md-color-canvas);
  color: var(--md-color-ink);
}

.admin-topbar {
  min-height: 64px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  padding: 12px 24px;
  background: var(--md-color-surface);
  border-bottom: 1px solid var(--md-color-line);
}

.admin-brand {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  color: var(--md-color-ink);
  text-decoration: none;
  font-weight: 800;
  letter-spacing: 0;
}

.admin-brand__mark {
  width: 34px;
  height: 34px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 8px;
  background: rgb(var(--v-theme-primary));
  color: rgb(var(--v-theme-on-primary));
  font-size: 0.8rem;
}

.admin-topbar__actions,
.admin-actions {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
}

.admin-layout {
  display: grid;
  grid-template-columns: 280px minmax(0, 1fr);
  gap: 24px;
  padding: 24px;
}

.admin-side,
.admin-content {
  background: var(--md-color-surface);
  border: 1px solid var(--md-color-line);
  border-radius: 8px;
}

.admin-side {
  align-self: start;
  display: grid;
  gap: 16px;
  padding: 20px;
}

.admin-side h1,
.admin-section-head h2,
.company-card h3 {
  margin: 0;
  letter-spacing: 0;
}

.admin-kicker {
  margin: 0 0 4px;
  color: var(--md-color-muted);
  font-size: 0.76rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0;
}

.admin-stat {
  display: grid;
  gap: 4px;
  padding: 12px;
  background: var(--md-color-canvas);
  border: 1px solid var(--md-color-line);
  border-radius: 8px;
}

.admin-stat span,
.company-card p,
.editor-card span,
.invite-row span {
  color: var(--md-color-muted);
  font-size: 0.82rem;
}

.admin-stat strong {
  overflow-wrap: anywhere;
}

.admin-content {
  padding: 20px;
}

.admin-section-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 16px;
}

.admin-loading {
  min-height: 160px;
  display: grid;
  place-items: center;
}

.company-grid,
.tool-grid,
.editor-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
  gap: 12px;
}

.company-card,
.tool-card,
.editor-card,
.invite-row {
  border-radius: 8px;
  background: var(--md-color-surface);
  border: 1px solid var(--md-color-line);
}

.company-card {
  cursor: pointer;
  transition: border-color 0.18s ease, transform 0.18s ease;
}

.company-card:hover,
.tool-card:hover {
  border-color: rgb(var(--v-theme-primary));
  transform: translateY(-1px);
}

.company-card__top,
.company-card__meta {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
}

.company-card__top {
  margin-bottom: 14px;
}

.company-card h3 {
  font-size: 1rem;
  overflow-wrap: anywhere;
}

.company-card p {
  margin: 6px 0 12px;
  overflow-wrap: anywhere;
}

.company-card__meta {
  color: var(--md-color-muted);
  font-size: 0.82rem;
}

.tool-card,
.editor-card,
.invite-row {
  min-height: 82px;
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 16px;
  color: var(--md-color-ink);
  text-decoration: none;
  font-weight: 700;
}

.editor-card div,
.invite-row div,
.invite-result {
  display: grid;
  gap: 4px;
}

.invite-list {
  display: grid;
  gap: 10px;
}

.invite-row {
  min-height: 64px;
  grid-template-columns: auto minmax(0, 1fr) auto;
}

.empty-state {
  padding: 20px;
  color: var(--md-color-muted);
  border: 1px dashed var(--md-color-line);
  border-radius: 8px;
}

@media (max-width: 860px) {
  .admin-topbar,
  .admin-section-head {
    align-items: flex-start;
    flex-direction: column;
  }

  .admin-layout {
    grid-template-columns: 1fr;
    padding: 16px;
  }

  .admin-topbar__actions,
  .admin-actions {
    width: 100%;
  }

  .invite-row {
    grid-template-columns: auto minmax(0, 1fr);
  }
}
</style>
