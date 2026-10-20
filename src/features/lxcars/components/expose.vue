<!-- src/features/lxcars/components/expose.vue -->

<template>
    <div class="doc" :style="accent_color ? { '--doc-accent': accent_color } : {}">
        <!-- Seite 1: Titelseite -->
        <DocumentPage v-bind="page_props(1)">
            <h1 class="doc-title">
                <span class="doc-title__make">{{ car_document.c_m }}</span>
                {{ car_document.c_mt }}
            </h1>

            <div v-if="images[0]" class="doc-hero" :style="{ backgroundImage: `url(${images[0]})` }" />

            <div v-if="facts.length" class="doc-facts doc-facts--top doc-facts--compact">
                <div v-for="fact in facts" :key="fact.key" class="doc-fact">
                    <div class="doc-fact__label">{{ $t('PriceTag.' + fact.key) }}</div>
                    <div class="doc-fact__value">{{ fact.value }}</div>
                </div>
            </div>

            <div v-if="car_document.expose_message" class="doc-text doc-message">{{ car_document.expose_message }}</div>

            <div class="doc-price doc-price--compact">
                <span class="doc-price__label">{{ $t('PriceTag.price') }}</span>
                <span class="doc-price__value">{{ money(car_document.asking_price) }}</span>
            </div>

            <div v-if="contact_cards.length" class="doc-contact">
                <div v-for="card in contact_cards" :key="card.key" class="doc-contact__card">
                    <div class="doc-fact__label">{{ $t('Expose.' + card.key) }}</div>
                    <div v-for="line in card.lines" :key="line" class="doc-contact__line">{{ line }}</div>
                </div>
            </div>
        </DocumentPage>

        <!-- Seite 2: Ausstattung -->
        <DocumentPage v-if="has_equipment_page" v-bind="page_props(2)">
            <template v-if="highlight_lines.length">
                <h2 class="doc-heading">{{ $t('Expose.highlights') }}</h2>
                <ul class="doc-highlights">
                    <li v-for="line in highlight_lines" :key="line">{{ line }}</li>
                </ul>
            </template>
            <template v-if="equipment_lines.length">
                <h2 class="doc-heading">{{ $t('Expose.equipment') }}</h2>
                <ul class="doc-list doc-list--small">
                    <li v-for="line in equipment_lines" :key="line">{{ line }}</li>
                </ul>
            </template>
            <template v-if="car_document.description">
                <h2 class="doc-heading">{{ $t('Expose.description') }}</h2>
                <div class="doc-text">{{ car_document.description }}</div>
            </template>
        </DocumentPage>

        <!-- Technische Daten und Verbrauch -->
        <DocumentPage v-bind="page_props(technical_page_number)">
            <h2 class="doc-heading">{{ $t('Expose.technical_data') }}</h2>
            <table class="doc-table">
                <tr v-for="row in technical_rows" :key="row.key">
                    <td>{{ $t('Expose.fields.' + row.key) }}</td>
                    <td>{{ row.value }}</td>
                </tr>
            </table>
            <EfficiencyBlock :car_document="car_document" />
            <div v-if="car_document.terms" class="doc-note doc-note--bottom">{{ car_document.terms }}</div>
        </DocumentPage>

        <!-- Fotoseiten: alle Fotos, sechs pro Seite -->
        <DocumentPage v-for="(group, i) in photo_groups" :key="i" v-bind="page_props(technical_page_number + 1 + i)">
            <h2 class="doc-heading">{{ $t('Expose.photos') }}</h2>
            <div class="doc-photos">
                <div v-for="image in group" :key="image" class="doc-photos__item" :style="{ backgroundImage: `url(${image})` }" />
            </div>
        </DocumentPage>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import DocumentPage from './document-page.vue';
import EfficiencyBlock from './efficiency-block.vue';
import { carDocumentFacts, carDocumentTechnicalRows, formatMoney as money, formatMonthYear, formatKm, formatPower, formatPaint, filled, textLines } from './document-format.js';
import './documents.css';

const PHOTOS_PER_PAGE = 6;

const props = defineProps({
    car_document: { type: Object, required: true },
    company_logo: { type: String, default: '' },
    accent_color: { type: String, default: null },
    images: { type: Array, default: () => [] },
});

const { t } = useI18n();

const facts = computed(() => carDocumentFacts(props.car_document));
const highlight_lines = computed(() => textLines(props.car_document.equipment_highlights));
const equipment_lines = computed(() => textLines(props.car_document.tag_equipment));

const has_equipment_page = computed(() =>
    highlight_lines.value.length > 0 || equipment_lines.value.length > 0 || !!props.car_document.description
);
const technical_page_number = computed(() => (has_equipment_page.value ? 3 : 2));

const photo_groups = computed(() => {
    const groups = [];
    for (let i = 0; i < props.images.length; i += PHOTOS_PER_PAGE) {
        groups.push(props.images.slice(i, i + PHOTOS_PER_PAGE));
    }
    return groups;
});
const total_pages = computed(() => technical_page_number.value + photo_groups.value.length);

// Alle technischen Angaben in einer Tabelle; leere Zeilen entfallen
const technical_rows = computed(() => {
    const c = props.car_document;
    return filled([
        { key: 'first_registration', value: formatMonthYear(c.c_d) },
        { key: 'mileage', value: formatKm(c.c_km) },
        { key: 'fuel', value: c.fuel_type },
        { key: 'power', value: formatPower(c.power_kw) },
        { key: 'transmission', value: c.transmission },
        { key: 'engine_code', value: c.engine_code },
        { key: 'paint', value: formatPaint(c.c_color, c.paint_code) },
        { key: 'hu', value: formatMonthYear(c.c_hu) },
        ...carDocumentTechnicalRows(c, t),
    ]);
});

// Ansprechpartner und Fahrzeugstandort; Karten ohne Angaben entfallen
const contact_cards = computed(() => {
    const c = props.car_document;
    return [
        { key: 'contact', lines: [c.contact_name, c.contact_phone, c.contact_email].filter(Boolean) },
        { key: 'location', lines: textLines(c.vehicle_location) },
    ].filter(card => card.lines.length);
});

function page_props(page) {
    return {
        number_label: t('Expose.expose_number'),
        number_value: props.car_document.expose_number,
        number_sub: `${t('PriceTag.vehicle_number')} ${props.car_document.vehicle_number}`,
        company_logo: props.company_logo,
        page,
        total: total_pages.value,
    };
}
</script>
