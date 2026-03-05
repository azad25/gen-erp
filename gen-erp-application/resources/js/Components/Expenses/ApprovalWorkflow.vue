<template>
  <div class="space-y-4">
    <!-- Current Status Badge -->
    <div class="flex items-center justify-between">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">
          Current Status
        </label>
        <Badge :variant="currentStatus">{{ getStatusLabel(currentStatus) }}</Badge>
      </div>
      <div v-if="canTransition" class="text-sm text-gray-500">
        {{ getTransitionHint() }}
      </div>
    </div>

    <!-- Workflow Progress -->
    <div class="relative">
      <div class="absolute top-1/2 left-0 right-0 h-0.5 bg-gray-200 -translate-y-1/2"></div>
      <div class="flex justify-between relative">
        <div
          v-for="step in workflowSteps"
          :key="step.status"
          class="flex flex-col items-center"
          :class="{ 'opacity-50': !isStepReached(step.status) }"
        >
          <div
            class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium z-10"
            :class="getStepClass(step.status)"
          >
            {{ isStepReached(step.status) ? '✓' : getStepNumber(step.status) }}
          </div>
          <span class="text-xs mt-2 text-gray-600">{{ step.label }}</span>
        </div>
      </div>
    </div>

    <!-- Action Buttons -->
    <div v-if="canTransition" class="flex gap-2">
      <Button
        v-for="transition in availableTransitions"
        :key="transition.to"
        :variant="getButtonVariant(transition.to)"
        :disabled="loading"
        @click="handleTransition(transition)"
      >
        {{ transition.label }}
      </Button>
    </div>

    <!-- History Timeline -->
    <div v-if="history.length > 0" class="space-y-2">
      <h4 class="text-sm font-medium text-gray-900">Approval History</h4>
      <div class="space-y-2">
        <div
          v-for="(item, index) in history"
          :key="index"
          class="flex items-start gap-3 text-sm"
        >
          <div class="flex-shrink-0">
            <div
              class="w-2 h-2 rounded-full mt-1.5"
              :class="getStatusColor(item.status)"
            ></div>
          </div>
          <div class="flex-1">
            <div class="flex items-center gap-2">
              <span class="font-medium">{{ item.user?.name }}</span>
              <Badge :variant="item.status" size="sm">{{ getStatusLabel(item.status) }}</Badge>
            </div>
            <p class="text-gray-500 text-xs">{{ formatDate(item.created_at) }}</p>
            <p v-if="item.notes" class="text-gray-600 mt-1">{{ item.notes }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Notes Input -->
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">
        Notes (optional)
      </label>
      <textarea
        v-model="notes"
        rows="2"
        placeholder="Add any notes about this action..."
        class="w-full border rounded-lg px-3 py-2 text-sm"
      ></textarea>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { useApi } from '@/Composables/useApi.js'
import Badge from '@/Components/UI/Badge.vue'
import Button from '@/Components/ui/Button.vue'

const props = defineProps({
  expense: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['statusChanged'])

const page = usePage()
const { post } = useApi()

const loading = ref(false)
const notes = ref('')
const history = ref([])

const workflowSteps = [
  { status: 'draft', label: 'Draft' },
  { status: 'submitted', label: 'Submitted' },
  { status: 'approved', label: 'Approved' },
  { status: 'paid', label: 'Paid' }
]

const statusOrder = ['draft', 'submitted', 'approved', 'paid']

const currentStatus = computed(() => props.expense.status || 'draft')

const canTransition = computed(() => {
  const currentIndex = statusOrder.indexOf(currentStatus.value)
  return currentIndex < statusOrder.length - 1
})

const availableTransitions = computed(() => {
  const currentIndex = statusOrder.indexOf(currentStatus.value)
  const transitions = []

  if (currentIndex >= 0 && currentIndex < statusOrder.length - 1) {
    const nextStatus = statusOrder[currentIndex + 1]
    transitions.push({
      to: nextStatus,
      label: getStatusLabel(nextStatus)
    })
  }

  return transitions
})

const getTransitionHint = () => {
  const nextStatus = statusOrder[statusOrder.indexOf(currentStatus.value) + 1]
  return `Ready to ${getStatusLabel(nextStatus)}`
}

const isStepReached = (status) => {
  const currentIndex = statusOrder.indexOf(currentStatus.value)
  const stepIndex = statusOrder.indexOf(status)
  return stepIndex <= currentIndex
}

const getStepNumber = (status) => {
  return statusOrder.indexOf(status) + 1
}

const getStepClass = (status) => {
  const isReached = isStepReached(status)
  const isCurrent = status === currentStatus.value

  if (isCurrent) {
    return 'bg-blue-600 text-white ring-4 ring-blue-200'
  } else if (isReached) {
    return 'bg-green-500 text-white'
  } else {
    return 'bg-gray-200 text-gray-500'
  }
}

const getStatusLabel = (status) => {
  const labels = {
    draft: 'Draft',
    submitted: 'Submitted',
    approved: 'Approved',
    paid: 'Paid'
  }
  return labels[status] || status
}

const getStatusColor = (status) => {
  const colors = {
    draft: 'bg-gray-400',
    submitted: 'bg-yellow-400',
    approved: 'bg-green-500',
    paid: 'bg-blue-500'
  }
  return colors[status] || 'bg-gray-400'
}

const getButtonVariant = (status) => {
  const variants = {
    submitted: 'secondary',
    approved: 'success',
    paid: 'primary'
  }
  return variants[status] || 'secondary'
}

const handleTransition = async (transition) => {
  loading.value = true

  try {
    await post(`/expenses/${props.expense.id}/transition`, {
      to_status: transition.to,
      notes: notes.value
    })

    emit('statusChanged', {
      status: transition.to,
      notes: notes.value
    })

    notes.value = ''
    await loadHistory()
  } catch (error) {
    console.error('Error transitioning expense:', error)
  } finally {
    loading.value = false
  }
}

const loadHistory = async () => {
  try {
    const response = await get(`/expenses/${props.expense.id}/history`)
    history.value = response.data || []
  } catch (error) {
    console.error('Error loading history:', error)
  }
}

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleString()
}

onMounted(() => {
  loadHistory()
})
</script>
