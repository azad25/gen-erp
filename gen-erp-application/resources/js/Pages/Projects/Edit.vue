<template>
  <AppLayout title="Edit Project">
    <template #header>
      <div class="flex justify-between items-center">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
          Edit Project: {{ project?.name }}
        </h2>
        <div class="flex space-x-3">
          <Link :href="route('projects.show', projectId)" 
                class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md text-sm font-medium">
            View Project
          </Link>
          <Link :href="route('projects.index')" 
                class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md text-sm font-medium">
            Back to Projects
          </Link>
        </div>
      </div>
    </template>

    <div class="py-12">
      <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
        <div v-if="loading" class="text-center py-12">
          <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
          <p class="mt-2 text-gray-500">Loading project...</p>
        </div>

        <div v-else-if="project" class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6">
            <form @submit.prevent="submitForm" class="space-y-6">
              <!-- Basic Information -->
              <div>
                <h3 class="text-lg font-medium text-gray-900 mb-4">Basic Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <div>
                    <label class="block text-sm font-medium text-gray-700">Project Name *</label>
                    <input
                      v-model="form.name"
                      type="text"
                      required
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                      placeholder="Enter project name"
                    />
                    <p v-if="errors.name" class="mt-1 text-sm text-red-600">{{ errors.name[0] }}</p>
                  </div>

                  <div>
                    <label class="block text-sm font-medium text-gray-700">Project Code</label>
                    <input
                      v-model="form.code"
                      type="text"
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                      placeholder="Project code"
                    />
                    <p v-if="errors.code" class="mt-1 text-sm text-red-600">{{ errors.code[0] }}</p>
                  </div>

                  <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700">Description</label>
                    <textarea
                      v-model="form.description"
                      rows="3"
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                      placeholder="Enter project description"
                    ></textarea>
                  </div>
                </div>
              </div>

              <!-- Project Details -->
              <div>
                <h3 class="text-lg font-medium text-gray-900 mb-4">Project Details</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <div>
                    <label class="block text-sm font-medium text-gray-700">Status</label>
                    <select
                      v-model="form.status"
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    >
                      <option value="planning">Planning</option>
                      <option value="active">Active</option>
                      <option value="on_hold">On Hold</option>
                      <option value="completed">Completed</option>
                      <option value="cancelled">Cancelled</option>
                    </select>
                  </div>

                  <div>
                    <label class="block text-sm font-medium text-gray-700">Priority</label>
                    <select
                      v-model="form.priority"
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    >
                      <option value="low">Low</option>
                      <option value="medium">Medium</option>
                      <option value="high">High</option>
                      <option value="critical">Critical</option>
                    </select>
                  </div>

                  <div>
                    <label class="block text-sm font-medium text-gray-700">Start Date</label>
                    <input
                      v-model="form.start_date"
                      type="date"
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    />
                  </div>

                  <div>
                    <label class="block text-sm font-medium text-gray-700">Due Date</label>
                    <input
                      v-model="form.due_date"
                      type="date"
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    />
                  </div>

                  <div>
                    <label class="block text-sm font-medium text-gray-700">Project Manager</label>
                    <select
                      v-model="form.project_manager_id"
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    >
                      <option value="">Select Project Manager</option>
                      <option v-for="user in users" :key="user.id" :value="user.id">
                        {{ user.name }}
                      </option>
                    </select>
                  </div>

                  <div>
                    <label class="block text-sm font-medium text-gray-700">Customer</label>
                    <select
                      v-model="form.customer_id"
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    >
                      <option value="">Select Customer (Optional)</option>
                      <option v-for="customer in customers" :key="customer.id" :value="customer.id">
                        {{ customer.name }}
                      </option>
                    </select>
                  </div>
                </div>
              </div>

              <!-- Budget & Time -->
              <div>
                <h3 class="text-lg font-medium text-gray-900 mb-4">Budget & Time Estimation</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <div>
                    <label class="block text-sm font-medium text-gray-700">Budget (BDT)</label>
                    <input
                      v-model.number="form.budget"
                      type="number"
                      min="0"
                      step="0.01"
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                      placeholder="0.00"
                    />
                  </div>

                  <div>
                    <label class="block text-sm font-medium text-gray-700">Estimated Hours</label>
                    <input
                      v-model.number="form.estimated_hours"
                      type="number"
                      min="0"
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                      placeholder="0"
                    />
                  </div>

                  <div>
                    <label class="block text-sm font-medium text-gray-700">Progress (%)</label>
                    <input
                      v-model.number="form.progress_percentage"
                      type="number"
                      min="0"
                      max="100"
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                      placeholder="0"
                    />
                  </div>

                  <div class="flex items-center space-x-6">
                    <div class="flex items-center">
                      <input
                        v-model="form.is_billable"
                        type="checkbox"
                        class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                      />
                      <label class="ml-2 text-sm text-gray-700">Billable</label>
                    </div>

                    <div class="flex items-center">
                      <input
                        v-model="form.is_public"
                        type="checkbox"
                        class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                      />
                      <label class="ml-2 text-sm text-gray-700">Public</label>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Project Stats (Read-only) -->
              <div>
                <h3 class="text-lg font-medium text-gray-900 mb-4">Project Statistics</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                  <div class="bg-gray-50 p-4 rounded-lg">
                    <p class="text-sm font-medium text-gray-500">Actual Cost</p>
                    <p class="text-lg font-semibold text-gray-900">৳{{ project.actual_cost || 0 }}</p>
                  </div>
                  <div class="bg-gray-50 p-4 rounded-lg">
                    <p class="text-sm font-medium text-gray-500">Actual Hours</p>
                    <p class="text-lg font-semibold text-gray-900">{{ project.actual_hours || 0 }} hrs</p>
                  </div>
                  <div class="bg-gray-50 p-4 rounded-lg">
                    <p class="text-sm font-medium text-gray-500">Created</p>
                    <p class="text-lg font-semibold text-gray-900">{{ formatDate(project.created_at) }}</p>
                  </div>
                </div>
              </div>

              <!-- Form Actions -->
              <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200">
                <Link :href="route('projects.show', projectId)"
                      class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50">
                  Cancel
                </Link>
                <button
                  type="submit"
                  :disabled="saving"
                  class="bg-blue-600 py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50"
                >
                  <span v-if="saving">Saving...</span>
                  <span v-else>Save Changes</span>
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, onMounted, reactive } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
  projectId: [String, Number]
})

const project = ref(null)
const users = ref([])
const customers = ref([])
const loading = ref(true)
const saving = ref(false)
const errors = ref({})

const form = reactive({
  name: '',
  code: '',
  description: '',
  status: 'planning',
  priority: 'medium',
  start_date: '',
  due_date: '',
  project_manager_id: '',
  customer_id: '',
  budget: null,
  estimated_hours: null,
  progress_percentage: 0,
  is_billable: true,
  is_public: false
})

onMounted(async () => {
  await Promise.all([
    fetchProject(),
    fetchUsers(),
    fetchCustomers()
  ])
})

const fetchProject = async () => {
  try {
    const response = await fetch(`/api/v1/projects/${props.projectId}`, {
      headers: {
        'Authorization': `Bearer ${document.querySelector('meta[name="api-token"]')?.content}`,
        'Accept': 'application/json',
      }
    })
    
    if (response.ok) {
      const data = await response.json()
      project.value = data.data
      
      // Populate form with project data
      Object.keys(form).forEach(key => {
        if (project.value[key] !== undefined) {
          form[key] = project.value[key]
        }
      })
    }
  } catch (error) {
    console.error('Failed to fetch project:', error)
  } finally {
    loading.value = false
  }
}

const fetchUsers = async () => {
  try {
    const response = await fetch('/api/v1/users', {
      headers: {
        'Authorization': `Bearer ${document.querySelector('meta[name="api-token"]')?.content}`,
        'Accept': 'application/json',
      }
    })
    
    if (response.ok) {
      const data = await response.json()
      users.value = data.data
    }
  } catch (error) {
    console.error('Failed to fetch users:', error)
  }
}

const fetchCustomers = async () => {
  try {
    const response = await fetch('/api/v1/customers', {
      headers: {
        'Authorization': `Bearer ${document.querySelector('meta[name="api-token"]')?.content}`,
        'Accept': 'application/json',
      }
    })
    
    if (response.ok) {
      const data = await response.json()
      customers.value = data.data
    }
  } catch (error) {
    console.error('Failed to fetch customers:', error)
  }
}

const submitForm = async () => {
  saving.value = true
  errors.value = {}
  
  try {
    const response = await fetch(`/api/v1/projects/${props.projectId}`, {
      method: 'PUT',
      headers: {
        'Authorization': `Bearer ${document.querySelector('meta[name="api-token"]')?.content}`,
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: JSON.stringify(form)
    })
    
    const data = await response.json()
    
    if (response.ok) {
      router.visit(route('projects.show', props.projectId))
    } else {
      if (data.errors) {
        errors.value = data.errors
      }
    }
  } catch (error) {
    console.error('Failed to update project:', error)
  } finally {
    saving.value = false
  }
}

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString()
}
</script>