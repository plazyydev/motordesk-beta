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
          <v-btn icon="mdi-logout" variant="text" :title="'Logout'" @click="logout" />
        </div>
      </header>

      <section class="admin-layout">
        <aside class="admin-side">
          <div>
            <p class="admin-kicker">System</p>
            <h1>Admin</h1>
          </div>

          <div class="admin-stat">
            <span>Firmen</span>
            <strong>{{ clients.length }}</strong>
          </div>
          <div class="admin-stat">
            <span>Aktive Firma</span>
            <strong>{{ activeClientName || '-' }}</strong>
          </div>
          <div class="admin-stat">
            <span>Admin-Recht</span>
            <strong>{{ canCreateCompany ? 'Aktiv' : 'Nicht aktiv' }}</strong>
          </div>
        </aside>

        <main class="admin-content">
          <div class="admin-section-head">
            <div>
              <p class="admin-kicker">Mandanten</p>
              <h2>Firmen</h2>
            </div>
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

          <v-alert v-if="errorMessage" type="error" variant="tonal" closable class="mb-4" @click:close="errorMessage = ''">
            {{ errorMessage }}
          </v-alert>

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
                <p>{{ client.code }}</p>
              </v-card-text>
            </v-card>
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
    </v-main>
  </v-app>
</template>

<script setup>
import { computed, nextTick, onMounted, ref } from 'vue'
import { oserpStore } from '@/core/stores/oserp.store.js'
import { AuthStatus } from '@/core/constants/auth.js'

const oserp = oserpStore()

const loading = ref(true)
const errorMessage = ref('')
const clients = ref([])
const showCreateCompanyDialog = ref(false)
const createCompanyNameRef = ref(null)
const createCompanyName = ref('')
const createCompanyDbName = ref('')
const createCompanySkr = ref('skr03')
const createCompanyError = ref('')
const createCompanyLoading = ref(false)

const skrOptions = [
  { title: 'SKR03', value: 'skr03' },
  { title: 'SKR04', value: 'skr04' },
]

const tools = [
  { title: 'Firmenkonfiguration', icon: 'mdi-domain-cog', href: '/system/mandantenkonfiguration' },
  { title: 'Benutzer', icon: 'mdi-account-cog', href: '/benutzer/konfiguration' },
  { title: 'Personal', icon: 'mdi-account-group', href: '/personal' },
  { title: 'Fahrzeuge', icon: 'mdi-car-multiple', href: '/fahrzeug' },
  { title: 'Systemupdate', icon: 'mdi-update', href: '/system/aktualisierung' },
  { title: 'Developer Tools', icon: 'mdi-wrench', href: '/system/developer-tools' },
]

const activeClientName = computed(() => oserp.session.client || '')
const canCreateCompany = computed(() => !!oserp.session.can_create_company)
const canSubmitCompany = computed(() => createCompanyName.value.trim() !== '' && createCompanyDbName.value.trim() !== '')
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
    await reloadClients()
  } catch (error) {
    errorMessage.value = readError(error, 'Admin-Daten konnten nicht geladen werden.')
  } finally {
    loading.value = false
  }
}

async function reloadClients() {
  const result = await oserp.fetchClients()
  clients.value = result.clients || []
}

async function switchClient(client) {
  if (!client?.code || client.name === activeClientName.value) return
  loading.value = true
  errorMessage.value = ''
  try {
    await oserp.switchClient(client.code)
    await reloadClients()
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
    await reloadClients()
  } catch (error) {
    createCompanyError.value = readError(error, 'Firma konnte nicht angelegt werden.')
  } finally {
    createCompanyLoading.value = false
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
  return error?.message || error?.code || fallback
}
</script>

<style scoped>
.admin-shell {
  min-height: 100vh;
  background: var(--md-color-canvas);
  color: var(--md-color-ink);
}

.admin-topbar {
  height: 64px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  padding: 0 24px;
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

.admin-topbar__actions {
  display: flex;
  align-items: center;
  gap: 10px;
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
.company-card p {
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
.tool-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
  gap: 12px;
}

.company-card,
.tool-card {
  border-radius: 8px;
  background: var(--md-color-surface);
  transition: border-color 0.18s ease, transform 0.18s ease;
}

.company-card {
  cursor: pointer;
}

.company-card:hover,
.tool-card:hover {
  border-color: rgb(var(--v-theme-primary));
  transform: translateY(-1px);
}

.company-card__top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 14px;
}

.company-card h3 {
  font-size: 1rem;
  overflow-wrap: anywhere;
}

.company-card p {
  margin: 6px 0 0;
  overflow-wrap: anywhere;
}

.tool-card {
  min-height: 82px;
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 16px;
  color: var(--md-color-ink);
  text-decoration: none;
  border: 1px solid var(--md-color-line);
  font-weight: 700;
}

@media (max-width: 860px) {
  .admin-topbar,
  .admin-section-head {
    align-items: flex-start;
    flex-direction: column;
  }

  .admin-topbar {
    height: auto;
    padding: 16px;
  }

  .admin-layout {
    grid-template-columns: 1fr;
    padding: 16px;
  }

  .admin-topbar__actions {
    width: 100%;
    flex-wrap: wrap;
  }
}
</style>
