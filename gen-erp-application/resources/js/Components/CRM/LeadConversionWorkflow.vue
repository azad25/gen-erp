<template>
  <div class="bg-white rounded-lg border border-gray-200 shadow-sm">
    <!-- Header -->
    <div class="px-4 py-3 border-b border-gray-200">
      <div class="flex items-center justify-between">
        <h3 class="text-sm font-medium text-gray-900">Lead Conversion</h3>
        <button
          @click="collapsed = !collapsed"
          class="text-gray-400 hover:text-gray-600"
        >
          <ChevronUpIcon v-if="!collapsed" class="h-4 w-4" />
          <ChevronDownIcon v-else class="h-4 w-4" />
        </button>
      </div>
    </div>

    <!-- Content -->
    <div v-if="!collapsed" class="p-4">
      <!-- Conversion Progress -->
      <div class="mb-6">
        <div class="flex items-center justify-between mb-2">
          <span class="text-sm font-medium text-gray-700">Conversion Progress</span>
          <span class="text-sm text-gray-500">{{ currentStep }}/{{ totalSteps }}</span>
        </div>
        <div class="w-full bg-gray-200 rounded-full h-2">
          <div
            class="bg-indigo-600 h-2 rounded-full transition-all duration-300"
            :style="{ width: `${(currentStep / totalSteps) * 100}%` }"
          ></div>
        </div>
      </div>

      <!-- Step Navigation -->
      <div class="mb-6">
        <nav class="flex space-x-4" aria-label="Progress">
          <div
            v-for="(step, index) in conversionSteps"
            :key="step.id"
            class="flex items-center"
          >
            <div
              class="flex items-center justify-center w-8 h-8 rounded-full text-sm font-medium"
              :class="getStepClass(index + 1)"
            >
              <CheckIcon v-if="index + 1 < currentStep" class="h-4 w-4" />
              <span v-else>{{ index + 1 }}</span>
            </div>
            <span
              class="ml-2 text-sm font-medium"
              :class="index + 1 === currentStep ? 'text-indigo-600' : 'text-gray-500'"
            >
              {{ step.title }}
            </span>
            <ArrowRightIcon
              v-if="index < conversionSteps.length - 1"
              class="ml-4 h-4 w-4 text-gray-400"
            />
          </div>
        </nav>
      </div>

      <!-- Step Content -->
      <div class="space-y-6">
        <!-- Step 1: Lead Qualification -->
        <div v-if="currentStep === 1" class="space-y-4">
          <h4 class="text-lg font-medium text-gray-900">Lead Qualification</h4>
          
          <!-- Qualification Criteria -->
          <div class="bg-gray-50 rounded-lg p-4">
            <h5 class="text-sm font-medium text-gray-900 mb-3">Qualification Checklist</h5>
            <div class="space-y-2">
              <label
                v-for="criteria in qualificationCriteria"
                :key="criteria.id"
                class="flex items-center"
              >
                <input
                  v-model="qualificationData[criteria.id]"
                  type="checkbox"
                  class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                />
                <span class="ml-2 text-sm text-gray-700">{{ criteria.label }}</span>
              </label>
            </div>
          </div>

          <!-- Lead Score Display -->
          <div class="bg-blue-50 rounded-lg p-4">
            <div class="flex items-center justify-between">
              <span class="text-sm font-medium text-blue-900">Current Lead Score</span>
              <span class="text-2xl font-bold text-blue-600">{{ lead.score || 0 }}/100</span>
            </div>
            <div class="mt-2 w-full bg-blue-200 rounded-full h-2">
              <div
                class="bg-blue-600 h-2 rounded-full"
                :style="{ width: `${lead.score || 0}%` }"
              ></div>
            </div>
          </div>

          <!-- Qualification Notes -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Qualification Notes
            </label>
            <textarea
              v-model="qualificationNotes"
              rows="3"
              placeholder="Add notes about lead qualification..."
              class="w-full text-sm border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
            ></textarea>
          </div>
        </div>

        <!-- Step 2: Opportunity Creation -->
        <div v-if="currentStep === 2" class="space-y-4">
          <h4 class="text-lg font-medium text-gray-900">Create Opportunity</h4>
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Opportunity Title -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Opportunity Title
              </label>
              <input
                v-model="opportunityData.title"
                type="text"
                :placeholder="`${lead.first_name} ${lead.last_name} - ${lead.company}`"
                class="w-full text-sm border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
              />
            </div>

            <!-- Expected Value -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Expected Value
              </label>
              <div class="relative">
                <input
                  v-model.number="opportunityData.value"
                  type="number"
                  min="0"
                  step="0.01"
                  placeholder="0.00"
                  class="w-full text-sm border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500 pl-8"
                />
                <CurrencyDollarIcon class="absolute left-2 top-2.5 h-4 w-4 text-gray-400" />
              </div>
            </div>

            <!-- Pipeline Stage -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Pipeline Stage
              </label>
              <select
                v-model="opportunityData.stage_id"
                class="w-full text-sm border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
              >
                <option value="">Select stage</option>
                <option
                  v-for="stage in pipelineStages"
                  :key="stage.id"
                  :value="stage.id"
                >
                  {{ stage.name }} ({{ stage.probability }}%)
                </option>
              </select>
            </div>

            <!-- Expected Close Date -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Expected Close Date
              </label>
              <input
                v-model="opportunityData.expected_close_date"
                type="date"
                class="w-full text-sm border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
              />
            </div>
          </div>

          <!-- Opportunity Description -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Description
            </label>
            <textarea
              v-model="opportunityData.description"
              rows="3"
              placeholder="Describe the opportunity..."
              class="w-full text-sm border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
            ></textarea>
          </div>
        </div>

        <!-- Step 3: Customer Creation -->
        <div v-if="currentStep === 3" class="space-y-4">
          <h4 class="text-lg font-medium text-gray-900">Create Customer Record</h4>
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Customer Type -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Customer Type
              </label>
              <select
                v-model="customerData.type"
                class="w-full text-sm border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
              >
                <option value="individual">Individual</option>
                <option value="business">Business</option>
              </select>
            </div>

            <!-- Customer Category -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Category
              </label>
              <select
                v-model="customerData.category"
                class="w-full text-sm border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
              >
                <option value="premium">Premium</option>
                <option value="standard">Standard</option>
                <option value="basic">Basic</option>
              </select>
            </div>

            <!-- Credit Limit -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Credit Limit
              </label>
              <div class="relative">
                <input
                  v-model.number="customerData.credit_limit"
                  type="number"
                  min="0"
                  step="0.01"
                  placeholder="0.00"
                  class="w-full text-sm border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500 pl-8"
                />
                <CurrencyDollarIcon class="absolute left-2 top-2.5 h-4 w-4 text-gray-400" />
              </div>
            </div>

            <!-- Payment Terms -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Payment Terms
              </label>
              <select
                v-model="customerData.payment_terms"
                class="w-full text-sm border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
              >
                <option value="net_30">Net 30</option>
                <option value="net_15">Net 15</option>
                <option value="due_on_receipt">Due on Receipt</option>
                <option value="cash_on_delivery">Cash on Delivery</option>
              </select>
            </div>
          </div>

          <!-- Billing Address -->
          <div>
            <h5 class="text-sm font-medium text-gray-900 mb-2">Billing Address</h5>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <input
                  v-model="customerData.billing_address.street"
                  type="text"
                  placeholder="Street Address"
                  class="w-full text-sm border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                />
              </div>
              <div>
                <input
                  v-model="customerData.billing_address.city"
                  type="text"
                  placeholder="City"
                  class="w-full text-sm border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                />
              </div>
              <div>
                <input
                  v-model="customerData.billing_address.state"
                  type="text"
                  placeholder="State/Province"
                  class="w-full text-sm border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                />
              </div>
              <div>
                <input
                  v-model="customerData.billing_address.postal_code"
                  type="text"
                  placeholder="Postal Code"
                  class="w-full text-sm border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                />
              </div>
            </div>
          </div>
        </div>

        <!-- Step 4: Review & Convert -->
        <div v-if="currentStep === 4" class="space-y-4">
          <h4 class="text-lg font-medium text-gray-900">Review & Convert</h4>
          
          <!-- Conversion Summary -->
          <div class="bg-gray-50 rounded-lg p-4">
            <h5 class="text-sm font-medium text-gray-900 mb-3">Conversion Summary</h5>
            <div class="space-y-2 text-sm">
              <div class="flex justify-between">
                <span class="text-gray-600">Lead:</span>
                <span class="font-medium">{{ lead.first_name }} {{ lead.last_name }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-600">Company:</span>
                <span class="font-medium">{{ lead.company }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-600">Opportunity Value:</span>
                <span class="font-medium">${{ opportunityData.value?.toLocaleString() || '0' }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-600">Expected Close:</span>
                <span class="font-medium">{{ formatDate(opportunityData.expected_close_date) }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-600">Customer Type:</span>
                <span class="font-medium">{{ customerData.type }}</span>
              </div>
            </div>
          </div>

          <!-- Final Notes -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Conversion Notes
            </label>
            <textarea
              v-model="conversionNotes"
              rows="3"
              placeholder="Add final notes about the conversion..."
              class="w-full text-sm border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
            ></textarea>
          </div>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="flex justify-between mt-6 pt-4 border-t border-gray-200">
        <button
          v-if="currentStep > 1"
          @click="previousStep"
          class="bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium py-2 px-4 rounded-md"
        >
          Previous
        </button>
        <div class="flex space-x-3 ml-auto">
          <button
            @click="$emit('close')"
            class="bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium py-2 px-4 rounded-md"
          >
            Cancel
          </button>
          <button
            v-if="currentStep < totalSteps"
            @click="nextStep"
            :disabled="!canProceed"
            class="bg-indigo-600 hover:bg-indigo-700 disabled:bg-gray-300 text-white text-sm font-medium py-2 px-4 rounded-md"
          >
            Next
          </button>
          <button
            v-else
            @click="convertLead"
            :disabled="loading || !canProceed"
            class="bg-green-600 hover:bg-green-700 disabled:bg-gray-300 text-white text-sm font-medium py-2 px-4 rounded-md"
          >
            {{ loading ? 'Converting...' : 'Convert Lead' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import {
  ChevronUpIcon,
  ChevronDownIcon,
  CheckIcon,
  ArrowRightIcon,
  CurrencyDollarIcon
} from '@heroicons/vue/24/outline'
import { useApi } from '@/Composables/useApi'
import { useToast } from '@/Composables/useToast'

const props = defineProps({
  lead: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['close', 'converted'])

const { get, post, loading } = useApi()
const { showSuccess, showError } = useToast()

// Reactive data
const collapsed = ref(false)
const currentStep = ref(1)
const totalSteps = ref(4)
const pipelineStages = ref([])

const conversionSteps = [
  { id: 1, title: 'Qualify' },
  { id: 2, title: 'Opportunity' },
  { id: 3, title: 'Customer' },
  { id: 4, title: 'Review' }
]

const qualificationCriteria = [
  { id: 'budget_confirmed', label: 'Budget confirmed' },
  { id: 'authority_identified', label: 'Decision maker identified' },
  { id: 'need_established', label: 'Need established' },
  { id: 'timeline_defined', label: 'Timeline defined' },
  { id: 'competition_assessed', label: 'Competition assessed' }
]

const qualificationData = reactive({
  budget_confirmed: false,
  authority_identified: false,
  need_established: false,
  timeline_defined: false,
  competition_assessed: false
})

const qualificationNotes = ref('')

const opportunityData = reactive({
  title: '',
  value: null,
  stage_id: '',
  expected_close_date: '',
  description: ''
})

const customerData = reactive({
  type: 'business',
  category: 'standard',
  credit_limit: 0,
  payment_terms: 'net_30',
  billing_address: {
    street: '',
    city: '',
    state: '',
    postal_code: ''
  }
})

const conversionNotes = ref('')

// Computed properties
const canProceed = computed(() => {
  switch (currentStep.value) {
    case 1:
      return Object.values(qualificationData).some(value => value === true)
    case 2:
      return opportunityData.title && opportunityData.value && opportunityData.stage_id
    case 3:
      return customerData.type && customerData.category
    case 4:
      return true
    default:
      return false
  }
})

// Methods
const fetchPipelineStages = async () => {
  try {
    const data = await get('/api/v1/crm/pipeline-stages')
    pipelineStages.value = data.data
  } catch (err) {
    console.error('Failed to fetch pipeline stages:', err)
  }
}

const getStepClass = (step) => {
  if (step < currentStep.value) {
    return 'bg-indigo-600 text-white'
  } else if (step === currentStep.value) {
    return 'bg-indigo-100 text-indigo-600 border-2 border-indigo-600'
  } else {
    return 'bg-gray-200 text-gray-500'
  }
}

const nextStep = () => {
  if (currentStep.value < totalSteps.value && canProceed.value) {
    currentStep.value++
  }
}

const previousStep = () => {
  if (currentStep.value > 1) {
    currentStep.value--
  }
}

const convertLead = async () => {
  try {
    const conversionData = {
      lead_id: props.lead.id,
      qualification: {
        criteria: qualificationData,
        notes: qualificationNotes.value
      },
      opportunity: opportunityData,
      customer: customerData,
      notes: conversionNotes.value
    }
    
    const data = await post('/api/v1/crm/leads/convert', conversionData)
    
    showSuccess('Lead converted successfully!')
    emit('converted', data.data)
  } catch (err) {
    console.error('Failed to convert lead:', err)
    showError('Failed to convert lead')
  }
}

const formatDate = (dateString) => {
  if (!dateString) return 'Not set'
  return new Date(dateString).toLocaleDateString()
}

// Lifecycle
onMounted(() => {
  fetchPipelineStages()
  
  // Pre-fill opportunity data
  opportunityData.title = `${props.lead.first_name} ${props.lead.last_name} - ${props.lead.company}`
})
</script>