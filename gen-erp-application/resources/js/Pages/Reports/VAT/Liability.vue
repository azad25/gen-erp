<template>
  
    
      <AppLayout>
        <div class="py-6">
          <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-6">
              <h1 class="text-2xl font-semibold text-gray-900">VAT Liability</h1>
              <p class="text-gray-600 mt-1">View your VAT liability report</p>
            </div>

        <!-- Filters -->
        <div class="bg-white rounded-lg shadow mb-6 p-6">
          <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Month</label>
              <select v-model="filters.month" class="w-full border-gray-300 rounded-md shadow-sm">
                <option value="1">January</option>
                <option value="2">February</option>
                <option value="3">March</option>
                <option value="4">April</option>
                <option value="5">May</option>
                <option value="6">June</option>
                <option value="7">July</option>
                <option value="8">August</option>
                <option value="9">September</option>
                <option value="10">October</option>
                <option value="11">November</option>
                <option value="12">December</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Year</label>
              <input type="number" v-model="filters.year" class="w-full border-gray-300 rounded-md shadow-sm" />
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
            <h2 class="text-lg font-semibold text-gray-900">VAT Liability for {{ reportData.period }}</h2>
          </div>
          
          <!-- Summary -->
          <div class="p-6 bg-gray-50 border-b">
            <div class="grid grid-cols-4 gap-4 text-center">
              <div>
                <div class="text-sm text-gray-500">Output VAT</div>
                <div class="text-lg font-bold text-blue-600">{{ formatCurrency(reportData.output_vat.total_vat) }}</div>
              </div>
              <div>
                <div class="text-sm text-gray-500">Input VAT (Credit)</div>
                <div class="text-lg font-bold text-green-600">{{ formatCurrency(reportData.input_vat.total_vat) }}</div>
              </div>
              <div>
                <div class="text-sm text-gray-500">Adjustments</div>
                <div class="text-lg font-bold text-orange-600">{{ formatCurrency(reportData.adjustments.credit_note_vat) }}</div>
              </div>
              <div>
                <div class="text-sm text-gray-500">Net VAT Payable</div>
                <div class="text-2xl font-bold" :class="reportData.summary.net_vat_payable > 0 ? 'text-red-600' : 'text-green-600'">
                  {{ formatCurrency(reportData.summary.net_vat_payable) }}
                </div>
              </div>
            </div>
          </div>

          <!-- Details -->
          <div class="p-6 grid grid-cols-2 gap-6">
            <!-- Output VAT -->
            <div>
              <h3 class="text-md font-semibold text-gray-900 mb-3 border-b pb-2">Output VAT (Sales)</h3>
              <table class="min-w-full">
                <tbody>
                  <tr>
                    <td class="py-2 text-sm text-gray-600">Total Sales</td>
                    <td class="py-2 text-right text-sm text-gray-900">{{ formatCurrency(reportData.output_vat.total_sales) }}</td>
                  </tr>
                  <tr>
                    <td class="py-2 text-sm text-gray-600">Total VAT</td>
                    <td class="py-2 text-right text-sm text-gray-900">{{ formatCurrency(reportData.output_vat.total_vat) }}</td>
                  </tr>
                  <tr>
                    <td class="py-2 text-sm text-gray-600">Invoices</td>
                    <td class="py-2 text-right text-sm text-gray-900">{{ reportData.output_vat.invoice_count }}</td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Input VAT -->
            <div>
              <h3 class="text-md font-semibold text-gray-900 mb-3 border-b pb-2">Input VAT (Purchases)</h3>
              <table class="min-w-full">
                <tbody>
                  <tr>
                    <td class="py-2 text-sm text-gray-600">Total Purchases</td>
                    <td class="py-2 text-right text-sm text-gray-900">{{ formatCurrency(reportData.input_vat.total_purchases) }}</td>
                  </tr>
                  <tr>
                    <td class="py-2 text-sm text-gray-600">Total VAT</td>
                    <td class="py-2 text-right text-sm text-gray-900">{{ formatCurrency(reportData.input_vat.total_vat) }}</td>
                  </tr>
                  <tr>
                    <td class="py-2 text-sm text-gray-600">Receipts</td>
                    <td class="py-2 text-right text-sm text-gray-900">{{ reportData.input_vat.receipt_count }}</td>
                  </tr>
                </tbody>
              </table>
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
  month: new Date().getMonth() + 1,
  year: new Date().getFullYear(),
});

const loading = ref(false);
const reportData = ref(null);

const generateReport = async () => {
  loading.value = true;
  try {
    const response = await fetch('/api/v1/reports/vat_liability', {
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
  window.open(`/api/v1/reports/vat_liability/pdf?month=${filters.value.month}&year=${filters.value.year}`, '_blank');
};

const downloadExcel = () => {
  window.open(`/api/v1/reports/vat_liability/excel?month=${filters.value.month}&year=${filters.value.year}`, '_blank');
};

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('en-BD', { style: 'currency', currency: 'BDT' }).format(amount / 100);
};
</script>
