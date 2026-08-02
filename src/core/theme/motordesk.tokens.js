export const motordeskPalette = {
    brand: '#1F6FEB',
    brandDark: '#1659C7',
    brandSoft: '#EAF2FF',
    ink: '#172033',
    muted: '#5F6B7A',
    line: '#DDE3EA',
    canvas: '#F6F8FB',
    surface: '#FFFFFF',
    success: '#168A53',
    warning: '#B76E00',
    error: '#C9372C',
    info: '#1F6FEB',
    darkCanvas: '#101418',
    darkSurface: '#171D24',
    darkElevated: '#202832',
    darkLine: '#344050',
    darkInk: '#E8EEF6',
    darkMuted: '#AAB6C4',
}

export const motordeskRadii = {
    xs: '3px',
    sm: '4px',
    md: '6px',
    lg: '8px',
}

export const motordeskSpacing = {
    xs: '4px',
    sm: '8px',
    md: '12px',
    lg: '16px',
    xl: '24px',
}

export const motordeskThemeNames = {
    light: 'light',
    dark: 'dark',
}

export const motordeskVuetifyThemes = {
    light: {
        dark: false,
        colors: {
            background: motordeskPalette.canvas,
            surface: motordeskPalette.surface,
            primary: motordeskPalette.brand,
            secondary: motordeskPalette.ink,
            accent: motordeskPalette.brandSoft,
            error: motordeskPalette.error,
            info: motordeskPalette.info,
            success: motordeskPalette.success,
            warning: motordeskPalette.warning,
        },
    },
    dark: {
        dark: true,
        colors: {
            background: motordeskPalette.darkCanvas,
            surface: motordeskPalette.darkSurface,
            primary: '#7DB1FF',
            secondary: motordeskPalette.darkInk,
            accent: motordeskPalette.darkElevated,
            error: '#FF8A80',
            info: '#7DB1FF',
            success: '#54D18A',
            warning: '#FFC266',
        },
    },
}

export function normalizeThemeMode(value, fallback = 'light') {
    const normalized = String(value || '').toLowerCase()
    if (['light', 'dark', 'system'].includes(normalized)) return normalized
    return fallback
}
