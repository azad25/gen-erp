<template>
  <nav :class="navClass">
    <ul :class="listClass">
      <li 
        v-for="item in menuItems" 
        :key="item.id"
        :class="itemClass"
        class="relative"
      >
        <!-- Menu Item Link -->
        <component
          :is="item.url.startsWith('http') ? 'a' : 'NuxtLink'"
          :to="item.url.startsWith('http') ? undefined : item.url"
          :href="item.url.startsWith('http') ? item.url : undefined"
          :target="item.target"
          :rel="item.target === '_blank' ? 'noopener noreferrer' : undefined"
          :class="[
            linkClass,
            {
              [activeLinkClass]: isMenuItemActive(item, currentPath),
              'cursor-pointer': item.children && item.children.length > 0
            }
          ]"
          @click="handleItemClick(item)"
          @mouseenter="handleMouseEnter(item)"
          @mouseleave="handleMouseLeave(item)"
        >
          {{ item.label }}
          
          <!-- Dropdown Arrow -->
          <svg 
            v-if="item.children && item.children.length > 0"
            class="ml-1 w-4 h-4 transition-transform duration-200"
            :class="{ 'rotate-180': openDropdowns.includes(item.id) }"
            fill="none" 
            stroke="currentColor" 
            viewBox="0 0 24 24"
          >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
          </svg>
        </component>

        <!-- Dropdown Menu -->
        <Transition
          name="dropdown"
          @enter="onDropdownEnter"
          @after-enter="onDropdownAfterEnter"
          @leave="onDropdownLeave"
          @after-leave="onDropdownAfterLeave"
        >
          <div
            v-if="item.children && item.children.length > 0 && (openDropdowns.includes(item.id) || (mode === 'desktop' && hoveredItem === item.id))"
            :class="[
              dropdownClass,
              mode === 'desktop' ? 'absolute top-full left-0 mt-1 min-w-48 z-50' : 'mt-2'
            ]"
          >
            <ul :class="dropdownListClass">
              <li 
                v-for="child in item.children" 
                :key="child.id"
                :class="dropdownItemClass"
              >
                <component
                  :is="child.url.startsWith('http') ? 'a' : 'NuxtLink'"
                  :to="child.url.startsWith('http') ? undefined : child.url"
                  :href="child.url.startsWith('http') ? child.url : undefined"
                  :target="child.target"
                  :rel="child.target === '_blank' ? 'noopener noreferrer' : undefined"
                  :class="[
                    dropdownLinkClass,
                    { [activeDropdownLinkClass]: isActiveUrl(child.url, currentPath) }
                  ]"
                  @click="handleChildClick(child)"
                >
                  {{ child.label }}
                </component>
              </li>
            </ul>
          </div>
        </Transition>
      </li>
    </ul>
  </nav>
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

interface Props {
  menuItems: MenuItem[]
  mode?: 'desktop' | 'mobile'
  navClass?: string
  listClass?: string
  itemClass?: string
  linkClass?: string
  activeLinkClass?: string
  dropdownClass?: string
  dropdownListClass?: string
  dropdownItemClass?: string
  dropdownLinkClass?: string
  activeDropdownLinkClass?: string
}

const props = withDefaults(defineProps<Props>(), {
  mode: 'desktop',
  navClass: '',
  listClass: 'flex space-x-8',
  itemClass: '',
  linkClass: 'text-gray-700 hover:text-gray-900 px-3 py-2 text-sm font-medium transition-colors flex items-center',
  activeLinkClass: 'text-blue-600',
  dropdownClass: 'bg-white shadow-lg rounded-md border border-gray-200 py-2',
  dropdownListClass: 'space-y-1',
  dropdownItemClass: '',
  dropdownLinkClass: 'block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900 transition-colors',
  activeDropdownLinkClass: 'text-blue-600 bg-blue-50'
})

const emit = defineEmits<{
  itemClick: [item: MenuItem]
  childClick: [child: MenuItem]
}>()

const route = useRoute()
const { isActiveUrl, isMenuItemActive } = useMenu()

const currentPath = computed(() => route.path)
const openDropdowns = ref<string[]>([])
const hoveredItem = ref<string | null>(null)

// Handle item click
const handleItemClick = (item: MenuItem) => {
  if (item.children && item.children.length > 0) {
    // Toggle dropdown for mobile or items without URLs
    if (props.mode === 'mobile' || !item.url || item.url === '#') {
      toggleDropdown(item.id)
    }
  }
  
  emit('itemClick', item)
}

// Handle child item click
const handleChildClick = (child: MenuItem) => {
  // Close all dropdowns when child is clicked
  openDropdowns.value = []
  hoveredItem.value = null
  
  emit('childClick', child)
}

// Toggle dropdown
const toggleDropdown = (itemId: string) => {
  const index = openDropdowns.value.indexOf(itemId)
  if (index > -1) {
    openDropdowns.value.splice(index, 1)
  } else {
    openDropdowns.value.push(itemId)
  }
}

// Mouse hover handlers for desktop
const handleMouseEnter = (item: MenuItem) => {
  if (props.mode === 'desktop' && item.children && item.children.length > 0) {
    hoveredItem.value = item.id
  }
}

const handleMouseLeave = (item: MenuItem) => {
  if (props.mode === 'desktop') {
    // Delay hiding to allow moving to dropdown
    setTimeout(() => {
      if (hoveredItem.value === item.id) {
        hoveredItem.value = null
      }
    }, 100)
  }
}

// Dropdown transition handlers
const onDropdownEnter = (el: Element) => {
  const element = el as HTMLElement
  element.style.opacity = '0'
  element.style.transform = 'translateY(-10px)'
}

const onDropdownAfterEnter = (el: Element) => {
  const element = el as HTMLElement
  element.style.opacity = '1'
  element.style.transform = 'translateY(0)'
}

const onDropdownLeave = (el: Element) => {
  const element = el as HTMLElement
  element.style.opacity = '0'
  element.style.transform = 'translateY(-10px)'
}

const onDropdownAfterLeave = (el: Element) => {
  const element = el as HTMLElement
  element.style.opacity = '1'
  element.style.transform = 'translateY(0)'
}

// Close dropdowns when clicking outside
const closeDropdowns = () => {
  openDropdowns.value = []
  hoveredItem.value = null
}

// Close dropdowns when route changes
watch(() => route.path, () => {
  closeDropdowns()
})

// Expose methods for parent components
defineExpose({
  closeDropdowns
})
</script>

<style scoped>
.dropdown-enter-active,
.dropdown-leave-active {
  transition: all 0.2s ease;
}

.dropdown-enter-from,
.dropdown-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}

/* Ensure dropdowns appear above other content */
.relative {
  position: relative;
}

/* Mobile-specific styles */
@media (max-width: 768px) {
  .dropdown-class {
    position: static !important;
    box-shadow: none !important;
    border: none !important;
    background: transparent !important;
    padding: 0 !important;
    margin-top: 0.5rem !important;
  }
}
</style>