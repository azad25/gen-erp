<template>
  
    
      <AppLayout>
        <div class="py-6">
          <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-6">
              <h1 class="text-2xl font-semibold text-gray-900">Profit & Loss Statement</h1>
              <p class="text-gray-600 mt-1">View your profit and loss report</p>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-lg shadow mb-6 p-6">
              <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">From Date</label>
                  <input type="date" v-model="filters.from_date" class="w-full border-gray-300 rounded-md shadow-sm" />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">To Date</label>
                  <input type="date" v-model="filters.to_date" class="w-full border-gray-300 rounded-md shadow-sm" />
                </div>
                <div class="flex items-end space-x-2">
                  <button @click="generateReport" :disabled="loading" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded disabled:opacity-50">
                    {{ loading ? 'Generating...' : 'Generate Report' }}
                  </button>
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
            <h2 class="text-lg font-semibold text-gray-900">
              Profit & Loss Statement from {{ formatDate(filters.from_date) }} to {{ formatDate(filters.to_date) }}
            </h2>
          </div>
          <div class="p-6">
            <!-- Revenue Section -->
            <div class="mb-6">
              <h3 class="text-md font-semibold text-gray-900 mb-3 border-b pb-2">Revenue</h3>
              <table class="min-w-full">
                <tbody>
                  <tr v-for="item in reportData.revenue.accounts" :key="item.id">
                    <td class="py-2 text-sm text-gray-900">{{ item.name }}</td>
                    <td class="py-2 text-right text-sm text-gray-900">{{ formatCurrency(item.amount) }}</td>
                  </tr>
                  <tr class="border-t">
                    <td class="py-2 font-semibold text-gray-900">Total Revenue</td>
                    <td class="py-2 text-right font-semibold text-gray-900">{{ formatCurrency(reportData.revenue.total) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Expenses Section -->
            <div class="mb-6">
              <h3 class="text-md font-semibold text-gray-900 mb-3 border-b pb-2">Expenses</h3>
              <table class="min-w-full">
                <tbody>
                  <tr v-for="item in reportData.expenses.accounts" :key="item.id">
                    <td class="py-2 text-sm text-gray-900">{{ item.name }}</td>
                    <td class="py-2 text-right text-sm text-gray-900">{{ formatCurrency(item.amount) }}</td>
                  </tr>
                  <tr class="border-t">
                    <td class="py-2 font-semibold text-gray-900">Total Expenses</td>
                    <td class="py-2 text-right font-semibold text-gray-900">{{ formatCurrency(reportData.expenses.total) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Net Profit/Loss -->
            <div class="bg-gray-50 p-4 rounded-lg">
              <div class="flex justify-between items-center">
                <span class="text-lg font-semibold text-gray-900">Net Profit/Loss</span>
                <span :class="reportData.net_profit >= 0 ? 'text-green-600' : 'text-red-600'" class="text-2xl font-bold">
                  {{ formatCurrency(reportData.net_profit) }}
                </span>
              </div>
            </div>
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
  from_date: new Date(new Date().setMonth(new Date().getMonth() - 1)).toISOString().split('T')[0],
  to_date: new Date().toISOString().split('T')[0],
});

const loading = ref(false);
const reportData = ref(null);

const generateReport = async () => {
  loading.value = true;
  try {
    const response = await fetch('/api/v1/reports/profit_loss', {
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
  window.open(`/api/v1/reports/profit_loss/pdf?from_date=${filters.value.from_date}&to_date=${filters.value.to_date}`, '_blank');
};

const downloadExcel = () => {
  window.open(`/api/v1/reports/profit_loss/excel?from_date=${filters.value.from_date}&to_date=${filters.value.to_date}`, '_blank');
};

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('en-GB');
};

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('en-BD', { style: 'currency', currency: 'BDT' }).format(amount / 100);
};
</script>
