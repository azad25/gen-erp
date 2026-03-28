<template>
  
    
      <AppLayout>
        <div class="py-6">
          <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-6">
              <h1 class="text-2xl font-semibold text-gray-900">Accounts Payable Aging</h1>
              <p class="text-gray-600 mt-1">View your accounts payable aging report</p>
            </div>

        <!-- Filters -->
        <div class="bg-white rounded-lg shadow mb-6 p-6">
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">As of Date</label>
              <input type="date" v-model="filters.as_of_date" class="w-full border-gray-300 rounded-md shadow-sm" />
            </div>
            <div class="flex items-end">
              <button @click="generateReport" :disabled="loading" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded disabled:opacity-50">
                {{ loading ? 'Generating...' : 'Generate Report' }}
              </button>
            </div>
            <div class="flex items-end space-x-2">
              <button @click="downloadPdf" :disabled="!reportData" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded disabled:opacity-50">
                Download PDF
              </button>
              <button @click="downloadExcel" :disabled="!reportData" class="bg-purple-600 hover:bg-purple-700 text-white font-bold py-2 px-4 rounded disabled:opacity-50">
                Download Excel
              </button>
            </div>
          </div>
        </div>

        <!-- Report Data -->
        <div v-if="reportData" class="bg-white rounded-lg shadow">
          <div class="p-6 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">AP Aging as of {{ formatDate(filters.as_of_date) }}</h2>
          </div>
          
          <!-- Summary -->
          <div class="p-6 bg-gray-50 border-b">
            <div class="grid grid-cols-6 gap-4 text-center">
              <div>
                <div class="text-sm text-gray-500">Total Outstanding</div>
                <div class="text-lg font-bold text-gray-900">{{ formatCurrency(reportData.summary.total_outstanding) }}</div>
              </div>
              <div>
                <div class="text-sm text-gray-500">Current</div>
                <div class="text-lg font-bold text-green-600">{{ formatCurrency(reportData.summary.current) }}</div>
              </div>
              <div>
                <div class="text-sm text-gray-500">1-30 Days</div>
                <div class="text-lg font-bold text-yellow-600">{{ formatCurrency(reportData.summary.days_1_30) }}</div>
              </div>
              <div>
                <div class="text-sm text-gray-500">31-60 Days</div>
                <div class="text-lg font-bold text-orange-600">{{ formatCurrency(reportData.summary.days_31_60) }}</div>
              </div>
              <div>
                <div class="text-sm text-gray-500">61-90 Days</div>
                <div class="text-lg font-bold text-red-600">{{ formatCurrency(reportData.summary.days_61_90) }}</div>
              </div>
              <div>
                <div class="text-sm text-gray-500">Over 90 Days</div>
                <div class="text-lg font-bold text-red-800">{{ formatCurrency(reportData.summary.days_over_90) }}</div>
              </div>
            </div>
          </div>

          <!-- Supplier Details -->
          <div class="p-6">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Supplier</th>
                  <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total</th>
                  <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Current</th>
                  <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">1-30</th>
                  <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">31-60</th>
                  <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">61-90</th>
                  <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">90+</th>
                  <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">POs</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="supplier in reportData.suppliers" :key="supplier.supplier_id">
                  <td class="px-4 py-3 text-sm text-gray-900">
                    <div class="font-medium">{{ supplier.supplier_name }}</div>
                    <div class="text-xs text-gray-500">{{ supplier.supplier_code }}</div>
                  </td>
                  <td class="px-4 py-3 text-right text-sm font-bold text-gray-900">{{ formatCurrency(supplier.total_outstanding) }}</td>
                  <td class="px-4 py-3 text-right text-sm text-green-600">{{ formatCurrency(supplier.current) }}</td>
                  <td class="px-4 py-3 text-right text-sm text-yellow-600">{{ formatCurrency(supplier.days_1_30) }}</td>
                  <td class="px-4 py-3 text-right text-sm text-orange-600">{{ formatCurrency(supplier.days_31_60) }}</td>
                  <td class="px-4 py-3 text-right text-sm text-red-600">{{ formatCurrency(supplier.days_61_90) }}</td>
                  <td class="px-4 py-3 text-right text-sm text-red-800">{{ formatCurrency(supplier.days_over_90) }}</td>
                  <td class="px-4 py-3 text-center text-sm text-gray-600">{{ supplier.po_count }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
          </div>
        </div>
      </AppLayout>
    
  
</template>

<script setup>
import { ref } from 'vue';
import AppLayout from "@/Layouts/AppLayout.vue"

const filters = ref({
  as_of_date: new Date().toISOString().split('T')[0],
});

const loading = ref(false);
const reportData = ref(null);

const generateReport = async () => {
  loading.value = true;
  try {
    const response = await fetch('/api/v1/reports/aging/ap', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(filters.value),
    });
    reportData.value = await response.json();
  } catch (error) {
    console.error('Error generating report:', error);
  } finally {
    loading.value = false;
  }
};

const downloadPdf = () => {
  window.open(`/api/v1/reports/aging/ap/pdf?as_of_date=${filters.value.as_of_date}`, '_blank');
};

const downloadExcel = () => {
  window.open(`/api/v1/reports/aging/ap/excel?as_of_date=${filters.value.as_of_date}`, '_blank');
};

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('en-GB');
};

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('en-BD', { style: 'currency', currency: 'BDT' }).format(amount / 100);
};
</script>
