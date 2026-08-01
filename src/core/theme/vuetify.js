import { createVuetify } from 'vuetify'
import { de, en } from 'vuetify/locale'
import { motordeskThemeNames, motordeskVuetifyThemes } from './motordesk.tokens.js'

const vuetify = createVuetify({
    locale: {
        locale: 'de',
        fallback: 'en',
        messages: { de, en },
    },
    theme: {
        defaultTheme: motordeskThemeNames.light,
        themes: motordeskVuetifyThemes,
    },
    defaults: {
        VBtn: {
            rounded: 'sm',
        },
        VCard: {
            rounded: 'sm',
        },
        VDialog: {
            scrollable: true,
        },
        VTextField: {
            density: 'comfortable',
        },
        VSelect: {
            density: 'comfortable',
        },
        VTextarea: {
            density: 'comfortable',
        },
        VAutocomplete: {
            density: 'comfortable',
        },
    },
})

export default vuetify
