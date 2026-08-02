import { createApp } from 'vue'
import { createPinia } from 'pinia'
import AdminPanel from './AdminPanel.vue'

import 'vuetify/styles'
import vuetify from '@/core/theme/vuetify.js'
import '@mdi/font/css/materialdesignicons.css'
import '@/style.css'

const app = createApp(AdminPanel)

app.use(createPinia())
app.use(vuetify)
app.mount('#admin-app')
