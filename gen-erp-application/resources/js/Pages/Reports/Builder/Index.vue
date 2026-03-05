<template>
  <ThemeProvider>
    <SidebarProvider>
      <AdminLayout>
        <div class="py-6">
          <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-6">
              <h1 class="text-2xl font-semibold text-gray-900">Report Builder</h1>
              <p class="text-gray-600 mt-1">Create custom reports</p>
            </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <!-- Configuration -->
          <div class="lg:col-span-1">
            <div class="bg-white rounded-lg shadow p-6 mb-6">
              <h2 class="text-lg font-semibold text-gray-900 mb-4">Report Configuration</h2>
              
              <div class="space-y-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Entity Type</label>
                  <select v-model="config.entity_type" @change="loadAvailableFields" class="w-full border-gray-300 rounded-md shadow-sm">
                    <option value="">Select Entity</option>
                    <option value="customer">Customers</option>
                    <option value="product">Products</option>
                    <option value="invoice">Invoices</option>
                    <option value="purchase">Purchase Orders</option>
                    <option value="employee">Employees</option>
                    <option value="expense">Expenses</option>
                    <option value="supplier">Suppliers</option>
                  </select>
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Report Name</label>
                  <input type="text" v-model="config.name" class="w-full border-gray-300 rounded-md shadow-sm" placeholder="My Custom Report" />
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Visualization</label>
                  <select v-model="config.visualisation" class="w-full border-gray-300 rounded-md shadow-sm">
                    <option value="table">Table</option>
                    <option value="bar">Bar Chart</option>
                    <option value="line">Line Chart</option>
                    <option value="pie">Pie Chart</option>
                  </select>
                </div>

                <div class="flex items-center space-x-4">
                  <label class="flex items-center">
                    <input type="checkbox" v-model="config.is_scheduled" class="mr-2" />
                    <span class="text-sm text-gray-700">Schedule Report</span>
                  </label>
                </div>

                <div v-if="config.is_scheduled">
                  <label class="block text-sm font-medium text-gray-700 mb-2">Frequency</label>
                  <select v-model="config.schedule_frequency" class="w-full border-gray-300 rounded-md shadow-sm">
                    <option value="daily">Daily</option>
                    <option value="weekly">Weekly</option>
                    <option value="monthly">Monthly</option>
                  </select>
                </div>
              </div>
            </div>

            <!-- Available Fields -->
            <div v-if="availableFields.length > 0" class="bg-white rounded-lg shadow p-6 mb-6">
              <h2 class="text-lg font-semibold text-gray-900 mb-4">Available Fields</h2>
              <div class="space-y-2 max-h-64 overflow-y-auto">
                <label v-for="field in availableFields" :key="field.key" class="flex items-center">
                  <input type="checkbox" v-model="config.selected_fields" :value="field.key" class="mr-2" />
                  <span class="text-sm text-gray-700">{{ field.label }}</span>
                </label>
              </div>
            </div>

            <!-- Actions -->
            <div class="bg-white rounded-lg shadow p-6">
              <div class="space-y-3">
                <button @click="saveReport" :disabled="loading" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded disabled:opacity-50">
                  {{ loading ? 'Saving...' : 'Save Report' }}
                </button>
                <button @click="runReport" :disabled="!reportId" class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                  Run Report
                </button>
                <button @click="downloadPdf" :disabled="!reportResults" class="w-full bg-purple-600 hover:bg-purple-700 text-white font-bold py-2 px-4 rounded disabled:opacity-50">
                  Download PDF
                </button>
                <button @click="downloadExcel" :disabled="!reportResults" class="w-full bg-orange-600 hover:bg-orange-700 text-white font-bold py-2 px-4 rounded disabled:opacity-50">
                  Download Excel
                </button>
              </div>
            </div>
          </div>

          <!-- Results -->
          <div class="lg:col-span-2">
            <div v-if="reportResults" class="bg-white rounded-lg shadow">
              <div class="p-6 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Report Results</h2>
              </div>
              <div class="p-6">
                <table class="min-w-full divide-y divide-gray-200">
                  <thead class="bg-gray-50">
                    <tr>
                      <th v-for="column in reportResults.columns" :key="column" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                        {{ column }}
                      </th>
                    </tr>
                  </thead>
                  <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-for="(row, index) in reportResults.rows" :key="index">
                      <td v-for="(value, key) in row" :key="key" class="px-4 py-3 text-sm text-gray-900">
                        {{ formatValue(value, key) }}
                      </td>
                    </tr>
                  </tbody>
                </table>
                <div class="mt-4 text-sm text-gray-500">
                  Total: {{ reportResults.total }} records
                </div>
              </div>
            </div>

            <div v-else class="bg-white rounded-lg shadow p-12 text-center">
              <div class="text-gray-400">
                <svg class="mx-auto h-12 w-12 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10h.01M9 21h12a2 2 0 002-2V5a2 2 0 00-2-2H9a2 2 0 00-2 2v14a2 2 0 002 2z" />
                </svg>
                <p class="text-gray-500">Configure and run a report to see results</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</SidebarProvider>
</ThemeProvider>
</template>

<script setup>
import { ref } from 'vue';
import ThemeProvider from '@/Components/Layout/ThemeProvider.vue';
import SidebarProvider from '@/Components/Layout/SidebarProvider.vue';
import AdminLayout from '@/Components/Layout/AdminLayout.vue';

const config = ref({
  name: '',
  entity_type: '',
  selected_fields: [],
  filters: {},
  group_by: '',
  aggregate: [],
  sort_field: '',
  sort_direction: 'asc',
  visualisation: 'table',
  is_scheduled: false,
  schedule_frequency: 'monthly',
});

const availableFields = ref([]);
const loading = ref(false);
const reportId = ref(null);
const reportResults = ref(null);

const loadAvailableFields = async () => {
  if (!config.value.entity_type) return;
  
  try {
    const response = await fetch(`/api/v1/reports/fields/${config.value.entity_type}`);
    availableFields.value = await response.json();
  } catch (error) {
    console.error('Error loading fields:', error);
  }
};

const saveReport = async () => {
  if (!config.value.name || !config.value.entity_type) {
    alert('Please enter a report name and select an entity type');
    return;
  }

  loading.value = true;
  try {
    const response = await fetch('/api/v1/reports/saved', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(config.value),
    });
    const data = await response.json();
    reportId.value = data.id;
    alert('Report saved successfully!');
  } catch (error) {
    console.error('Error saving report:', error);
    alert('Error saving report');
  } finally {
    loading.value = false;
  }
};

const runReport = async () => {
  if (!reportId.value) return;

  loading.value = true;
  try {
    const response = await fetch(`/api/v1/reports/saved/${reportId.value}/run`);
    reportResults.value = await response.json();
  } catch (error) {
    console.error('Error running report:', error);
  } finally {
    loading.value = false;
  }
};

const downloadPdf = () => {
  window.open(`/api/v1/reports/saved/${reportId.value}/pdf`, '_blank');
};

const downloadExcel = () => {
  window.open(`/api/v1/reports/saved/${reportId.value}/excel`, '_blank');
};

const formatValue = (value, key) => {
  if (key.includes('amount') || key.includes('price') || key.includes('value') || key.includes('total')) {
    return new Intl.NumberFormat('en-BD', { style: 'currency', currency: 'BDT' }).format(value / 100);
  }
  if (key.includes('date') || key.includes('at')) {
    return new Date(value).toLocaleDateString('en-GB');
  }
  return value;
};
</script>
