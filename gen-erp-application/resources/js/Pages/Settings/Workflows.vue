<template>
  <ThemeProvider>
    <SidebarProvider>
      <AdminLayout>
        <div class="space-y-6">
          <!-- Header -->
          <div class="flex items-center justify-between">
            <div>
              <h1 class="text-xl font-bold text-black">Workflows</h1>
              <p class="text-sm text-gray-1">Manage workflows for your business</p>
            </div>
            <div class="flex items-center gap-2">
              <Button variant="secondary" size="sm" @click="handleExport">Export</Button>
              <Button size="sm" @click="showCreateModal = true">+ New Workflow</Button>
            </div>
          </div>

          <!-- Workflow List -->
          <Card>
            <div v-if="loading" class="flex items-center justify-center py-16">
              <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
            </div>

            <div v-else-if="workflows.length === 0" class="flex flex-col items-center justify-center py-16">
              <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                <span class="text-3xl">�</span>
              </div>
              <h3 class="text-lg font-semibold text-black mb-2">No Workflows Found</h3>
              <p class="text-sm text-gray-1 text-center max-w-md mb-4">
                Create your first workflow to start managing approval processes.
              </p>
              <Button size="sm" @click="showCreateModal = true">Create Workflow</Button>
            </div>

            <div v-else class="divide-y">
              <div
                v-for="workflow in workflows"
                :key="workflow.id"
                class="p-4 hover:bg-gray-50 transition-colors"
              >
                <div class="flex items-start justify-between">
                  <div class="flex-1">
                    <div class="flex items-center gap-3">
                      <h3 class="font-semibold text-black">{{ workflow.name }}</h3>
                      <Badge :variant="workflow.is_active ? 'success' : 'secondary'">
                        {{ workflow.is_active ? 'Active' : 'Inactive' }}
                      </Badge>
                      <Badge v-if="workflow.is_default" variant="primary">Default</Badge>
                    </div>
                    <p class="text-sm text-gray-1 mt-1">
                      Document Type: <span class="font-medium">{{ workflow.document_type }}</span>
                    </p>
                    <div class="flex items-center gap-4 mt-2 text-sm text-gray-1">
                      <span>{{ workflow.statuses_count || 0 }} Statuses</span>
                      <span>{{ workflow.transitions_count || 0 }} Transitions</span>
                      <span>{{ workflow.instances_count || 0 }} Instances</span>
                    </div>
                  </div>
                  <div class="flex items-center gap-2">
                    <Button variant="ghost" size="sm" @click="editWorkflow(workflow)">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                      </svg>
                    </Button>
                    <Button variant="ghost" size="sm" @click="duplicateWorkflow(workflow)">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                      </svg>
                    </Button>
                    <Button
                      variant="ghost"
                      size="sm"
                      class="text-red-600 hover:text-red-700 hover:bg-red-50"
                      @click="deleteWorkflow(workflow)"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                      </svg>
                    </Button>
                  </div>
                </div>
              </div>
            </div>
          </Card>
        </div>

        <!-- Create/Edit Workflow Modal -->
        <Modal v-if="showCreateModal || showEditModal" @close="closeModal">
          <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-hidden flex flex-col">
            <div class="p-6 border-b">
              <h2 class="text-xl font-bold text-black">
                {{ isEditing ? 'Edit Workflow' : 'Create New Workflow' }}
              </h2>
            </div>

            <div class="p-6 overflow-y-auto flex-1">
              <form @submit.prevent="saveWorkflow">
                <div class="space-y-4">
                  <!-- Basic Info -->
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Workflow Name</label>
                    <input
                      v-model="form.name"
                      type="text"
                      class="w-full border rounded-lg px-3 py-2"
                      placeholder="e.g., Purchase Order Approval"
                      required
                    />
                  </div>

                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Document Type</label>
                    <select
                      v-model="form.document_type"
                      class="w-full border rounded-lg px-3 py-2"
                      required
                    >
                      <option value="">Select document type...</option>
                      <option value="purchase_order">Purchase Order</option>
                      <option value="sales_order">Sales Order</option>
                      <option value="expense_claim">Expense Claim</option>
                      <option value="invoice">Invoice</option>
                      <option value="leave_request">Leave Request</option>
                    </select>
                  </div>

                  <div class="flex items-center gap-4">
                    <label class="flex items-center gap-2">
                      <input
                        v-model="form.is_active"
                        type="checkbox"
                        class="rounded"
                      />
                      <span class="text-sm text-gray-700">Active</span>
                    </label>

                    <label class="flex items-center gap-2">
                      <input
                        v-model="form.is_default"
                        type="checkbox"
                        class="rounded"
                        :disabled="!form.is_active"
                      />
                      <span class="text-sm text-gray-700">Default Workflow</span>
                    </label>
                  </div>

                  <!-- Statuses -->
                  <div class="border-t pt-4">
                    <div class="flex items-center justify-between mb-2">
                      <h3 class="font-semibold text-black">Statuses</h3>
                      <Button size="sm" variant="secondary" @click="addStatus">+ Add Status</Button>
                    </div>

                    <div v-if="form.statuses.length === 0" class="text-sm text-gray-1 py-4">
                      No statuses defined. Add at least one status.
                    </div>

                    <div v-else class="space-y-2">
                      <div
                        v-for="(status, index) in form.statuses"
                        :key="index"
                        class="flex items-center gap-2 p-3 bg-gray-50 rounded-lg"
                      >
                        <div class="flex-1 grid grid-cols-3 gap-2">
                          <input
                            v-model="status.key"
                            type="text"
                            class="border rounded px-2 py-1 text-sm"
                            placeholder="Key (e.g., draft)"
                            required
                          />
                          <input
                            v-model="status.label"
                            type="text"
                            class="border rounded px-2 py-1 text-sm"
                            placeholder="Label (e.g., Draft)"
                            required
                          />
                          <select
                            v-model="status.color"
                            class="border rounded px-2 py-1 text-sm"
                          >
                            <option value="gray">Gray</option>
                            <option value="blue">Blue</option>
                            <option value="green">Green</option>
                            <option value="yellow">Yellow</option>
                            <option value="red">Red</option>
                          </select>
                        </div>

                        <label class="flex items-center gap-1">
                          <input
                            v-model="status.is_initial"
                            type="checkbox"
                            class="rounded"
                          />
                          <span class="text-xs">Initial</span>
                        </label>

                        <label class="flex items-center gap-1">
                          <input
                            v-model="status.is_terminal"
                            type="checkbox"
                            class="rounded"
                          />
                          <span class="text-xs">Terminal</span>
                        </label>

                        <Button
                          variant="ghost"
                          size="sm"
                          @click="removeStatus(index)"
                          class="text-red-600"
                        >
                          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                          </svg>
                        </Button>
                      </div>
                    </div>
                  </div>

                  <!-- Transitions -->
                  <div class="border-t pt-4">
                    <div class="flex items-center justify-between mb-2">
                      <h3 class="font-semibold text-black">Transitions</h3>
                      <Button size="sm" variant="secondary" @click="addTransition">+ Add Transition</Button>
                    </div>

                    <div v-if="form.transitions.length === 0" class="text-sm text-gray-1 py-4">
                      No transitions defined. Add transitions to connect statuses.
                    </div>

                    <div v-else class="space-y-2">
                      <div
                        v-for="(transition, index) in form.transitions"
                        :key="index"
                        class="p-3 bg-gray-50 rounded-lg"
                      >
                        <div class="grid grid-cols-2 gap-2 mb-2">
                          <select
                            v-model="transition.from_status_key"
                            class="border rounded px-2 py-1 text-sm"
                            required
                          >
                            <option value="">From Status</option>
                            <option
                              v-for="status in form.statuses"
                              :key="status.key"
                              :value="status.key"
                            >
                              {{ status.label }}
                            </option>
                          </select>

                          <select
                            v-model="transition.to_status_key"
                            class="border rounded px-2 py-1 text-sm"
                            required
                          >
                            <option value="">To Status</option>
                            <option
                              v-for="status in form.statuses"
                              :key="status.key"
                              :value="status.key"
                            >
                              {{ status.label }}
                            </option>
                          </select>
                        </div>

                        <input
                          v-model="transition.label"
                          type="text"
                          class="border rounded px-2 py-1 text-sm mb-2 w-full"
                          placeholder="Transition Label (e.g., Submit for Approval)"
                          required
                        />

                        <div class="flex items-center gap-2 mb-2">
                          <label class="flex items-center gap-1">
                            <input
                              v-model="transition.requires_approval"
                              type="checkbox"
                              class="rounded"
                            />
                            <span class="text-xs">Requires Approval</span>
                          </label>

                          <div v-if="transition.requires_approval" class="flex-1">
                            <select
                              v-model="transition.approver_roles"
                              multiple
                              class="border rounded px-2 py-1 text-sm w-full"
                            >
                              <option value="owner">Owner</option>
                              <option value="admin">Admin</option>
                              <option value="manager">Manager</option>
                              <option value="employee">Employee</option>
                            </select>
                          </div>
                        </div>

                        <div class="flex items-center justify-between">
                          <Button
                            variant="ghost"
                            size="sm"
                            @click="removeTransition(index)"
                            class="text-red-600"
                          >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                          </Button>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </form>
            </div>

            <div class="p-6 border-t flex justify-end gap-2">
              <Button variant="secondary" @click="closeModal">Cancel</Button>
              <Button @click="saveWorkflow" :disabled="saving">
                {{ saving ? 'Saving...' : 'Save Workflow' }}
              </Button>
            </div>
          </div>
        </Modal>
      </AdminLayout>
    </SidebarProvider>
  </ThemeProvider>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import ThemeProvider from '@/Components/Layout/ThemeProvider.vue'
import SidebarProvider from '@/Components/Layout/SidebarProvider.vue'
import AdminLayout from '@/Components/Layout/AdminLayout.vue'
import Card from '@/Components/ui/Card.vue'
import Button from '@/Components/ui/Button.vue'
import Badge from '@/Components/UI/Badge.vue'
import Modal from '@/Components/UI/Modal.vue'
import { useApi } from '@/Composables/useApi.js'

const { get, post, put, delete: del } = useApi()

const workflows = ref([])
const loading = ref(false)
const saving = ref(false)
const showCreateModal = ref(false)
const showEditModal = ref(false)
const isEditing = ref(false)
const editingId = ref(null)

const form = ref({
  name: '',
  document_type: '',
  is_active: true,
  is_default: false,
  statuses: [],
  transitions: []
})

const fetchWorkflows = async () => {
  loading.value = true
  try {
    const response = await get('/workflow-definitions')
    workflows.value = response.data
  } catch (error) {
    console.error('Error fetching workflows:', error)
  } finally {
    loading.value = false
  }
}

const addStatus = () => {
  form.value.statuses.push({
    key: '',
    label: '',
    color: 'gray',
    is_initial: false,
    is_terminal: false
  })
}

const removeStatus = (index) => {
  form.value.statuses.splice(index, 1)
}

const addTransition = () => {
  form.value.transitions.push({
    from_status_key: '',
    to_status_key: '',
    label: '',
    requires_approval: false,
    approver_roles: []
  })
}

const removeTransition = (index) => {
  form.value.transitions.splice(index, 1)
}

const closeModal = () => {
  showCreateModal.value = false
  showEditModal.value = false
  isEditing.value = false
  editingId.value = null
  form.value = {
    name: '',
    document_type: '',
    is_active: true,
    is_default: false,
    statuses: [],
    transitions: []
  }
}

const editWorkflow = (workflow) => {
  isEditing.value = true
  editingId.value = workflow.id
  form.value = {
    name: workflow.name,
    document_type: workflow.document_type,
    is_active: workflow.is_active,
    is_default: workflow.is_default,
    statuses: workflow.statuses || [],
    transitions: workflow.transitions || []
  }
  showEditModal.value = true
}

const duplicateWorkflow = async (workflow) => {
  if (!confirm(`Duplicate "${workflow.name}" workflow?`)) return

  try {
    const response = await post('/workflow-definitions', {
      ...workflow,
      name: `${workflow.name} (Copy)`,
      is_default: false
    })
    await fetchWorkflows()
  } catch (error) {
    console.error('Error duplicating workflow:', error)
    alert('Failed to duplicate workflow')
  }
}

const deleteWorkflow = async (workflow) => {
  if (!confirm(`Delete "${workflow.name}" workflow? This action cannot be undone.`)) return

  try {
    await del(`/workflow-definitions/${workflow.id}`)
    await fetchWorkflows()
  } catch (error) {
    console.error('Error deleting workflow:', error)
    alert('Failed to delete workflow')
  }
}

const saveWorkflow = async () => {
  if (!form.value.name || !form.value.document_type) {
    alert('Please fill in all required fields')
    return
  }

  if (form.value.statuses.length === 0) {
    alert('Please add at least one status')
    return
  }

  if (!form.value.statuses.some(s => s.is_initial)) {
    alert('Please mark one status as initial')
    return
  }

  saving.value = true
  try {
    if (isEditing.value) {
      await put(`/workflow-definitions/${editingId.value}`, form.value)
    } else {
      await post('/workflow-definitions', form.value)
    }
    await fetchWorkflows()
    closeModal()
  } catch (error) {
    console.error('Error saving workflow:', error)
    alert('Failed to save workflow')
  } finally {
    saving.value = false
  }
}

const handleExport = () => {
  alert('Export functionality coming soon')
}

onMounted(() => {
  fetchWorkflows()
})
</script>
