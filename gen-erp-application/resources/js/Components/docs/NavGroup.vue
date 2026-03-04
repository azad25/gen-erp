<template>
  <a
    v-if="item.type === 'file'"
    class="nav-item"
    :class="{ active: item.slug === currentSlug }"
    @click.prevent="$emit('navigate', item.slug)"
  >
    {{ item.title }}
  </a>

  <div v-else-if="item.type === 'folder'" class="nav-group">
    <button class="nav-group-header" @click="open = !open" :class="{ open }">
      <span>{{ item.title }}</span>
      <svg
        class="chevron"
        width="14"
        height="14"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="2.5"
      >
        <polyline points="9 18 15 12 9 6" />
      </svg>
    </button>

    <Transition name="nav-collapse">
      <div v-if="open" class="nav-children">
        <NavGroup
          v-for="child in item.children"
          :key="child.slug"
          :item="child"
          :current-slug="currentSlug"
          @navigate="$emit('navigate', $event)"
        />
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
  item: { type: Object, required: true },
  currentSlug: { type: String, default: '' },
});

defineEmits(['navigate']);

const hasActivePage = (items, slug) => items?.some(i => i.slug === slug || hasActivePage(i.children, slug));

const open = ref(hasActivePage(props.item.children, props.currentSlug));

watch(
  () => props.currentSlug,
  slug => {
    if (hasActivePage(props.item.children, slug)) open.value = true;
  },
);
</script>

<style scoped>
.nav-collapse-enter-active,
.nav-collapse-leave-active {
  transition: all 0.2s ease;
  overflow: hidden;
}
.nav-collapse-enter-from,
.nav-collapse-leave-to {
  max-height: 0;
  opacity: 0;
}
.nav-collapse-enter-to,
.nav-collapse-leave-from {
  max-height: 1000px;
  opacity: 1;
}

.chevron {
  transition: transform 0.2s ease;
}
.nav-group-header.open .chevron {
  transform: rotate(90deg);
}
</style>

