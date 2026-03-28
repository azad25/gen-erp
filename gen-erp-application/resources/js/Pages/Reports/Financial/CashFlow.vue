<template>
  
    
      <AppLayout>
        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
              <div class="mb-6">
                <h1 class="text-2xl font-semibold text-gray-900">Cash Flow Statement</h1>
                <p class="text-gray-600 mt-1">View your cash flow statement</p>
              <input type="date" v-model="filters.from_date" class="w-full border-gray-300 rounded-md shadow-sm" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">To Date</label>
              <input type="date" v-model="filters.to_date" class="w-full border-gray-300 rounded-md shadow-sm" />
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
            <h2 class="text-lg font-semibold text-gray-900">
              Cash Flow Statement from {{ formatDate(filters.from_date) }} to {{ formatDate(filters.to_date) }}
            </h2>
          </div>
          <div class="p-6">
            <!-- Operating Activities -->
            <div class="mb-6">
              <h3 class="text-md font-semibold text-gray-900 mb-3 border-b pb-2">Operating Activities</h3>
              <table class="min-w-full">
                <tbody>
                  <tr>
                    <td class="py-2 text-sm text-gray-900">Net Income</td>
                    <td class="py-2 text-right text-sm text-gray-900">{{ formatCurrency(reportData.operating_activities.net_income) }}</td>
                  </tr>
                  <tr v-for="item in reportData.operating_activities.adjustments.items" :key="item.name">
                    <td class="py-2 text-sm text-gray-600">{{ item.name }}</td>
                    <td class="py-2 text-right text-sm text-gray-900">{{ formatCurrency(item.amount) }}</td>
                  </tr>
                  <tr v-for="item in reportData.operating_activities.working_capital_changes.items" :key="item.name">
                    <td class="py-2 text-sm text-gray-600">{{ item.name }}</td>
                    <td class="py-2 text-right text-sm text-gray-900">{{ formatCurrency(item.amount) }}</td>
                  </tr>
                  <tr class="border-t">
                    <td class="py-2 font-semibold text-gray-900">Net Cash from Operating Activities</td>
                    <td class="py-2 text-right font-semibold text-gray-900">{{ formatCurrency(reportData.operating_activities.net_cash_from_operations) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Investing Activities -->
            <div class="mb-6">
              <h3 class="text-md font-semibold text-gray-900 mb-3 border-b pb-2">Investing Activities</h3>
              <table class="min-w-full">
                <tbody>
                  <tr v-for="item in reportData.investing_activities.items" :key="item.name">
                    <td class="py-2 text-sm text-gray-600">{{ item.name }}</td>
                    <td class="py-2 text-right text-sm text-gray-900">{{ formatCurrency(item.amount) }}</td>
                  </tr>
                  <tr class="border-t">
                    <td class="py-2 font-semibold text-gray-900">Net Cash from Investing Activities</td>
                    <td class="py-2 text-right font-semibold text-gray-900">{{ formatCurrency(reportData.investing_activities.net_cash_from_investing) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Financing Activities -->
            <div class="mb-6">
              <h3 class="text-md font-semibold text-gray-900 mb-3 border-b pb-2">Financing Activities</h3>
              <table class="min-w-full">
                <tbody>
                  <tr v-for="item in reportData.financing_activities.items" :key="item.name">
                    <td class="py-2 text-sm text-gray-600">{{ item.name }}</td>
                    <td class="py-2 text-right text-sm text-gray-900">{{ formatCurrency(item.amount) }}</td>
                  </tr>
                  <tr class="border-t">
                    <td class="py-2 font-semibold text-gray-900">Net Cash from Financing Activities</td>
                    <td class="py-2 text-right font-semibold text-gray-900">{{ formatCurrency(reportData.financing_activities.net_cash_from_financing) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Summary -->
            <div class="bg-gray-50 p-4 rounded-lg space-y-2">
              <div class="flex justify-between">
                <span class="text-sm text-gray-700">Net Change in Cash</span>
                <span class="text-sm font-semibold text-gray-900">{{ formatCurrency(reportData.net_change_in_cash) }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-sm text-gray-700">Cash Beginning Balance</span>
                <span class="text-sm font-semibold text-gray-900">{{ formatCurrency(reportData.cash_beginning) }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-sm text-gray-700">Cash Ending Balance</span>
                <span class="text-sm font-semibold text-gray-900">{{ formatCurrency(reportData.cash_ending) }}</span>
              </div>
              <div class="border-t pt-2 mt-2">
                <div class="flex justify-between font-bold text-gray-900">
                  <span>Reconciliation Difference</span>
                  <span>{{ formatCurrency(reportData.reconciliation_difference) }}</span>
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
    const response = await fetch('/api/v1/reports/cash_flow', {
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
  window.open(`/api/v1/reports/cash_flow/pdf?from_date=${filters.value.from_date}&to_date=${filters.value.to_date}`, '_blank');
};

const downloadExcel = () => {
  window.open(`/api/v1/reports/cash_flow/excel?from_date=${filters.value.from_date}&to_date=${filters.value.to_date}`, '_blank');
};

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('en-GB');
};

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('en-BD', { style: 'currency', currency: 'BDT' }).format(amount / 100);
};
</script>
