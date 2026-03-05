<template>
  <ThemeProvider>
    <SidebarProvider>
      <AdminLayout>
        <div class="space-y-6">
          <!-- Header -->
          <div class="flex items-center gap-4">
            <Button variant="ghost" @click="$inertia.visit('/workflows')">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
              </svg>
            </Button>
            <div>
              <h1 class="text-xl font-bold text-black">Workflow Instance Details</h1>
              <p class="text-sm text-gray-1">{{ workflowInstance?.document_type }} #{{ workflowInstance?.document_id }}</p>
            </div>
          </div>

          <div v-if="loading" class="flex items-center justify-center py-16">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
          </div>

          <div v-else-if="!workflowInstance" class="text-center py-16">
            <p class="text-gray-1">Workflow instance not found</p>
          </div>

          <div v-else class="space-y-6">
            <!-- Current Status Card -->
            <Card>
              <div class="p-6">
                <div class="flex items-center justify-between">
                  <div>
                    <h2 class="text-lg font-semibold text-black mb-2">Current Status</h2>
                    <Badge :variant="workflowInstance.currentStatus?.color || 'default'" size="lg">
                      {{ workflowInstance.currentStatus?.label || 'Unknown' }}
                    </Badge>
                  </div>
                  <div class="text-right">
                    <p class="text-sm text-gray-1">Started</p>
                    <p class="font-mono text-sm">{{ formatDate(workflowInstance.started_at) }}</p>
                    <p v-if="workflowInstance.completed_at" class="text-sm text-gray-1 mt-2">Completed</p>
                    <p v-if="workflowInstance.completed_at" class="font-mono text-sm">{{ formatDate(workflowInstance.completed_at) }}</p>
                  </div>
                </div>
              </div>
            </Card>

            <!-- Workflow Progress Visualization -->
            <Card>
              <div class="p-6">
                <h2 class="text-lg font-semibold text-black mb-4">Workflow Progress</h2>
                <div class="relative">
                  <!-- Progress Line -->
                  <div class="absolute top-1/2 left-0 right-0 h-0.5 bg-gray-200 -translate-y-1/2"></div>
                  
                  <!-- Status Steps -->
                  <div class="flex justify-between relative">
                    <div
                      v-for="(status, index) in sortedStatuses"
                      :key="status.key"
                      class="flex flex-col items-center"
                    >
                      <!-- Status Circle -->
                      <div
                        class="w-10 h-10 rounded-full flex items-center justify-center text-sm font-medium z-10 transition-all"
                        :class="getStatusClass(status.key)"
                      >
                        {{ getStatusIcon(status.key) }}
                      </div>
                      
                      <!-- Status Label -->
                      <span class="text-xs mt-2 text-center max-w-20">
                        {{ status.label }}
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            </Card>

            <!-- Available Actions -->
            <Card v-if="availableTransitions.length > 0">
              <div class="p-6">
                <h2 class="text-lg font-semibold text-black mb-4">Available Actions</h2>
                <div class="flex flex-wrap gap-2">
                  <Button
                    v-for="transition in availableTransitions"
                    :key="transition.id"
                    :variant="getButtonVariant(transition.to_status_key)"
                    @click="handleTransition(transition)"
                  >
                    {{ transition.label }}
                  </Button>
                </div>
              </div>
            </Card>

            <!-- Document Details -->
            <Card>
              <div class="p-6">
                <h2 class="text-lg font-semibold text-black mb-4">Document Details</h2>
                <div class="grid grid-cols-2 gap-4">
                  <div>
                    <p class="text-sm text-gray-1">Document Type</p>
                    <p class="font-medium">{{ workflowInstance.document_type }}</p>
                  </div>
                  <div>
                    <p class="text-sm text-gray-1">Document ID</p>
                    <p class="font-medium">#{{ workflowInstance.document_id }}</p>
                  </div>
                  <div>
                    <p class="text-sm text-gray-1">Workflow Definition</p>
                    <p class="font-medium">{{ workflowInstance.definition?.name }}</p>
                  </div>
                  <div>
                    <p class="text-sm text-gray-1">Company ID</p>
                    <p class="font-medium">#{{ workflowInstance.company_id }}</p>
                  </div>
                </div>
              </div>
            </Card>

            <!-- Workflow History -->
            <Card>
              <div class="p-6">
                <h2 class="text-lg font-semibold text-black mb-4">Workflow History</h2>
                <div v-if="workflowInstance.history?.length === 0" class="text-sm text-gray-1 py-4">
                  No history recorded yet
                </div>
                <div v-else class="space-y-4">
                  <div
                    v-for="(history, index) in workflowInstance.history"
                    :key="history.id"
                    class="flex items-start gap-4"
                  >
                    <!-- Timeline Dot -->
                    <div class="flex flex-col items-center">
                      <div
                        class="w-3 h-3 rounded-full"
                        :class="getHistoryDotColor(history)"
                      ></div>
                      <div
                        v-if="index < workflowInstance.history.length - 1"
                        class="w-0.5 h-8 bg-gray-200"
                      ></div>
                    </div>

                    <!-- History Content -->
                    <div class="flex-1 pb-4">
                      <div class="flex items-center gap-2 mb-1">
                        <span class="font-medium">{{ history.triggered_by?.name || 'System' }}</span>
                        <Badge :variant="history.to_status_key" size="sm">
                          {{ getStatusLabel(history.to_status_key) }}
                        </Badge>
                      </div>
                      <p class="text-sm text-gray-1 mb-1">{{ history.comment }}</p>
                      <p class="text-xs text-gray-1">{{ formatDate(history.created_at) }}</p>
                    </div>
                  </div>
                </div>
              </div>
            </Card>

            <!-- Pending Approvals -->
            <Card v-if="pendingApprovals.length > 0">
              <div class="p-6">
                <h2 class="text-lg font-semibold text-black mb-4">Pending Approvals</h2>
                <div class="space-y-3">
                  <div
                    v-for="approval in pendingApprovals"
                    :key="approval.id"
                    class="flex items-center justify-between p-3 bg-gray-50 rounded-lg"
                  >
                    <div>
                      <p class="font-medium">{{ approval.approver?.name || 'Unknown' }}</p>
                      <p class="text-sm text-gray-1">{{ approval.transition?.label }}</p>
                    </div>
                    <Badge variant="warning">Pending</Badge>
                  </div>
                </div>
              </div>
            </Card>
          </div>
        </div>

        <!-- Transition Modal -->
        <Modal v-if="showTransitionModal" @close="closeTransitionModal">
          <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
            <div class="p-6 border-b">
              <h2 class="text-xl font-bold text-black">Execute Transition</h2>
            </div>
            <div class="p-6">
              <p class="text-sm text-gray-1 mb-4">
                Are you sure you want to execute "{{ selectedTransition?.label }}"?
              </p>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Notes (optional)</label>
                <textarea
                  v-model="transitionNotes"
                  rows="3"
                  class="w-full border rounded-lg px-3 py-2"
                  placeholder="Add any notes about this action..."
                ></textarea>
              </div>
            </div>
            <div class="p-6 border-t flex justify-end gap-2">
              <Button variant="secondary" @click="closeTransitionModal">Cancel</Button>
              <Button @click="confirmTransition" :disabled="transitioning">
                {{ transitioning ? 'Processing...' : 'Confirm' }}
              </Button>
            </div>
          </div>
        </Modal>
      </AdminLayout>
    </SidebarProvider>
  </ThemeProvider>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { usePage } from '@inertiajs/vue3'
import ThemeProvider from '@/Components/Layout/ThemeProvider.vue'
import SidebarProvider from '@/Components/Layout/SidebarProvider.vue'
import AdminLayout from '@/Components/Layout/AdminLayout.vue'
import Card from '@/Components/ui/Card.vue'
import Button from '@/Components/ui/Button.vue'
import Badge from '@/Components/UI/Badge.vue'
import Modal from '@/Components/UI/Modal.vue'
import { useApi } from '@/Composables/useApi.js'

const page = usePage()
const { get, post } = useApi()

const workflowInstance = ref(null)
const loading = ref(false)
const showTransitionModal = ref(false)
const selectedTransition = ref(null)
const transitionNotes = ref('')
const transitioning = ref(false)

const sortedStatuses = computed(() => {
  if (!workflowInstance.value?.definition?.statuses) return []
  return [...workflowInstance.value.definition.statuses].sort((a, b) => 
    (a.display_order || 0) - (b.display_order || 0)
  )
})

const availableTransitions = computed(() => {
  if (!workflowInstance.value?.definition?.transitions) return []
  return workflowInstance.value.definition.transitions.filter(t => 
    t.from_status_key === workflowInstance.value.current_status_key
  )
})

const pendingApprovals = computed(() => {
  if (!workflowInstance.value?.approvals) return []
  return workflowInstance.value.approvals.filter(a => a.status === 'pending')
})

const fetchWorkflowInstance = async () => {
  loading.value = true
  try {
    const id = page.props.workflowInstanceId || new URLSearchParams(window.location.search).get('id')
    if (!id) return

    const response = await get(`/workflow-instances/${id}`)
    workflowInstance.value = response.data
  } catch (error) {
    console.error('Error fetching workflow instance:', error)
  } finally {
    loading.value = false
  }
}

const getStatusClass = (statusKey) => {
  const status = sortedStatuses.value.find(s => s.key === statusKey)
  const currentIndex = sortedStatuses.value.findIndex(s => s.key === workflowInstance.value?.current_status_key)
  const statusIndex = sortedStatuses.value.findIndex(s => s.key === statusKey)
  
  if (statusIndex < currentIndex) {
    return 'bg-green-500 text-white'
  } else if (statusIndex === currentIndex) {
    return 'bg-blue-600 text-white ring-4 ring-blue-200'
  } else {
    return 'bg-gray-200 text-gray-500'
  }
}

const getStatusIcon = (statusKey) => {
  const currentIndex = sortedStatuses.value.findIndex(s => s.key === workflowInstance.value?.current_status_key)
  const statusIndex = sortedStatuses.value.findIndex(s => s.key === statusKey)
  
  if (statusIndex < currentIndex) {
    return '✓'
  } else if (statusIndex === currentIndex) {
    return '●'
  } else {
    return statusIndex + 1
  }
}

const getStatusLabel = (statusKey) => {
  const status = sortedStatuses.value.find(s => s.key === statusKey)
  return status?.label || statusKey
}

const getButtonVariant = (statusKey) => {
  const status = sortedStatuses.value.find(s => s.key === statusKey)
  const color = status?.color || 'default'
  
  const variants = {
    gray: 'secondary',
    blue: 'primary',
    green: 'success',
    yellow: 'warning',
    red: 'danger'
  }
  
  return variants[color] || 'secondary'
}

const getHistoryDotColor = (history) => {
  const status = sortedStatuses.value.find(s => s.key === history.to_status_key)
  const color = status?.color || 'gray'
  
  const colors = {
    gray: 'bg-gray-400',
    blue: 'bg-blue-500',
    green: 'bg-green-500',
    yellow: 'bg-yellow-400',
    red: 'bg-red-500'
  }
  
  return colors[color] || 'bg-gray-400'
}

const handleTransition = (transition) => {
  selectedTransition.value = transition
  transitionNotes.value = ''
  showTransitionModal.value = true
}

const closeTransitionModal = () => {
  showTransitionModal.value = false
  selectedTransition.value = null
  transitionNotes.value = ''
}

const confirmTransition = async () => {
  if (!selectedTransition.value || !workflowInstance.value) return

  transitioning.value = true
  try {
    await post(`/workflow-instances/${workflowInstance.value.id}/transition`, {
      transition: selectedTransition.value.label,
      notes: transitionNotes.value
    })
    await fetchWorkflowInstance()
    closeTransitionModal()
  } catch (error) {
    console.error('Error executing transition:', error)
    alert('Failed to execute transition')
  } finally {
    transitioning.value = false
  }
}

const formatDate = (dateString) => {
  if (!dateString) return 'N/A'
  return new Date(dateString).toLocaleString()
}

onMounted(() => {
  fetchWorkflowInstance()
})
</script>
