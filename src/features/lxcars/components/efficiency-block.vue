<!-- src/features/lxcars/components/efficiency-block.vue -->

<template>
    <div>
        <h2 class="doc-heading">{{ $t('PriceTag.efficiency_title') }}</h2>
        <div class="doc-efficiency">
            <div class="doc-scale">
                <div
                    v-for="(cls, i) in efficiency_classes"
                    :key="cls"
                    class="doc-scale__row"
                    :class="{ 'doc-scale__row--active': cls === car_document.efficiency_class }"
                    :style="{ background: efficiency_colors[i], width: (34 + i * 8) + 'mm' }"
                >{{ cls }}</div>
            </div>
            <div class="doc-efficiency__values">
                <div>{{ $t('PriceTag.efficiency_class') }}: <strong>{{ car_document.efficiency_class || '-' }}</strong></div>
                <div>{{ $t('PriceTag.co2') }}: <strong>{{ car_document.co2_g_km ?? '-' }} g/km</strong></div>
                <div>{{ $t('PriceTag.consumption') }}: <strong>{{ car_document.consumption_combined ?? '-' }} l/100 km</strong></div>
            </div>
        </div>
        <div class="doc-note">{{ $t('PriceTag.disclaimer') }}</div>
    </div>
</template>

<script setup>
defineProps({
    car_document: { type: Object, required: true },
});

// Die Klassen A+ bis G sind gesetzlich festgelegt (PKW-EnVKV); die Farben laufen von gruen nach rot
const efficiency_classes = ['A+', 'A', 'B', 'C', 'D', 'E', 'F', 'G'];
const efficiency_colors = ['#00a651', '#4cb848', '#bed630', '#fff200', '#fdb913', '#f58220', '#ed1c24', '#b5121b'];
</script>
