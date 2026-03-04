<template>
  <Teleport to="body">
    <div class="search-overlay" @click.self="$emit('close')">
      <div class="search-modal">
        <div class="search-input-row">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="11" cy="11" r="8" />
            <path d="m21 21-4.35-4.35" />
          </svg>
          <input
            ref="inputRef"
            v-model="query"
            type="text"
            placeholder="Search documentation..."
            class="search-input"
            @input="onInput"
            @keydown.down.prevent="moveFocus(1)"
            @keydown.up.prevent="moveFocus(-1)"
            @keydown.enter.prevent="selectFocused"
            @keydown.esc="$emit('close')"
          />
          <button class="search-close" @click="$emit('close')">ESC</button>
        </div>

        <div class="search-results" v-if="query.length >= 2">
          <div v-if="loading" class="search-state">
            <div class="search-spinner" />
            Searching...
          </div>

          <div v-else-if="results.length === 0" class="search-state">
            No results for "<strong>{{ query }}</strong>"
          </div>

          <ul v-else class="results-list">
            <li
              v-for="(result, index) in results"
              :key="result.slug"
              class="result-item"
              :class="{ focused: focusIndex === index }"
              @mouseenter="focusIndex = index"
              @click="select(result)"
            >
              <div class="result-title" v-html="highlightQuery(result.title, query)" />
              <div class="result-snippet" v-html="highlightQuery(result.snippet, query)" />
              <div class="result-slug">{{ result.slug }}</div>
            </li>
          </ul>
        </div>

        <div v-else class="search-hint">
          <p>Type at least 2 characters to search.</p>
          <div class="search-shortcuts">
            <span><kbd>↑</kbd><kbd>↓</kbd> navigate</span>
            <span><kbd>↵</kbd> open</span>
            <span><kbd>esc</kbd> close</span>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, nextTick, onMounted } from 'vue';

const emit = defineEmits(['close', 'navigate']);

const query = ref('');
const results = ref([]);
const loading = ref(false);
const focusIndex = ref(0);
const inputRef = ref(null);

let debounceTimer = null;

onMounted(() => nextTick(() => inputRef.value?.focus()));

function onInput() {
  focusIndex.value = 0;
  clearTimeout(debounceTimer);

  if (query.value.length < 2) {
    results.value = [];
    return;
  }

  loading.value = true;
  debounceTimer = setTimeout(async () => {
    try {
      const res = await fetch(`/docs-api/search?q=${encodeURIComponent(query.value)}`);
      const data = await res.json();
      results.value = data.results;
    } finally {
      loading.value = false;
    }
  }, 250);
}

function moveFocus(direction) {
  focusIndex.value = Math.max(0, Math.min(results.value.length - 1, focusIndex.value + direction));
}

function selectFocused() {
  if (results.value[focusIndex.value]) {
    select(results.value[focusIndex.value]);
  }
}

function select(result) {
  emit('navigate', result.slug);
  emit('close');
}

function highlightQuery(text, queryText) {
  if (!text || !queryText) return text;
  const escaped = queryText.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
  return text.replace(new RegExp(`(${escaped})`, 'gi'), '<mark class="search-highlight">$1</mark>');
}
</script>

