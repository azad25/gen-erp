<template>
  <div class="h-full flex flex-col bg-gray-50">
    <!-- Header -->
    <div class="bg-white border-b border-gray-200 px-4 py-3">
      <div class="flex items-center justify-between">
        <div class="flex items-center space-x-4">
          <h1 class="text-lg font-semibold text-gray-900">Page Builder</h1>
          <div class="flex items-center space-x-2">
            <span class="text-sm text-gray-500">{{ page?.title || 'Untitled Page' }}</span>
            <span
              v-if="hasUnsavedChanges"
              class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800"
            >
              Unsaved changes
            </span>
          </div>
        </div>
        
        <div class="flex items-center space-x-3">
          <!-- View Mode Toggle -->
          <div class="flex items-center bg-gray-100 rounded-lg p-1">
            <button
              @click="viewMode = 'desktop'"
              :class="[
                'px-3 py-1 text-sm font-medium rounded-md transition-colors',
                viewMode === 'desktop' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-600 hover:text-gray-900'
              ]"
            >
              Desktop
            </button>
            <button
              @click="viewMode = 'tablet'"
              :class="[
                'px-3 py-1 text-sm font-medium rounded-md transition-colors',
                viewMode === 'tablet' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-600 hover:text-gray-900'
              ]"
            >
              Tablet
            </button>
            <button
              @click="viewMode = 'mobile'"
              :class="[
                'px-3 py-1 text-sm font-medium rounded-md transition-colors',
                viewMode === 'mobile' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-600 hover:text-gray-900'
              ]"
            >
              Mobile
            </button>
          </div>

          <!-- Actions -->
          <button
            @click="previewPage"
            class="bg-gray-600 hover:bg-gray-700 text-white text-sm font-medium py-2 px-3 rounded-md"
          >
            Preview
          </button>
          <button
            @click="savePage"
            :disabled="loading || !hasUnsavedChanges"
            class="bg-indigo-600 hover:bg-indigo-700 disabled:bg-gray-300 text-white text-sm font-medium py-2 px-3 rounded-md"
          >
            {{ loading ? 'Saving...' : 'Save' }}
          </button>
        </div>
      </div>
    </div>

    <div class="flex-1 flex overflow-hidden">
      <!-- Section Library Sidebar -->
      <div class="w-80 bg-white border-r border-gray-200 overflow-y-auto">
        <div class="p-4">
          <h2 class="text-sm font-semibold text-gray-900 mb-4">Add Sections</h2>
          
          <!-- Search -->
          <div class="mb-4">
            <input
              v-model="sectionSearch"
              type="text"
              placeholder="Search sections..."
              class="w-full text-sm border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
            />
          </div>

          <!-- Section Categories -->
          <div class="space-y-4">
            <div
              v-for="category in filteredSectionCategories"
              :key="category.name"
            >
              <h3 class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-2">
                {{ category.name }}
              </h3>
              <div class="grid grid-cols-2 gap-2">
                <button
                  v-for="section in category.sections"
                  :key="section.type"
                  @click="addSection(section)"
                  class="p-3 text-left border border-gray-200 rounded-lg hover:border-indigo-300 hover:bg-indigo-50 transition-colors"
                >
                  <div class="text-lg mb-1">{{ section.icon }}</div>
                  <div class="text-xs font-medium text-gray-900">{{ section.name }}</div>
                  <div class="text-xs text-gray-500">{{ section.description }}</div>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Main Editor Area -->
      <div class="flex-1 flex flex-col">
        <!-- Canvas -->
        <div class="flex-1 overflow-auto bg-gray-100 p-4">
          <div
            class="mx-auto bg-white shadow-lg rounded-lg overflow-hidden"
            :class="getCanvasClass()"
          >
            <!-- Page Sections -->
            <div
              v-if="pageSections.length === 0"
              class="py-20 text-center text-gray-500"
            >
              <div class="text-4xl mb-4">📄</div>
              <h3 class="text-lg font-medium text-gray-900 mb-2">Start Building Your Page</h3>
              <p class="text-sm">Add sections from the sidebar to begin creating your page.</p>
            </div>

            <div v-else class="relative">
              <div
                v-for="(section, index) in pageSections"
                :key="section.id"
                class="relative group"
                @click="selectSection(section, index)"
                :class="{ 'ring-2 ring-indigo-500': selectedSectionIndex === index }"
              >
                <!-- Section Controls -->
                <div
                  v-if="selectedSectionIndex === index"
                  class="absolute top-2 right-2 z-10 flex items-center space-x-1 bg-white rounded-md shadow-sm border border-gray-200"
                >
                  <button
                    @click.stop="moveSection(index, 'up')"
                    :disabled="index === 0"
                    class="p-1 text-gray-400 hover:text-gray-600 disabled:opacity-50"
                    title="Move Up"
                  >
                    <ChevronUpIcon class="h-4 w-4" />
                  </button>
                  <button
                    @click.stop="moveSection(index, 'down')"
                    :disabled="index === pageSections.length - 1"
                    class="p-1 text-gray-400 hover:text-gray-600 disabled:opacity-50"
                    title="Move Down"
                  >
                    <ChevronDownIcon class="h-4 w-4" />
                  </button>
                  <button
                    @click.stop="duplicateSection(index)"
                    class="p-1 text-gray-400 hover:text-gray-600"
                    title="Duplicate"
                  >
                    <DocumentDuplicateIcon class="h-4 w-4" />
                  </button>
                  <button
                    @click.stop="deleteSection(index)"
                    class="p-1 text-red-400 hover:text-red-600"
                    title="Delete"
                  >
                    <TrashIcon class="h-4 w-4" />
                  </button>
                </div>

                <!-- Section Content -->
                <SectionRenderer
                  :section="section"
                  :is-editing="true"
                  @update="updateSection(index, $event)"
                />
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Properties Panel -->
      <div
        v-if="selectedSection"
        class="w-80 bg-white border-l border-gray-200 overflow-y-auto"
      >
        <div class="p-4">
          <div class="flex items-center justify-between mb-4">
            <h2 class="text-sm font-semibold text-gray-900">Section Properties</h2>
            <button
              @click="selectedSection = null"
              class="text-gray-400 hover:text-gray-600"
            >
              <XMarkIcon class="h-4 w-4" />
            </button>
          </div>

          <SectionPropertiesForm
            :section="selectedSection"
            @update="updateSelectedSection"
          />
        </div>
      </div>
    </div>

    <!-- Preview Modal -->
    <div
      v-if="showPreview"
      class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
    >
      <div class="relative min-h-full">
        <div class="absolute top-4 right-4 z-10">
          <button
            @click="showPreview = false"
            class="bg-white hover:bg-gray-50 text-gray-700 p-2 rounded-full shadow-lg"
          >
            <XMarkIcon class="h-5 w-5" />
          </button>
        </div>
        
        <div class="bg-white">
          <div
            v-for="section in pageSections"
            :key="section.id"
          >
            <SectionRenderer
              :section="section"
              :is-editing="false"
            />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import {
  ChevronUpIcon,
  ChevronDownIcon,
  DocumentDuplicateIcon,
  TrashIcon,
  XMarkIcon
} from '@heroicons/vue/24/outline'
import { useApi } from '@/Composables/useApi'
import { useToast } from '@/Composables/useToast'
import SectionRenderer from './SectionRenderer.vue'
import SectionPropertiesForm from './SectionPropertiesForm.vue'

const props = defineProps({
  pageId: {
    type: [String, Number],
    required: true
  }
})

const emit = defineEmits(['saved'])

const { get, post, put, loading } = useApi()
const { showSuccess, showError } = useToast()

// Reactive data
const page = ref(null)
const pageSections = ref([])
const selectedSection = ref(null)
const selectedSectionIndex = ref(-1)
const viewMode = ref('desktop')
const sectionSearch = ref('')
const showPreview = ref(false)
const hasUnsavedChanges = ref(false)

// Section categories and types
const sectionCategories = [
  {
    name: 'Layout',
    sections: [
      { type: 'hero_banner', name: 'Hero Banner', icon: '🎯', description: 'Large banner section' },
      { type: 'text_block', name: 'Text Block', icon: '📝', description: 'Rich text content' },
      { type: 'image_text', name: 'Image & Text', icon: '🖼️', description: 'Image with text' },
      { type: 'cta_banner', name: 'CTA Banner', icon: '📢', description: 'Call to action' }
    ]
  },
  {
    name: 'Content',
    sections: [
      { type: 'gallery', name: 'Gallery', icon: '🖼️', description: 'Image gallery' },
      { type: 'testimonials', name: 'Testimonials', icon: '💬', description: 'Customer reviews' },
      { type: 'faq', name: 'FAQ', icon: '❓', description: 'Questions & answers' },
      { type: 'blog_posts', name: 'Blog Posts', icon: '📰', description: 'Latest blog posts' }
    ]
  },
  {
    name: 'Business',
    sections: [
      { type: 'team_grid', name: 'Team Grid', icon: '👥', description: 'Team members' },
      { type: 'stats', name: 'Statistics', icon: '📊', description: 'Key metrics' },
      { type: 'portfolio_grid', name: 'Portfolio', icon: '🎨', description: 'Work showcase' },
      { type: 'contact_form', name: 'Contact Form', icon: '📧', description: 'Contact form' }
    ]
  },
  {
    name: 'E-commerce',
    sections: [
      { type: 'product_grid', name: 'Product Grid', icon: '🛍️', description: 'Product showcase' },
      { type: 'product_detail', name: 'Product Detail', icon: '📦', description: 'Product details' },
      { type: 'shopping_cart_page', name: 'Shopping Cart', icon: '🛒', description: 'Cart page' },
      { type: 'checkout_form', name: 'Checkout', icon: '💳', description: 'Checkout form' },
      { type: 'product_reviews', name: 'Reviews', icon: '⭐', description: 'Product reviews' }
    ]
  },
  {
    name: 'Advanced',
    sections: [
      { type: 'custom_html', name: 'Custom HTML', icon: '💻', description: 'Custom code' },
      { type: 'customer_account', name: 'Account', icon: '👤', description: 'User account' }
    ]
  }
]

// Computed properties
const filteredSectionCategories = computed(() => {
  if (!sectionSearch.value) return sectionCategories
  
  return sectionCategories.map(category => ({
    ...category,
    sections: category.sections.filter(section =>
      section.name.toLowerCase().includes(sectionSearch.value.toLowerCase()) ||
      section.description.toLowerCase().includes(sectionSearch.value.toLowerCase())
    )
  })).filter(category => category.sections.length > 0)
})

const getCanvasClass = () => {
  const classes = {
    desktop: 'max-w-full',
    tablet: 'max-w-3xl',
    mobile: 'max-w-sm'
  }
  return classes[viewMode.value]
}

// Methods
const fetchPage = async () => {
  try {
    const data = await get(`/api/v1/cms/pages/${props.pageId}`)
    page.value = data.data
    pageSections.value = data.data.sections || []
  } catch (err) {
    console.error('Failed to fetch page:', err)
    showError('Failed to load page')
  }
}

const addSection = (sectionType) => {
  const newSection = {
    id: Date.now(), // Temporary ID
    type: sectionType.type,
    content: getDefaultSectionContent(sectionType.type),
    order: pageSections.value.length
  }
  
  pageSections.value.push(newSection)
  hasUnsavedChanges.value = true
  
  // Auto-select the new section
  selectSection(newSection, pageSections.value.length - 1)
}

const getDefaultSectionContent = (type) => {
  const defaults = {
    hero_banner: {
      title: 'Welcome to Our Website',
      subtitle: 'Discover amazing products and services',
      button_text: 'Get Started',
      button_url: '#',
      background_image: '',
      text_color: '#ffffff',
      background_color: '#1f2937'
    },
    text_block: {
      content: '<p>Add your content here...</p>',
      text_align: 'left',
      background_color: '#ffffff'
    },
    image_text: {
      title: 'Section Title',
      content: '<p>Add your description here...</p>',
      image_url: '',
      image_position: 'left',
      background_color: '#ffffff'
    },
    contact_form: {
      title: 'Contact Us',
      subtitle: 'Get in touch with our team',
      fields: ['name', 'email', 'message'],
      button_text: 'Send Message',
      background_color: '#ffffff'
    }
  }
  
  return defaults[type] || {}
}

const selectSection = (section, index) => {
  selectedSection.value = section
  selectedSectionIndex.value = index
}

const updateSection = (index, updates) => {
  pageSections.value[index] = { ...pageSections.value[index], ...updates }
  hasUnsavedChanges.value = true
}

const updateSelectedSection = (updates) => {
  if (selectedSectionIndex.value >= 0) {
    updateSection(selectedSectionIndex.value, updates)
    selectedSection.value = { ...selectedSection.value, ...updates }
  }
}

const moveSection = (index, direction) => {
  const newIndex = direction === 'up' ? index - 1 : index + 1
  if (newIndex < 0 || newIndex >= pageSections.value.length) return
  
  const sections = [...pageSections.value]
  const [movedSection] = sections.splice(index, 1)
  sections.splice(newIndex, 0, movedSection)
  
  pageSections.value = sections
  selectedSectionIndex.value = newIndex
  hasUnsavedChanges.value = true
}

const duplicateSection = (index) => {
  const section = pageSections.value[index]
  const duplicated = {
    ...section,
    id: Date.now(),
    order: index + 1
  }
  
  pageSections.value.splice(index + 1, 0, duplicated)
  hasUnsavedChanges.value = true
}

const deleteSection = (index) => {
  if (confirm('Delete this section?')) {
    pageSections.value.splice(index, 1)
    selectedSection.value = null
    selectedSectionIndex.value = -1
    hasUnsavedChanges.value = true
  }
}

const savePage = async () => {
  try {
    const payload = {
      sections: pageSections.value.map((section, index) => ({
        ...section,
        order: index
      }))
    }
    
    await put(`/api/v1/cms/pages/${props.pageId}/sections`, payload)
    hasUnsavedChanges.value = false
    showSuccess('Page saved successfully')
    emit('saved')
  } catch (err) {
    console.error('Failed to save page:', err)
    showError('Failed to save page')
  }
}

const previewPage = () => {
  showPreview.value = true
}

// Watch for changes
watch(pageSections, () => {
  hasUnsavedChanges.value = true
}, { deep: true })

// Lifecycle
onMounted(() => {
  fetchPage()
})

// Prevent accidental navigation with unsaved changes
window.addEventListener('beforeunload', (e) => {
  if (hasUnsavedChanges.value) {
    e.preventDefault()
    e.returnValue = ''
  }
})
</script>

<style scoped>
.section-renderer {
  min-height: 100px;
}
</style>