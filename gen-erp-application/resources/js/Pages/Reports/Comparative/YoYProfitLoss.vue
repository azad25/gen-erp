<template>
  
    
      <AppLayout>
        <Head title="Year-over-Year Profit & Loss" />
        
        <div class="py-6">
          <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-6">
              <h1 class="text-2xl font-semibold text-gray-900">Year-over-Year Profit & Loss</h1>
              <p class="text-gray-600 mt-1">Compare profit & loss between years</p>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-lg shadow mb-6 p-6">
              <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Current Period From</label>
                  <input type="date" v-model="filters.current_from" class="w-full border-gray-300 rounded-md shadow-sm" />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Current Period To</label>
                  <input type="date" v-model="filters.current_to" class="w-full border-gray-300 rounded-md shadow-sm" />
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
                <h2 class="text-lg font-semibold text-gray-900">YoY Comparison</h2>
              </div>
              
              <!-- Summary -->
              <div class="p-6 bg-gray-50 border-b">
                <div class="grid grid-cols-3 gap-4 text-center">
                  <div>
                    <div class="text-sm text-gray-500">Current Period</div>
                    <div class="text-md font-semibold text-gray-900">{{ reportData.current_period.period }}</div>
                  </div>
                  <div>
                    <div class="text-sm text-gray-500">Previous Period</div>
                    <div class="text-md font-semibold text-gray-900">{{ reportData.previous_period.period }}</div>
                  </div>
                  <div>
                    <div class="text-sm text-gray-500">Comparison Type</div>
                    <div class="text-md font-semibold text-gray-900">{{ reportData.comparison_type }}</div>
                  </div>
                </div>
              </div>

              <!-- Revenue Comparison -->
              <div class="p-6 border-b">
                <h3 class="text-md font-semibold text-gray-900 mb-3">Revenue Comparison</h3>
                <div class="grid grid-cols-3 gap-4">
                  <div class="bg-blue-50 p-4 rounded-lg">
                    <div class="text-sm text-gray-500">Current</div>
                    <div class="text-lg font-bold text-blue-600">{{ formatCurrency(reportData.current_period.revenue) }}</div>
                  </div>
                  <div class="bg-gray-50 p-4 rounded-lg">
                    <div class="text-sm text-gray-500">Previous</div>
                    <div class="text-lg font-bold text-gray-900">{{ formatCurrency(reportData.previous_period.revenue) }}</div>
                  </div>
                  <div class="bg-green-50 p-4 rounded-lg">
                    <div class="text-sm text-gray-500">Variance</div>
                    <div class="text-lg font-bold" :class="reportData.variance.revenue.percentage >= 0 ? 'text-green-600' : 'text-red-600'">
                      {{ reportData.variance.revenue.percentage }}%
                    </div>
                  </div>
                </div>
              </div>

              <!-- Expenses Comparison -->
              <div class="p-6 border-b">
                <h3 class="text-md font-semibold text-gray-900 mb-3">Expenses Comparison</h3>
                <div class="grid grid-cols-3 gap-4">
                  <div class="bg-orange-50 p-4 rounded-lg">
                    <div class="text-sm text-gray-500">Current</div>
                    <div class="text-lg font-bold text-orange-600">{{ formatCurrency(reportData.current_period.expenses) }}</div>
                  </div>
                  <div class="bg-gray-50 p-4 rounded-lg">
                    <div class="text-sm text-gray-500">Previous</div>
                    <div class="text-lg font-bold text-gray-900">{{ formatCurrency(reportData.previous_period.expenses) }}</div>
                  </div>
                  <div class="bg-green-50 p-4 rounded-lg">
                    <div class="text-sm text-gray-500">Variance</div>
                    <div class="text-lg font-bold" :class="reportData.variance.expenses.percentage >= 0 ? 'text-green-600' : 'text-red-600'">
                      {{ reportData.variance.expenses.percentage }}%
                    </div>
                  </div>
                </div>
              </div>

              <!-- Net Income Comparison -->
              <div class="p-6">
                <h3 class="text-md font-semibold text-gray-900 mb-3">Net Income Comparison</h3>
                <div class="grid grid-cols-3 gap-4">
                  <div class="bg-purple-50 p-4 rounded-lg">
                    <div class="text-sm text-gray-500">Current</div>
                    <div class="text-lg font-bold text-purple-600">{{ formatCurrency(reportData.current_period.net_income) }}</div>
                  </div>
                  <div class="bg-gray-50 p-4 rounded-lg">
                    <div class="text-sm text-gray-500">Previous</div>
                    <div class="text-lg font-bold text-gray-900">{{ formatCurrency(reportData.previous_period.net_income) }}</div>
                  </div>
                  <div class="bg-green-50 p-4 rounded-lg">
                    <div class="text-sm text-gray-500">Variance</div>
                    <div class="text-lg font-bold" :class="reportData.variance.net_income.percentage >= 0 ? 'text-green-600' : 'text-red-600'">
                      {{ reportData.variance.net_income.percentage }}%
                    </div>
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
  current_from: new Date(new Date().setFullYear(new Date().getFullYear() - 1)).toISOString().split('T')[0],
  current_to: new Date().toISOString().split('T')[0],
});

const loading = ref(false);
const reportData = ref(null);

const generateReport = async () => {
  loading.value = true;
  try {
    const response = await fetch('/api/v1/reports/comparative/yoy_profit_loss', {
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
  window.open(`/api/v1/reports/comparative/yoy_profit_loss/pdf?current_from=${filters.value.current_from}&current_to=${filters.value.current_to}`, '_blank');
};

const downloadExcel = () => {
  window.open(`/api/v1/reports/comparative/yoy_profit_loss/excel?current_from=${filters.value.current_from}&current_to=${filters.value.current_to}`, '_blank');
};

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('en-BD', { style: 'currency', currency: 'BDT' }).format(amount / 100);
};
</script>
