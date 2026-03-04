<template>
  <div class="bg-white rounded-lg border border-gray-200 shadow-sm">
    <!-- Header -->
    <div class="px-4 py-3 border-b border-gray-200">
      <div class="flex items-center justify-between">
        <h3 class="text-lg font-medium text-gray-900">Project Templates</h3>
        <div class="flex items-center space-x-3">
          <button
            @click="showCreateModal = true"
            class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium py-2 px-3 rounded-md"
          >
            Create Template
          </button>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex items-center justify-center py-12">
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
      <span class="ml-2 text-gray-600">Loading templates...</span>
    </div>

    <!-- Templates Grid -->
    <div v-else-if="templates.length > 0" class="p-6">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div
          v-for="template in templates"
          :key="template.id"
          class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow cursor-pointer"
          @click="selectTemplate(template)"
        >
          <!-- Template Header -->
          <div class="flex items-start justify-between mb-3">
            <div class="flex-1">
              <h4 class="text-sm font-medium text-gray-900">{{ template.name }}</h4>
              <p class="text-xs text-gray-500 mt-1">{{ template.description }}</p>
            </div>
            <div class="flex items-center space-x-1">
              <button
                @click.stop="editTemplate(template)"
                class="text-gray-400 hover:text-gray-600"
                title="Edit Template"
              >
                <PencilIcon class="h-4 w-4" />
              </button>
              <button
                @click.stop="duplicateTemplate(template)"
                class="text-gray-400 hover:text-gray-600"
                title="Duplicate Template"
              >
                <DocumentDuplicateIcon class="h-4 w-4" />
              </button>
              <button
                @click.stop="deleteTemplate(template)"
                class="text-red-400 hover:text-red-600"
                title="Delete Template"
              >
                <TrashIcon class="h-4 w-4" />
              </button>
            </div>
          </div>

          <!-- Template Stats -->
          <div class="grid grid-cols-2 gap-4 mb-3">
            <div class="text-center">
              <div class="text-lg font-semibold text-gray-900">{{ template.task_count || 0 }}</div>
              <div class="text-xs text-gray-500">Tasks</div>
            </div>
            <div class="text-center">
              <div class="text-lg font-semibold text-gray-900">{{ template.estimated_duration || 0 }}</div>
              <div class="text-xs text-gray-500">Days</div>
            </div>
          </div>

          <!-- Template Tags -->
          <div v-if="template.tags && template.tags.length > 0" class="flex flex-wrap gap-1 mb-3">
            <span
              v-for="tag in template.tags.slice(0, 3)"
              :key="tag"
              class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800"
            >
              {{ tag }}
            </span>
            <span
              v-if="template.tags.length > 3"
              class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800"
            >
              +{{ template.tags.length - 3 }}
            </span>
          </div>

          <!-- Template Category -->
          <div class="flex items-center justify-between">
            <span
              class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
              :class="getCategoryClass(template.category)"
            >
              {{ template.category || 'General' }}
            </span>
            <span class="text-xs text-gray-500">
              {{ formatDate(template.updated_at) }}
            </span>
          </div>

          <!-- Use Template Button -->
          <div class="mt-4">
            <button
              @click.stop="useTemplate(template)"
              class="w-full bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium py-2 px-3 rounded-md"
            >
              Use Template
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="text-center py-12">
      <FolderIcon class="mx-auto h-12 w-12 text-gray-400" />
      <h3 class="mt-2 text-sm font-medium text-gray-900">No templates found</h3>
      <p class="mt-1 text-sm text-gray-500">Get started by creating your first project template.</p>
      <div class="mt-6">
        <button
          @click="showCreateModal = true"
          class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium py-2 px-4 rounded-md"
        >
          Create Template
        </button>
      </div>
    </div>

    <!-- Create/Edit Template Modal -->
    <div
      v-if="showCreateModal || editingTemplate"
      class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
    >
      <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
        <div class="mt-3">
          <!-- Modal Header -->
          <div class="flex items-center justify-between pb-4 border-b">
            <h3 class="text-lg font-medium text-gray-900">
              {{ editingTemplate ? 'Edit Template' : 'Create Template' }}
            </h3>
            <button @click="closeModal" class="text-gray-400 hover:text-gray-600">
              <XMarkIcon class="h-6 w-6" />
            </button>
          </div>

          <!-- Template Form -->
          <form @submit.prevent="saveTemplate" class="mt-6 space-y-6">
            <!-- Basic Info -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label class="block text-sm font-medium text-gray-700">Template Name *</label>
                <input
                  v-model="templateForm.name"
                  type="text"
                  required
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700">Category</label>
                <select
                  v-model="templateForm.category"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                >
                  <option value="">Select Category</option>
                  <option value="software">Software Development</option>
                  <option value="marketing">Marketing</option>
                  <option value="design">Design</option>
                  <option value="research">Research</option>
                  <option value="event">Event Planning</option>
                  <option value="construction">Construction</option>
                  <option value="consulting">Consulting</option>
                  <option value="other">Other</option>
                </select>
              </div>
            </div>

            <!-- Description -->
            <div>
              <label class="block text-sm font-medium text-gray-700">Description</label>
              <textarea
                v-model="templateForm.description"
                rows="3"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                placeholder="Describe what this template is for..."
              ></textarea>
            </div>

            <!-- Duration and Settings -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
              <div>
                <label class="block text-sm font-medium text-gray-700">Estimated Duration (days)</label>
                <input
                  v-model.number="templateForm.estimated_duration"
                  type="number"
                  min="1"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700">Default Priority</label>
                <select
                  v-model="templateForm.default_priority"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                >
                  <option value="low">Low</option>
                  <option value="medium">Medium</option>
                  <option value="high">High</option>
                  <option value="urgent">Urgent</option>
                </select>
              </div>
              <div class="flex items-center">
                <input
                  v-model="templateForm.is_public"
                  type="checkbox"
                  class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                />
                <label class="ml-2 text-sm text-gray-700">Make template public</label>
              </div>
            </div>

            <!-- Tags -->
            <div>
              <label class="block text-sm font-medium text-gray-700">Tags</label>
              <input
                v-model="tagInput"
                type="text"
                placeholder="Enter tags separated by commas"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                @keydown.enter.prevent="addTag"
              />
              <div v-if="templateForm.tags.length > 0" class="flex flex-wrap gap-1 mt-2">
                <span
                  v-for="tag in templateForm.tags"
                  :key="tag"
                  class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800"
                >
                  {{ tag }}
                  <button
                    @click="removeTag(tag)"
                    class="ml-1 text-blue-600 hover:text-blue-800"
                  >
                    <XMarkIcon class="h-3 w-3" />
                  </button>
                </span>
              </div>
            </div>

            <!-- Template Tasks -->
            <div>
              <div class="flex items-center justify-between mb-3">
                <label class="block text-sm font-medium text-gray-700">Template Tasks</label>
                <button
                  type="button"
                  @click="addTask"
                  class="text-sm bg-gray-600 hover:bg-gray-700 text-white font-medium py-1 px-3 rounded-md"
                >
                  Add Task
                </button>
              </div>
              
              <div class="space-y-3 max-h-64 overflow-y-auto">
                <div
                  v-for="(task, index) in templateForm.tasks"
                  :key="index"
                  class="border border-gray-200 rounded-md p-3"
                >
                  <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div class="md:col-span-2">
                      <input
                        v-model="task.title"
                        type="text"
                        placeholder="Task title"
                        class="w-full text-sm border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                      />
                    </div>
                    <div class="flex items-center space-x-2">
                      <select
                        v-model="task.priority"
                        class="flex-1 text-sm border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                      >
                        <option value="low">Low</option>
                        <option value="medium">Medium</option>
                        <option value="high">High</option>
                        <option value="urgent">Urgent</option>
                      </select>
                      <button
                        type="button"
                        @click="removeTask(index)"
                        class="text-red-400 hover:text-red-600"
                      >
                        <TrashIcon class="h-4 w-4" />
                      </button>
                    </div>
                  </div>
                  <div class="mt-2">
                    <textarea
                      v-model="task.description"
                      rows="2"
                      placeholder="Task description (optional)"
                      class="w-full text-sm border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                    ></textarea>
                  </div>
                  <div class="grid grid-cols-2 gap-3 mt-2">
                    <div>
                      <input
                        v-model.number="task.estimated_hours"
                        type="number"
                        placeholder="Estimated hours"
                        min="0"
                        step="0.5"
                        class="w-full text-sm border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                      />
                    </div>
                    <div>
                      <input
                        v-model.number="task.order"
                        type="number"
                        placeholder="Order"
                        min="1"
                        class="w-full text-sm border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                      />
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-end space-x-3 pt-6 border-t">
              <button
                type="button"
                @click="closeModal"
                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
              >
                Cancel
              </button>
              <button
                type="submit"
                :disabled="loading"
                class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md hover:bg-indigo-700 disabled:opacity-50"
              >
                {{ editingTemplate ? 'Update Template' : 'Create Template' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Use Template Modal -->
    <div
      v-if="showUseModal"
      class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
    >
      <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-1/2 shadow-lg rounded-md bg-white">
        <div class="mt-3">
          <!-- Modal Header -->
          <div class="flex items-center justify-between pb-4 border-b">
            <h3 class="text-lg font-medium text-gray-900">Create Project from Template</h3>
            <button @click="showUseModal = false" class="text-gray-400 hover:text-gray-600">
              <XMarkIcon class="h-6 w-6" />
            </button>
          </div>

          <!-- Project Form -->
          <form @submit.prevent="createFromTemplate" class="mt-6 space-y-6">
            <div>
              <label class="block text-sm font-medium text-gray-700">Project Name *</label>
              <input
                v-model="projectForm.name"
                type="text"
                required
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700">Description</label>
              <textarea
                v-model="projectForm.description"
                rows="3"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
              ></textarea>
            </div>

            <div class="grid grid-cols-2 gap-6">
              <div>
                <label class="block text-sm font-medium text-gray-700">Start Date</label>
                <input
                  v-model="projectForm.start_date"
                  type="date"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700">Due Date</label>
                <input
                  v-model="projectForm.due_date"
                  type="date"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                />
              </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-end space-x-3 pt-6 border-t">
              <button
                type="button"
                @click="showUseModal = false"
                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
              >
                Cancel
              </button>
              <button
                type="submit"
                :disabled="loading"
                class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md hover:bg-indigo-700 disabled:opacity-50"
              >
                Create Project
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import {
  PencilIcon,
  DocumentDuplicateIcon,
  TrashIcon,
  FolderIcon,
  XMarkIcon
} from '@heroicons/vue/24/outline'
import { useApi } from '@/Composables/useApi'
import { useToast } from '@/Composables/useToast'

const emit = defineEmits(['template-used'])

const { get, post, put, delete: del, loading } = useApi()
const { showSuccess, showError } = useToast()

// Reactive data
const templates = ref([])
const showCreateModal = ref(false)
const showUseModal = ref(false)
const editingTemplate = ref(null)
const selectedTemplate = ref(null)
const tagInput = ref('')

const templateForm = reactive({
  name: '',
  description: '',
  category: '',
  estimated_duration: 30,
  default_priority: 'medium',
  is_public: false,
  tags: [],
  tasks: []
})

const projectForm = reactive({
  name: '',
  description: '',
  start_date: '',
  due_date: ''
})

// Methods
const fetchTemplates = async () => {
  try {
    const data = await get('/api/v1/project-templates')
    templates.value = data.data
  } catch (err) {
    console.error('Failed to fetch templates:', err)
    showError('Failed to load templates')
  }
}

const getCategoryClass = (category) => {
  const classes = {
    'software': 'bg-blue-100 text-blue-800',
    'marketing': 'bg-green-100 text-green-800',
    'design': 'bg-purple-100 text-purple-800',
    'research': 'bg-yellow-100 text-yellow-800',
    'event': 'bg-pink-100 text-pink-800',
    'construction': 'bg-orange-100 text-orange-800',
    'consulting': 'bg-indigo-100 text-indigo-800'
  }
  return classes[category] || 'bg-gray-100 text-gray-800'
}

const selectTemplate = (template) => {
  selectedTemplate.value = template
}

const editTemplate = (template) => {
  editingTemplate.value = template
  Object.assign(templateForm, {
    name: template.name,
    description: template.description || '',
    category: template.category || '',
    estimated_duration: template.estimated_duration || 30,
    default_priority: template.default_priority || 'medium',
    is_public: template.is_public || false,
    tags: [...(template.tags || [])],
    tasks: [...(template.tasks || [])]
  })
}

const duplicateTemplate = async (template) => {
  try {
    await post(`/api/v1/project-templates/${template.id}/duplicate`)
    showSuccess('Template duplicated successfully')
    fetchTemplates()
  } catch (err) {
    console.error('Failed to duplicate template:', err)
    showError('Failed to duplicate template')
  }
}

const deleteTemplate = async (template) => {
  if (!confirm(`Delete template "${template.name}"? This action cannot be undone.`)) return
  
  try {
    await del(`/api/v1/project-templates/${template.id}`)
    showSuccess('Template deleted successfully')
    fetchTemplates()
  } catch (err) {
    console.error('Failed to delete template:', err)
    showError('Failed to delete template')
  }
}

const useTemplate = (template) => {
  selectedTemplate.value = template
  projectForm.name = `${template.name} Project`
  projectForm.description = template.description || ''
  
  // Set default dates
  const today = new Date()
  projectForm.start_date = today.toISOString().split('T')[0]
  
  if (template.estimated_duration) {
    const endDate = new Date(today)
    endDate.setDate(endDate.getDate() + template.estimated_duration)
    projectForm.due_date = endDate.toISOString().split('T')[0]
  }
  
  showUseModal.value = true
}

const addTag = () => {
  if (!tagInput.value.trim()) return
  
  const newTags = tagInput.value.split(',').map(tag => tag.trim()).filter(tag => tag)
  templateForm.tags.push(...newTags.filter(tag => !templateForm.tags.includes(tag)))
  tagInput.value = ''
}

const removeTag = (tag) => {
  const index = templateForm.tags.indexOf(tag)
  if (index > -1) {
    templateForm.tags.splice(index, 1)
  }
}

const addTask = () => {
  templateForm.tasks.push({
    title: '',
    description: '',
    priority: 'medium',
    estimated_hours: 0,
    order: templateForm.tasks.length + 1
  })
}

const removeTask = (index) => {
  templateForm.tasks.splice(index, 1)
  // Reorder remaining tasks
  templateForm.tasks.forEach((task, i) => {
    task.order = i + 1
  })
}

const saveTemplate = async () => {
  try {
    const payload = { ...templateForm }
    
    if (editingTemplate.value) {
      await put(`/api/v1/project-templates/${editingTemplate.value.id}`, payload)
      showSuccess('Template updated successfully')
    } else {
      await post('/api/v1/project-templates', payload)
      showSuccess('Template created successfully')
    }
    
    closeModal()
    fetchTemplates()
  } catch (err) {
    console.error('Failed to save template:', err)
    showError('Failed to save template')
  }
}

const createFromTemplate = async () => {
  try {
    const payload = {
      ...projectForm,
      template_id: selectedTemplate.value.id
    }
    
    const data = await post('/api/v1/projects/from-template', payload)
    showSuccess('Project created from template successfully')
    showUseModal.value = false
    emit('template-used', data.data)
  } catch (err) {
    console.error('Failed to create project from template:', err)
    showError('Failed to create project from template')
  }
}

const closeModal = () => {
  showCreateModal.value = false
  editingTemplate.value = null
  
  // Reset form
  Object.assign(templateForm, {
    name: '',
    description: '',
    category: '',
    estimated_duration: 30,
    default_priority: 'medium',
    is_public: false,
    tags: [],
    tasks: []
  })
  tagInput.value = ''
}

const formatDate = (date) => {
  if (!date) return 'Never'
  return new Date(date).toLocaleDateString()
}

// Lifecycle
onMounted(() => {
  fetchTemplates()
})
</script>