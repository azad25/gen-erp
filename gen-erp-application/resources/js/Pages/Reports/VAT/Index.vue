<template>
  <AppLayout title="VAT Reports">
    <div class="p-6">
      <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">VAT Reports (Mushak)</h1>
        <p class="mt-1 text-sm text-gray-600">
          Generate Bangladesh VAT reports as required by NBR
        </p>
      </div>

      <!-- Period Selection -->
      <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h2 class="text-lg font-semibold mb-4">Select Period</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Month</label>
            <select
              v-model="selectedMonth"
              class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            >
              <option v-for="month in months" :key="month.value" :value="month.value">
                {{ month.label }}
              </option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Year</label>
            <select
              v-model="selectedYear"
              class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            >
              <option v-for="year in years" :key="year" :value="year">
                {{ year }}
              </option>
            </select>
          </div>
        </div>
      </div>

      <!-- Report Cards -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Mushak 6.1 -->
        <div class="bg-white rounded-lg shadow hover:shadow-lg transition-shadow cursor-pointer" @click="generateReport('mushak-61')">
          <div class="p-6">
            <div class="flex items-center justify-between mb-4">
              <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
              </div>
              <span v-if="loading === 'mushak-61'" class="animate-spin h-5 w-5 border-2 border-blue-600 border-t-transparent rounded-full"></span>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">Mushak 6.1</h3>
            <p class="text-sm text-gray-600">Purchase Register - Input VAT from suppliers</p>
          </div>
        </div>

        <!-- Mushak 6.2 -->
        <div class="bg-white rounded-lg shadow hover:shadow-lg transition-shadow cursor-pointer" @click="generateReport('mushak-62')">
          <div class="p-6">
            <div class="flex items-center justify-between mb-4">
              <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                </svg>
              </div>
              <span v-if="loading === 'mushak-62'" class="animate-spin h-5 w-5 border-2 border-green-600 border-t-transparent rounded-full"></span>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">Mushak 6.2</h3>
            <p class="text-sm text-gray-600">VAT Summary - Net VAT payable calculation</p>
          </div>
        </div>

        <!-- Mushak 6.6 -->
        <div class="bg-white rounded-lg shadow hover:shadow-lg transition-shadow cursor-pointer" @click="generateReport('mushak-66')">
          <div class="p-6">
            <div class="flex items-center justify-between mb-4">
              <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z" />
                </svg>
              </div>
              <span v-if="loading === 'mushak-66'" class="animate-spin h-5 w-5 border-2 border-yellow-600 border-t-transparent rounded-full"></span>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">Mushak 6.6</h3>
            <p class="text-sm text-gray-600">Credit Note Register - VAT adjustments</p>
          </div>
        </div>

        <!-- Mushak 9.1 -->
        <div class="bg-white rounded-lg shadow hover:shadow-lg transition-shadow cursor-pointer" @click="generateReport('mushak-91')">
          <div class="p-6">
            <div class="flex items-center justify-between mb-4">
              <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
              </div>
              <span v-if="loading === 'mushak-91'" class="animate-spin h-5 w-5 border-2 border-purple-600 border-t-transparent rounded-full"></span>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">Mushak 9.1</h3>
            <p class="text-sm text-gray-600">Treasury Challan - VAT payment form</p>
          </div>
        </div>
      </div>

      <!-- Report Display Area -->
      <div v-if="reportData" class="mt-6 bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-xl font-semibold">{{ reportData.report_name }}</h2>
          <div class="flex gap-2">
            <button
              @click="exportReport('pdf')"
              class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition-colors"
            >
              Export PDF
            </button>
            <button
              @click="exportReport('excel')"
              class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition-colors"
            >
              Export Excel
            </button>
            <button
              @click="reportData = null"
              class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 transition-colors"
            >
              Close
            </button>
          </div>
        </div>

        <!-- Company Info -->
        <div v-if="reportData.company" class="mb-4 p-4 bg-gray-50 rounded-md">
          <div class="grid grid-cols-2 gap-4">
            <div>
              <span class="text-sm font-medium text-gray-600">Company:</span>
              <span class="ml-2 text-sm text-gray-900">{{ reportData.company.name }}</span>
            </div>
            <div>
              <span class="text-sm font-medium text-gray-600">VAT BIN:</span>
              <span class="ml-2 text-sm text-gray-900">{{ reportData.company.vat_bin || 'N/A' }}</span>
            </div>
            <div v-if="reportData.period">
              <span class="text-sm font-medium text-gray-600">Period:</span>
              <span class="ml-2 text-sm text-gray-900">{{ reportData.period }}</span>
            </div>
          </div>
        </div>

        <!-- Summary (for Mushak 6.2 and 9.1) -->
        <div v-if="reportData.summary" class="mb-4">
          <h3 class="text-lg font-semibold mb-3">Summary</h3>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div v-for="(value, key) in reportData.summary" :key="key" class="p-4 bg-gray-50 rounded-md">
              <div class="text-sm font-medium text-gray-600 capitalize">{{ formatKey(key) }}</div>
              <div class="text-xl font-bold text-gray-900 mt-1">{{ formatCurrency(value) }}</div>
            </div>
          </div>
        </div>

        <!-- Data Table (for Mushak 6.1 and 6.6) -->
        <div v-if="reportData.data && Array.isArray(reportData.data)" class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th v-for="(value, key) in reportData.data[0]" :key="key" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {{ formatKey(key) }}
                </th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="(row, index) in reportData.data" :key="index">
                <td v-for="(value, key) in row" :key="key" class="px-4 py-3 text-sm text-gray-900">
                  {{ formatValue(key, value) }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Mushak 9.1 Specific Data -->
        <div v-if="reportData.data && reportData.data.challan_data" class="mt-4">
          <h3 class="text-lg font-semibold mb-3">Challan Information</h3>
          <div class="grid grid-cols-2 gap-4">
            <div v-for="(value, key) in reportData.data.challan_data" :key="key" class="p-3 bg-gray-50 rounded-md">
              <div class="text-sm font-medium text-gray-600 capitalize">{{ formatKey(key) }}</div>
              <div class="text-sm text-gray-900 mt-1">{{ value || 'N/A' }}</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import AppLayout from '../../../Layouts/AppLayout.vue'
import { useApi } from '../../../Composables/useApi.js'
import { useToast } from '../../../Composables/useToast.js'

const { get } = useApi()
const { showToast } = useToast()

const selectedMonth = ref(new Date().getMonth() + 1)
const selectedYear = ref(new Date().getFullYear())
const loading = ref(null)
const reportData = ref(null)

const months = [
  { value: 1, label: 'January' },
  { value: 2, label: 'February' },
  { value: 3, label: 'March' },
  { value: 4, label: 'April' },
  { value: 5, label: 'May' },
  { value: 6, label: 'June' },
  { value: 7, label: 'July' },
  { value: 8, label: 'August' },
  { value: 9, label: 'September' },
  { value: 10, label: 'October' },
  { value: 11, label: 'November' },
  { value: 12, label: 'December' },
]

const years = computed(() => {
  const currentYear = new Date().getFullYear()
  const yearList = []
  for (let i = currentYear; i >= currentYear - 5; i--) {
    yearList.push(i)
  }
  return yearList
})

const generateReport = async (reportType) => {
  loading.value = reportType
  reportData.value = null

  try {
    const response = await get(`/vat-reports/${reportType}`, {
      month: selectedMonth.value,
      year: selectedYear.value,
    })

    reportData.value = response
    showToast('Report generated successfully', 'success')
  } catch (error) {
    showToast('Failed to generate report', 'error')
    console.error('Report generation error:', error)
  } finally {
    loading.value = null
  }
}

const exportReport = (format) => {
  showToast(`Export to ${format.toUpperCase()} coming soon`, 'info')
  // TODO: Implement PDF/Excel export
}

const formatKey = (key) => {
  return key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
}

const formatValue = (key, value) => {
  if (key.includes('amount') || key.includes('value') || key.includes('total')) {
    return formatCurrency(value)
  }
  if (key.includes('rate')) {
    return `${value}%`
  }
  return value
}

const formatCurrency = (value) => {
  if (typeof value !== 'number') return value
  return new Intl.NumberFormat('en-BD', {
    style: 'currency',
    currency: 'BDT',
    minimumFractionDigits: 0,
  }).format(value)
}
</script>
