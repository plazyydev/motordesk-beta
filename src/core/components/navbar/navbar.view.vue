<!-- src/core/components/navbar/navbar.view.vue -->
<template>
  <v-app-bar class="motordesk-navbar" elevation="0" density="comfortable">
    <!-- Weroni — KI-Bürokauffrau (ganz links) -->
    <v-btn
      v-if="weroniEnabled"
      icon
      variant="text"
      :title="$t('weroni.tooltip')"
      :class="['ms-1', { 'weroni-blink': weroniPending }]"
      @click="weroni.togglePanel()"
    >
      <img :src="weroniIcon" width="24" height="24" alt="Weroni" style="border-radius: 50%;">
      <v-badge v-if="weroniPending" :content="weroni.pendingQuestionCount" color="error" floating offset-x="-4" offset-y="-4" />
    </v-btn>

    <router-link :to="{ name: 'startup' }" class="motordesk-brand ms-2" :title="brandTitle">
      <img
        v-if="companyLogo"
        :src="companyLogo"
        class="motordesk-brand__logo"
        :alt="brandTitle"
      >
      <img
        v-else
        :src="fallbackLogo"
        class="motordesk-brand__logo motordesk-brand__logo--fallback"
        alt="MotorDesk"
      >
    </router-link>

    <v-chip
      v-if="featureTitle"
      size="small"
      variant="tonal"
      color="primary"
      class="motordesk-feature-chip ms-2"
    >
      {{ featureTitle }}
    </v-chip>

    <!-- LxCars Schnellzugriff -->
    <v-btn
      v-if="oserpData.isLxCars()"
      icon
      variant="text"
      color="primary"
      :to="$t('routes.orderSearch')"
      :title="$t('CarView.orderSearchTooltip')"
    >
      <v-icon>mdi-clipboard-search-outline</v-icon>
    </v-btn>
    <v-btn
      v-if="oserpData.isLxCars()"
      icon
      variant="text"
      color="primary"
      :to="{ name: 'car-list' }"
      :title="$t('CarView.manageCars')"
      class="motordesk-quick-action"
    >
      <v-icon>mdi-car-multiple</v-icon>
    </v-btn>
    <v-btn
      v-if="oserpData.isLxCars()"
      icon
      variant="text"
      color="primary"
      :to="{ name: 'fahrzeug-neu' }"
      :title="$t('CarView.newCar')"
      class="motordesk-quick-action"
    >
      <v-icon>mdi-car-plus</v-icon>
    </v-btn>
    <v-btn
      icon
      variant="text"
      color="primary"
      :to="{ name: 'calendar' }"
      :title="$t('NavbarView.calendarTooltip')"
    >
      <v-icon>mdi-calendar-month</v-icon>
    </v-btn>
    <v-btn
      icon
      variant="text"
      color="primary"
      :to="{ name: 'call-history' }"
      :title="$t('NavbarView.callHistoryTooltip')"
    >
      <v-icon>mdi-phone</v-icon>
    </v-btn>
    <v-btn
      v-if="oserpData.isLxCars()"
      icon
      variant="text"
      color="primary"
      :to="{ name: 'car-new-from-scan' }"
      :title="$t('CarView.scanTooltip')"
      class="motordesk-scan-action"
    >
      <v-icon size="22">mdi-car-side</v-icon>
      <v-icon size="11" class="motordesk-scan-action__camera" color="primary">mdi-camera</v-icon>
    </v-btn>

    <!-- Responsive Menüs -->
    <ResponsiveMenu :menus="cards" @menu-click="handleMenuAction" />

    <!-- Globale Schnellsuche (Inline ab grossen Bildschirmen) -->
    <GlobalSearchComponent v-if="lgAndUp" class="mx-2" style="min-width: 240px; max-width: 400px; flex: 1" />

    <v-spacer />

    <!-- Firmenlogo oder Firmenname — Klick öffnet Firmenliste -->
    <v-menu v-model="clientMenuOpen" location="bottom end" :close-on-content-click="true" open-on-hover>
      <template #activator="{ props: menuProps }">
        <div v-bind="menuProps" class="motordesk-client-switch cursor-pointer" :title="t('NavbarView.switchClient')">
          <v-icon size="18" class="me-1">mdi-domain</v-icon>
          <span class="text-body-2 font-weight-medium me-1">{{ oserpData.session.client }}</span>
          <v-icon v-if="clientList.length > 1" size="x-small" class="me-1">mdi-chevron-down</v-icon>
        </div>
      </template>
      <v-list density="compact" min-width="220">
        <v-list-item
          v-for="client in clientList"
          :key="client.code"
          :disabled="client.name === oserpData.session.client"
          @click="doSwitchClient(client.code)"
        >
          <v-list-item-title class="text-body-2">{{ client.name }}</v-list-item-title>
          <template #append>
            <v-icon v-if="client.name === oserpData.session.client" size="small" color="primary">mdi-check</v-icon>
          </template>
        </v-list-item>
        <v-divider class="my-1" />
        <v-list-item
          :disabled="!oserpData.session.can_create_company"
          @click="openCreateCompanyDialog"
        >
          <template #prepend><v-icon size="small" class="me-2">mdi-plus-circle-outline</v-icon></template>
          <v-list-item-title class="text-body-2">{{ t('NavbarView.newCompany') }}</v-list-item-title>
        </v-list-item>
        <v-divider class="my-1" />
        <v-list-item href="/admin.html" @click="clientMenuOpen = false">
          <template #prepend><v-icon size="small" class="me-2">mdi-shield-account</v-icon></template>
          <v-list-item-title class="text-body-2">Admin-Panel</v-list-item-title>
        </v-list-item>
        <v-divider class="my-1" />
        <v-list-item :to="t('routes.clientConfig')" @click="clientMenuOpen = false">
          <template #prepend><v-icon size="small" class="me-2">mdi-cog</v-icon></template>
          <v-list-item-title class="text-body-2">{{ t('SystemMenu.clientConfig') }}</v-list-item-title>
        </v-list-item>
        <v-list-item :to="t('routes.developerTools')" @click="clientMenuOpen = false">
          <template #prepend><v-icon size="small" class="me-2">mdi-wrench</v-icon></template>
          <v-list-item-title class="text-body-2">{{ t('SystemMenu.developerTools') }}</v-list-item-title>
        </v-list-item>
        <v-list-item :to="t('routes.systemUpdate')" @click="clientMenuOpen = false">
          <template #prepend><v-icon size="small" class="me-2">mdi-update</v-icon></template>
          <v-list-item-title class="text-body-2">{{ t('SystemMenu.systemUpdate') }}</v-list-item-title>
        </v-list-item>
        <v-divider class="my-1" />
        <v-list-item :to="{ name: 'docs' }" @click="clientMenuOpen = false">
          <template #prepend><v-icon size="small" class="me-2">mdi-book-open-variant</v-icon></template>
          <v-list-item-title class="text-body-2">{{ t('SystemMenu.docs') }}</v-list-item-title>
        </v-list-item>
        <v-list-item @click="showAboutDialog = true; clientMenuOpen = false">
          <template #prepend><v-icon size="small" class="me-2">mdi-information-outline</v-icon></template>
          <v-list-item-title class="text-body-2">{{ t('SystemMenu.about') }}</v-list-item-title>
        </v-list-item>
      </v-list>
    </v-menu>

    <!-- Kamera / Videoüberwachung -->
    <v-btn
      icon
      variant="text"
      color="primary"
      @click="openInNewTab"
      :title="$t('NavbarView.newTabTooltip')"
      class="motordesk-nav-icon"
    >
      <v-icon>mdi-open-in-new</v-icon>
    </v-btn>

    <v-btn
      icon
      variant="text"
      color="primary"
      :to="{ name: 'camera' }"
      :title="t('CameraView.title')"
      class="motordesk-nav-icon"
    >
      <v-icon>mdi-cctv</v-icon>
    </v-btn>

    <!-- Account-Menü -->
    <v-menu v-model="accountMenuOpen" location="bottom end" :close-on-content-click="false">
      <template #activator="{ props }">
        <v-btn
          variant="text"
          color="primary"
          v-bind="props"
          :title="$t('NavbarView.accountTooltip')"
          class="text-none"
        >
          <v-icon
            start
            :color="sseConnected ? 'primary' : 'orange-darken-2'"
            :title="sseConnected ? t('NavbarView.sseConnected') : t('NavbarView.sseDisconnected')"
          >mdi-account-circle</v-icon>
          <span class="text-body-2">{{ oserpData.session.user }}</span>
        </v-btn>
      </template>

      <v-card min-width="280">
        <v-card-text class="pb-2">
          <div class="text-subtitle-2 font-weight-bold">{{ oserpData.session.user }}</div>
          <div class="text-caption text-medium-emphasis">{{ oserpData.session.client }}</div>
        </v-card-text>
        <v-divider />
        <v-card-text class="py-3">
          <div class="motordesk-theme-row">
            <span class="text-caption text-medium-emphasis">Darstellung</span>
            <ThemeSwitcher />
          </div>
        </v-card-text>
        <v-divider />
        <v-list density="compact">
          <v-list-item @click="logout">
            <template #prepend>
              <v-icon size="small" class="me-2">mdi-logout</v-icon>
            </template>
            <v-list-item-title>{{ $t('NavbarView.logoutTooltip') }}</v-list-item-title>
          </v-list-item>
          <v-list-item :to="{ name: 'user-config' }" @click="accountMenuOpen = false">
            <template #prepend>
              <v-icon size="small" class="me-2">mdi-cog</v-icon>
            </template>
            <v-list-item-title>{{ $t('NavbarView.userConfig') }}</v-list-item-title>
          </v-list-item>
          <template v-if="oserpData.isLxCars()">
            <v-divider class="my-1" />
            <v-list-item :to="{ name: 'lxcars-reports' }" @click="accountMenuOpen = false">
              <template #prepend>
                <v-icon size="small" class="me-2">mdi-chart-timeline-variant-shimmer</v-icon>
              </template>
              <v-list-item-title>{{ $t('NavbarView.reports') }}</v-list-item-title>
            </v-list-item>
          </template>
        </v-list>
      </v-card>
    </v-menu>

    <!-- Globale Schnellsuche auf kleinen Bildschirmen: eigene volle Zeile,
         damit das Eingabefeld nicht zusammengequetscht wird -->
    <template v-if="!lgAndUp" #extension>
      <GlobalSearchComponent class="mx-2" style="width: 100%" />
    </template>
  </v-app-bar>

  <!-- Info Bar: Neue Anrufe & E-Mails -->
  <InfoBarComponent />

  <!-- Weroni Panel (rechtes Drawer) -->
  <WeroniPanel v-if="weroniEnabled" />

  <div v-if="oserpData.customer_vendor !== false" class="text-left pa-2">
    <v-btn
      v-if="editRoute"
      icon
      size="20"
      class="me-2"
      :title="t('NavbarView.editCvTooltip')"
      @click="goToEdit"
    >
      <v-icon size="18">mdi-pencil</v-icon>
    </v-btn>
    <span class="px-2" v-if="cvSrc === 'C'">{{ $t('NavbarView.currentC') }}</span>
    <span class="px-2" v-else>{{ $t('NavbarView.currentV') }}</span>
    <span class="font-weight-bold">{{ oserpData.customer_vendor?.profile?.name }}</span>
    <span class="font-weight-semibold ps-2">( {{ cvSrc === 'C' ? oserpData.customer_vendor?.profile?.customernumber : oserpData.customer_vendor?.profile?.vendornumber }} )</span>
  </div>

  <!-- Demo-Modus Banner -->
  <v-banner
    v-if="isDemo"
    color="warning"
    density="compact"
    icon="mdi-flask-outline"
    class="text-center"
    stacked
  >
    <template #text>
      <span class="text-body-2 font-weight-medium">{{ t('NavbarView.demoBanner') }}</span>
    </template>
  </v-banner>

  <!-- Demo Inaktivitäts-Warnung -->
  <v-snackbar
    v-if="isDemo"
    :model-value="showDemoWarning"
    color="error"
    timeout="-1"
    location="top"
    multi-line
  >
    <div class="d-flex align-center">
      <v-icon class="me-3">mdi-alert</v-icon>
      <span>{{ t('NavbarView.demoResetWarning', { seconds: demoRemainingSeconds }) }}</span>
    </div>
  </v-snackbar>

  <!-- MessagesView Integration -->
  <MessagesView :messages="displayMessages" />

  <!-- Über-Dialog -->
  <AboutDialog v-model="showAboutDialog" app-title="MotorDesk" />

  <!-- Neue Firma anlegen -->
  <v-dialog v-model="showCreateCompanyDialog" max-width="500" persistent>
    <v-card>
      <v-card-title class="bg-primary text-white">
        <v-icon start>mdi-domain-plus</v-icon>
        {{ t('NavbarView.createCompanyTitle') }}
      </v-card-title>
      <v-card-text class="pa-4">
        <v-text-field
          ref="createCompanyNameRef"
          v-model="createCompanyName"
          :label="t('NavbarView.companyName')"
          variant="outlined"
          density="compact"
          class="mb-3"
          :error-messages="createCompanyError"
          @update:model-value="createCompanyError = ''"
          @keydown.enter="canSubmitCompany && doCreateCompany()"
        />
        <v-text-field
          v-model="createCompanyDbName"
          :label="t('NavbarView.dbName')"
          variant="outlined"
          density="compact"
          class="mb-3"
          :hint="t('NavbarView.dbNameHint')"
          persistent-hint
          @keydown.enter="canSubmitCompany && doCreateCompany()"
        />
        <v-select
          v-model="createCompanySkr"
          :items="skrOptions"
          :label="t('NavbarView.chartOfAccounts')"
          variant="outlined"
          density="compact"
        />
      </v-card-text>
      <v-card-actions>
        <v-btn @click="closeCreateCompanyDialog">{{ t('NavbarView.cancel') }}</v-btn>
        <v-spacer />
        <v-btn
          color="primary"
          variant="flat"
          :loading="createCompanyLoading"
          :disabled="!canSubmitCompany"
          @click="doCreateCompany"
        >
          <v-icon start>mdi-check</v-icon>
          {{ t('NavbarView.createCompany') }}
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script>
import { oserpStore } from '@/core/stores/oserp.store.js'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { computed, inject, nextTick, ref } from 'vue'
import { useDisplay } from 'vuetify'
import MessagesView from '@/core/components/messages/messages.view.vue'
import ResponsiveMenu from '@/core/components/menus/responsive.menus.vue'
import GlobalSearchComponent from '@/core/components/navbar/global-search.component.vue'
import InfoBarComponent from '@/core/components/navbar/info-bar.component.vue'
import { sseConnected } from '@/core/composables/useInfoBar'
import AboutDialog from '@/core/components/about/about.dialog.vue'
import ThemeSwitcher from '@/core/components/app-shell/theme-switcher.vue'
import weroniIcon from '@/assets/weroni/weroni-24.png'
import WeroniPanel from '@/features/weroni/components/weroni-panel.vue'
import { weroniStore } from '@/features/weroni/stores/weroni.store.js'
import { useNavigationCards } from '@/core/composables/navigation.cards'
import { useActivityTracker } from '@/core/composables/useActivityTracker'
import { MOTORDESK_FALLBACK_LOGO } from '@/core/branding.js'

export default {
  name: 'NavbarView',
  components: {
    MessagesView,
    ResponsiveMenu,
    GlobalSearchComponent,
    InfoBarComponent,
    AboutDialog,
    ThemeSwitcher,
    WeroniPanel
  },
  props: {
    // alte API (einzelne Nachricht)
    message: {
      type: Object,
      default: () => ({ title: '', description: '', type: 'info' })
    },
    // neue API (mehrere Nachrichten)
    messages: {
      type: Array,
      default: () => []
    }
  },
  setup(props) {
    const oserpData = oserpStore()
    const weroni = weroniStore()
    const router = useRouter()
    const { t } = useI18n()
    const { lgAndUp } = useDisplay()
    const cvSrc = computed(() => oserpData.customer_vendor?.profile?.src || 'C')
    const appReady = inject('appReady')

    const featureDisplayNames = {
      lxcars: 'LxCars'
    }

    const featureTitle = computed(() => {
      const active = oserpData.features.find(f => {
        const key = typeof f === 'object' ? f.value : f
        return featureDisplayNames[key]
      })
      if (!active) return ''
      const key = typeof active === 'object' ? active.value : active
      return featureDisplayNames[key]
    })
    const appTitle = computed(() => featureTitle.value || 'MotorDesk')

    // Demo-Modus
    const isDemo = computed(() => oserpData.session.is_demo)

    const { showWarning: showDemoWarning, remainingSeconds: demoRemainingSeconds } = isDemo.value
      ? useActivityTracker({
          inactivityMinutes: oserpData.session.demo_inactivity_minutes,
          onReset: async () => {
            appReady.value = false
            try { await oserpData.logout() } catch {}
            await router.push('/login')
            appReady.value = true
          }
        })
      : { showWarning: computed(() => false), remainingSeconds: computed(() => 0) }

    const editRoute = computed(() => {
      const profile = oserpData.customer_vendor?.profile
      if (!profile?.id) return null
      const routeName = profile.src === 'V' ? 'vendor-edit' : 'customer-edit'
      return { name: routeName, params: { id: profile.id } }
    })

    function goToEdit() {
      if (editRoute.value) router.push(editRoute.value)
    }

    // Weroni
    const weroniEnabled = computed(() => {
        const v = oserpData.getClientDefaultValue('weroni_enabled', false)
        return v === true || v === 'true' || v === 't' || v === '1'
    })
    const weroniPending = computed(() => weroni.pendingQuestionCount > 0)

    // Weroni: Einmalig offene Rückfragen laden (Updates kommen via SSE)
    if (weroniEnabled.value) {
        weroni.fetchPendingCount()
    }

    // Navigation Cards aus Composable
    const { cards } = useNavigationCards(true)

    // Firmenwechsel
    const accountMenuOpen = ref(false)
    const clientMenuOpen = ref(false)
    const showAboutDialog = ref(false)
    const clientList = ref([])

    // Firmenliste einmalig beim Laden der Navbar holen
    oserpData.fetchClients()
      .then(({ clients }) => { clientList.value = clients })
      .catch(() => {})

    async function doSwitchClient(clientCode) {
      accountMenuOpen.value = false
      try {
        await oserpData.switchClient(clientCode)
        router.push({ name: 'startup' })
      } catch (err) {
        console.error('Firmenwechsel fehlgeschlagen:', err)
      }
    }

    // Neue Firma anlegen
    const showCreateCompanyDialog = ref(false)
    const createCompanySkr = ref('')
    const createCompanyName = ref('')
    const createCompanyDbName = ref('')
    const createCompanyLoading = ref(false)
    const createCompanyError = ref('')
    const createCompanyNameRef = ref(null)
    const canSubmitCompany = computed(() => createCompanyName.value.trim() !== '' && createCompanyDbName.value.trim() !== '')

    const skrOptions = [
      { title: 'SKR03', value: 'skr03' },
      { title: 'SKR04', value: 'skr04' }
    ]

    function openCreateCompanyDialog() {
      clientMenuOpen.value = false
      createCompanySkr.value = 'skr03'
      createCompanyName.value = ''
      createCompanyDbName.value = ''
      createCompanyError.value = ''
      showCreateCompanyDialog.value = true
      nextTick(() => createCompanyNameRef.value?.focus())
    }

    function closeCreateCompanyDialog() {
      showCreateCompanyDialog.value = false
      createCompanySkr.value = ''
      createCompanyName.value = ''
      createCompanyDbName.value = ''
      createCompanyError.value = ''
    }

    async function doCreateCompany() {
      createCompanyLoading.value = true
      createCompanyError.value = ''
      try {
        const companyName = createCompanyName.value.trim()
        await oserpData.createCompany(
          companyName,
          createCompanyDbName.value.trim(),
          createCompanySkr.value
        )
        closeCreateCompanyDialog()
        // Firmenliste neu laden und zur neuen Firma wechseln
        const { clients } = await oserpData.fetchClients()
        clientList.value = clients
        const newClient = clients.find(c => c.name === companyName)
        if (newClient) {
          await oserpData.switchClient(newClient.code)
          router.push({ name: 'startup' })
        }
      } catch (err) {
        const errorCode = err.code || err.message || 'UNKNOWN_ERROR'
        const key = 'NavbarView.createCompanyError.' + errorCode
        createCompanyError.value = t(key) !== key ? t(key) : t('NavbarView.createCompanyError.UNKNOWN_ERROR')
      } finally {
        createCompanyLoading.value = false
      }
    }

    // Firmenlogo (nur Anzeige, Upload jetzt in Firmenkonfiguration)
    const companyLogo = computed(() => oserpData.getBrandingConfig().companyLogo || null)
    const brandTitle = computed(() => companyLogo.value ? oserpData.session.client : 'MotorDesk')
    const fallbackLogo = MOTORDESK_FALLBACK_LOGO

    const logout = async () => {
      appReady.value = false
      try {
        await oserpData.logout()
      } catch (err) {
        console.error('Logout error:', err)
      }
      await router.push('/login')
      appReady.value = true
    }

    const openInNewTab = () => {
      window.open(window.location.href, '_blank')
    }

    const mapType = (t) => (['info', 'warning', 'error', 'success'].includes(t) ? t : 'info')

    // Handler für Menü-Aktionen ohne Route
    const handleMenuAction = (item) => {
      // Hier können Custom-Actions behandelt werden
      console.log('Menu action:', item)
    }

    // Anzeige-Messages ableiten: bevorzugt prop.messages, dann prop.message, sonst Fallback
    const displayMessages = computed(() => {
      const list = []

      if (Array.isArray(props.messages) && props.messages.length) {
        props.messages.forEach(m => {
          if (m && m.title) list.push({
            title: m.title,
            description: m.description || '',
            type: mapType(m.type || 'info')
          })
        })
      }

      if (props.message && props.message.title) {
        list.push({
          title: props.message.title,
          description: props.message.description || '',
          type: mapType(props.message.type || 'info')
        })
      }

      if (!oserpData.customer_vendor) {
        list.push({
          title: 'Willkommen bei MotorDesk!',
          description:
            'Es scheint, als haetten Sie noch keine Kunden angelegt. Beginnen Sie damit, Ihren ersten Kunden zu erstellen, um MotorDesk zu erkunden.',
          type: 'info',
        })
      }

      return list
    })

    return {
      oserpData,
      lgAndUp,
      appTitle,
      featureTitle,
      brandTitle,
      isDemo,
      showDemoWarning,
      demoRemainingSeconds,
      logout,
      openInNewTab,
      t,
      cvSrc,
      editRoute,
      goToEdit,
      displayMessages,
      cards,
      handleMenuAction,
      accountMenuOpen,
      clientMenuOpen,
      clientList,
      doSwitchClient,
      companyLogo,
      fallbackLogo,
      showAboutDialog,
      sseConnected,
      weroni,
      weroniIcon,
      weroniEnabled,
      weroniPending,
      showCreateCompanyDialog,
      createCompanySkr,
      createCompanyNameRef,
      canSubmitCompany,
      createCompanyName,
      createCompanyDbName,
      createCompanyLoading,
      createCompanyError,
      skrOptions,
      openCreateCompanyDialog,
      closeCreateCompanyDialog,
      doCreateCompany
    }
  }
}
</script>

<style scoped>
.motordesk-navbar {
  background: rgb(var(--v-theme-surface)) !important;
  color: rgb(var(--v-theme-on-surface));
  border-bottom: 1px solid var(--md-color-line);
}

.motordesk-brand {
  min-width: 0;
  height: 40px;
  display: inline-flex;
  align-items: center;
  gap: 10px;
  padding: 0 10px;
  color: var(--md-color-ink);
  text-decoration: none;
  border-radius: var(--md-radius-md);
  transition: background-color 0.18s ease, color 0.18s ease;
}

.motordesk-brand:hover {
  background: var(--md-color-brand-soft);
}

.motordesk-brand__logo {
  height: 34px;
  width: auto;
  max-width: 186px;
  object-fit: contain;
}

.motordesk-brand__logo--fallback {
  height: 36px;
  max-width: 172px;
}

.v-theme--dark .motordesk-brand__logo--fallback {
  filter: brightness(1.45) contrast(1.05);
}

.motordesk-feature-chip {
  flex: 0 0 auto;
}

.motordesk-quick-action,
.motordesk-nav-icon,
.motordesk-scan-action {
  border-radius: var(--md-radius-md);
}

.motordesk-scan-action {
  position: relative;
}

.motordesk-scan-action__camera {
  position: absolute;
  right: 6px;
  bottom: 6px;
}

.motordesk-client-switch {
  display: inline-flex;
  align-items: center;
  min-height: 36px;
  max-width: 220px;
  padding: 0 10px;
  color: var(--md-color-ink);
  border: 1px solid var(--md-color-line);
  border-radius: var(--md-radius-md);
  background: var(--md-color-surface);
}

.motordesk-client-switch span {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.motordesk-theme-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
}

.cursor-pointer {
  cursor: pointer;
}
.weroni-blink {
  animation: weroni-pulse 1.5s ease-in-out infinite;
}
@keyframes weroni-pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.4; }
}
</style>
