<template>
  
    
      <AppLayout>
        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
              <div class="mb-6">
                <h1 class="text-2xl font-semibold text-gray-900">Balance Sheet</h1>
                <p class="text-gray-600 mt-1">View your balance sheet report</p>
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
            <h2 class="text-lg font-semibold text-gray-900">Balance Sheet as of {{ formatDate(filters.as_of_date) }}</h2>
          </div>
          <div class="p-6">
            <div class="grid grid-cols-2 gap-6">
              <!-- Assets -->
              <div>
                <h3 class="text-md font-semibold text-gray-900 mb-3 border-b pb-2">Assets</h3>
                <div v-for="category in reportData.assets" :key="category.name" class="mb-4">
                  <div class="font-medium text-sm text-gray-700 mb-2">{{ category.name }}</div>
                  <table class="min-w-full">
                    <tbody>
                      <tr v-for="account in category.accounts" :key="account.id">
                        <td class="py-1 text-sm text-gray-600">{{ account.name }}</td>
                        <td class="py-1 text-right text-sm text-gray-900">{{ formatCurrency(account.amount) }}</td>
                      </tr>
                      <tr class="border-t">
                        <td class="py-1 font-semibold text-gray-900">Total {{ category.name }}</td>
                        <td class="py-1 text-right font-semibold text-gray-900">{{ formatCurrency(category.total) }}</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
                <div class="border-t pt-3 mt-3">
                  <div class="flex justify-between font-bold text-gray-900">
                    <span>Total Assets</span>
                    <span>{{ formatCurrency(reportData.total_assets) }}</span>
                  </div>
                </div>
              </div>

              <!-- Liabilities & Equity -->
              <div>
                <h3 class="text-md font-semibold text-gray-900 mb-3 border-b pb-2">Liabilities & Equity</h3>
                
                <!-- Liabilities -->
                <div class="mb-4">
                  <div class="font-medium text-sm text-gray-700 mb-2">Liabilities</div>
                  <table class="min-w-full">
                    <tbody>
                      <tr v-for="account in reportData.liabilities" :key="account.id">
                        <td class="py-1 text-sm text-gray-600">{{ account.name }}</td>
                        <td class="py-1 text-right text-sm text-gray-900">{{ formatCurrency(account.amount) }}</td>
                      </tr>
                      <tr class="border-t">
                        <td class="py-1 font-semibold text-gray-900">Total Liabilities</td>
                        <td class="py-1 text-right font-semibold text-gray-900">{{ formatCurrency(reportData.total_liabilities) }}</td>
                      </tr>
                    </tbody>
                  </table>
                </div>

                <!-- Equity -->
                <div class="mb-4">
                  <div class="font-medium text-sm text-gray-700 mb-2">Equity</div>
                  <table class="min-w-full">
                    <tbody>
                      <tr v-for="account in reportData.equity" :key="account.id">
                        <td class="py-1 text-sm text-gray-600">{{ account.name }}</td>
                        <td class="py-1 text-right text-sm text-gray-900">{{ formatCurrency(account.amount) }}</td>
                      </tr>
                      <tr class="border-t">
                        <td class="py-1 font-semibold text-gray-900">Total Equity</td>
                        <td class="py-1 text-right font-semibold text-gray-900">{{ formatCurrency(reportData.total_equity) }}</td>
                      </tr>
                    </tbody>
                  </table>
                </div>

                <div class="border-t pt-3 mt-3">
                  <div class="flex justify-between font-bold text-gray-900">
                    <span>Total Liabilities & Equity</span>
                    <span>{{ formatCurrency(reportData.total_liabilities + reportData.total_equity) }}</span>
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
  as_of_date: new Date().toISOString().split('T')[0],
});

const loading = ref(false);
const reportData = ref(null);

const generateReport = async () => {
  loading.value = true;
  try {
    const response = await fetch('/api/v1/reports/balance_sheet', {
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
  window.open(`/api/v1/reports/balance_sheet/pdf?as_of_date=${filters.value.as_of_date}`, '_blank');
};

const downloadExcel = () => {
  window.open(`/api/v1/reports/balance_sheet/excel?as_of_date=${filters.value.as_of_date}`, '_blank');
};

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('en-GB');
};

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('en-BD', { style: 'currency', currency: 'BDT' }).format(amount / 100);
};
</script>
