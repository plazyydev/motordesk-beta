<template>
  <v-main class="motordesk-shell">
    <div
      v-if="!ready"
      class="motordesk-shell__loading"
      role="status"
      aria-live="polite"
    >
      <slot name="loading" />
    </div>
    <div
      v-else
      class="motordesk-shell__content"
      :class="contentClass"
    >
      <slot />
    </div>
  </v-main>
</template>

<script setup>
defineProps({
  ready: {
    type: Boolean,
    default: false,
  },
  contentClass: {
    type: [String, Array, Object],
    default: '',
  },
})
</script>

<style scoped>
.motordesk-shell {
  min-height: 100vh;
  background: var(--md-color-canvas);
}

.motordesk-shell__loading {
  min-height: 100vh;
  display: grid;
  place-items: center;
  padding: var(--md-space-xl);
}

.motordesk-shell__content {
  min-height: 100vh;
}
</style>
