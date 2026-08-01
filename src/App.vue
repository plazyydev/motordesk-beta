<!-- App.vue -->
<template>
  <v-app>
    <MotorDeskShell :ready="appReady">
      <template #loading>
        <div class="app-loading">
          <v-icon size="64" color="primary" class="mb-4">mdi-timer-sand</v-icon>
          <div class="text-h6 text-grey-darken-1">{{ t('App.loading') }}</div>
        </div>
      </template>
      <router-view :key="route.path" />
    </MotorDeskShell>
  </v-app>
</template>

<script setup>
import { ref, provide } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useUserPrefs } from '@/core/composables/useUserPrefs.js'
import MotorDeskShell from '@/core/components/app-shell/motordesk-shell.vue'

const { t } = useI18n()
useUserPrefs()
const router = useRouter()
const route = useRoute()
const appReady = ref(false)

provide('appReady', appReady)

router.isReady().then(() => {
  appReady.value = true
})
</script>

<style scoped>
.app-loading {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 100vh;
}
</style>
