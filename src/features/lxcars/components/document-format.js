// src/features/lxcars/components/document-format.js — Formatierung fuer Preisschild und Exposé

export function formatMoney(value) {
    if (value === null || value === undefined || value === '') return '-';
    return new Intl.NumberFormat('de-DE', { style: 'currency', currency: 'EUR', maximumFractionDigits: 0 }).format(Number(value));
}

export function formatMonthYear(value) {
    if (!value) return null;
    const [year, month] = value.split('-');
    return `${month}/${year}`;
}

export function formatKm(value) {
    return value != null ? new Intl.NumberFormat('de-DE').format(value) + ' km' : null;
}

export function formatPower(power_kw) {
    return power_kw != null ? `${power_kw} kW (${Math.round(power_kw * 1.36)} PS)` : null;
}

/** Lack als "Lackname (Lackcode)"; fehlt eines von beiden, steht nur das andere da */
export function formatPaint(c_color, paint_code) {
    const code = paint_code ? `(${paint_code})` : '';
    return [c_color, code].filter(Boolean).join(' ') || null;
}

/** Die Eckdaten, die auf Preisschild und Exposé-Titelseite stehen; leere Werte entfallen */
export function carDocumentFacts(car_document) {
    return [
        { key: 'first_registration', value: formatMonthYear(car_document.c_d) },
        { key: 'mileage', value: formatKm(car_document.c_km) },
        { key: 'fuel', value: car_document.fuel_type },
        { key: 'power', value: formatPower(car_document.power_kw) },
        { key: 'transmission', value: car_document.transmission },
        { key: 'engine_code', value: car_document.engine_code },
        { key: 'paint', value: formatPaint(car_document.c_color, car_document.paint_code) },
        { key: 'hu', value: formatMonthYear(car_document.c_hu) },
    ].filter(fact => fact.value);
}
