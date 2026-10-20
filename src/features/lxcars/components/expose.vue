<!-- src/features/lxcars/components/expose.vue -->

<template>
    <div class="doc" :style="accent_color ? { '--doc-accent': accent_color } : {}">
        <!-- Seite 1: Titelseite -->
        <section class="doc-page">
            <header class="doc-header">
                <img v-if="company_logo" :src="company_logo" class="doc-logo" alt="">
                <span v-else />
                <div class="doc-number">
                    <div class="doc-number__label">{{ $t('Expose.expose_number') }}</div>
                    <div class="doc-number__value">{{ car_document.expose_number }}</div>
                    <div class="doc-number__sub">{{ $t('PriceTag.vehicle_number') }} {{ car_document.vehicle_number }}</div>
                </div>
            </header>
            <div class="doc-body">
                <h1 class="doc-title">
                    <span class="doc-title__make">{{ car_document.c_m }}</span>
                    {{ car_document.c_mt }}
                </h1>
                <div class="doc-subtitle">{{ car_document.c_color }}</div>
                <div v-if="images[0]" class="doc-hero" :style="{ backgroundImage: `url(${images[0]})` }" />
                <div class="doc-facts">
                    <div v-for="fact in facts" :key="fact.key" class="doc-fact">
                        <div class="doc-fact__label">{{ $t('PriceTag.' + fact.key) }}</div>
                        <div class="doc-fact__value">{{ fact.value }}</div>
                    </div>
                </div>
                <div class="doc-price">
                    <span class="doc-price__label">{{ $t('PriceTag.price') }}</span>
                    <span class="doc-price__value">{{ money(car_document.asking_price) }}</span>
                </div>
            </div>
            <footer class="doc-footer">
                <span>{{ $t('PriceTag.page', { n: 1, total: 3 }) }}</span>
                <span>{{ car_document.expose_number }}</span>
            </footer>
        </section>

        <!-- Seite 2: Technische Daten, Ausstattung, Beschreibung -->
        <section class="doc-page">
            <header class="doc-header">
                <img v-if="company_logo" :src="company_logo" class="doc-logo" alt="">
                <span v-else />
                <div class="doc-number">
                    <div class="doc-number__label">{{ $t('Expose.expose_number') }}</div>
                    <div class="doc-number__value">{{ car_document.expose_number }}</div>
                    <div class="doc-number__sub">{{ car_document.c_m }} {{ car_document.c_mt }}</div>
                </div>
            </header>
            <div class="doc-body">
                <h2 class="doc-heading">{{ $t('Expose.technical_data') }}</h2>
                <table class="doc-table">
                    <tr v-for="row in technical_rows" :key="row.key">
                        <td>{{ $t('Expose.fields.' + row.key) }}</td>
                        <td>{{ row.value }}</td>
                    </tr>
                </table>
                <template v-if="car_document.tag_equipment">
                    <h2 class="doc-heading">{{ $t('Expose.equipment') }}</h2>
                    <div class="doc-list">{{ car_document.tag_equipment }}</div>
                </template>
                <template v-if="car_document.description">
                    <h2 class="doc-heading">{{ $t('Expose.description') }}</h2>
                    <div class="doc-text">{{ car_document.description }}</div>
                </template>
            </div>
            <footer class="doc-footer">
                <span>{{ $t('PriceTag.page', { n: 2, total: 3 }) }}</span>
                <span>{{ car_document.expose_number }}</span>
            </footer>
        </section>

        <!-- Seite 3: Verbrauch und CO₂ -->
        <section class="doc-page">
            <header class="doc-header">
                <img v-if="company_logo" :src="company_logo" class="doc-logo" alt="">
                <span v-else />
                <div class="doc-number">
                    <div class="doc-number__label">{{ $t('Expose.expose_number') }}</div>
                    <div class="doc-number__value">{{ car_document.expose_number }}</div>
                    <div class="doc-number__sub">{{ car_document.c_m }} {{ car_document.c_mt }}</div>
                </div>
            </header>
            <div class="doc-body">
                <EfficiencyBlock :car_document="car_document" />
                <div class="doc-note" style="margin-top: 16mm">{{ $t('Expose.terms') }}</div>
            </div>
            <footer class="doc-footer">
                <span>{{ $t('PriceTag.page', { n: 3, total: 3 }) }}</span>
                <span>{{ car_document.expose_number }}</span>
            </footer>
        </section>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import EfficiencyBlock from './efficiency-block.vue';
import { carDocumentFacts, formatMoney as money, formatMonthYear, formatKm, formatPower } from './document-format.js';
import './documents.css';

const props = defineProps({
    car_document: { type: Object, required: true },
    company_logo: { type: String, default: '' },
    accent_color: { type: String, default: null },
    images: { type: Array, default: () => [] },
});

const { t } = useI18n();

const facts = computed(() => carDocumentFacts(props.car_document));

const technical_rows = computed(() => {
    const c = props.car_document;
    return [
        { key: 'first_registration', value: formatMonthYear(c.c_d) },
        { key: 'mileage', value: formatKm(c.c_km) },
        { key: 'fuel', value: c.fuel_type },
        { key: 'power', value: formatPower(c.power_kw) },
        { key: 'transmission', value: c.transmission },
        { key: 'engine_code', value: c.engine_code },
        { key: 'c_color', value: c.c_color },
        { key: 'paint_code', value: c.paint_code },
        { key: 'doors', value: c.doors },
        { key: 'seats', value: c.seats },
        { key: 'previous_owners', value: c.previous_owners },
        { key: 'accident_free', value: c.accident_free == null ? null : t(c.accident_free ? 'Expose.yes' : 'Expose.no') },
        { key: 'hu', value: formatMonthYear(c.c_hu) },
        { key: 'c_fin', value: c.c_fin },
    ].filter(row => row.value !== null && row.value !== undefined && row.value !== '');
});
</script>
