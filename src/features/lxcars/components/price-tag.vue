<!-- src/features/lxcars/components/price-tag.vue -->

<template>
    <div class="price-tag" :style="accent_color ? { '--pt-accent': accent_color } : {}">
        <div class="price-tag__header">
            <img v-if="company_logo" :src="company_logo" class="price-tag__logo" alt="">
            <span v-else />
            <span class="price-tag__plate">{{ car_price_tag.c_ln }}</span>
        </div>

        <div class="price-tag__body">
            <h1 class="price-tag__title">
                <span class="price-tag__title-make">{{ car_price_tag.c_m }}</span>
                {{ car_price_tag.c_mt }}
            </h1>
            <div class="price-tag__subtitle">{{ car_price_tag.c_color }}</div>

            <div class="price-tag__facts">
                <div v-for="fact in facts" :key="fact.key" class="price-tag__fact">
                    <div class="price-tag__fact-label">{{ $t('PriceTag.' + fact.key) }}</div>
                    <div class="price-tag__fact-value">{{ fact.value }}</div>
                </div>
            </div>

            <div class="price-tag__equipment">{{ car_price_tag.tag_equipment }}</div>

            <div class="price-tag__price-box">
                <span class="price-tag__price-label">{{ $t('PriceTag.price') }}</span>
                <span class="price-tag__price">{{ money(car_price_tag.asking_price) }}</span>
            </div>
        </div>

        <div class="price-tag__footer">
            <div class="price-tag__efficiency">
                <div class="price-tag__scale">
                    <div
                        v-for="(cls, i) in efficiency_classes"
                        :key="cls"
                        class="price-tag__scale-row"
                        :class="{ 'price-tag__scale-row--active': cls === car_price_tag.efficiency_class }"
                        :style="{ background: efficiency_colors[i], width: (22 + i * 5) + 'mm' }"
                    >{{ cls }}</div>
                </div>
                <div class="price-tag__efficiency-values">
                    <div>{{ $t('PriceTag.efficiency_class') }}: <strong>{{ car_price_tag.efficiency_class || '-' }}</strong></div>
                    <div>{{ $t('PriceTag.co2') }}: <strong>{{ car_price_tag.co2_g_km ?? '-' }} g/km</strong></div>
                    <div>{{ $t('PriceTag.consumption') }}: <strong>{{ car_price_tag.consumption_combined ?? '-' }} l/100 km</strong></div>
                </div>
            </div>
            <div class="price-tag__disclaimer">{{ $t('PriceTag.disclaimer') }}</div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import './price-tag.css';

const props = defineProps({
    car_price_tag: { type: Object, required: true },
    company_logo: { type: String, default: '' },
    accent_color: { type: String, default: null },
});

const { t } = useI18n();

// Farbverlauf der Effizienzskala von gruen (A+) nach rot (G); die Klassen selbst sind gesetzlich festgelegt (PKW-EnVKV)
const efficiency_classes = ['A+', 'A', 'B', 'C', 'D', 'E', 'F', 'G'];
const efficiency_colors = ['#00a651', '#4cb848', '#bed630', '#fff200', '#fdb913', '#f58220', '#ed1c24', '#b5121b'];

function money(value) {
    if (value === null || value === undefined || value === '') return '-';
    return new Intl.NumberFormat('de-DE', { style: 'currency', currency: 'EUR', maximumFractionDigits: 0 }).format(Number(value));
}

function date(value) {
    if (!value) return null;
    const [year, month] = value.split('-');
    return `${month}/${year}`;
}

const facts = computed(() => {
    const c = props.car_price_tag;
    return [
        { key: 'first_registration', value: date(c.c_d) },
        { key: 'mileage', value: c.c_km != null ? new Intl.NumberFormat('de-DE').format(c.c_km) + ' km' : null },
        { key: 'fuel', value: c.fuel_type },
        { key: 'power', value: c.power_kw != null ? `${c.power_kw} kW (${Math.round(c.power_kw * 1.36)} PS)` : null },
        { key: 'hu', value: date(c.c_hu) },
    ].filter(f => f.value);
});
</script>
