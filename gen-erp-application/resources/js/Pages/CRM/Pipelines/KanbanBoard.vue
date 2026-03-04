<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="bg-white shadow rounded-lg p-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Sales Pipeline</h1>
          <p class="mt-1 text-sm text-gray-600">
            Drag and drop opportunities between stages to update their status
          </p>
        </div>
        <div class="flex items-center space-x-3">
          <select
            v-model="selectedPipeline"
            @change="fetchPipelineData"
            class="rounded-md border-gray-300 text-sm"
          >
            <option v-for="pipeline in pipelines" :key="pipeline.id" :value="pipeline.id">
              {{ pipeline.name }}
            </option>
          </select>
          <button
            @click="showCreateOpportunityModal = true"
            class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md text-sm font-medium"
          >
            Add Opportunity
          </button>
        </div>
      </div>
    </div>

    <!-- Pipeline Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
      <div class="bg-white shadow rounded-lg p-6">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <ChartBarIcon class="h-8 w-8 text-blue-600" />
          </div>
          <div class="ml-4">
            <div class="text-2xl font-bold text-gray-900">{{ pipelineStats.total_opportunities }}</div>
            <div class="text-sm text-gray-600">Total Opportunities</div>
          </div>
        </div>
      </div>
      
      <div class="bg-white shadow rounded-lg p-6">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <CurrencyDollarIcon class="h-8 w-8 text-green-600" />
          </div>
          <div class="ml-4">
            <div class="text-2xl font-bold text-gray-900">৳{{ formatNumber(pipelineStats.total_value) }}</div>
            <div class="text-sm text-gray-600">Pipeline Value</div>
          </div>
        </div>
      </div>
      
      <div class="bg-white shadow rounded-lg p-6">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <TrophyIcon class="h-8 w-8 text-yellow-600" />
          </div>
          <div class="ml-4">
            <div class="text-2xl font-bold text-gray-900">৳{{ formatNumber(pipelineStats.weighted_value) }}</div>
            <div class="text-sm text-gray-600">Weighted Value</div>
          </div>
        </div>
      </div>
      
      <div class="bg-white shadow rounded-lg p-6">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <ClockIcon class="h-8 w-8 text-purple-600" />
          </div>
          <div class="ml-4">
            <div class="text-2xl font-bold text-gray-900">{{ pipelineStats.average_deal_size }}</div>
            <div class="text-sm text-gray-600">Avg Deal Size</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Kanban Board -->
    <div class="bg-white shadow rounded-lg p-6">
      <div class="flex space-x-6 overflow-x-auto pb-4">
        <div
          v-for="stage in stages"
          :key="stage.id"
          class="flex-shrink-0 w-80"
        >
          <!-- Stage Header -->
          <div class="flex items-center justify-between mb-4 p-3 bg-gray-50 rounded-lg">
            <div class="flex items-center space-x-2">
              <div class="w-3 h-3 rounded-full" :style="{ backgroundColor: stage.color }"></div>
              <h3 class="font-medium text-gray-900">{{ stage.name }}</h3>
              <span class="bg-gray-200 text-gray-700 text-xs font-medium px-2 py-1 rounded-full">
                {{ stage.opportunities?.length || 0 }}
              </span>
            </div>
            <div class="text-right">
              <div class="text-sm font-semibold text-gray-900">
                ৳{{ formatNumber(getStageValue(stage)) }}
              </div>
              <div class="text-xs text-gray-500">{{ stage.probability }}% prob</div>
            </div>
          </div>

          <!-- Opportunities -->
          <div
            class="space-y-3 min-h-[400px]"
            @drop="onDrop($event, stage)"
            @dragover.prevent
            @dragenter.prevent
          >
            <div
              v-for="opportunity in stage.opportunities"
              :key="opportunity.id"
              :draggable="true"
              @dragstart="onDragStart($event, opportunity)"
              class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm hover:shadow-md cursor-move transition-shadow"
            >
              <!-- Opportunity Header -->
              <div class="flex items-start justify-between mb-2">
                <h4 class="font-medium text-gray-900 text-sm leading-tight">
                  {{ opportunity.name }}
                </h4>
                <div class="flex items-center space-x-1 ml-2">
                  <span
                    v-if="opportunity.priority === 'high'"
                    class="w-2 h-2 bg-red-500 rounded-full"
                    title="High Priority"
                  ></span>
                  <button
                    @click="viewOpportunity(opportunity)"
                    class="text-gray-400 hover:text-gray-600"
                  >
                    <EyeIcon class="w-4 h-4" />
                  </button>
                </div>
              </div>

              <!-- Opportunity Details -->
              <div class="space-y-2">
                <div class="flex items-center justify-between">
                  <span class="text-lg font-semibold text-gray-900">
                    ৳{{ formatNumber(opportunity.amount) }}
                  </span>
                  <span class="text-xs text-gray-500">
                    {{ opportunity.probability }}%
                  </span>
                </div>

                <div class="flex items-center space-x-2 text-xs text-gray-500">
                  <UserIcon class="w-3 h-3" />
                  <span>{{ opportunity.lead?.name || opportunity.customer?.name || 'No contact' }}</span>
                </div>

                <div class="flex items-center space-x-2 text-xs text-gray-500">
                  <CalendarIcon class="w-3 h-3" />
                  <span>
                    {{ opportunity.expected_close_date ? formatDate(opportunity.expected_close_date) : 'No date set' }}
                  </span>
                </div>

                <div v-if="opportunity.assigned_to" class="flex items-center space-x-2 text-xs text-gray-500">
                  <UserCircleIcon class="w-3 h-3" />
                  <span>{{ opportunity.assigned_user?.name }}</span>
                </div>
              </div>

              <!-- Tags -->
              <div v-if="opportunity.tags?.length" class="flex flex-wrap gap-1 mt-3">
                <span
                  v-for="tag in opportunity.tags"
                  :key="tag.id"
                  class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full"
                >
                  {{ tag.name }}
                </span>
              </div>

              <!-- Progress Bar -->
              <div class="mt-3">
                <div class="flex items-center justify-between text-xs text-gray-500 mb-1">
                  <span>Progress</span>
                  <span>{{ opportunity.probability }}%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-1.5">
                  <div
                    class="bg-indigo-600 h-1.5 rounded-full transition-all duration-300"
                    :style="{ width: `${opportunity.probability}%` }"
                  ></div>
                </div>
              </div>
            </div>

            <!-- Add Opportunity Button -->
            <button
              @click="showCreateOpportunityModal = true; preselectedStage = stage"
              class="w-full border-2 border-dashed border-gray-300 rounded-lg p-4 text-gray-500 hover:border-gray-400 hover:text-gray-600 transition-colors"
            >
              <PlusIcon class="w-5 h-5 mx-auto mb-1" />
              <span class="text-sm">Add Opportunity</span>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Create Opportunity Modal -->
    <CreateOpportunityModal
      v-if="showCreateOpportunityModal"
      :preselected-stage="preselectedStage"
      :pipeline-id="selectedPipeline"
      @close="showCreateOpportunityModal = false; preselectedStage = null"
      @created="handleOpportunityCreated"
    />

    <!-- View Opportunity Modal -->
    <ViewOpportunityModal
      v-if="selectedOpportunity"
      :opportunity="selectedOpportunity"
      @close="selectedOpportunity = null"
      @updated="handleOpportunityUpdated"
    />
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import {
  ChartBarIcon,
  CurrencyDollarIcon,
  TrophyIcon,
  ClockIcon,
  EyeIcon,
  UserIcon,
  UserCircleIcon,
  CalendarIcon,
  PlusIcon
} from '@heroicons/vue/24/outline'
import { useToast } from '@/Composables/useToast'
import CreateOpportunityModal from './CreateOpportunityModal.vue'
import ViewOpportunityModal from './ViewOpportunityModal.vue'

const { showToast } = useToast()

// Reactive data
const pipelines = ref([])
const selectedPipeline = ref(null)
const stages = ref([])
const opportunities = ref([])
const pipelineStats = ref({
  total_opportunities: 0,
  total_value: 0,
  weighted_value: 0,
  average_deal_size: 0
})

const showCreateOpportunityModal = ref(false)
const preselectedStage = ref(null)
const selectedOpportunity = ref(null)
const draggedOpportunity = ref(null)

// Methods
const fetchPipelines = async () => {
  try {
    const response = await fetch('/api/v1/crm/pipelines', {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Accept': 'application/json',
      }
    })
    
    if (response.ok) {
      const data = await response.json()
      pipelines.value = data.data
      if (pipelines.value.length > 0 && !selectedPipeline.value) {
        selectedPipeline.value = pipelines.value.find(p => p.is_default)?.id || pipelines.value[0].id
      }
    }
  } catch (error) {
    console.error('Failed to fetch pipelines:', error)
  }
}

const fetchPipelineData = async () => {
  if (!selectedPipeline.value) return
  
  try {
    const [stagesResponse, opportunitiesResponse, statsResponse] = await Promise.all([
      fetch(`/api/v1/crm/pipelines/${selectedPipeline.value}/stages`, {
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('token')}`,
          'Accept': 'application/json',
        }
      }),
      fetch(`/api/v1/crm/opportunities?pipeline_id=${selectedPipeline.value}`, {
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('token')}`,
          'Accept': 'application/json',
        }
      }),
      fetch(`/api/v1/crm/pipelines/${selectedPipeline.value}/metrics`, {
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('token')}`,
          'Accept': 'application/json',
        }
      })
    ])
    
    if (stagesResponse.ok) {
      const stagesData = await stagesResponse.json()
      stages.value = stagesData.data
    }
    
    if (opportunitiesResponse.ok) {
      const opportunitiesData = await opportunitiesResponse.json()
      opportunities.value = opportunitiesData.data
      organizeOpportunitiesByStage()
    }
    
    if (statsResponse.ok) {
      const statsData = await statsResponse.json()
      pipelineStats.value = statsData.data
    }
  } catch (error) {
    console.error('Failed to fetch pipeline data:', error)
  }
}

const organizeOpportunitiesByStage = () => {
  stages.value.forEach(stage => {
    stage.opportunities = opportunities.value.filter(opp => opp.stage_id === stage.id)
  })
}

const onDragStart = (event, opportunity) => {
  draggedOpportunity.value = opportunity
  event.dataTransfer.effectAllowed = 'move'
}

const onDrop = async (event, targetStage) => {
  event.preventDefault()
  
  if (!draggedOpportunity.value || draggedOpportunity.value.stage_id === targetStage.id) {
    return
  }
  
  try {
    const response = await fetch(`/api/v1/crm/opportunities/${draggedOpportunity.value.uuid}/move-stage`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: JSON.stringify({
        stage_id: targetStage.id,
        reason: `Moved to ${targetStage.name}`
      })
    })
    
    if (response.ok) {
      // Update local state
      const opportunityIndex = opportunities.value.findIndex(opp => opp.id === draggedOpportunity.value.id)
      if (opportunityIndex !== -1) {
        opportunities.value[opportunityIndex].stage_id = targetStage.id
        opportunities.value[opportunityIndex].probability = targetStage.probability
        organizeOpportunitiesByStage()
      }
      
      showToast(`Opportunity moved to ${targetStage.name}`, 'success')
      fetchPipelineData() // Refresh stats
    } else {
      const error = await response.json()
      showToast(error.message || 'Failed to move opportunity', 'error')
    }
  } catch (error) {
    console.error('Failed to move opportunity:', error)
    showToast('Failed to move opportunity', 'error')
  } finally {
    draggedOpportunity.value = null
  }
}

const viewOpportunity = (opportunity) => {
  selectedOpportunity.value = opportunity
}

const handleOpportunityCreated = () => {
  showCreateOpportunityModal.value = false
  preselectedStage.value = null
  fetchPipelineData()
  showToast('Opportunity created successfully', 'success')
}

const handleOpportunityUpdated = () => {
  selectedOpportunity.value = null
  fetchPipelineData()
  showToast('Opportunity updated successfully', 'success')
}

const getStageValue = (stage) => {
  return stage.opportunities?.reduce((total, opp) => total + (opp.amount || 0), 0) || 0
}

const formatNumber = (num) => {
  if (num >= 1000000) {
    return (num / 1000000).toFixed(1) + 'M'
  } else if (num >= 1000) {
    return (num / 1000).toFixed(1) + 'K'
  }
  return num?.toLocaleString() || '0'
}

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric'
  })
}

// Lifecycle
onMounted(async () => {
  await fetchPipelines()
  if (selectedPipeline.value) {
    await fetchPipelineData()
  }
})
</script>

<style scoped>
.cursor-move:active {
  cursor: grabbing;
}
</style>