<template>
  <div class="bg-white rounded-lg border border-gray-200 shadow-sm">
    <!-- Header -->
    <div class="px-4 py-3 border-b border-gray-200">
      <div class="flex items-center justify-between">
        <h3 class="text-sm font-medium text-gray-900">Activity Timeline</h3>
        <div class="flex items-center space-x-2">
          <select
            v-model="selectedFilter"
            @change="fetchActivities"
            class="text-xs border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
          >
            <option value="all">All Activities</option>
            <option value="calls">Calls</option>
            <option value="emails">Emails</option>
            <option value="meetings">Meetings</option>
            <option value="notes">Notes</option>
            <option value="tasks">Tasks</option>
          </select>
          <button
            @click="showAddActivityModal = true"
            class="text-xs bg-indigo-600 hover:bg-indigo-700 text-white px-2 py-1 rounded-md"
          >
            Add Activity
          </button>
          <button
            @click="collapsed = !collapsed"
            class="text-gray-400 hover:text-gray-600"
          >
            <ChevronUpIcon v-if="!collapsed" class="h-4 w-4" />
            <ChevronDownIcon v-else class="h-4 w-4" />
          </button>
        </div>
      </div>
    </div>

    <!-- Content -->
    <div v-if="!collapsed" class="p-4">
      <!-- Loading State -->
      <div v-if="loading" class="flex justify-center py-8">
        <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-indigo-600"></div>
      </div>

      <!-- Empty State -->
      <div v-else-if="activities.length === 0" class="text-center py-8">
        <ClockIcon class="mx-auto h-12 w-12 text-gray-400" />
        <h3 class="mt-2 text-sm font-medium text-gray-900">No activities yet</h3>
        <p class="mt-1 text-sm text-gray-500">Get started by adding your first activity.</p>
        <div class="mt-6">
          <button
            @click="showAddActivityModal = true"
            class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700"
          >
            <PlusIcon class="h-4 w-4 mr-2" />
            Add Activity
          </button>
        </div>
      </div>

      <!-- Timeline -->
      <div v-else class="flow-root">
        <ul role="list" class="-mb-8">
          <li v-for="(activity, activityIdx) in activities" :key="activity.id">
            <div class="relative pb-8">
              <span
                v-if="activityIdx !== activities.length - 1"
                class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200"
                aria-hidden="true"
              />
              <div class="relative flex space-x-3">
                <div>
                  <span
                    class="h-8 w-8 rounded-full flex items-center justify-center ring-8 ring-white"
                    :class="getActivityIconClass(activity.type)"
                  >
                    <component :is="getActivityIcon(activity.type)" class="h-4 w-4" />
                  </span>
                </div>
                <div class="flex-1 min-w-0">
                  <div class="flex items-center justify-between">
                    <div>
                      <p class="text-sm font-medium text-gray-900">
                        {{ activity.title }}
                      </p>
                      <p class="text-xs text-gray-500">
                        {{ activity.user?.name }} • {{ formatDate(activity.created_at) }}
                      </p>
                    </div>
                    <div class="flex items-center space-x-2">
                      <span
                        class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
                        :class="getActivityStatusClass(activity.status)"
                      >
                        {{ activity.status }}
                      </span>
                      <button
                        @click="editActivity(activity)"
                        class="text-gray-400 hover:text-gray-600"
                      >
                        <PencilIcon class="h-3 w-3" />
                      </button>
                    </div>
                  </div>
                  <div v-if="activity.description" class="mt-2 text-sm text-gray-700">
                    {{ activity.description }}
                  </div>
                  <div v-if="activity.metadata" class="mt-2 space-y-1">
                    <div v-if="activity.metadata.duration" class="text-xs text-gray-500">
                      Duration: {{ activity.metadata.duration }} minutes
                    </div>
                    <div v-if="activity.metadata.outcome" class="text-xs text-gray-500">
                      Outcome: {{ activity.metadata.outcome }}
                    </div>
                    <div v-if="activity.metadata.next_action" class="text-xs text-gray-500">
                      Next Action: {{ activity.metadata.next_action }}
                    </div>
                  </div>
                  <div v-if="activity.attachments && activity.attachments.length > 0" class="mt-2">
                    <div class="flex flex-wrap gap-1">
                      <span
                        v-for="attachment in activity.attachments"
                        :key="attachment.id"
                        class="inline-flex items-center px-2 py-1 rounded-md text-xs bg-gray-100 text-gray-700"
                      >
                        <PaperClipIcon class="h-3 w-3 mr-1" />
                        {{ attachment.name }}
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </li>
        </ul>
      </div>

      <!-- Load More -->
      <div v-if="hasMore" class="mt-6 text-center">
        <button
          @click="loadMore"
          :disabled="loading"
          class="text-sm text-indigo-600 hover:text-indigo-500 disabled:opacity-50"
        >
          {{ loading ? 'Loading...' : 'Load More Activities' }}
        </button>
      </div>
    </div>

    <!-- Add Activity Modal -->
    <AddActivityModal
      v-if="showAddActivityModal"
      :lead-id="leadId"
      @close="showAddActivityModal = false"
      @saved="handleActivitySaved"
    />

    <!-- Edit Activity Modal -->
    <EditActivityModal
      v-if="editingActivity"
      :activity="editingActivity"
      @close="editingActivity = null"
      @saved="handleActivitySaved"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import {
  ChevronUpIcon,
  ChevronDownIcon,
  ClockIcon,
  PlusIcon,
  PencilIcon,
  PaperClipIcon,
  PhoneIcon,
  EnvelopeIcon,
  CalendarIcon,
  DocumentTextIcon,
  CheckCircleIcon
} from '@heroicons/vue/24/outline'
import { useApi } from '@/Composables/useApi'
import AddActivityModal from './AddActivityModal.vue'
import EditActivityModal from './EditActivityModal.vue'

const props = defineProps({
  leadId: {
    type: [String, Number],
    required: true
  }
})

const emit = defineEmits(['activity-added', 'activity-updated'])

const { get, loading } = useApi()

// Reactive data
const collapsed = ref(false)
const activities = ref([])
const selectedFilter = ref('all')
const currentPage = ref(1)
const hasMore = ref(false)
const showAddActivityModal = ref(false)
const editingActivity = ref(null)

// Methods
const fetchActivities = async (page = 1) => {
  try {
    const params = {
      page,
      filter: selectedFilter.value,
      per_page: 10
    }
    
    const data = await get(`/api/v1/crm/leads/${props.leadId}/activities`, params)
    
    if (page === 1) {
      activities.value = data.data
    } else {
      activities.value.push(...data.data)
    }
    
    currentPage.value = page
    hasMore.value = data.meta?.has_more_pages || false
  } catch (err) {
    console.error('Failed to fetch activities:', err)
  }
}

const loadMore = () => {
  fetchActivities(currentPage.value + 1)
}

const editActivity = (activity) => {
  editingActivity.value = activity
}

const handleActivitySaved = (activity) => {
  showAddActivityModal.value = false
  editingActivity.value = null
  
  // Refresh activities
  fetchActivities()
  
  emit('activity-added', activity)
}

const getActivityIcon = (type) => {
  const icons = {
    call: PhoneIcon,
    email: EnvelopeIcon,
    meeting: CalendarIcon,
    note: DocumentTextIcon,
    task: CheckCircleIcon
  }
  return icons[type] || ClockIcon
}

const getActivityIconClass = (type) => {
  const classes = {
    call: 'bg-blue-500 text-white',
    email: 'bg-green-500 text-white',
    meeting: 'bg-purple-500 text-white',
    note: 'bg-yellow-500 text-white',
    task: 'bg-indigo-500 text-white'
  }
  return classes[type] || 'bg-gray-500 text-white'
}

const getActivityStatusClass = (status) => {
  const classes = {
    completed: 'bg-green-100 text-green-800',
    pending: 'bg-yellow-100 text-yellow-800',
    cancelled: 'bg-red-100 text-red-800',
    scheduled: 'bg-blue-100 text-blue-800'
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

const formatDate = (dateString) => {
  const date = new Date(dateString)
  const now = new Date()
  const diffTime = Math.abs(now - date)
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
  
  if (diffDays === 1) {
    return 'Yesterday'
  } else if (diffDays < 7) {
    return `${diffDays} days ago`
  } else {
    return date.toLocaleDateString('en-US', {
      month: 'short',
      day: 'numeric',
      year: date.getFullYear() !== now.getFullYear() ? 'numeric' : undefined
    })
  }
}

// Lifecycle
onMounted(() => {
  fetchActivities()
})
</script>