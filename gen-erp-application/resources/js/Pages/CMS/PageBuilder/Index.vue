<template>
  <div class="h-screen flex flex-col bg-gray-100">
    <Head :title="`Page Builder - ${page.title}`" />
    
    <!-- Header -->
    <div class="bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between">
      <div class="flex items-center space-x-4">
        <Link
          :href="route('cms.sites.pages.index', page.site_id)"
          class="text-gray-500 hover:text-gray-700"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
          </svg>
        </Link>
        <h1 class="text-xl font-semibold text-gray-900">{{ page.title }}</h1>
        <span
          :class="{
            'bg-green-100 text-green-800': page.status === 'published',
            'bg-yellow-100 text-yellow-800': page.status === 'draft'
          }"
          class="px-2 py-1 text-xs font-medium rounded-full"
        >
          {{ page.status }}
        </span>
      </div>
      
      <div class="flex items-center space-x-3">
        <!-- Device Preview Toggle -->
        <div class="flex bg-gray-100 rounded-lg p-1">
          <button
            v-for="device in devices"
            :key="device.name"
            @click="currentDevice = device.name"
            :class="{
              'bg-white shadow-sm': currentDevice === device.name,
              'text-gray-500': currentDevice !== device.name
            }"
            class="px-3 py-1 text-sm font-medium rounded-md transition-colors"
          >
            {{ device.label }}
          </button>
        </div>
        
        <!-- Actions -->
        <button
          @click="savePage"
          :disabled="saving"
          class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium disabled:opacity-50"
        >
          {{ saving ? 'Saving...' : 'Save' }}
        </button>
        
        <button
          @click="publishPage"
          :disabled="publishing"
          class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm font-medium disabled:opacity-50"
        >
          {{ publishing ? 'Publishing...' : 'Publish' }}
        </button>
        
        <button
          @click="previewPage"
          class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md text-sm font-medium"
        >
          Preview
        </button>
      </div>
    </div>

    <!-- Main Content -->
    <div class="flex-1 flex overflow-hidden">
      <!-- Left Sidebar - Section Library -->
      <div class="w-80 bg-white border-r border-gray-200 overflow-y-auto">
        <div class="p-4">
          <h2 class="text-lg font-semibold text-gray-900 mb-4">Section Library</h2>
          
          <!-- Search -->
          <div class="mb-4">
            <input
              v-model="sectionSearch"
              type="text"
              placeholder="Search sections..."
              class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm"
            />
          </div>
          
          <!-- Categories -->
          <div class="space-y-4">
            <div v-for="category in filteredSectionCategories" :key="category.name">
              <h3 class="text-sm font-medium text-gray-700 mb-2">{{ category.name }}</h3>
              <div class="grid grid-cols-2 gap-2">
                <div
                  v-for="section in category.sections"
                  :key="section.type"
                  @click="addSection(section)"
                  class="p-3 border border-gray-200 rounded-lg cursor-pointer hover:border-blue-300 hover:bg-blue-50 transition-colors"
                >
                  <div class="flex flex-col items-center text-center">
                    <div class="w-8 h-8 mb-2 text-gray-600">
                      <Icon :name="section.icon" class="w-full h-full" />
                    </div>
                    <span class="text-xs font-medium text-gray-900">{{ section.label }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Center - Canvas -->
      <div class="flex-1 flex flex-col overflow-hidden">
        <div class="flex-1 overflow-y-auto bg-gray-50 p-6">
          <div
            :class="{
              'max-w-sm mx-auto': currentDevice === 'mobile',
              'max-w-2xl mx-auto': currentDevice === 'tablet',
              'max-w-full': currentDevice === 'desktop'
            }"
            class="bg-white shadow-lg rounded-lg overflow-hidden transition-all duration-300"
          >
            <!-- Page Canvas -->
            <div
              ref="canvas"
              class="min-h-screen"
            >
              <draggable
                v-model="pageSections"
                group="sections"
                item-key="id"
                @change="onSectionReorder"
                class="space-y-0"
              >
                <template #item="{ element: section, index }">
                  <div
                    :key="section.id"
                    @click="selectSection(section, index)"
                    :class="{
                      'ring-2 ring-blue-500': selectedSection?.id === section.id
                    }"
                    class="relative group hover:ring-2 hover:ring-blue-300 transition-all cursor-pointer"
                  >
                    <!-- Section Content -->
                    <SectionRenderer
                      :section="section"
                      :is-editing="true"
                    />
                    
                    <!-- Section Controls -->
                    <div
                      v-show="selectedSection?.id === section.id"
                      class="absolute top-2 right-2 flex space-x-1 bg-white rounded-md shadow-lg border border-gray-200 p-1"
                    >
                      <button
                        @click.stop="duplicateSection(section, index)"
                        class="p-1 text-gray-500 hover:text-blue-600"
                        title="Duplicate"
                      >
                        <Icon name="heroicons:document-duplicate" class="w-4 h-4" />
                      </button>
                      <button
                        @click.stop="moveSection(index, index - 1)"
                        :disabled="index === 0"
                        class="p-1 text-gray-500 hover:text-blue-600 disabled:opacity-50"
                        title="Move Up"
                      >
                        <Icon name="heroicons:chevron-up" class="w-4 h-4" />
                      </button>
                      <button
                        @click.stop="moveSection(index, index + 1)"
                        :disabled="index === pageSections.length - 1"
                        class="p-1 text-gray-500 hover:text-blue-600 disabled:opacity-50"
                        title="Move Down"
                      >
                        <Icon name="heroicons:chevron-down" class="w-4 h-4" />
                      </button>
                      <button
                        @click.stop="deleteSection(index)"
                        class="p-1 text-gray-500 hover:text-red-600"
                        title="Delete"
                      >
                        <Icon name="heroicons:trash" class="w-4 h-4" />
                      </button>
                    </div>
                  </div>
                </template>
              </draggable>
              
              <!-- Empty State -->
              <div
                v-if="pageSections.length === 0"
                class="flex items-center justify-center h-96 text-gray-500"
              >
                <div class="text-center">
                  <Icon name="heroicons:plus-circle" class="w-12 h-12 mx-auto mb-4 text-gray-400" />
                  <h3 class="text-lg font-medium text-gray-900 mb-2">Start building your page</h3>
                  <p class="text-sm text-gray-500">Add sections from the library on the left</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Right Sidebar - Properties Panel -->
      <div class="w-80 bg-white border-l border-gray-200 overflow-y-auto">
        <div class="p-4">
          <div v-if="selectedSection">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Section Properties</h2>
            
            <!-- Section Type Info -->
            <div class="mb-6 p-3 bg-gray-50 rounded-lg">
              <div class="flex items-center space-x-2 mb-2">
                <Icon :name="getSectionIcon(selectedSection.type)" class="w-5 h-5 text-gray-600" />
                <span class="font-medium text-gray-900">{{ getSectionLabel(selectedSection.type) }}</span>
              </div>
              <p class="text-sm text-gray-600">{{ getSectionDescription(selectedSection.type) }}</p>
            </div>
            
            <!-- Dynamic Properties Form -->
            <SectionPropertiesForm
              :section="selectedSection"
              :section-index="selectedSectionIndex"
              @update="updateSectionContent"
            />
          </div>
          
          <div v-else class="text-center py-12">
            <Icon name="heroicons:cursor-arrow-rays" class="w-12 h-12 mx-auto mb-4 text-gray-400" />
            <h3 class="text-lg font-medium text-gray-900 mb-2">No section selected</h3>
            <p class="text-sm text-gray-500">Click on a section to edit its properties</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import draggable from 'vuedraggable'
import { usePageBuilderStore } from '@/Stores/pageBuilderStore'
import SectionRenderer from '@/Components/CMS/SectionRenderer.vue'
import SectionPropertiesForm from '@/Components/CMS/SectionPropertiesForm.vue'
import Icon from '@/Components/UI/Icon.vue'

const props = defineProps({
  page: Object,
  sections: Array,
  sectionTypes: Array
})

const pageBuilderStore = usePageBuilderStore()

// Reactive data
const currentDevice = ref('desktop')
const sectionSearch = ref('')
const selectedSection = ref(null)
const selectedSectionIndex = ref(null)
const pageSections = ref([...props.sections])
const saving = ref(false)
const publishing = ref(false)

// Device options
const devices = [
  { name: 'desktop', label: 'Desktop' },
  { name: 'tablet', label: 'Tablet' },
  { name: 'mobile', label: 'Mobile' }
]

// Section categories
const sectionCategories = computed(() => {
  const categories = {}
  
  props.sectionTypes.forEach(section => {
    const category = section.category || 'Other'
    if (!categories[category]) {
      categories[category] = {
        name: category,
        sections: []
      }
    }
    categories[category].sections.push(section)
  })
  
  return Object.values(categories)
})

// Filtered section categories based on search
const filteredSectionCategories = computed(() => {
  if (!sectionSearch.value) return sectionCategories.value
  
  return sectionCategories.value.map(category => ({
    ...category,
    sections: category.sections.filter(section =>
      section.label.toLowerCase().includes(sectionSearch.value.toLowerCase()) ||
      section.type.toLowerCase().includes(sectionSearch.value.toLowerCase())
    )
  })).filter(category => category.sections.length > 0)
})

// Methods
const addSection = (sectionType) => {
  const newSection = {
    id: Date.now(),
    type: sectionType.type,
    content: sectionType.defaultContent || {},
    sort_order: pageSections.value.length
  }
  
  pageSections.value.push(newSection)
  selectSection(newSection, pageSections.value.length - 1)
}

const selectSection = (section, index) => {
  selectedSection.value = section
  selectedSectionIndex.value = index
}

const duplicateSection = (section, index) => {
  const duplicated = {
    ...section,
    id: Date.now(),
    sort_order: index + 1
  }
  
  pageSections.value.splice(index + 1, 0, duplicated)
  updateSortOrders()
}

const moveSection = (fromIndex, toIndex) => {
  if (toIndex < 0 || toIndex >= pageSections.value.length) return
  
  const section = pageSections.value.splice(fromIndex, 1)[0]
  pageSections.value.splice(toIndex, 0, section)
  updateSortOrders()
  
  // Update selected section index
  if (selectedSectionIndex.value === fromIndex) {
    selectedSectionIndex.value = toIndex
  }
}

const deleteSection = (index) => {
  if (confirm('Are you sure you want to delete this section?')) {
    pageSections.value.splice(index, 1)
    updateSortOrders()
    
    // Clear selection if deleted section was selected
    if (selectedSectionIndex.value === index) {
      selectedSection.value = null
      selectedSectionIndex.value = null
    }
  }
}

const updateSortOrders = () => {
  pageSections.value.forEach((section, index) => {
    section.sort_order = index
  })
}

const onSectionReorder = () => {
  updateSortOrders()
}

const updateSectionContent = (content) => {
  if (selectedSection.value) {
    selectedSection.value.content = { ...content }
  }
}

const getSectionIcon = (type) => {
  const sectionType = props.sectionTypes.find(s => s.type === type)
  return sectionType?.icon || 'heroicons:square-3-stack-3d'
}

const getSectionLabel = (type) => {
  const sectionType = props.sectionTypes.find(s => s.type === type)
  return sectionType?.label || type
}

const getSectionDescription = (type) => {
  const sectionType = props.sectionTypes.find(s => s.type === type)
  return sectionType?.description || 'Section component'
}

const savePage = async () => {
  saving.value = true
  
  try {
    await router.put(route('cms.pages.update', props.page.id), {
      sections: pageSections.value
    }, {
      preserveState: true,
      preserveScroll: true
    })
  } finally {
    saving.value = false
  }
}

const publishPage = async () => {
  publishing.value = true
  
  try {
    await router.post(route('cms.pages.publish', props.page.id), {
      sections: pageSections.value
    })
  } finally {
    publishing.value = false
  }
}

const previewPage = () => {
  const previewUrl = route('cms.pages.preview', props.page.id)
  window.open(previewUrl, '_blank')
}

onMounted(() => {
  // Initialize page builder store
  pageBuilderStore.initializePage(props.page, pageSections.value)
})
</script>