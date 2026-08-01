<template>
  <header class="motordesk-header">
    <div class="motordesk-header__title">
      <slot name="title">
        <span>{{ title }}</span>
      </slot>
    </div>
    <div v-if="$slots.actions" class="motordesk-header__actions">
      <slot name="actions" />
    </div>
  </header>
</template>

<script setup>
defineProps({
  title: {
    type: String,
    default: 'MotorDesk',
  },
})
</script>

<style scoped>
.motordesk-header {
  min-height: var(--md-toolbar-height);
  display: flex;
  align-items: center;
  gap: var(--md-space-md);
  padding: 0 var(--md-space-lg);
  border-bottom: 1px solid var(--md-color-line);
  background: var(--md-color-surface);
}

.motordesk-header__title {
  min-width: 0;
  flex: 1;
  color: var(--md-color-ink);
  font-size: 1rem;
  font-weight: 650;
}

.motordesk-header__actions {
  display: flex;
  align-items: center;
  gap: var(--md-space-sm);
}
</style>
