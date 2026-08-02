<template>
  <v-app-bar class="motordesk-login-bar" elevation="0" density="comfortable" aria-label="MotorDesk Navbar">
    <v-toolbar-title class="text-h6">
      <router-link to="/" class="motordesk-login-brand">
        <img :src="fallbackLogo" class="motordesk-login-brand__logo" alt="MotorDesk">
      </router-link>
    </v-toolbar-title>
  </v-app-bar>
  <v-container>
    <v-row justify="center" class="pt-4">
      <v-col cols="12" sm="6" md="6" lg="6" xl="2">
        <v-card variant="outlined" class="border-md">
          <v-card-title class="pb-5">
            <strong>{{ t('LoginView.title') }}</strong>
          </v-card-title>

          <v-card-text>
            <v-form @submit.prevent="login">
              <v-text-field
                id="login-name" v-model="username" :label="t('LoginView.username')" autocomplete="username" autofocus
                variant="outlined" density="comfortable" class="mb-3"
                @focus="clearError" @keydown="clearError" @keyup.enter="login"
              />
              <v-text-field
                id="login-password" v-model="password" :label="t('LoginView.password')" type="password" autocomplete="current-password"
                variant="outlined" density="comfortable"
                @focus="clearError" @keydown="clearError" @keyup.enter="login"
              />
              <v-text-field
                id="client" v-model="clientCode" :label="t('LoginView.client')" autocomplete="organization"
                inputmode="numeric" pattern="[0-9]*" :placeholder="clientPlaceholder"
                variant="outlined" density="comfortable" class="pt-3"
                @update:model-value="clientCode = normalizeCompanyNumber(clientCode)"
                @focus="clearError" @keydown="clearError" @keyup.enter="login"
              />
              <v-checkbox
                id="remember-me" v-model="rememberMe" :label="t('LoginView.rememberMe')"
                density="compact" hide-details class="mt-0"
              />
              <ErrorView :message="errorMessage" :type="errorType" @clear="clearError" />
              <v-btn type="submit" color="primary" class="mt-3" :loading="loading">
                {{ t('LoginView.button') }}
              </v-btn>
            </v-form>
          </v-card-text>

          <v-divider />

          <v-card-text class="text-center">
            <router-link to="/datenschutz" class="text-caption text-grey text-decoration-none">
              {{ t('LoginView.privacy') }}
            </router-link>
            <span class="text-caption text-grey mx-2 update-trigger" @click="runUpdate">|</span>
            <router-link to="/datenloeschung" class="text-caption text-grey text-decoration-none">
              {{ t('LoginView.dataDeletion') }}
            </router-link>
            <v-alert v-if="updateLoading" type="info" variant="tonal" density="compact" class="mt-3">
              <v-progress-linear indeterminate color="info" class="mb-1" />
              {{ t('LoginView.updateButton') }}…
            </v-alert>
            <v-alert v-if="updateSuccess" type="success" variant="tonal" density="compact" class="mt-3">
              {{ t('LoginView.updateSuccess') }}
            </v-alert>
            <v-alert v-if="updateError" type="error" variant="tonal" closable density="compact" class="mt-3" @click:close="updateError = ''">
              {{ updateError }}
            </v-alert>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
  </v-container>
</template>

<script>
import { ref } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { oserpStore } from '@/core/stores/oserp.store.js'
import ErrorView from '@/core/components/messages/error.view.vue'
import { AuthStatus } from '@/core/constants/auth.js';
import { MOTORDESK_FALLBACK_LOGO } from '@/core/branding.js'

export default {
  name: 'LoginView',
  components: { ErrorView },
  setup() {
    const { t } = useI18n()
    const router = useRouter()
    const route  = useRoute()
    const oserp  = oserpStore()
    const fallbackLogo = MOTORDESK_FALLBACK_LOGO

    // Form-State
    const username    = ref('')
    const password    = ref('')
    const isAdminRedirect = String(route.query.redirect || '').includes('/admin.html')
    const clientCode  = ref(isAdminRedirect ? '0' : '')
    const clientPlaceholder = isAdminRedirect ? '0' : '2200'
    const rememberMe  = ref(false)

    // UI-State
    const loading      = ref(false)
    const errorMessage = ref('')
    const errorType    = ref('warning')

    // Update-State
    const updateLoading = ref(false)
    const updateSuccess = ref(false)
    const updateError   = ref('')

    const clearError = () => {
      errorMessage.value = ''
      errorType.value = 'warning'
    }

    const normalizeCompanyNumber = (value) => String(value || '').replace(/[^0-9]+/g, '')

    // Schema-Mismatch: Tabelle/Spalte fehlt in DB -> Update-Skript kann das beheben.
    // PG-SQLSTATE: 42703 = undefined column, 42P01 = undefined table.
    const isSchemaMismatchError = (err) => {
      if (err?.code !== 'API_DATABASE_ERROR') return false
      const msg = err?.message || ''
      return msg.includes('SQLSTATE[42703]') || msg.includes('SQLSTATE[42P01]')
    }

    const login = async (isRetry = false) => {
      clearError()
      if (!username.value || !password.value || !clientCode.value) {
        errorMessage.value = t('LoginView.EMPTY_FIELDS')
        errorType.value    = 'warning'
        return
      }

      loading.value = true
      try {
        const result = await oserp.login(username.value, password.value, clientCode.value, rememberMe.value)

        if (result === AuthStatus.UPDATE_REQUIRED) {
          router.replace({ name: 'update' })
          return
        }

        const redirectPath = route.query.redirect || '/'
        router.replace(redirectPath)
      } catch (err) {
        // Template-Handler uebergeben Event-Objekte; deshalb strikter Vergleich auf true
        if (isRetry !== true && isSchemaMismatchError(err)) {
          loading.value = false
          const ok = await runUpdate()
          if (ok) {
            await login(true)
            return
          }
          errorMessage.value = t('LoginView.updateError')
          errorType.value    = 'error'
          return
        }
        const i18nKey = `LoginView.${err?.code || 'ERROR'}`
        errorMessage.value = t(i18nKey)
        errorType.value    = 'error'
      } finally {
        loading.value = false
      }
    }

    const runUpdate = async () => {
      updateLoading.value = true
      updateSuccess.value = false
      updateError.value   = ''

      try {
        const response = await fetch('/api/update/', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ action: 'updateSchema', dry_run: false })
        })
        const data = await response.json()

        if (data.success) {
          updateSuccess.value = true
          return true
        }
        updateError.value = readUpdateError(data)
        return false
      } catch (error) {
        console.error('Fehler beim Update:', error)
        updateError.value = t('LoginView.updateError')
        return false
      } finally {
        updateLoading.value = false
      }
    }

    const readUpdateError = (data) => {
      const firstError = data?.payload?.errors?.[0]
      if (firstError) return firstError

      const firstClientError = data?.payload?.clients
        ?.flatMap(client => client?.update?.errors || [])
        ?.find(Boolean)
      if (firstClientError) return firstClientError

      return data?.text || t('LoginView.updateError')
    }

    return {
      t, username, password, clientCode, clientPlaceholder, rememberMe,
      fallbackLogo,
      loading, errorMessage, errorType, clearError, login,
      updateLoading, updateSuccess, updateError, runUpdate, normalizeCompanyNumber
    }
  }
}
</script>

<style scoped>
.motordesk-login-bar {
  background: rgb(var(--v-theme-surface)) !important;
  color: rgb(var(--v-theme-on-surface));
  border-bottom: 1px solid var(--md-color-line);
}

.motordesk-login-brand {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  color: var(--md-color-ink);
  text-decoration: none;
  font-weight: 800;
  letter-spacing: 0;
}

.motordesk-login-brand__logo {
  height: 38px;
  width: auto;
  max-width: 180px;
  object-fit: contain;
}

.v-theme--dark .motordesk-login-brand__logo {
  filter: brightness(1.45) contrast(1.05);
}

.update-trigger { cursor: default; user-select: none; }
</style>
