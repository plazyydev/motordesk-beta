import { createApp } from 'vue'
import App from './App.vue'
import router from './core/router'
import { createPinia } from 'pinia'
import { oserpStore } from '@/core/stores/oserp.store'

// i18n
import i18n from './i18n'

// Vuetify (Tree-Shaking via vite-plugin-vuetify — kein manueller Import nötig)
import 'vuetify/styles'
import vuetify from './core/theme/vuetify.js'
import '@mdi/font/css/materialdesignicons.css'
import './style.css'
import preserveCursor from '@/core/directives/preserveCursor'

// App & Plugins
const pinia = createPinia()
const app = createApp(App)

// Globale Zahlformatierungsfunktion
app.config.globalProperties.$n = (value, opts = {}) => {
  const num = typeof value === 'string' ? Number(value) : value
  if (!Number.isFinite(num)) return ''
  return new Intl.NumberFormat(i18n.global.locale.value, {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
    ...opts
  }).format(num)
}

// Plugins registrieren
app.use(router)
app.use(pinia)
app.use(i18n)
app.use(vuetify)
app.directive('preserve-cursor', preserveCursor)

/* 🔥 WICHTIG: NACH pinia */
const store = oserpStore()

// Debug-Modus prüfen
if (!store.isDebugMode()) {
  // Todo: console.log wieder aktivieren, wenn Debug-Modus aus
  //console.log = () => {}
  console.debug = () => {}
  console.warn = () => {}
}

// Mount
app.mount('#app')
