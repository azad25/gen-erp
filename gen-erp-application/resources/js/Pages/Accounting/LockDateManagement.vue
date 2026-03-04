<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="bg-white shadow rounded-lg p-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Lock Date Management</h1>
          <p class="mt-1 text-sm text-gray-600">
            Control period close and prevent modifications to historical data
          </p>
        </div>
        <div class="flex items-center space-x-3">
          <div class="text-right">
            <div class="text-sm text-gray-500">Current Lock Date</div>
            <div class="text-lg font-semibold" :class="lockDateStatus.class">
              {{ lockDateStatus.text }}
            </div>
          </div>
          <div class="w-3 h-3 rounded-full" :class="lockDateStatus.indicator"></div>
        </div>
      </div>
    </div>

    <!-- Current Status Card -->
    <div class="bg-white shadow rounded-lg p-6">
      <h2 class="text-lg font-medium text-gray-900 mb-4">Current Status</h2>
      
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="text-center p-4 bg-gray-50 rounded-lg">
          <div class="text-2xl font-bold text-gray-900">
            {{ lockDateInfo?.days_since_lock || 'N/A' }}
          </div>
          <div class="text-sm text-gray-600">Days Since Lock</div>
        </div>
        
        <div class="text-center p-4 bg-blue-50 rounded-lg">
          <div class="text-2xl font-bold text-blue-600">
            {{ lockDateInfo?.is_locked ? 'Locked' : 'Open' }}
          </div>
          <div class="text-sm text-gray-600">Period Status</div>
        </div>
        
        <div class="text-center p-4 bg-green-50 rounded-lg">
          <div class="text-2xl font-bold text-green-600">
            {{ lockDateInfo?.company_name }}
          </div>
          <div class="text-sm text-gray-600">Company</div>
        </div>
      </div>
    </div>

    <!-- Update Lock Date -->
    <div class="bg-white shadow rounded-lg p-6">
      <h2 class="text-lg font-medium text-gray-900 mb-4">Update Lock Date</h2>
      
      <form @submit.prevent="updateLockDate" class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label for="lock_date" class="block text-sm font-medium text-gray-700">
              New Lock Date
            </label>
            <input
              id="lock_date"
              v-model="form.lock_date"
              type="date"
              :max="today"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
              required
            />
            <p class="mt-1 text-xs text-gray-500">
              Cannot be moved backwards or set to future dates
            </p>
          </div>
          
          <div class="flex items-end">
            <button
              type="button"
              @click="validateLockDate"
              :disabled="!form.lock_date || validating"
              class="mr-2 px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
            >
              {{ validating ? 'Validating...' : 'Validate' }}
            </button>
            
            <button
              type="submit"
              :disabled="updating || !form.lock_date"
              class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
            >
              {{ updating ? 'Updating...' : 'Update Lock Date' }}
            </button>
          </div>
        </div>
        
        <!-- Validation Results -->
        <div v-if="validationResult" class="mt-4 p-4 rounded-md" :class="validationResult.is_valid ? 'bg-green-50 border border-green-200' : 'bg-yellow-50 border border-yellow-200'">
          <div class="flex">
            <div class="flex-shrink-0">
              <CheckCircleIcon v-if="validationResult.is_valid" class="h-5 w-5 text-green-400" />
              <ExclamationTriangleIcon v-else class="h-5 w-5 text-yellow-400" />
            </div>
            <div class="ml-3">
              <h3 class="text-sm font-medium" :class="validationResult.is_valid ? 'text-green-800' : 'text-yellow-800'">
                {{ validationResult.is_valid ? 'Validation Passed' : 'Validation Warnings' }}
              </h3>
              <div class="mt-2 text-sm" :class="validationResult.is_valid ? 'text-green-700' : 'text-yellow-700'">
                <p>Affected transactions: {{ validationResult.affected_transactions?.total || 0 }}</p>
                <ul v-if="validationResult.warnings?.length" class="mt-1 list-disc list-inside">
                  <li v-for="warning in validationResult.warnings" :key="warning">{{ warning }}</li>
                </ul>
              </div>
            </div>
          </div>
        </div>
      </form>
    </div>

    <!-- Month-End Close -->
    <div class="bg-white shadow rounded-lg p-6">
      <h2 class="text-lg font-medium text-gray-900 mb-4">Month-End Close</h2>
      <p class="text-sm text-gray-600 mb-4">
        Perform a comprehensive month-end close with integrity checks and automatic lock date setting.
      </p>
      
      <form @submit.prevent="performMonthEndClose" class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label for="close_date" class="block text-sm font-medium text-gray-700">
              Close Date
            </label>
            <input
              id="close_date"
              v-model="monthEndForm.close_date"
              type="date"
              :max="today"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
              required
            />
          </div>
          
          <div class="flex items-end">
            <button
              type="submit"
              :disabled="closing || !monthEndForm.close_date"
              class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 disabled:opacity-50"
            >
              {{ closing ? 'Processing...' : 'Perform Month-End Close' }}
            </button>
          </div>
        </div>
      </form>
    </div>

    <!-- Recent Activity -->
    <div v-if="closeResult" class="bg-white shadow rounded-lg p-6">
      <h2 class="text-lg font-medium text-gray-900 mb-4">Close Results</h2>
      
      <div class="space-y-3">
        <div class="flex items-center">
          <CheckCircleIcon class="h-5 w-5 text-green-500 mr-2" />
          <span class="text-sm text-gray-900">Integrity check passed</span>
        </div>
        
        <div class="text-sm text-gray-600">
          <p>Invoices checked: {{ closeResult.invoices_checked }}</p>
          <p>Journal entries checked: {{ closeResult.journal_entries_checked }}</p>
          <p>New lock date: {{ closeResult.lock_date_formatted }}</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { CheckCircleIcon, ExclamationTriangleIcon } from '@heroicons/vue/24/outline'
import { useToast } from '@/Composables/useToast'
import { useCompany } from '@/Composables/useCompany'

const { showToast } = useToast()
const { currentCompany } = useCompany()

// Reactive data
const lockDateInfo = ref(null)
const validationResult = ref(null)
const closeResult = ref(null)
const updating = ref(false)
const validating = ref(false)
const closing = ref(false)

const form = ref({
  lock_date: ''
})

const monthEndForm = ref({
  close_date: ''
})

// Computed
const today = computed(() => {
  return new Date().toISOString().split('T')[0]
})

const lockDateStatus = computed(() => {
  if (!lockDateInfo.value) {
    return {
      text: 'Loading...',
      class: 'text-gray-500',
      indicator: 'bg-gray-300'
    }
  }
  
  if (!lockDateInfo.value.is_locked) {
    return {
      text: 'No Lock Date',
      class: 'text-yellow-600',
      indicator: 'bg-yellow-400'
    }
  }
  
  return {
    text: lockDateInfo.value.lock_date_formatted,
    class: 'text-green-600',
    indicator: 'bg-green-400'
  }
})

// Methods
const fetchLockDateInfo = async () => {
  try {
    const response = await fetch(`/api/v1/companies/${currentCompany.value.id}/lock-date`, {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Accept': 'application/json',
      }
    })
    
    if (response.ok) {
      const data = await response.json()
      lockDateInfo.value = data.data
    }
  } catch (error) {
    console.error('Failed to fetch lock date info:', error)
    showToast('Failed to load lock date information', 'error')
  }
}

const validateLockDate = async () => {
  if (!form.value.lock_date) return
  
  validating.value = true
  try {
    const response = await fetch(`/api/v1/companies/${currentCompany.value.id}/lock-date/validate`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: JSON.stringify({
        proposed_lock_date: form.value.lock_date
      })
    })
    
    if (response.ok) {
      const data = await response.json()
      validationResult.value = data.data
    } else {
      const error = await response.json()
      showToast(error.message || 'Validation failed', 'error')
    }
  } catch (error) {
    console.error('Validation failed:', error)
    showToast('Failed to validate lock date', 'error')
  } finally {
    validating.value = false
  }
}

const updateLockDate = async () => {
  updating.value = true
  try {
    const response = await fetch(`/api/v1/companies/${currentCompany.value.id}/lock-date`, {
      method: 'PUT',
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: JSON.stringify({
        lock_date: form.value.lock_date
      })
    })
    
    if (response.ok) {
      const data = await response.json()
      lockDateInfo.value = data.data
      validationResult.value = null
      form.value.lock_date = ''
      showToast('Lock date updated successfully', 'success')
    } else {
      const error = await response.json()
      showToast(error.message || 'Failed to update lock date', 'error')
    }
  } catch (error) {
    console.error('Failed to update lock date:', error)
    showToast('Failed to update lock date', 'error')
  } finally {
    updating.value = false
  }
}

const performMonthEndClose = async () => {
  if (!confirm('Are you sure you want to perform month-end close? This action cannot be undone.')) {
    return
  }
  
  closing.value = true
  try {
    const response = await fetch(`/api/v1/companies/${currentCompany.value.id}/lock-date/month-end-close`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: JSON.stringify({
        close_date: monthEndForm.value.close_date
      })
    })
    
    if (response.ok) {
      const data = await response.json()
      closeResult.value = data.data
      lockDateInfo.value = {
        ...lockDateInfo.value,
        lock_date: data.data.lock_date,
        lock_date_formatted: data.data.lock_date_formatted,
        is_locked: true
      }
      monthEndForm.value.close_date = ''
      showToast('Month-end close completed successfully', 'success')
    } else {
      const error = await response.json()
      showToast(error.message || 'Month-end close failed', 'error')
    }
  } catch (error) {
    console.error('Month-end close failed:', error)
    showToast('Failed to perform month-end close', 'error')
  } finally {
    closing.value = false
  }
}

// Lifecycle
onMounted(() => {
  fetchLockDateInfo()
})
</script>