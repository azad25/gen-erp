<template>
  <ThemeProvider>
    <SidebarProvider>
      <AdminLayout>
        <div class="py-6">
          <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-6">
              <h1 class="text-2xl font-semibold text-gray-900">Trial Balance</h1>
              <p class="text-gray-600 mt-1">View your trial balance report</p>
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
                <h2 class="text-lg font-semibold text-gray-900">Trial Balance as of {{ formatDate(filters.as_of_date) }}</h2>
              </div>
              <div class="p-6">
                <table class="min-w-full divide-y divide-gray-200">
                  <thead class="bg-gray-50">
                    <tr>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Account Code</th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Account Name</th>
                      <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Debit</th>
                      <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Credit</th>
                    </tr>
                  </thead>
                  <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-for="account in reportData.accounts" :key="account.id">
                      <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ account.code }}</td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ account.name }}</td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900">{{ formatCurrency(account.debit) }}</td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900">{{ formatCurrency(account.credit) }}</td>
                    </tr>
                  </tbody>
                  <tfoot class="bg-gray-50">
                    <tr>
                      <td colspan="2" class="px-6 py-4 text-right font-semibold text-gray-900">Total</td>
                      <td class="px-6 py-4 text-right font-semibold text-gray-900">{{ formatCurrency(reportData.total_debit) }}</td>
                      <td class="px-6 py-4 text-right font-semibold text-gray-900">{{ formatCurrency(reportData.total_credit) }}</td>
                    </tr>
                  </tfoot>
                </table>
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

const filters = ref({
  as_of_date: new Date().toISOString().split('T')[0],
});

const loading = ref(false);
const reportData = ref(null);

const generateReport = async () => {
  loading.value = true;
  try {
    const response = await fetch('/api/v1/reports/trial_balance', {
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
  window.open(`/api/v1/reports/trial_balance/pdf?as_of_date=${filters.value.as_of_date}`, '_blank');
};

const downloadExcel = () => {
  window.open(`/api/v1/reports/trial_balance/excel?as_of_date=${filters.value.as_of_date}`, '_blank');
};

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('en-GB');
};

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('en-BD', { style: 'currency', currency: 'BDT' }).format(amount / 100);
};
</script>
