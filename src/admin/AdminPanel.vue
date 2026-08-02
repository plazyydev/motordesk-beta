<template>
  <v-app>
    <v-main class="admin-shell">
      <header class="admin-topbar">
        <a class="admin-brand" href="/admin.html">
          <img :src="fallbackLogo" class="admin-brand__logo" alt="MotorDesk">
          <span class="admin-brand__label">Admin Hub</span>
        </a>
        <div class="admin-topbar__actions">
          <v-chip size="small" variant="tonal" color="primary">
            {{ sessionLabel }}
          </v-chip>
          <v-btn icon="mdi-text-box-search-outline" variant="text" title="Logs" @click="openLogs('web')" />
          <v-btn icon="mdi-cog-outline" variant="text" title="Einstellungen" @click="openSettingsDrawer" />
          <v-btn
            href="/startup"
            variant="tonal"
            color="primary"
            prepend-icon="mdi-arrow-left"
            :disabled="oserp.session.is_system_client || !clients.length"
          >
            App
          </v-btn>
          <v-btn icon="mdi-logout" variant="text" title="Logout" @click="logout" />
        </div>
      </header>

      <section class="admin-layout">
        <main class="admin-content">
          <v-alert v-if="errorMessage" type="error" variant="tonal" closable class="mb-4" @click:close="errorMessage = ''">
            {{ errorMessage }}
          </v-alert>

          <v-alert v-if="profileSuccess" type="success" variant="tonal" closable class="mb-4" @click:close="profileSuccess = ''">
            {{ profileSuccess }}
          </v-alert>

          <div class="admin-hero">
            <div>
              <p class="admin-kicker">{{ isOperator ? 'Betreiber' : 'Firma' }}</p>
              <h1>{{ isOperator ? 'Admin Hub' : 'Panel Admin' }}</h1>
              <span>{{ isOperator ? 'Uebersicht ueber alle Kundenpanels.' : 'Verwaltung fuer dein Firmenpanel.' }}</span>
            </div>
            <div class="admin-overview">
              <div class="admin-overview__item">
                <span>Panels</span>
                <strong>{{ clients.length }}</strong>
              </div>
              <div class="admin-overview__item">
                <span>Benutzer</span>
                <strong>{{ users.length }}</strong>
              </div>
              <div class="admin-overview__item">
                <span>Einladungen</span>
                <strong>{{ invites.length }}</strong>
              </div>
              <div class="admin-overview__item">
                <span>Rolle</span>
                <strong>{{ isOperator ? 'Betreiber' : 'Firmen-Admin' }}</strong>
              </div>
            </div>
          </div>

          <v-alert v-if="panelSettingsSuccess" type="success" variant="tonal" closable class="mb-4" @click:close="panelSettingsSuccess = ''">
            {{ panelSettingsSuccess }}
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
              <p class="admin-kicker">{{ isOperator ? 'Kundenpanels' : 'Eigenes Panel' }}</p>
              <h2>Firmen</h2>
            </div>
            <div class="admin-actions">
              <v-btn
                color="primary"
                variant="tonal"
                prepend-icon="mdi-account-plus"
                :disabled="!canManageUsers || clients.length === 0"
                @click="openInviteDialog(selectedClient)"
              >
                Benutzer anlegen
              </v-btn>
              <v-btn
                v-if="canCreateCompany"
                color="primary"
                variant="flat"
                prepend-icon="mdi-domain-plus"
                @click="openCreateCompanyDialog"
              >
                Neues Panel
              </v-btn>
            </div>
          </div>

          <div v-if="loading" class="admin-loading">
            <v-progress-circular indeterminate color="primary" />
          </div>

          <template v-else>
            <div v-if="!clients.length" class="empty-state company-empty">
              <v-icon color="primary" size="34">mdi-domain-plus</v-icon>
              <div>
                <strong>Noch kein Kundenpanel angelegt.</strong>
                <span>Lege zuerst ein Panel an. Die erste automatische Firmennummer ist 2200.</span>
              </div>
              <v-btn
                v-if="canCreateCompany"
                color="primary"
                variant="flat"
                prepend-icon="mdi-domain-plus"
                @click="openCreateCompanyDialog"
              >
                Erstes Panel anlegen
              </v-btn>
            </div>

            <div v-else class="company-grid">
              <v-card
                v-for="client in clients"
                :key="client.code"
                class="company-card"
                :class="{ 'company-card--selected': selectedClient?.code === client.code }"
                variant="outlined"
                @click="selectClient(client)"
              >
                <v-card-text>
                  <div class="company-card__top">
                    <v-icon color="primary">mdi-view-dashboard</v-icon>
                    <div class="company-card__chips">
                      <v-chip v-if="client.name === activeClientName" size="x-small" color="primary" variant="flat">
                        Aktiv
                      </v-chip>
                      <v-chip size="x-small" :color="verificationColor(client.verification_status)" variant="tonal">
                        {{ verificationLabel(client.verification_status) }}
                      </v-chip>
                      <v-chip v-if="client.debug_enabled" size="x-small" color="warning" variant="tonal">
                        Debug
                      </v-chip>
                      <v-chip v-if="client.health === 'database_error'" size="x-small" color="error" variant="tonal">
                        DB Fehler
                      </v-chip>
                    </div>
                  </div>
                  <p class="company-number">{{ companyNumber(client) }}</p>
                  <h3>{{ client.name }}</h3>
                  <div class="company-card__meta">
                    <span>{{ client.assigned_users || 0 }} Benutzer</span>
                    <span>{{ client.smtp_configured ? 'SMTP bereit' : 'SMTP offen' }}</span>
                  </div>
                </v-card-text>
              </v-card>
            </div>

            <section v-if="selectedClient" class="panel-detail">
              <div class="panel-detail__head">
                <div>
                  <p class="admin-kicker">Panel</p>
                  <h2>{{ selectedClient.name }}</h2>
                  <span>{{ companyNumber(selectedClient) }}</span>
                </div>
                <div class="admin-actions">
                  <v-btn color="primary" variant="tonal" prepend-icon="mdi-login" @click="switchClient(selectedClient)">
                    In Panel wechseln
                  </v-btn>
                  <v-btn color="primary" variant="flat" prepend-icon="mdi-account-plus" :disabled="!canManageUsers" @click="openInviteDialog(selectedClient)">
                    Benutzer anlegen
                  </v-btn>
                </div>
              </div>

              <div class="panel-status-grid">
                <div class="panel-status">
                  <span>Firmennummer</span>
                  <strong>{{ companyNumber(selectedClient) }}</strong>
                </div>
                <div class="panel-status">
                  <span>Gewerbepruefung</span>
                  <strong>{{ verificationLabel(selectedClient.verification_status) }}</strong>
                </div>
                <div class="panel-status">
                  <span>Einrichtung</span>
                  <strong>{{ setupLabel(selectedClient.setup_status) }}</strong>
                </div>
                <div class="panel-status">
                  <span>Firmendaten</span>
                  <strong>{{ selectedClient.master_data_locked ? 'Gesperrt' : 'Bearbeitbar' }}</strong>
                </div>
                <div class="panel-status">
                  <span>Debug</span>
                  <strong>{{ selectedClient.debug_enabled ? 'Aktiv' : 'Aus' }}</strong>
                </div>
              </div>

              <v-alert type="info" variant="tonal" class="mt-4">
                Firmendaten wie Name, Adresse, USt-ID, Firmennummer und Logo sind bei gesperrten Panels nur fuer Betreiber aenderbar.
              </v-alert>

              <div v-if="canEditPanelSettings" class="operator-panel mt-4">
                <div class="admin-section-head compact">
                  <div>
                    <p class="admin-kicker">Betreiber</p>
                    <h3>Panel-Einstellungen</h3>
                  </div>
                  <v-btn
                    color="primary"
                    variant="flat"
                    :loading="panelSettingsLoading"
                    @click="savePanelSettings"
                  >
                    <v-icon start>mdi-content-save</v-icon>
                    Speichern
                  </v-btn>
                </div>

                <div class="operator-grid">
                  <v-text-field
                    v-model="panelCompanyNumber"
                    label="Firmennummer"
                    variant="outlined"
                    density="comfortable"
                    hide-details
                    @update:model-value="panelCompanyNumber = normalizeCompanyNumber(panelCompanyNumber)"
                  />
                  <v-select
                    v-model="panelVerificationStatus"
                    :items="verificationOptions"
                    label="Gewerbepruefung"
                    variant="outlined"
                    density="comfortable"
                    hide-details
                  />
                  <v-select
                    v-model="panelSetupStatus"
                    :items="setupOptions"
                    label="Einrichtung"
                    variant="outlined"
                    density="comfortable"
                    hide-details
                  />
                  <v-switch
                    v-model="panelMasterDataLocked"
                    color="primary"
                    inset
                    label="Firmendaten sperren"
                    hide-details
                  />
                  <v-switch
                    v-model="panelDebugEnabled"
                    color="warning"
                    inset
                    label="Debug-Modus fuer dieses Panel"
                    hide-details
                  />
                </div>
              </div>
            </section>
          </template>

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
              <v-icon color="primary">{{ roleIcon(invite.role) }}</v-icon>
              <div>
                <strong>{{ invite.name || invite.email }}</strong>
                <span>{{ invite.email }} - {{ invite.company_number || 'ohne Firmennummer' }} - {{ invite.client_name || 'Firma geloescht' }}</span>
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

      <v-navigation-drawer
        v-model="showSettingsDrawer"
        location="right"
        temporary
        width="430"
        class="settings-drawer"
      >
        <div class="settings-drawer__head">
          <div>
            <p class="admin-kicker">Admin Hub</p>
            <h2>Einstellungen</h2>
          </div>
          <v-btn icon="mdi-close" variant="text" title="Schliessen" @click="showSettingsDrawer = false" />
        </div>

        <section class="settings-block">
          <h3>Meine Daten</h3>
          <v-text-field
            v-model="profileName"
            label="Name"
            variant="outlined"
            density="comfortable"
            class="mb-3"
          />
          <v-text-field
            v-model="profileEmail"
            label="E-Mail"
            type="email"
            variant="outlined"
            density="comfortable"
            class="mb-3"
          />
          <v-select
            v-model="profileThemeMode"
            :items="profileThemeOptions"
            label="Darstellung"
            variant="outlined"
            density="comfortable"
            class="mb-3"
          />
          <v-switch
            v-model="profileAdminDebug"
            color="warning"
            inset
            label="Admin-Debug in der Browser-Konsole"
            hide-details
            class="mb-4"
          />
          <v-btn
            color="primary"
            variant="flat"
            :loading="profileSaving"
            @click="saveAdminProfile"
          >
            <v-icon start>mdi-content-save</v-icon>
            Speichern
          </v-btn>
        </section>

        <v-divider />

        <section class="settings-block">
          <h3>Werkzeuge</h3>
          <div class="settings-actions">
            <v-btn variant="tonal" color="primary" prepend-icon="mdi-web" @click="openLogs('web')">
              Web-Logs
            </v-btn>
            <v-btn variant="tonal" color="primary" prepend-icon="mdi-database-search" @click="openLogs('database')">
              Datenbank-Logs
            </v-btn>
          </div>
          <v-alert type="info" variant="tonal" class="mt-4">
            SMTP wird pro Panel hinterlegt. Wenn SMTP fehlt, wird der Zugang angelegt und die Zugangsdaten bleiben hier zum Kopieren sichtbar.
          </v-alert>
        </section>
      </v-navigation-drawer>

      <v-dialog v-model="showLogsDialog" max-width="980">
        <v-card class="log-dialog">
          <v-card-title class="log-dialog__title">
            <div>
              <p class="admin-kicker">Reparatur</p>
              <span>Logs</span>
            </div>
            <v-btn icon="mdi-close" variant="text" title="Schliessen" @click="showLogsDialog = false" />
          </v-card-title>
          <v-card-text>
            <div class="log-toolbar">
              <v-btn
                :variant="logsChannel === 'web' ? 'flat' : 'tonal'"
                color="primary"
                prepend-icon="mdi-web"
                :loading="logsLoading && logsChannel === 'web'"
                @click="loadLogs('web')"
              >
                Webseite
              </v-btn>
              <v-btn
                :variant="logsChannel === 'database' ? 'flat' : 'tonal'"
                color="primary"
                prepend-icon="mdi-database-search"
                :loading="logsLoading && logsChannel === 'database'"
                @click="loadLogs('database')"
              >
                Datenbank
              </v-btn>
            </div>
            <v-alert v-if="logsMessage" :type="logsAvailable ? 'success' : 'info'" variant="tonal" class="my-4">
              {{ logsMessage }}
              <template v-if="logsCommand">
                <br>
                <code>{{ logsCommand }}</code>
              </template>
            </v-alert>
            <pre class="admin-log-output">{{ logsContent || 'Noch keine Logzeilen geladen.' }}</pre>
          </v-card-text>
        </v-card>
      </v-dialog>

      <v-dialog v-model="showCreateCompanyDialog" max-width="560" persistent>
        <v-card>
          <v-card-title class="bg-primary text-white">
            <v-icon start>mdi-domain-plus</v-icon>
            Neues Panel
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
              v-model="createCompanyNumber"
              label="Firmennummer"
              variant="outlined"
              density="comfortable"
              class="mb-3"
              hint="Leer lassen fuer automatische Nummer ab 2200"
              persistent-hint
              @update:model-value="createCompanyNumber = normalizeCompanyNumber(createCompanyNumber)"
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
            Zugang anlegen
          </v-card-title>
          <v-card-text class="pa-4">
            <v-select
              v-model="inviteClientId"
              :items="clients"
              :item-title="clientSelectLabel"
              item-value="code"
              label="Panel / Firmennummer"
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
              :items="availableRoleOptions"
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
              class="mb-2"
            />
            <v-switch
              v-model="inviteGeneratePassword"
              color="primary"
              inset
              label="Startpasswort automatisch generieren"
              hide-details
              class="mb-2"
            />
            <v-text-field
              v-if="!inviteGeneratePassword"
              v-model="invitePassword"
              label="Startpasswort"
              :type="invitePasswordVisible ? 'text' : 'password'"
              variant="outlined"
              density="comfortable"
              :append-inner-icon="invitePasswordVisible ? 'mdi-eye-off' : 'mdi-eye'"
              hint="Mindestens 10 Zeichen."
              persistent-hint
              :error-messages="invitePasswordError"
              @click:append-inner="invitePasswordVisible = !invitePasswordVisible"
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
import { useTheme } from 'vuetify'
import { oserpStore } from '@/core/stores/oserp.store.js'
import { AuthStatus } from '@/core/constants/auth.js'
import { MOTORDESK_FALLBACK_LOGO } from '@/core/branding.js'
import { motordeskThemeNames, normalizeThemeMode } from '@/core/theme/motordesk.tokens.js'

const oserp = oserpStore()
const fallbackLogo = MOTORDESK_FALLBACK_LOGO
const vuetifyTheme = useTheme()

const loading = ref(true)
const errorMessage = ref('')
const panelSettingsSuccess = ref('')
const profileSuccess = ref('')
const clients = ref([])
const users = ref([])
const invites = ref([])
const adminContext = ref({})
const adminProfile = ref({})
const selectedClientId = ref(null)
const inviteResult = ref(null)

const showSettingsDrawer = ref(false)
const profileName = ref('')
const profileEmail = ref('')
const profileThemeMode = ref('light')
const profileAdminDebug = ref(false)
const profileSaving = ref(false)

const showLogsDialog = ref(false)
const logsLoading = ref(false)
const logsChannel = ref('web')
const logsContent = ref('')
const logsMessage = ref('')
const logsCommand = ref('')
const logsAvailable = ref(false)

const showCreateCompanyDialog = ref(false)
const createCompanyNameRef = ref(null)
const createCompanyName = ref('')
const createCompanyNumber = ref('')
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
const inviteRole = ref('company_user')
const inviteSendEmail = ref(true)
const inviteResetPassword = ref(true)
const inviteGeneratePassword = ref(true)
const invitePassword = ref('')
const invitePasswordVisible = ref(false)
const inviteLoading = ref(false)
const inviteError = ref('')

const panelCompanyNumber = ref('')
const panelVerificationStatus = ref('pending')
const panelSetupStatus = ref('needs_review')
const panelMasterDataLocked = ref(true)
const panelDebugEnabled = ref(false)
const panelSettingsLoading = ref(false)

const skrOptions = [
  { title: 'SKR03', value: 'skr03' },
  { title: 'SKR04', value: 'skr04' },
]

const verificationOptions = [
  { title: 'Offen', value: 'pending' },
  { title: 'Geprueft', value: 'verified' },
  { title: 'Abgelehnt', value: 'rejected' },
]

const setupOptions = [
  { title: 'Pruefung offen', value: 'needs_review' },
  { title: 'In Einrichtung', value: 'in_setup' },
  { title: 'Bereit', value: 'ready' },
]

const roleOptions = [
  { title: 'Benutzer', value: 'company_user' },
  { title: 'Firmen-Admin', value: 'company_admin' },
  { title: 'Setup-Mitarbeiter', value: 'setup_agent' },
]

const profileThemeOptions = [
  { title: 'Hell', value: 'light' },
  { title: 'Dunkel', value: 'dark' },
  { title: 'System', value: 'system' },
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
const activeCompanyNumber = computed(() => oserp.session.company_number || '')
const isOperator = computed(() => !!adminContext.value.is_operator)
const canCreateCompany = computed(() => !!adminContext.value.can_create_company || !!oserp.session.can_create_company)
const canManageUsers = computed(() => !!adminContext.value.can_manage_users)
const canEditPanelSettings = computed(() => !!adminContext.value.can_edit_panel_settings)
const selectedClient = computed(() => clients.value.find(client => client.code === selectedClientId.value) || clients.value[0] || null)
const canSubmitCompany = computed(() => createCompanyName.value.trim() !== '' && createCompanyDbName.value.trim() !== '')
const invitePasswordError = computed(() => {
  if (inviteGeneratePassword.value || !invitePassword.value.trim()) return ''
  return invitePassword.value.trim().length >= 10 ? '' : 'Mindestens 10 Zeichen.'
})
const canSubmitInvite = computed(() => {
  if (!inviteClientId.value || inviteEmail.value.trim() === '') return false
  if (!inviteGeneratePassword.value && invitePassword.value.trim().length < 10) return false
  return true
})
const availableRoleOptions = computed(() => isOperator.value ? roleOptions : roleOptions.filter(role => role.value === 'company_user'))
const isAdminDebugEnabled = computed(() => !!adminProfile.value.admin_debug || profileAdminDebug.value)
const sessionLabel = computed(() => {
  if (!oserp.session.user) return 'Nicht angemeldet'
  const panel = activeCompanyNumber.value ? `${activeCompanyNumber.value} - ${activeClientName.value}` : activeClientName.value
  return `${oserp.session.user} - ${panel}`
})

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

function resolveAdminTheme(mode) {
  const normalized = normalizeThemeMode(mode, 'light')
  if (normalized === 'system') {
    return window.matchMedia?.('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'
  }
  return normalized
}

function applyAdminTheme(mode) {
  const resolved = resolveAdminTheme(mode)
  vuetifyTheme.global.name.value = resolved === 'dark' ? motordeskThemeNames.dark : motordeskThemeNames.light
  document.documentElement.dataset.adminTheme = resolved
}

function adminDebug(...message) {
  if (isAdminDebugEnabled.value) {
    console.debug('[MotorDesk Admin]', ...message)
  }
}

async function adminPost(payload) {
  adminDebug('request', payload)
  const { data } = await axios.post('/api/admin/', payload)
  adminDebug('response', data)
  if (!data.success) {
    throw new Error(adminErrorMessage(data))
  }
  return data.payload || {}
}

function adminErrorMessage(data) {
  const firstError = data?.payload?.errors?.[0]
  if (firstError) return normalizeErrorValue(firstError, 'ADMIN_API_ERROR')

  const firstClientError = data?.payload?.clients
    ?.flatMap(client => client?.update?.errors || [])
    ?.find(Boolean)
  if (firstClientError) return normalizeErrorValue(firstClientError, 'ADMIN_API_ERROR')

  return normalizeErrorValue(data?.debug || data?.payload || data?.text, 'ADMIN_API_ERROR')
}

async function reloadAdminOverview() {
  const overview = await adminPost({ action: 'getAdminOverview' })
  errorMessage.value = ''
  clients.value = overview.clients || []
  users.value = overview.users || []
  invites.value = overview.invites || []
  adminContext.value = overview.context || {}
  adminProfile.value = overview.profile || {}
  syncProfileForm(adminProfile.value)
  applyAdminTheme(profileThemeMode.value)

  if (!selectedClientId.value || !clients.value.some(client => client.code === selectedClientId.value)) {
    selectedClientId.value = clients.value[0]?.code || null
  }
  if (selectedClient.value) {
    syncPanelForm(selectedClient.value)
  }
}

function selectClient(client) {
  if (!client) return
  selectedClientId.value = client.code
  syncPanelForm(client)
}

function syncPanelForm(client) {
  panelCompanyNumber.value = companyNumber(client)
  panelVerificationStatus.value = client.verification_status || 'pending'
  panelSetupStatus.value = client.setup_status || 'needs_review'
  panelMasterDataLocked.value = client.master_data_locked !== false
  panelDebugEnabled.value = !!client.debug_enabled
}

function syncProfileForm(profile) {
  profileName.value = profile?.name || profile?.login || ''
  profileEmail.value = profile?.email || ''
  profileThemeMode.value = normalizeThemeMode(profile?.theme_mode, 'light')
  profileAdminDebug.value = !!profile?.admin_debug
}

function openSettingsDrawer() {
  syncProfileForm(adminProfile.value)
  profileSuccess.value = ''
  showSettingsDrawer.value = true
}

async function saveAdminProfile() {
  profileSaving.value = true
  errorMessage.value = ''
  profileSuccess.value = ''
  try {
    const payload = await adminPost({
      action: 'updateAdminProfile',
      name: profileName.value.trim(),
      email: profileEmail.value.trim(),
      theme_mode: profileThemeMode.value,
      admin_debug: profileAdminDebug.value,
    })
    adminProfile.value = payload.profile || {}
    syncProfileForm(adminProfile.value)
    applyAdminTheme(profileThemeMode.value)
    profileSuccess.value = 'Admin-Einstellungen gespeichert.'
  } catch (error) {
    errorMessage.value = readError(error, 'Admin-Einstellungen konnten nicht gespeichert werden.')
  } finally {
    profileSaving.value = false
  }
}

function openLogs(channel = 'web') {
  showLogsDialog.value = true
  loadLogs(channel)
}

async function loadLogs(channel = logsChannel.value) {
  logsChannel.value = channel
  logsLoading.value = true
  logsContent.value = ''
  logsMessage.value = ''
  logsCommand.value = ''
  logsAvailable.value = false
  try {
    const payload = await adminPost({
      action: 'getAdminLogs',
      channel,
      client_id: selectedClient.value?.code || adminContext.value.current_client_id,
    })
    logsContent.value = payload.content || ''
    logsMessage.value = payload.message || ''
    logsCommand.value = payload.command || ''
    logsAvailable.value = !!payload.available
  } catch (error) {
    logsMessage.value = readError(error, 'Logs konnten nicht geladen werden.')
  } finally {
    logsLoading.value = false
  }
}

async function switchClient(client) {
  if (!client?.code) return
  selectClient(client)
  if (client.name === activeClientName.value) return

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
  createCompanyNumber.value = ''
  createCompanyDbName.value = ''
  createCompanySkr.value = 'skr03'
  createCompanyError.value = ''
  showCreateCompanyDialog.value = true
  nextTick(() => createCompanyNameRef.value?.focus())
}

function closeCreateCompanyDialog() {
  showCreateCompanyDialog.value = false
  createCompanyName.value = ''
  createCompanyNumber.value = ''
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

function normalizeCompanyNumber(value) {
  return String(value || '')
    .replace(/[^0-9]+/g, '')
}

async function doCreateCompany() {
  createCompanyLoading.value = true
  createCompanyError.value = ''
  errorMessage.value = ''
  try {
    const companyName = createCompanyName.value.trim()
    await oserp.createCompany(companyName, createCompanyDbName.value.trim(), createCompanySkr.value, createCompanyNumber.value.trim())
    closeCreateCompanyDialog()
    await reloadAdminOverview()
    const created = clients.value.find(client => client.name === companyName)
    if (created) selectClient(created)
  } catch (error) {
    createCompanyError.value = readError(error, 'Firma konnte nicht angelegt werden.')
  } finally {
    createCompanyLoading.value = false
  }
}

function openInviteDialog(client = null) {
  const target = client || selectedClient.value || clients.value[0]
  inviteClientId.value = target?.code || null
  inviteEmail.value = ''
  inviteName.value = ''
  inviteLogin.value = ''
  inviteRole.value = availableRoleOptions.value[0]?.value || 'company_user'
  inviteSendEmail.value = true
  inviteResetPassword.value = true
  inviteGeneratePassword.value = true
  invitePassword.value = ''
  invitePasswordVisible.value = false
  inviteError.value = ''
  showInviteDialog.value = true
  nextTick(() => inviteEmailRef.value?.focus())
}

function closeInviteDialog() {
  showInviteDialog.value = false
  inviteError.value = ''
  invitePassword.value = ''
}

async function doCreateInvite() {
  inviteLoading.value = true
  inviteError.value = ''
  errorMessage.value = ''
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
      password: inviteGeneratePassword.value ? '' : invitePassword.value.trim(),
    })
    inviteResult.value = payload
    closeInviteDialog()
    await reloadAdminOverview()
  } catch (error) {
    inviteError.value = readError(error, 'Zugang konnte nicht angelegt werden.')
  } finally {
    inviteLoading.value = false
  }
}

async function savePanelSettings() {
  if (!selectedClient.value) return
  panelSettingsLoading.value = true
  panelSettingsSuccess.value = ''
  errorMessage.value = ''
  try {
    const payload = await adminPost({
      action: 'updateCompanyPanelSettings',
      client_id: selectedClient.value.code,
      company_number: panelCompanyNumber.value,
      verification_status: panelVerificationStatus.value,
      setup_status: panelSetupStatus.value,
      master_data_locked: panelMasterDataLocked.value,
      debug_enabled: panelDebugEnabled.value,
    })
    const updated = payload.client
    clients.value = clients.value.map(client => client.code === updated.code ? { ...client, ...updated } : client)
    selectedClientId.value = updated.code
    syncPanelForm(selectedClient.value)
    panelSettingsSuccess.value = 'Panel-Einstellungen gespeichert.'
  } catch (error) {
    errorMessage.value = readError(error, 'Panel-Einstellungen konnten nicht gespeichert werden.')
  } finally {
    panelSettingsLoading.value = false
  }
}

async function copyInvite(result) {
  const credentials = result.credentials || {}
  const text = [
    'MotorDesk Zugang',
    `Login: ${credentials.login || ''}`,
    credentials.password ? `Passwort: ${credentials.password}` : '',
    `Admin-Hub: ${credentials.admin_url || '/admin.html'}`,
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

function companyNumber(client) {
  return client?.company_number || (client?.code ? String(2199 + Number(client.code)) : '')
}

function clientSelectLabel(client) {
  return `${companyNumber(client)} - ${client.name}`
}

function verificationLabel(status) {
  return {
    verified: 'Geprueft',
    rejected: 'Abgelehnt',
    pending: 'Offen',
  }[status] || 'Offen'
}

function verificationColor(status) {
  return {
    verified: 'success',
    rejected: 'error',
    pending: 'warning',
  }[status] || 'warning'
}

function setupLabel(status) {
  return {
    ready: 'Bereit',
    in_setup: 'In Einrichtung',
    needs_review: 'Pruefung offen',
  }[status] || 'Pruefung offen'
}

function roleIcon(role) {
  return {
    setup_agent: 'mdi-account-hard-hat',
    company_admin: 'mdi-shield-account',
    company_user: 'mdi-account',
  }[role] || 'mdi-account'
}

function normalizeErrorValue(value, fallback) {
  if (!value) return fallback
  if (typeof value === 'string') return value
  if (Array.isArray(value)) return value.map(item => normalizeErrorValue(item, '')).filter(Boolean).join('\n') || fallback
  if (typeof value === 'object') {
    return value.message || value.error || value.text || value.code || JSON.stringify(value)
  }
  return String(value)
}

function readError(error, fallback) {
  return normalizeErrorValue(
    error?.response?.data?.debug || error?.response?.data?.payload || error?.response?.data?.text || error?.message || error?.code,
    fallback
  )
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

.admin-brand__logo {
  height: 38px;
  width: auto;
  max-width: 184px;
  object-fit: contain;
}

.admin-brand__label {
  color: var(--md-color-muted);
  font-size: 0.9rem;
  font-weight: 700;
}

.v-theme--dark .admin-brand__logo {
  filter: brightness(1.45) contrast(1.05);
}

.admin-topbar__actions,
.admin-actions {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
}

.admin-layout {
  padding: 24px;
}

.admin-content,
.panel-detail {
  background: var(--md-color-surface);
  border: 1px solid var(--md-color-line);
  border-radius: 8px;
}

.admin-hero h1,
.admin-section-head h2,
.settings-drawer h2,
.settings-drawer h3,
.panel-detail h2,
.operator-panel h3,
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

.admin-hero {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 18px;
  padding-bottom: 18px;
  margin-bottom: 18px;
  border-bottom: 1px solid var(--md-color-line);
}

.admin-hero h1 {
  font-size: 1.9rem;
}

.admin-hero span {
  color: var(--md-color-muted);
}

.admin-overview {
  min-width: min(520px, 100%);
  display: grid;
  grid-template-columns: repeat(4, minmax(96px, 1fr));
  gap: 10px;
}

.admin-overview__item,
.panel-status,
.operator-panel {
  padding: 12px;
  background: var(--md-color-canvas);
  border: 1px solid var(--md-color-line);
  border-radius: 8px;
}

.admin-overview__item {
  display: grid;
  gap: 4px;
}

.admin-overview__item span,
.company-card p,
.editor-card span,
.invite-row span,
.panel-detail__head span,
.panel-status span {
  color: var(--md-color-muted);
  font-size: 0.82rem;
}

.admin-overview__item strong,
.panel-status strong {
  overflow-wrap: anywhere;
}

.admin-content {
  padding: 20px;
}

.settings-drawer {
  background: var(--md-color-surface) !important;
  color: var(--md-color-ink);
}

.settings-drawer__head,
.log-dialog__title {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
  padding: 18px 20px;
  border-bottom: 1px solid var(--md-color-line);
}

.settings-block {
  padding: 20px;
}

.settings-block h3 {
  margin-bottom: 14px;
}

.settings-actions,
.log-toolbar {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
}

.log-dialog {
  background: var(--md-color-surface);
  color: var(--md-color-ink);
}

.admin-log-output {
  min-height: 360px;
  max-height: 56vh;
  overflow: auto;
  margin: 0;
  padding: 14px;
  background: var(--md-color-canvas);
  border: 1px solid var(--md-color-line);
  border-radius: 8px;
  color: var(--md-color-ink);
  font-family: Consolas, Monaco, 'Courier New', monospace;
  font-size: 0.82rem;
  line-height: 1.5;
  white-space: pre-wrap;
}

.admin-section-head,
.panel-detail__head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 16px;
}

.admin-section-head.compact {
  margin-bottom: 12px;
}

.admin-loading {
  min-height: 160px;
  display: grid;
  place-items: center;
}

.company-grid,
.tool-grid,
.editor-grid,
.panel-status-grid,
.operator-grid {
  display: grid;
  gap: 12px;
}

.company-grid,
.tool-grid,
.editor-grid {
  grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
}

.panel-status-grid,
.operator-grid {
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
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
  transition: border-color 0.18s ease, transform 0.18s ease, box-shadow 0.18s ease;
}

.company-card:hover,
.company-card--selected,
.tool-card:hover {
  border-color: rgb(var(--v-theme-primary));
  transform: translateY(-1px);
}

.company-card--selected {
  box-shadow: 0 0 0 1px rgba(var(--v-theme-primary), 0.22);
}

.company-card__top,
.company-card__meta,
.company-card__chips {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
}

.company-card__top {
  margin-bottom: 14px;
}

.company-card__chips {
  justify-content: flex-end;
  flex-wrap: wrap;
}

.company-number {
  margin: 0 0 6px;
  font-weight: 800;
  color: rgb(var(--v-theme-primary)) !important;
}

.company-card h3 {
  font-size: 1rem;
  overflow-wrap: anywhere;
}

.company-card__meta {
  margin-top: 12px;
  color: var(--md-color-muted);
  font-size: 0.82rem;
}

.panel-detail {
  margin-top: 16px;
  padding: 18px;
}

.panel-status {
  display: grid;
  gap: 4px;
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

.company-empty {
  min-height: 150px;
  display: flex;
  align-items: center;
  gap: 16px;
  flex-wrap: wrap;
}

.company-empty div {
  min-width: 220px;
  flex: 1;
  display: grid;
  gap: 4px;
}

.company-empty strong {
  color: var(--md-color-ink);
}

@media (max-width: 860px) {
  .admin-topbar,
  .admin-hero,
  .admin-section-head,
  .panel-detail__head {
    align-items: flex-start;
    flex-direction: column;
  }

  .admin-layout {
    padding: 16px;
  }

  .admin-overview {
    min-width: 0;
    width: 100%;
    grid-template-columns: repeat(2, minmax(0, 1fr));
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
