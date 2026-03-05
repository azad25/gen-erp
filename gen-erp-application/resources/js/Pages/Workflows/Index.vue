<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-xl font-bold text-black">Workflow Instances</h1>
        <p class="text-sm text-gray-1">Track workflow processes</p>
      </div>
      <div class="flex items-center gap-2">
        <select
          v-model="filters.status"
          class="border rounded-lg px-3 py-2 text-sm"
          @change="fetchWorkflows(1)"
        >
          <option value="">All Statuses</option>
          <option value="draft">Draft</option>
          <option value="pending_approval">Pending Approval</option>
          <option value="approved">Approved</option>
          <option value="rejected">Rejected</option>
          <option value="completed">Completed</option>
        </select>
        <select
          v-model="filters.document_type"
          class="border rounded-lg px-3 py-2 text-sm"
          @change="fetchWorkflows(1)"
        >
          <option value="">All Document Types</option>
          <option value="purchase_order">Purchase Order</option>
          <option value="sales_order">Sales Order</option>
          <option value="expense_claim">Expense Claim</option>
          <option value="invoice">Invoice</option>
          <option value="leave_request">Leave Request</option>
        </select>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex items-center justify-center py-16">
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
    </div>

    <!-- Empty State -->
    <div v-else-if="workflows.length === 0" class="flex flex-col items-center justify-center py-16 bg-white rounded-lg">
      <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mb-4">
        <span class="text-3xl">📋</span>
      </div>
      <h3 class="text-lg font-semibold text-black mb-2">No Workflow Instances Found</h3>
      <p class="text-sm text-gray-1 text-center max-w-md">
        Create a document to start tracking its workflow progress.
      </p>
    </div>

    <!-- Workflow List -->
    <div v-else class="bg-white rounded-lg divide-y">
      <div
        v-for="workflow in workflows"
        :key="workflow.id"
        class="p-4 hover:bg-gray-50 transition-colors cursor-pointer"
        @click="$inertia.visit(`/workflows/${workflow.id}`)"
      >
        <div class="flex items-start justify-between">
          <div class="flex-1">
            <div class="flex items-center gap-3 mb-1">
              <h3 class="font-semibold text-black">{{ workflow.definition?.name || 'Unknown Workflow' }}</h3>
              <Badge :variant="workflow.current_status_key || 'default'">
                {{ getStatusLabel(workflow.current_status_key) }}
              </Badge>
            </div>
            <p class="text-sm text-gray-1 mb-2">
              {{ workflow.document_type }} #{{ workflow.document_id }}
            </p>
            <div class="flex items-center gap-4 text-xs text-gray-1">
              <span>Started: {{ formatDate(workflow.started_at) }}</span>
              <span v-if="workflow.completed_at">Completed: {{ formatDate(workflow.completed_at) }}</span>
              <span v-if="workflow.approvals_count > 0">
                {{ workflow.approvals_count }} Approval{{ workflow.approvals_count > 1 ? 's' : '' }}
              </span>
            </div>
          </div>
          <div class="flex items-center gap-2">
            <Button
              v-if="workflow.can_transition"
              size="sm"
              @click.stop="handleTransition(workflow.id)"
            >
              Transition
            </Button>
            <Button variant="ghost" size="sm" @click.stop="$inertia.visit(`/workflows/${workflow.id}`)">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
              </svg>
            </Button>
          </div>
        </div>
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="pagination.total_pages > 1" class="flex items-center justify-center gap-2">
      <Button
        variant="secondary"
        size="sm"
        :disabled="pagination.current_page === 1"
        @click="fetchWorkflows(pagination.current_page - 1)"
      >
        Previous
      </Button>
      <span class="text-sm text-gray-1">
        Page {{ pagination.current_page }} of {{ pagination.total_pages }}
      </span>
      <Button
        variant="secondary"
        size="sm"
        :disabled="pagination.current_page === pagination.total_pages"
        @click="fetchWorkflows(pagination.current_page + 1)"
      >
        Next
      </Button>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import Badge from '../../Components/UI/Badge.vue'
import Button from '../../Components/UI/Button.vue'
import { useApi } from '../../Composables/useApi.js'

const { get, post } = useApi()

const workflows = ref([])
const loading = ref(false)
const pagination = ref({
  current_page: 1,
  total_pages: 1,
  total: 0
})

const filters = ref({
  status: '',
  document_type: ''
})

const fetchWorkflows = async (page = 1) => {
  loading.value = true
  try {
    const params = {
      page,
      per_page: 15,
      ...(filters.value.status && { status: filters.value.status }),
      ...(filters.value.document_type && { document_type: filters.value.document_type })
    }
    
    const response = await get('/workflow-instances', params)
    workflows.value = response.data
    pagination.value = response.meta
  } catch (error) {
    console.error('Error fetching workflows:', error)
  } finally {
    loading.value = false
  }
}

const handleTransition = async (id) => {
  if (!confirm('Are you sure you want to transition this workflow?')) return
  
  try {
    await post(`/workflow-instances/${id}/transition`)
    await fetchWorkflows(pagination.value.current_page)
  } catch (error) {
    console.error('Error transitioning workflow:', error)
    alert('Failed to transition workflow')
  }
}

const getStatusLabel = (statusKey) => {
  const labels = {
    draft: 'Draft',
    pending_approval: 'Pending Approval',
    approved: 'Approved',
    rejected: 'Rejected',
    completed: 'Completed'
  }
  return labels[statusKey] || statusKey
}

const formatDate = (dateString) => {
  if (!dateString) return 'N/A'
  return new Date(dateString).toLocaleDateString()
}

onMounted(() => {
  fetchWorkflows()
})
</script>
