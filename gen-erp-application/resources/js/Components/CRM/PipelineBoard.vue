<template>
  <div class="flex flex-col h-full">
    <!-- Board Header -->
    <div class="flex items-center justify-between mb-6">
      <div>
        <h2 class="text-xl font-semibold text-gray-900">{{ pipeline?.name || 'Sales Pipeline' }}</h2>
        <p class="text-sm text-gray-600">{{ pipeline?.description || 'Manage your sales opportunities' }}</p>
      </div>
      <div class="flex items-center space-x-3">
        <select
          v-if="pipelines.length > 1"
          v-model="selectedPipelineId"
          @change="loadPipeline"
          class="text-sm border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
        >
          <option v-for="p in pipelines" :key="p.id" :value="p.id">
            {{ p.name }}
          </option>
        </select>
        <button
          @click="showCreateOpportunityModal = true"
          class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md text-sm font-medium"
        >
          Add Opportunity
        </button>
        <button
          @click="showPipelineSettings = true"
          class="bg-gray-600 hover:bg-gray-700 text-white px-3 py-2 rounded-md text-sm font-medium"
        >
          Settings
        </button>
      </div>
    </div>

    <!-- Pipeline Statistics -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
      <div class="bg-white p-4 rounded-lg border border-gray-200">
        <div class="text-2xl font-bold text-gray-900">{{ stats.total_opportunities }}</div>
        <div class="text-sm text-gray-600">Total Opportunities</div>
      </div>
      <div class="bg-white p-4 rounded-lg border border-gray-200">
        <div class="text-2xl font-bold text-green-600">${{ formatCurrency(stats.total_value) }}</div>
        <div class="text-sm text-gray-600">Pipeline Value</div>
      </div>
      <div class="bg-white p-4 rounded-lg border border-gray-200">
        <div class="text-2xl font-bold text-blue-600">${{ formatCurrency(stats.weighted_value) }}</div>
        <div class="text-sm text-gray-600">Weighted Value</div>
      </div>
      <div class="bg-white p-4 rounded-lg border border-gray-200">
        <div class="text-2xl font-bold text-purple-600">{{ stats.avg_deal_size ? '$' + formatCurrency(stats.avg_deal_size) : '-' }}</div>
        <div class="text-sm text-gray-600">Avg Deal Size</div>
      </div>
    </div>

    <!-- Pipeline Board -->
    <div class="flex-1 overflow-x-auto">
      <div class="flex space-x-6 h-full min-w-max pb-6">
        <!-- Stage Column -->
        <div
          v-for="stage in stages"
          :key="stage.id"
          class="flex-shrink-0 w-80 bg-gray-50 rounded-lg"
        >
          <!-- Stage Header -->
          <div class="p-4 border-b border-gray-200">
            <div class="flex items-center justify-between">
              <div class="flex items-center space-x-2">
                <h3 class="font-medium text-gray-900">{{ stage.name }}</h3>
                <span class="bg-gray-200 text-gray-700 text-xs px-2 py-1 rounded-full">
                  {{ getStageOpportunityCount(stage.id) }}
                </span>
              </div>
              <div class="flex items-center space-x-1">
                <span class="text-xs text-gray-500">{{ stage.probability }}%</span>
                <button
                  @click="addOpportunity(stage.id)"
                  class="p-1 text-gray-400 hover:text-gray-600 rounded"
                  :title="`Add opportunity to ${stage.name}`"
                >
                  <PlusIcon class="h-4 w-4" />
                </button>
              </div>
            </div>
            <div class="mt-2 text-sm text-gray-600">
              ${{ formatCurrency(getStageValue(stage.id)) }}
            </div>
          </div>

          <!-- Stage Opportunities -->
          <div
            class="p-4 min-h-96 max-h-96 overflow-y-auto"
            @drop="onDrop($event, stage.id)"
            @dragover.prevent
            @dragenter.prevent
            :class="{ 'bg-blue-50 border-2 border-dashed border-blue-300': dragOverStage === stage.id }"
          >
            <div class="space-y-3">
              <!-- Opportunity Card -->
              <div
                v-for="opportunity in getStageOpportunities(stage.id)"
                :key="opportunity.id"
                :draggable="!readonly"
                @dragstart="onDragStart($event, opportunity)"
                @dragend="onDragEnd"
                class="cursor-move"
                :class="{ 'opacity-50': draggedOpportunity?.id === opportunity.id }"
              >
                <div class="bg-white rounded-lg border border-gray-200 p-4 shadow-sm hover:shadow-md transition-shadow">
                  <!-- Opportunity Header -->
                  <div class="flex items-start justify-between mb-3">
                    <div class="flex-1">
                      <h4 class="text-sm font-medium text-gray-900 line-clamp-2 mb-1">
                        {{ opportunity.name }}
                      </h4>
                      <p class="text-xs text-gray-600">{{ opportunity.account?.name || 'No Account' }}</p>
                    </div>
                    <div class="ml-2 flex-shrink-0">
                      <span :class="getPriorityColor(opportunity.priority)" class="w-2 h-2 rounded-full"></span>
                    </div>
                  </div>

                  <!-- Opportunity Value -->
                  <div class="mb-3">
                    <div class="text-lg font-semibold text-gray-900">
                      ${{ formatCurrency(opportunity.amount) }}
                    </div>
                    <div class="text-xs text-gray-500">
                      Weighted: ${{ formatCurrency(opportunity.amount * (stage.probability / 100)) }}
                    </div>
                  </div>

                  <!-- Opportunity Details -->
                  <div class="space-y-2 mb-3">
                    <!-- Owner -->
                    <div v-if="opportunity.owner" class="flex items-center text-xs text-gray-600">
                      <UserIcon class="h-3 w-3 mr-1" />
                      <span>{{ opportunity.owner.name }}</span>
                    </div>

                    <!-- Close Date -->
                    <div v-if="opportunity.close_date" class="flex items-center text-xs">
                      <CalendarIcon class="h-3 w-3 mr-1" />
                      <span :class="{ 
                        'text-red-600 font-medium': isOverdue(opportunity.close_date),
                        'text-yellow-600 font-medium': isDueSoon(opportunity.close_date),
                        'text-gray-600': !isOverdue(opportunity.close_date) && !isDueSoon(opportunity.close_date)
                      }">
                        {{ formatCloseDate(opportunity.close_date) }}
                      </span>
                    </div>

                    <!-- Source -->
                    <div v-if="opportunity.source" class="flex items-center text-xs text-gray-600">
                      <TagIcon class="h-3 w-3 mr-1" />
                      <span>{{ formatSource(opportunity.source) }}</span>
                    </div>
                  </div>

                  <!-- Progress Bar -->
                  <div v-if="opportunity.progress !== undefined" class="mb-3">
                    <div class="flex items-center justify-between text-xs text-gray-600 mb-1">
                      <span>Progress</span>
                      <span>{{ opportunity.progress }}%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-1.5">
                      <div 
                        class="bg-indigo-600 h-1.5 rounded-full transition-all duration-300" 
                        :style="`width: ${opportunity.progress}%`"
                      ></div>
                    </div>
                  </div>

                  <!-- Tags -->
                  <div v-if="opportunity.tags && opportunity.tags.length > 0" class="mb-3">
                    <div class="flex flex-wrap gap-1">
                      <span
                        v-for="tag in opportunity.tags.slice(0, 2)"
                        :key="tag"
                        class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-700"
                      >
                        {{ tag }}
                      </span>
                      <span
                        v-if="opportunity.tags.length > 2"
                        class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600"
                      >
                        +{{ opportunity.tags.length - 2 }}
                      </span>
                    </div>
                  </div>

                  <!-- Actions -->
                  <div class="flex items-center justify-between pt-3 border-t border-gray-100">
                    <div class="flex items-center space-x-2 text-xs text-gray-500">
                      <!-- Activities Count -->
                      <div v-if="opportunity.activities_count" class="flex items-center">
                        <ChatBubbleLeftIcon class="h-3 w-3 mr-1" />
                        <span>{{ opportunity.activities_count }}</span>
                      </div>
                      
                      <!-- Last Activity -->
                      <div v-if="opportunity.last_activity_at">
                        <span>{{ formatRelativeTime(opportunity.last_activity_at) }}</span>
                      </div>
                    </div>
                    
                    <div class="flex items-center space-x-1">
                      <button
                        @click.stop="viewOpportunity(opportunity)"
                        class="p-1 text-gray-400 hover:text-gray-600 rounded"
                        title="View details"
                      >
                        <EyeIcon class="h-3 w-3" />
                      </button>
                      <button
                        @click.stop="editOpportunity(opportunity)"
                        class="p-1 text-gray-400 hover:text-gray-600 rounded"
                        title="Edit"
                      >
                        <PencilIcon class="h-3 w-3" />
                      </button>
                      <button
                        @click.stop="showOpportunityActions(opportunity)"
                        class="p-1 text-gray-400 hover:text-gray-600 rounded"
                        title="More actions"
                      >
                        <EllipsisVerticalIcon class="h-3 w-3" />
                      </button>
                    </div>
                  </div>
                </div>
              </div>
              
              <!-- Empty Stage Message -->
              <div v-if="getStageOpportunities(stage.id).length === 0" class="text-center py-8">
                <div class="text-gray-400">
                  <CurrencyDollarIcon class="mx-auto h-8 w-8 mb-2" />
                  <p class="text-sm">No opportunities</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Create Opportunity Modal -->
    <CreateOpportunityModal
      v-if="showCreateOpportunityModal"
      :pipeline-id="selectedPipelineId"
      :stage-id="selectedStageId"
      @close="showCreateOpportunityModal = false"
      @created="handleOpportunityCreated"
    />

    <!-- View Opportunity Modal -->
    <ViewOpportunityModal
      v-if="viewingOpportunity"
      :opportunity="viewingOpportunity"
      @close="viewingOpportunity = null"
      @updated="handleOpportunityUpdated"
    />

    <!-- Pipeline Settings Modal -->
    <PipelineSettingsModal
      v-if="showPipelineSettings"
      :pipeline="pipeline"
      :stages="stages"
      @close="showPipelineSettings = false"
      @updated="loadPipeline"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import {
  PlusIcon,
  UserIcon,
  CalendarIcon,
  TagIcon,
  ChatBubbleLeftIcon,
  EyeIcon,
  PencilIcon,
  EllipsisVerticalIcon,
  CurrencyDollarIcon
} from '@heroicons/vue/24/outline'
import { useApi } from '@/Composables/useApi'
import { useToast } from '@/Composables/useToast'
import CreateOpportunityModal from './CreateOpportunityModal.vue'
import ViewOpportunityModal from './ViewOpportunityModal.vue'
import PipelineSettingsModal from './PipelineSettingsModal.vue'

const props = defineProps({
  pipelineId: {
    type: [String, Number],
    default: null
  },
  readonly: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['opportunity-moved', 'opportunity-created', 'opportunity-updated'])

const { get, post, loading } = useApi()
const { showToast } = useToast()

// Reactive data
const pipelines = ref([])
const pipeline = ref(null)
const stages = ref([])
const opportunities = ref([])
const stats = ref({
  total_opportunities: 0,
  total_value: 0,
  weighted_value: 0,
  avg_deal_size: 0
})

const selectedPipelineId = ref(props.pipelineId)
const selectedStageId = ref(null)
const draggedOpportunity = ref(null)
const dragOverStage = ref(null)
const viewingOpportunity = ref(null)

const showCreateOpportunityModal = ref(false)
const showPipelineSettings = ref(false)

// Computed properties
const getStageOpportunities = (stageId) => {
  return opportunities.value.filter(opp => opp.stage_id === stageId)
}

const getStageOpportunityCount = (stageId) => {
  return getStageOpportunities(stageId).length
}

const getStageValue = (stageId) => {
  return getStageOpportunities(stageId).reduce((sum, opp) => sum + (opp.amount || 0), 0)
}

// Methods
const fetchPipelines = async () => {
  try {
    const data = await get('/api/v1/crm/pipelines')
    pipelines.value = data.data
    
    if (!selectedPipelineId.value && pipelines.value.length > 0) {
      selectedPipelineId.value = pipelines.value[0].id
    }
  } catch (err) {
    console.error('Failed to fetch pipelines:', err)
    showToast('Failed to load pipelines', 'error')
  }
}

const loadPipeline = async () => {
  if (!selectedPipelineId.value) return
  
  try {
    const data = await get(`/api/v1/crm/pipelines/${selectedPipelineId.value}`)
    pipeline.value = data.data
    stages.value = data.data.stages || []
    opportunities.value = data.data.opportunities || []
    stats.value = data.data.stats || stats.value
  } catch (err) {
    console.error('Failed to load pipeline:', err)
    showToast('Failed to load pipeline', 'error')
  }
}

// Drag and Drop
const onDragStart = (event, opportunity) => {
  if (props.readonly) return
  draggedOpportunity.value = opportunity
  event.dataTransfer.effectAllowed = 'move'
}

const onDragEnd = () => {
  draggedOpportunity.value = null
  dragOverStage.value = null
}

const onDrop = async (event, stageId) => {
  if (props.readonly) return
  
  event.preventDefault()
  dragOverStage.value = null
  
  if (draggedOpportunity.value && draggedOpportunity.value.stage_id !== stageId) {
    try {
      await post(`/api/v1/crm/opportunities/${draggedOpportunity.value.id}/move`, {
        stage_id: stageId
      })
      
      showToast('Opportunity moved successfully', 'success')
      emit('opportunity-moved', {
        opportunity: draggedOpportunity.value,
        fromStage: draggedOpportunity.value.stage_id,
        toStage: stageId
      })
      
      await loadPipeline()
    } catch (err) {
      console.error('Failed to move opportunity:', err)
      showToast('Failed to move opportunity', 'error')
    }
  }
  
  draggedOpportunity.value = null
}

// Actions
const addOpportunity = (stageId) => {
  selectedStageId.value = stageId
  showCreateOpportunityModal.value = true
}

const viewOpportunity = (opportunity) => {
  viewingOpportunity.value = opportunity
}

const editOpportunity = (opportunity) => {
  // TODO: Implement edit opportunity modal
  showToast('Edit opportunity functionality coming soon', 'info')
}

const showOpportunityActions = (opportunity) => {
  // TODO: Implement opportunity actions menu
  showToast('Opportunity actions menu coming soon', 'info')
}

const handleOpportunityCreated = () => {
  showCreateOpportunityModal.value = false
  selectedStageId.value = null
  loadPipeline()
  showToast('Opportunity created successfully', 'success')
  emit('opportunity-created')
}

const handleOpportunityUpdated = () => {
  viewingOpportunity.value = null
  loadPipeline()
  showToast('Opportunity updated successfully', 'success')
  emit('opportunity-updated')
}

// Utility functions
const getPriorityColor = (priority) => {
  const colors = {
    low: 'bg-green-400',
    medium: 'bg-yellow-400',
    high: 'bg-orange-400',
    urgent: 'bg-red-400'
  }
  return colors[priority] || 'bg-gray-400'
}

const formatCurrency = (amount) => {
  if (!amount) return '0'
  return new Intl.NumberFormat('en-US').format(amount)
}

const formatCloseDate = (date) => {
  if (!date) return 'No close date'
  const closeDate = new Date(date)
  const now = new Date()
  const diffTime = closeDate - now
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
  
  if (diffDays < 0) {
    return `${Math.abs(diffDays)} days overdue`
  } else if (diffDays === 0) {
    return 'Closes today'
  } else if (diffDays === 1) {
    return 'Closes tomorrow'
  } else if (diffDays <= 7) {
    return `Closes in ${diffDays} days`
  } else {
    return closeDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })
  }
}

const formatSource = (source) => {
  return source.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())
}

const formatRelativeTime = (date) => {
  if (!date) return ''
  const now = new Date()
  const past = new Date(date)
  const diffTime = now - past
  const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24))
  
  if (diffDays === 0) return 'Today'
  if (diffDays === 1) return '1d ago'
  if (diffDays < 7) return `${diffDays}d ago`
  if (diffDays < 30) return `${Math.floor(diffDays / 7)}w ago`
  return `${Math.floor(diffDays / 30)}m ago`
}

const isOverdue = (date) => {
  if (!date) return false
  return new Date(date) < new Date()
}

const isDueSoon = (date) => {
  if (!date) return false
  const closeDate = new Date(date)
  const now = new Date()
  const diffTime = closeDate - now
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
  return diffDays >= 0 && diffDays <= 7
}

// Lifecycle
onMounted(async () => {
  await fetchPipelines()
  if (selectedPipelineId.value) {
    await loadPipeline()
  }
})
</script>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>