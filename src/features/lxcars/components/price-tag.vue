<!-- src/features/lxcars/components/price-tag.vue -->

<template>
    <div class="doc" :style="accent_color ? { '--doc-accent': accent_color } : {}">
        <!-- Seite 1: Preisschild -->
        <DocumentPage
            :number_label="$t('PriceTag.vehicle_number')"
            :number_value="car_document.vehicle_number"
            :number_sub="car_document.c_ln"
            :company_logo="company_logo"
            :page="1"
            :total="2"
        >
            <h1 class="doc-title">
                <span class="doc-title__make">{{ car_document.c_m }}</span>
                {{ car_document.c_mt }}
            </h1>

            <div v-if="facts.length" class="doc-facts doc-facts--top doc-facts--compact">
                <div v-for="fact in facts" :key="fact.key" class="doc-fact">
                    <div class="doc-fact__label">{{ $t('PriceTag.' + fact.key) }}</div>
                    <div class="doc-fact__value">{{ fact.value }}</div>
                </div>
            </div>

            <div class="doc-fill">
                <template v-if="technical_rows.length">
                    <h2 class="doc-heading">{{ $t('Expose.technical_data') }}</h2>
                    <div class="doc-inline">
                        <span v-for="row in technical_rows" :key="row.key">
                            {{ $t('Expose.fields.' + row.key) }}: <strong>{{ row.value }}</strong>
                        </span>
                    </div>
                </template>

                <template v-if="equipment_lines.length">
                    <h2 class="doc-heading">{{ $t('Expose.equipment') }}</h2>
                    <ul class="doc-list doc-list--small">
                        <li v-for="line in equipment_lines" :key="line">{{ line }}</li>
                    </ul>
                </template>

                <template v-if="car_document.description">
                    <h2 class="doc-heading">{{ $t('Expose.description') }}</h2>
                    <div class="doc-text doc-text--clamp">{{ car_document.description }}</div>
                </template>
            </div>

            <div class="doc-price">
                <span class="doc-price__label">{{ $t('PriceTag.price') }}</span>
                <span class="doc-price__value">{{ money(car_document.asking_price) }}</span>
            </div>
        </DocumentPage>

        <!-- Seite 2: Verbrauch und CO₂ -->
        <DocumentPage
            :number_label="$t('PriceTag.vehicle_number')"
            :number_value="car_document.vehicle_number"
            :number_sub="car_document.c_ln"
            :company_logo="company_logo"
            :page="2"
            :total="2"
        >
            <h1 class="doc-title">
                <span class="doc-title__make">{{ car_document.c_m }}</span>
                {{ car_document.c_mt }}
            </h1>
            <EfficiencyBlock :car_document="car_document" />
            <div v-if="car_document.terms" class="doc-note doc-note--bottom">{{ car_document.terms }}</div>
        </DocumentPage>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import DocumentPage from './document-page.vue';
import EfficiencyBlock from './efficiency-block.vue';
import { carDocumentFacts, carDocumentTechnicalRows, formatMoney as money, textLines } from './document-format.js';
import './documents.css';

const props = defineProps({
    car_document: { type: Object, required: true },
    company_logo: { type: String, default: '' },
    accent_color: { type: String, default: null },
});

const { t } = useI18n();

const facts = computed(() => carDocumentFacts(props.car_document));
const technical_rows = computed(() => carDocumentTechnicalRows(props.car_document, t));
const equipment_lines = computed(() => textLines(props.car_document.tag_equipment));
</script>
