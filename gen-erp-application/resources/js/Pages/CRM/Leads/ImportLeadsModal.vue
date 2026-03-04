<template>
  <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
      <div class="mt-3">
        <!-- Header -->
        <div class="flex items-center justify-between pb-4 border-b">
          <h3 class="text-lg font-medium text-gray-900">Import Leads</h3>
          <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600">
            <XMarkIcon class="h-6 w-6" />
          </button>
        </div>

        <!-- Content -->
        <div class="mt-6">
          <!-- Step Indicator -->
          <div class="mb-8">
            <div class="flex items-center">
              <div class="flex items-center text-sm">
                <div :class="[
                  'flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center',
                  currentStep >= 1 ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-600'
                ]">
                  1
                </div>
                <div class="ml-2 font-medium">Upload File</div>
              </div>
              <div class="flex-1 h-px bg-gray-200 mx-4"></div>
              <div class="flex items-center text-sm">
                <div :class="[
                  'flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center',
                  currentStep >= 2 ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-600'
                ]">
                  2
                </div>
                <div class="ml-2 font-medium">Map Fields</div>
              </div>
              <div class="flex-1 h-px bg-gray-200 mx-4"></div>
              <div class="flex items-center text-sm">
                <div :class="[
                  'flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center',
                  currentStep >= 3 ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-600'
                ]">
                  3
                </div>
                <div class="ml-2 font-medium">Import</div>
              </div>
            </div>
          </div>

          <!-- Step 1: Upload File -->
          <div v-if="currentStep === 1" class="space-y-6">
            <div>
              <h4 class="text-md font-medium text-gray-900 mb-4">Upload CSV File</h4>
              <div class="border-2 border-dashed border-gray-300 rounded-lg p-6">
                <div class="text-center">
                  <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                    <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                  </svg>
                  <div class="mt-4">
                    <label for="file-upload" class="cursor-pointer">
                      <span class="mt-2 block text-sm font-medium text-gray-900">
                        Drop your CSV file here, or 
                        <span class="text-indigo-600 hover:text-indigo-500">browse</span>
                      </span>
                      <input
                        id="file-upload"
                        name="file-upload"
                        type="file"
                        accept=".csv"
                        @change="handleFileUpload"
                        class="sr-only"
                      />
                    </label>
                    <p class="mt-1 text-xs text-gray-500">CSV files only, up to 10MB</p>
                  </div>
                </div>
              </div>
              
              <div v-if="selectedFile" class="mt-4 p-4 bg-green-50 rounded-lg">
                <div class="flex items-center">
                  <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                  </svg>
                  <span class="ml-2 text-sm text-green-800">{{ selectedFile.name }} ({{ formatFileSize(selectedFile.size) }})</span>
                </div>
              </div>
            </div>

            <div class="bg-blue-50 rounded-lg p-4">
              <h5 class="text-sm font-medium text-blue-900 mb-2">CSV Format Requirements:</h5>
              <ul class="text-sm text-blue-800 space-y-1">
                <li>• First row should contain column headers</li>
                <li>• Required fields: name, email</li>
                <li>• Optional fields: phone, company_name, job_title, source, notes</li>
                <li>• Use comma (,) as delimiter</li>
              </ul>
            </div>

            <div class="text-center">
              <button
                @click="downloadTemplate"
                class="text-indigo-600 hover:text-indigo-500 text-sm font-medium"
              >
                Download CSV Template
              </button>
            </div>
          </div>

          <!-- Step 2: Map Fields -->
          <div v-if="currentStep === 2" class="space-y-6">
            <div>
              <h4 class="text-md font-medium text-gray-900 mb-4">Map CSV Columns to Lead Fields</h4>
              <div class="space-y-4">
                <div v-for="field in leadFields" :key="field.key" class="grid grid-cols-2 gap-4 items-center">
                  <div>
                    <label class="block text-sm font-medium text-gray-700">
                      {{ field.label }}
                      <span v-if="field.required" class="text-red-500">*</span>
                    </label>
                  </div>
                  <div>
                    <select
                      v-model="fieldMapping[field.key]"
                      class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                      <option value="">-- Select Column --</option>
                      <option v-for="column in csvColumns" :key="column" :value="column">
                        {{ column }}
                      </option>
                    </select>
                  </div>
                </div>
              </div>
            </div>

            <div v-if="previewData.length > 0" class="bg-gray-50 rounded-lg p-4">
              <h5 class="text-sm font-medium text-gray-900 mb-3">Preview (First 3 rows)</h5>
              <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                  <thead>
                    <tr>
                      <th v-for="field in leadFields" :key="field.key" class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">
                        {{ field.label }}
                      </th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-gray-200">
                    <tr v-for="(row, index) in previewData.slice(0, 3)" :key="index">
                      <td v-for="field in leadFields" :key="field.key" class="px-3 py-2 text-sm text-gray-900">
                        {{ getMappedValue(row, field.key) || '-' }}
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <!-- Step 3: Import -->
          <div v-if="currentStep === 3" class="space-y-6">
            <div class="text-center">
              <div v-if="importing" class="space-y-4">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600 mx-auto"></div>
                <p class="text-sm text-gray-600">Importing leads... {{ importProgress }}%</p>
                <div class="w-full bg-gray-200 rounded-full h-2">
                  <div class="bg-indigo-600 h-2 rounded-full transition-all duration-300" :style="`width: ${importProgress}%`"></div>
                </div>
              </div>
              
              <div v-else-if="importComplete" class="space-y-4">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100">
                  <svg class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                  </svg>
                </div>
                <div>
                  <h4 class="text-lg font-medium text-gray-900">Import Complete!</h4>
                  <p class="text-sm text-gray-600 mt-2">
                    Successfully imported {{ importResults.success }} leads.
                    <span v-if="importResults.errors > 0">
                      {{ importResults.errors }} rows had errors and were skipped.
                    </span>
                  </p>
                </div>
              </div>
              
              <div v-else>
                <h4 class="text-lg font-medium text-gray-900 mb-4">Ready to Import</h4>
                <p class="text-sm text-gray-600 mb-6">
                  {{ previewData.length }} leads will be imported with the current field mapping.
                </p>
                <button
                  @click="startImport"
                  class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2 rounded-md text-sm font-medium"
                >
                  Start Import
                </button>
              </div>
            </div>
          </div>

          <!-- Actions -->
          <div class="flex items-center justify-between pt-6 border-t mt-6">
            <button
              v-if="currentStep > 1 && !importing && !importComplete"
              @click="previousStep"
              class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
            >
              Previous
            </button>
            <div v-else></div>

            <div class="flex space-x-3">
              <button
                @click="$emit('close')"
                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
              >
                {{ importComplete ? 'Close' : 'Cancel' }}
              </button>
              <button
                v-if="currentStep < 3 && !importing"
                @click="nextStep"
                :disabled="!canProceed"
                class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md hover:bg-indigo-700 disabled:opacity-50"
              >
                Next
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { XMarkIcon } from '@heroicons/vue/24/outline'
import { useApi } from '@/Composables/useApi'
import { useToast } from '@/Composables/useToast'

const emit = defineEmits(['close', 'imported'])

const { post } = useApi()
const { showToast } = useToast()

const currentStep = ref(1)
const selectedFile = ref(null)
const csvColumns = ref([])
const previewData = ref([])
const importing = ref(false)
const importComplete = ref(false)
const importProgress = ref(0)
const importResults = ref({ success: 0, errors: 0 })

const leadFields = [
  { key: 'name', label: 'Full Name', required: true },
  { key: 'email', label: 'Email', required: true },
  { key: 'phone', label: 'Phone', required: false },
  { key: 'company_name', label: 'Company Name', required: false },
  { key: 'job_title', label: 'Job Title', required: false },
  { key: 'source', label: 'Source', required: false },
  { key: 'notes', label: 'Notes', required: false }
]

const fieldMapping = ref({})

const canProceed = computed(() => {
  if (currentStep.value === 1) {
    return selectedFile.value !== null
  }
  if (currentStep.value === 2) {
    const requiredFields = leadFields.filter(f => f.required)
    return requiredFields.every(field => fieldMapping.value[field.key])
  }
  return true
})

const handleFileUpload = (event) => {
  const file = event.target.files[0]
  if (file && file.type === 'text/csv') {
    selectedFile.value = file
    parseCSV(file)
  } else {
    showToast('Please select a valid CSV file', 'error')
  }
}

const parseCSV = (file) => {
  const reader = new FileReader()
  reader.onload = (e) => {
    const csv = e.target.result
    const lines = csv.split('\n')
    const headers = lines[0].split(',').map(h => h.trim().replace(/"/g, ''))
    
    csvColumns.value = headers
    
    // Parse preview data
    const data = []
    for (let i = 1; i < Math.min(lines.length, 11); i++) {
      if (lines[i].trim()) {
        const values = lines[i].split(',').map(v => v.trim().replace(/"/g, ''))
        const row = {}
        headers.forEach((header, index) => {
          row[header] = values[index] || ''
        })
        data.push(row)
      }
    }
    previewData.value = data
  }
  reader.readAsText(file)
}

const getMappedValue = (row, fieldKey) => {
  const columnName = fieldMapping.value[fieldKey]
  return columnName ? row[columnName] : ''
}

const nextStep = () => {
  if (canProceed.value) {
    currentStep.value++
  }
}

const previousStep = () => {
  currentStep.value--
}

const startImport = async () => {
  importing.value = true
  importProgress.value = 0
  
  try {
    const formData = new FormData()
    formData.append('file', selectedFile.value)
    formData.append('field_mapping', JSON.stringify(fieldMapping.value))
    
    // Simulate progress
    const progressInterval = setInterval(() => {
      if (importProgress.value < 90) {
        importProgress.value += 10
      }
    }, 200)
    
    const response = await fetch('/api/v1/crm/leads/import', {
      method: 'POST',
      body: formData,
      headers: {
        'Authorization': `Bearer ${document.querySelector('meta[name="api-token"]')?.content}`,
        'X-Requested-With': 'XMLHttpRequest'
      }
    })
    
    clearInterval(progressInterval)
    importProgress.value = 100
    
    if (response.ok) {
      const data = await response.json()
      importResults.value = data.data
      importComplete.value = true
      showToast('Leads imported successfully', 'success')
      
      setTimeout(() => {
        emit('imported')
      }, 2000)
    } else {
      throw new Error('Import failed')
    }
  } catch (err) {
    console.error('Failed to import leads:', err)
    showToast('Failed to import leads', 'error')
  } finally {
    importing.value = false
  }
}

const downloadTemplate = () => {
  const headers = ['name', 'email', 'phone', 'company_name', 'job_title', 'source', 'notes']
  const sampleData = [
    'John Doe,john@example.com,+1234567890,Acme Corp,Manager,website,Interested in our services',
    'Jane Smith,jane@example.com,+1234567891,Tech Inc,Developer,referral,Looking for solutions'
  ]
  
  const csvContent = [headers.join(','), ...sampleData].join('\n')
  const blob = new Blob([csvContent], { type: 'text/csv' })
  const url = window.URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = 'leads_template.csv'
  a.click()
  window.URL.revokeObjectURL(url)
}

const formatFileSize = (bytes) => {
  if (bytes === 0) return '0 Bytes'
  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}
</script>