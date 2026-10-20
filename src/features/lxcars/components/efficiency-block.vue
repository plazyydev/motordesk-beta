<!-- src/features/lxcars/components/efficiency-block.vue -->

<template>
    <div>
        <h2 class="doc-heading">{{ $t('PriceTag.efficiency_title') }}</h2>
        <div class="doc-efficiency">
            <div v-if="car_document.efficiency_class" class="doc-scale">
                <div
                    v-for="(cls, i) in efficiency_classes"
                    :key="cls"
                    class="doc-scale__row"
                    :class="{ 'doc-scale__row--active': cls === car_document.efficiency_class }"
                    :style="{ background: efficiency_colors[i], width: (26 + i * 5) + 'mm' }"
                >{{ cls }}</div>
            </div>
            <table class="doc-values">
                <tr v-if="car_document.efficiency_class">
                    <td>{{ $t('PriceTag.efficiency_class') }}</td>
                    <td><strong>{{ car_document.efficiency_class }}</strong></td>
                </tr>
                <tr v-if="car_document.co2_g_km != null">
                    <td>{{ $t('PriceTag.co2') }}</td>
                    <td><strong>{{ car_document.co2_g_km }} g/km</strong></td>
                </tr>
                <tr v-for="row in consumption_rows" :key="row.key">
                    <td>{{ $t('PriceTag.' + row.key) }}</td>
                    <td><strong>{{ row.value }} {{ car_document.consumption_unit }}</strong></td>
                </tr>
            </table>
        </div>
        <div class="doc-note">{{ $t('PriceTag.disclaimer') }}</div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { filled } from './document-format.js';

const props = defineProps({
    car_document: { type: Object, required: true },
});

// Die Klassen A+ bis G sind gesetzlich festgelegt (PKW-EnVKV); die Farben laufen von gruen nach rot
const efficiency_classes = ['A+', 'A', 'B', 'C', 'D', 'E', 'F', 'G'];
const efficiency_colors = ['#00a651', '#4cb848', '#bed630', '#fff200', '#fdb913', '#f58220', '#ed1c24', '#b5121b'];

const consumption_rows = computed(() => filled([
    { key: 'consumption_combined', value: props.car_document.consumption_combined },
    { key: 'consumption_city', value: props.car_document.consumption_city },
    { key: 'consumption_highway', value: props.car_document.consumption_highway },
]));
</script>
