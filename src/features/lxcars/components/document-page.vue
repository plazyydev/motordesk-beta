<!-- src/features/lxcars/components/document-page.vue — eine A4-Seite mit Kopf (Logo, Nummer) und Fuss -->

<template>
    <section class="doc-page">
        <header class="doc-header">
            <img v-if="company_logo" :src="company_logo" class="doc-logo" alt="">
            <span v-else />
            <div class="doc-number">
                <div class="doc-number__label">{{ number_label }}</div>
                <div class="doc-number__value">{{ number_value }}</div>
                <div v-if="number_sub" class="doc-number__sub">{{ number_sub }}</div>
            </div>
        </header>
        <div class="doc-body">
            <slot />
        </div>
        <footer class="doc-footer">
            <span>{{ $t('PriceTag.page', { n: page, total }) }}</span>
            <span>{{ number_value }}</span>
        </footer>
    </section>
</template>

<script setup>
defineProps({
    number_label: { type: String, required: true },
    number_value: { type: String, default: '' },
    number_sub: { type: String, default: '' },
    company_logo: { type: String, default: '' },
    page: { type: Number, required: true },
    total: { type: Number, required: true },
});
</script>
