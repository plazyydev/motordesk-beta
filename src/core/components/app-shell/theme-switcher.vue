<template>
  <v-btn-toggle
    v-model="mode"
    density="compact"
    variant="outlined"
    mandatory
    divided
    class="motordesk-theme-switcher"
    @update:model-value="saveMode"
  >
    <v-btn value="light" icon="mdi-white-balance-sunny" :title="titleLight" />
    <v-btn value="system" icon="mdi-theme-light-dark" :title="titleSystem" />
    <v-btn value="dark" icon="mdi-moon-waning-crescent" :title="titleDark" />
  </v-btn-toggle>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { oserpStore } from '@/core/stores/oserp.store.js'
import { normalizeThemeMode } from '@/core/theme/motordesk.tokens.js'

const props = defineProps({
  titleLight: {
    type: String,
    default: 'Hell',
  },
  titleSystem: {
    type: String,
    default: 'System',
  },
  titleDark: {
    type: String,
    default: 'Dunkel',
  },
})

void props

const oserp = oserpStore()
const storedMode = computed(() => normalizeThemeMode(oserp.getConfigValue('theme_mode', 'system')))
const mode = ref(storedMode.value)

watch(storedMode, value => {
  mode.value = value
})

function saveMode(value) {
  const normalized = normalizeThemeMode(value)
  mode.value = normalized
  oserp.setConfigValue('theme_mode', normalized)
}
</script>

<style scoped>
.motordesk-theme-switcher {
  border-radius: var(--md-radius-md);
}
</style>
