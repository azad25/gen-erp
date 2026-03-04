<template>
  <header class="bg-white shadow-sm border-b border-gray-200">
    <div class="container-custom">
      <div class="flex items-center justify-between h-16">
        <!-- Logo -->
        <div class="flex-shrink-0">
          <NuxtLink to="/" class="flex items-center">
            <NuxtImg
              v-if="tenant?.settings?.logo"
              :src="tenant.settings.logo"
              :alt="tenant.name"
              class="h-8 w-auto"
              loading="eager"
            />
            <span
              v-else
              class="text-xl font-bold"
              :style="{ color: 'var(--primary-color)' }"
            >
              {{ tenant?.name || 'Site Name' }}
            </span>
          </NuxtLink>
        </div>

        <!-- Desktop Navigation -->
        <div class="hidden md:block">
          <MenuRenderer
            v-if="headerMenu?.items && headerMenu.items.length > 0"
            :menu-items="headerMenu.items"
            mode="desktop"
            @item-click="handleMenuClick"
            @child-click="handleMenuClick"
          />
          <nav v-else class="flex space-x-8">
            <!-- Fallback menu -->
            <NuxtLink
              to="/"
              class="text-gray-700 hover:text-gray-900 px-3 py-2 text-sm font-medium transition-colors"
              :class="{ 'text-blue-600': isActive('/') }"
            >
              Home
            </NuxtLink>
            <NuxtLink
              to="/about"
              class="text-gray-700 hover:text-gray-900 px-3 py-2 text-sm font-medium transition-colors"
              :class="{ 'text-blue-600': isActive('/about') }"
            >
              About
            </NuxtLink>
            <NuxtLink
              to="/services"
              class="text-gray-700 hover:text-gray-900 px-3 py-2 text-sm font-medium transition-colors"
              :class="{ 'text-blue-600': isActive('/services') }"
            >
              Services
            </NuxtLink>
            <NuxtLink
              to="/portfolio"
              class="text-gray-700 hover:text-gray-900 px-3 py-2 text-sm font-medium transition-colors"
              :class="{ 'text-blue-600': isActive('/portfolio') }"
            >
              Portfolio
            </NuxtLink>
            <NuxtLink
              to="/blog"
              class="text-gray-700 hover:text-gray-900 px-3 py-2 text-sm font-medium transition-colors"
              :class="{ 'text-blue-600': isActive('/blog') }"
            >
              Blog
            </NuxtLink>
            <NuxtLink
              to="/contact"
              class="text-gray-700 hover:text-gray-900 px-3 py-2 text-sm font-medium transition-colors"
              :class="{ 'text-blue-600': isActive('/contact') }"
            >
              Contact
            </NuxtLink>
          </nav>
        </div>

        <!-- Mobile menu button -->
        <div class="md:hidden">
          <button
            @click="mobileMenuOpen = !mobileMenuOpen"
            class="text-gray-700 hover:text-gray-900 focus:outline-none focus:text-gray-900"
            aria-label="Toggle mobile menu"
          >
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path
                v-if="!mobileMenuOpen"
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M4 6h16M4 12h16M4 18h16"
              />
              <path
                v-else
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M6 18L18 6M6 6l12 12"
              />
            </svg>
          </button>
        </div>
      </div>

      <!-- Mobile Navigation -->
      <Transition
        name="mobile-menu"
        @enter="onMobileMenuEnter"
        @after-enter="onMobileMenuAfterEnter"
        @leave="onMobileMenuLeave"
        @after-leave="onMobileMenuAfterLeave"
      >
        <div
          v-if="mobileMenuOpen"
          class="md:hidden border-t border-gray-200 py-4 mobile-menu"
        >
          <MenuRenderer
            v-if="headerMenu?.items && headerMenu.items.length > 0"
            :menu-items="headerMenu.items"
            mode="mobile"
            nav-class=""
            list-class="space-y-2"
            item-class=""
            link-class="block px-3 py-2 text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50 rounded-md transition-colors flex items-center justify-between"
            active-link-class="text-blue-600 bg-blue-50"
            dropdown-class="ml-4 mt-2 space-y-1"
            dropdown-list-class="space-y-1"
            dropdown-item-class=""
            dropdown-link-class="block px-3 py-2 text-sm text-gray-600 hover:text-gray-900 hover:bg-gray-50 rounded-md transition-colors"
            active-dropdown-link-class="text-blue-600 bg-blue-50"
            @item-click="handleMobileMenuClick"
            @child-click="handleMobileMenuClick"
            ref="mobileMenuRenderer"
          />
          <div v-else class="space-y-2">
            <!-- Fallback mobile menu -->
            <NuxtLink
              to="/"
              class="block px-3 py-2 text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50 rounded-md transition-colors"
              :class="{ 'text-blue-600 bg-blue-50': isActive('/') }"
              @click="mobileMenuOpen = false"
            >
              Home
            </NuxtLink>
            <NuxtLink
              to="/about"
              class="block px-3 py-2 text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50 rounded-md transition-colors"
              :class="{ 'text-blue-600 bg-blue-50': isActive('/about') }"
              @click="mobileMenuOpen = false"
            >
              About
            </NuxtLink>
            <NuxtLink
              to="/services"
              class="block px-3 py-2 text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50 rounded-md transition-colors"
              :class="{ 'text-blue-600 bg-blue-50': isActive('/services') }"
              @click="mobileMenuOpen = false"
            >
              Services
            </NuxtLink>
            <NuxtLink
              to="/portfolio"
              class="block px-3 py-2 text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50 rounded-md transition-colors"
              :class="{ 'text-blue-600 bg-blue-50': isActive('/portfolio') }"
              @click="mobileMenuOpen = false"
            >
              Portfolio
            </NuxtLink>
            <NuxtLink
              to="/blog"
              class="block px-3 py-2 text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50 rounded-md transition-colors"
              :class="{ 'text-blue-600 bg-blue-50': isActive('/blog') }"
              @click="mobileMenuOpen = false"
            >
              Blog
            </NuxtLink>
            <NuxtLink
              to="/contact"
              class="block px-3 py-2 text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50 rounded-md transition-colors"
              :class="{ 'text-blue-600 bg-blue-50': isActive('/contact') }"
              @click="mobileMenuOpen = false"
            >
              Contact
            </NuxtLink>
          </div>
        </div>
      </Transition>
    </div>
  </header>
</template>

<script setup>
interface MenuItem {
  id: string
  label: string
  url: string
  page_id?: string
  target: string
  sort_order: number
  parent_id?: string
  children?: MenuItem[]
}

defineProps<{
  tenant?: any
}>()

const route = useRoute()
const { getHeaderMenu, isActiveUrl } = useMenu()
const mobileMenuOpen = ref(false)
const mobileMenuRenderer = ref()

// Get header menu
const headerMenu = computed(() => getHeaderMenu())

// Check if URL is active
const isActive = (url: string) => {
  return isActiveUrl(url, route.path)
}

// Handle menu item clicks
const handleMenuClick = (item: MenuItem) => {
  // Handle any special menu item logic here
  console.log('Menu item clicked:', item.label)
}

// Handle mobile menu item clicks
const handleMobileMenuClick = (item: MenuItem) => {
  // Close mobile menu if item has a URL and no children
  if (item.url && item.url !== '#' && (!item.children || item.children.length === 0)) {
    mobileMenuOpen.value = false
  }
  
  handleMenuClick(item)
}

// Mobile menu transition handlers
const onMobileMenuEnter = (el: Element) => {
  const element = el as HTMLElement
  element.style.height = '0'
  element.style.opacity = '0'
  element.style.overflow = 'hidden'
}

const onMobileMenuAfterEnter = (el: Element) => {
  const element = el as HTMLElement
  element.style.height = 'auto'
  element.style.opacity = '1'
  element.style.overflow = 'visible'
}

const onMobileMenuLeave = (el: Element) => {
  const element = el as HTMLElement
  element.style.height = element.scrollHeight + 'px'
  element.style.opacity = '1'
  element.style.overflow = 'hidden'
  
  // Force reflow
  element.offsetHeight
  
  element.style.height = '0'
  element.style.opacity = '0'
}

const onMobileMenuAfterLeave = (el: Element) => {
  const element = el as HTMLElement
  element.style.height = 'auto'
  element.style.opacity = '1'
  element.style.overflow = 'visible'
}

// Close mobile menu when route changes
watch(() => route.path, () => {
  mobileMenuOpen.value = false
  if (mobileMenuRenderer.value) {
    mobileMenuRenderer.value.closeDropdowns()
  }
})

// Close mobile menu when clicking outside
const closeMobileMenu = () => {
  mobileMenuOpen.value = false
}

// Handle escape key
const handleKeydown = (event: KeyboardEvent) => {
  if (event.key === 'Escape' && mobileMenuOpen.value) {
    mobileMenuOpen.value = false
  }
}

onMounted(() => {
  document.addEventListener('keydown', handleKeydown)
})

onUnmounted(() => {
  document.removeEventListener('keydown', handleKeydown)
})
</script>

<style scoped>
.mobile-menu-enter-active,
.mobile-menu-leave-active {
  transition: all 0.3s ease;
}

.mobile-menu-enter-from,
.mobile-menu-leave-to {
  height: 0;
  opacity: 0;
}

.mobile-menu {
  overflow: hidden;
}
</style>