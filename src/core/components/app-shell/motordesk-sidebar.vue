<template>
  <aside class="motordesk-sidebar" :class="{ 'motordesk-sidebar--rail': rail }">
    <nav class="motordesk-sidebar__nav" aria-label="MotorDesk navigation">
      <button
        v-for="item in items"
        :key="item.value || item.title"
        type="button"
        class="motordesk-sidebar__item"
        :class="{ 'motordesk-sidebar__item--active': item.value === active }"
        :title="item.title"
        @click="$emit('select', item)"
      >
        <v-icon v-if="item.icon" size="20">{{ item.icon }}</v-icon>
        <span v-if="!rail" class="motordesk-sidebar__label">{{ item.title }}</span>
      </button>
    </nav>
  </aside>
</template>

<script setup>
defineProps({
  items: {
    type: Array,
    default: () => [],
  },
  active: {
    type: String,
    default: '',
  },
  rail: {
    type: Boolean,
    default: false,
  },
})

defineEmits(['select'])
</script>

<style scoped>
.motordesk-sidebar {
  width: 248px;
  min-height: 100%;
  padding: var(--md-space-sm);
  border-right: 1px solid var(--md-color-line);
  background: var(--md-color-surface);
}

.motordesk-sidebar--rail {
  width: 56px;
}

.motordesk-sidebar__nav {
  display: grid;
  gap: var(--md-space-xs);
}

.motordesk-sidebar__item {
  min-height: 40px;
  display: flex;
  align-items: center;
  gap: var(--md-space-sm);
  width: 100%;
  padding: 0 var(--md-space-sm);
  color: var(--md-color-muted);
  background: transparent;
  border: 0;
  border-radius: var(--md-radius-md);
  cursor: pointer;
  text-align: left;
}

.motordesk-sidebar__item:hover,
.motordesk-sidebar__item--active {
  color: var(--md-color-brand);
  background: var(--md-color-brand-soft);
}

.motordesk-sidebar__label {
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
</style>
