// src/core/composables/useUserPrefs.js

import { watch } from 'vue'
import { useTheme } from 'vuetify'
import { useI18n } from 'vue-i18n'
import { oserpStore } from '@/core/stores/oserp.store.js'
import { motordeskThemeNames, normalizeThemeMode } from '@/core/theme/motordesk.tokens.js'

/**
 * Wendet benutzerspezifische Einstellungen an (Dark Mode, Sprache).
 * Wird in App.vue aufgerufen und reagiert auf Session-Änderungen.
 */
export function useUserPrefs() {
    const theme = useTheme()
    const { locale } = useI18n()
    const oserp = oserpStore()
    const systemPrefersDark = window.matchMedia?.('(prefers-color-scheme: dark)')

    function boolFromConfig(value) {
        return value === true || value === 'true' || value === 't' || value === '1' || value === 1
    }

    function resolveThemeMode() {
        const explicitMode = oserp.getConfigValue('theme_mode', null)
        if (explicitMode !== null && explicitMode !== '') {
            return normalizeThemeMode(explicitMode)
        }

        const legacyDarkMode = oserp.getConfigValue('dark_mode', null)
        if (legacyDarkMode !== null && legacyDarkMode !== '') {
            return boolFromConfig(legacyDarkMode) ? 'dark' : 'light'
        }

        const companyDefault = oserp.getClientDefaultValue('company_default_theme', 'system')
        return normalizeThemeMode(companyDefault)
    }

    function applyTheme() {
        const mode = resolveThemeMode()
        const useDark = mode === 'dark' || (mode === 'system' && systemPrefersDark?.matches)
        theme.global.name.value = useDark ? motordeskThemeNames.dark : motordeskThemeNames.light
        document.documentElement.dataset.themeMode = mode
    }

    function apply() {
        applyTheme()

        // Locale
        const userLocale = oserp.getConfigValue('locale', '')
        const supportedLocales = ['de', 'en', 'pl', 'uk', 'ru', 'fr', 'nl', 'da', 'nb', 'sv', 'et', 'lv', 'lt', 'es', 'it', 'pt', 'cs', 'ro', 'tr', 'fi', 'zh']
        if (userLocale && supportedLocales.includes(userLocale)) {
            locale.value = userLocale
        }
    }

    // Auf Session-Änderungen reagieren (Login, Firmenwechsel)
    watch(() => oserp.session?.company_config?.company_employee_config, () => {
        apply()
    }, { deep: true })

    watch(() => oserp.session?.company_config?.defaults_oserp, () => {
        apply()
    }, { deep: true })

    systemPrefersDark?.addEventListener?.('change', applyTheme)

    // Sofort anwenden falls Session schon geladen
    apply()
}
