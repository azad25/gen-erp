<template>
  
    
      <AppLayout>
        <div class="py-6">
          <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-6">
              <h1 class="text-2xl font-semibold text-gray-900">Inventory Valuation</h1>
              <p class="text-gray-600 mt-1">View your inventory valuation report</p>
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
            <h2 class="text-lg font-semibold text-gray-900">Inventory Valuation as of {{ formatDate(filters.as_of_date) }}</h2>
          </div>
          
          <!-- Summary -->
          <div class="p-6 bg-gray-50 border-b">
            <div class="grid grid-cols-5 gap-4 text-center">
              <div>
                <div class="text-sm text-gray-500">Total Quantity</div>
                <div class="text-lg font-bold text-gray-900">{{ reportData.summary.total_quantity }}</div>
              </div>
              <div>
                <div class="text-sm text-gray-500">Total Value</div>
                <div class="text-lg font-bold text-gray-900">{{ formatCurrency(reportData.summary.total_value) }}</div>
              </div>
              <div>
                <div class="text-sm text-gray-500">Products</div>
                <div class="text-lg font-bold text-gray-900">{{ reportData.summary.product_count }}</div>
              </div>
              <div>
                <div class="text-sm text-gray-500">Warehouses</div>
                <div class="text-lg font-bold text-gray-900">{{ reportData.summary.warehouse_count }}</div>
              </div>
              <div>
                <div class="text-sm text-gray-500">Avg Unit Cost</div>
                <div class="text-lg font-bold text-gray-900">{{ formatCurrency(reportData.summary.average_unit_cost) }}</div>
              </div>
            </div>
          </div>

          <!-- Product Details -->
          <div class="p-6">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                  <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Quantity</th>
                  <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total Value</th>
                  <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Avg Cost</th>
                  <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Warehouses</th>
                  <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Layers</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="product in reportData.products" :key="product.product_id">
                  <td class="px-4 py-3 text-sm text-gray-900">
                    <div class="font-medium">{{ product.product_name }}</div>
                    <div class="text-xs text-gray-500">{{ product.product_code }} | {{ product.unit }}</div>
                  </td>
                  <td class="px-4 py-3 text-right text-sm text-gray-900">{{ product.total_quantity }}</td>
                  <td class="px-4 py-3 text-right text-sm font-bold text-gray-900">{{ formatCurrency(product.total_value) }}</td>
                  <td class="px-4 py-3 text-right text-sm text-gray-600">{{ formatCurrency(product.average_unit_cost) }}</td>
                  <td class="px-4 py-3 text-center text-sm text-gray-600">{{ product.warehouse_breakdown.length }}</td>
                  <td class="px-4 py-3 text-center text-sm text-gray-600">{{ product.layer_count }}</td>
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
    const response = await fetch('/api/v1/reports/inventory_valuation', {
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
  window.open(`/api/v1/reports/inventory_valuation/pdf?as_of_date=${filters.value.as_of_date}`, '_blank');
};

const downloadExcel = () => {
  window.open(`/api/v1/reports/inventory_valuation/excel?as_of_date=${filters.value.as_of_date}`, '_blank');
};

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('en-GB');
};

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('en-BD', { style: 'currency', currency: 'BDT' }).format(amount / 100);
};
</script>
