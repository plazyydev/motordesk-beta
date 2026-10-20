<!-- src/features/lxcars/components/price-tag.vue -->

<template>
    <div class="doc" :style="accent_color ? { '--doc-accent': accent_color } : {}">
        <!-- Seite 1: Preisschild -->
        <section class="doc-page">
            <header class="doc-header">
                <img v-if="company_logo" :src="company_logo" class="doc-logo" alt="">
                <span v-else />
                <div class="doc-number">
                    <div class="doc-number__label">{{ $t('PriceTag.vehicle_number') }}</div>
                    <div class="doc-number__value">{{ car_document.vehicle_number }}</div>
                    <div class="doc-number__sub">{{ car_document.c_ln }}</div>
                </div>
            </header>

            <div class="doc-body">
                <h1 class="doc-title">
                    <span class="doc-title__make">{{ car_document.c_m }}</span>
                    {{ car_document.c_mt }}
                </h1>
                <div class="doc-subtitle" />

                <div class="doc-facts">
                    <div v-for="fact in facts" :key="fact.key" class="doc-fact">
                        <div class="doc-fact__label">{{ $t('PriceTag.' + fact.key) }}</div>
                        <div class="doc-fact__value">{{ fact.value }}</div>
                    </div>
                </div>

                <div class="doc-list">{{ car_document.tag_equipment }}</div>

                <div class="doc-price">
                    <span class="doc-price__label">{{ $t('PriceTag.price') }}</span>
                    <span class="doc-price__value">{{ money(car_document.asking_price) }}</span>
                </div>
            </div>

            <footer class="doc-footer">
                <span>{{ $t('PriceTag.page', { n: 1, total: 2 }) }}</span>
                <span>{{ car_document.vehicle_number }}</span>
            </footer>
        </section>

        <!-- Seite 2: Verbrauch und CO₂ -->
        <section class="doc-page">
            <header class="doc-header">
                <img v-if="company_logo" :src="company_logo" class="doc-logo" alt="">
                <span v-else />
                <div class="doc-number">
                    <div class="doc-number__label">{{ $t('PriceTag.vehicle_number') }}</div>
                    <div class="doc-number__value">{{ car_document.vehicle_number }}</div>
                    <div class="doc-number__sub">{{ car_document.c_ln }}</div>
                </div>
            </header>
            <div class="doc-body">
                <h1 class="doc-title">
                    <span class="doc-title__make">{{ car_document.c_m }}</span>
                    {{ car_document.c_mt }}
                </h1>
                <div class="doc-subtitle" />
                <EfficiencyBlock :car_document="car_document" />
            </div>
            <footer class="doc-footer">
                <span>{{ $t('PriceTag.page', { n: 2, total: 2 }) }}</span>
                <span>{{ car_document.vehicle_number }}</span>
            </footer>
        </section>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import EfficiencyBlock from './efficiency-block.vue';
import { carDocumentFacts, formatMoney as money } from './document-format.js';
import './documents.css';

const props = defineProps({
    car_document: { type: Object, required: true },
    company_logo: { type: String, default: '' },
    accent_color: { type: String, default: null },
});

const facts = computed(() => carDocumentFacts(props.car_document));
</script>
