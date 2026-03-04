<template>
  <div class="docs-shell" :class="{ dark: isDark }">
    <header class="docs-topbar">
      <div class="docs-topbar-left">
        <button class="sidebar-toggle" @click="sidebarOpen = !sidebarOpen" aria-label="Toggle sidebar">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="3" y1="6" x2="21" y2="6" />
            <line x1="3" y1="12" x2="21" y2="12" />
            <line x1="3" y1="18" x2="21" y2="18" />
          </svg>
        </button>
        <a href="/docs" class="docs-logo">
          <span class="docs-logo-mark">GenERP</span>
          <span class="docs-logo-label">Docs</span>
        </a>
      </div>

      <div class="docs-topbar-center">
        <button class="search-trigger" @click="searchOpen = true">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="11" cy="11" r="8" />
            <path d="m21 21-4.35-4.35" />
          </svg>
          <span>Search documentation...</span>
          <kbd>⌘K</kbd>
        </button>
      </div>

      <div class="docs-topbar-right">
        <button class="icon-btn" @click="toggleDark" :title="isDark ? 'Light mode' : 'Dark mode'">
          <svg v-if="isDark" width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
            <path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z" />
          </svg>
          <svg v-else width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="4" />
            <path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41" />
          </svg>
        </button>
        <a href="/" class="btn-back">← Back to App</a>
      </div>
    </header>

    <div class="docs-body">
      <div v-if="sidebarOpen" class="sidebar-overlay" @click="sidebarOpen = false" />

      <aside class="docs-sidebar" :class="{ open: sidebarOpen }">
        <nav class="docs-nav">
          <template v-if="navLoading">
            <div v-for="i in 8" :key="i" class="nav-skeleton" />
          </template>

          <template v-else>
            <a
              href="/docs"
              class="nav-item nav-home"
              :class="{ active: currentSlug === 'index' || currentSlug === '' }"
              @click.prevent="navigate('index')"
            >
              🏠 Home
            </a>

            <NavGroup
              v-for="item in navigation"
              :key="item.slug"
              :item="item"
              :current-slug="currentSlug"
              @navigate="navigate"
            />
          </template>
        </nav>
      </aside>

      <main class="docs-main">
        <div class="docs-content-wrapper">
          <div v-if="pageLoading" class="page-loading">
            <div class="loading-bar" />
            <div class="loading-skeleton">
              <div class="sk-title" />
              <div class="sk-line" />
              <div class="sk-line sk-short" />
              <div class="sk-line" />
            </div>
          </div>

          <div v-else-if="pageError" class="page-error">
            <div class="error-icon">📄</div>
            <h2>Page not found</h2>
            <p>This documentation page doesn't exist yet.</p>
            <a href="/docs" @click.prevent="navigate('index')">← Back to home</a>
          </div>

          <article v-else-if="currentPage" class="docs-article">
            <div class="article-header">
              <h1 class="article-title">{{ currentPage.title }}</h1>
              <div class="article-meta">
                <span class="meta-date">Updated {{ currentPage.last_modified }}</span>
              </div>
            </div>

            <div class="docs-content" v-html="currentPage.html" />

            <nav class="page-nav" v-if="prevPage || nextPage">
              <a v-if="prevPage" class="page-nav-btn prev" @click.prevent="navigate(prevPage.slug)">
                <span class="nav-label">← Previous</span>
                <span class="nav-title">{{ prevPage.title }}</span>
              </a>
              <a v-if="nextPage" class="page-nav-btn next" @click.prevent="navigate(nextPage.slug)">
                <span class="nav-label">Next →</span>
                <span class="nav-title">{{ nextPage.title }}</span>
              </a>
            </nav>
          </article>
        </div>
      </main>

      <aside class="docs-toc" v-if="currentPage?.headings?.length > 1">
        <div class="toc-inner">
          <p class="toc-label">On this page</p>
          <ul class="toc-list">
            <li
              v-for="heading in currentPage.headings"
              :key="heading.anchor"
              :class="['toc-item', `toc-h${heading.level}`]"
            >
              <a :href="`#${heading.anchor}`" class="toc-link">{{ heading.text }}</a>
            </li>
          </ul>
        </div>
      </aside>
    </div>

    <SearchModal v-if="searchOpen" @close="searchOpen = false" @navigate="navigate" />
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import NavGroup from './NavGroup.vue';
import SearchModal from './SearchModal.vue';

const route = useRoute();
const router = useRouter();

const navigation = ref([]);
const currentPage = ref(null);
const navLoading = ref(true);
const pageLoading = ref(false);
const pageError = ref(false);
const sidebarOpen = ref(false);
const searchOpen = ref(false);
const isDark = ref(localStorage.getItem('docs-dark') === 'true');

const currentSlug = computed(() => {
  const path = route.params.path;
  if (!path || path === '' || (Array.isArray(path) && path.length === 0)) return 'index';
  return Array.isArray(path) ? path.join('/') : path;
});

const allPages = computed(() => flattenNav(navigation.value));

const currentIndex = computed(() => allPages.value.findIndex(p => p.slug === currentSlug.value));
const prevPage = computed(() => (currentIndex.value > 0 ? allPages.value[currentIndex.value - 1] : null));
const nextPage = computed(() =>
  currentIndex.value < allPages.value.length - 1 ? allPages.value[currentIndex.value + 1] : null,
);

function flattenNav(items) {
  const pages = [];
  for (const item of items) {
    if (item.type === 'file') pages.push(item);
    if (item.type === 'folder' && item.children) pages.push(...flattenNav(item.children));
  }
  return pages;
}

async function loadNavigation() {
  navLoading.value = true;
  try {
    const res = await fetch('/docs-api/navigation');
    const data = await res.json();
    navigation.value = data.navigation;
  } catch (e) {
    console.error('Failed to load navigation', e);
  } finally {
    navLoading.value = false;
  }
}

async function loadPage(slug) {
  pageLoading.value = true;
  pageError.value = false;
  currentPage.value = null;

  try {
    const res = await fetch(`/docs-api/page?path=${encodeURIComponent(slug)}`);
    if (!res.ok) {
      pageError.value = true;
      return;
    }
    currentPage.value = await res.json();
    document.title = `${currentPage.value.title} — GenERP BD Docs`;
  } catch (e) {
    pageError.value = true;
  } finally {
    pageLoading.value = false;
  }
}

function navigate(slug) {
  sidebarOpen.value = false;
  const path = slug === 'index' ? '' : slug;
  router.push('/' + path);
}

function toggleDark() {
  isDark.value = !isDark.value;
  localStorage.setItem('docs-dark', isDark.value);
  document.documentElement.classList.toggle('dark', isDark.value);
}

function handleKeydown(e) {
  if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
    e.preventDefault();
    searchOpen.value = true;
  }
  if (e.key === 'Escape') {
    searchOpen.value = false;
    sidebarOpen.value = false;
  }
}

watch(
  currentSlug,
  slug => loadPage(slug),
  { immediate: false },
);

onMounted(async () => {
  document.documentElement.classList.toggle('dark', isDark.value);
  window.addEventListener('keydown', handleKeydown);
  await loadNavigation();
  await loadPage(currentSlug.value);
});

onUnmounted(() => {
  window.removeEventListener('keydown', handleKeydown);
});
</script>

