<template>
  <ThemeProvider>
    <SidebarProvider>
      <AdminLayout>
        <div class="py-6">
          <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-6">
              <h1 class="text-2xl font-semibold text-gray-900">Mushak 6.3 - VAT Invoice</h1>
              <p class="text-gray-600 mt-1">Generate Mushak 6.3 VAT invoice PDF</p>
            </div>

        <!-- Invoice Selection -->
        <div class="bg-white rounded-lg shadow mb-6 p-6">
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Invoice Number</label>
              <input type="text" v-model="invoiceNumber" class="w-full border-gray-300 rounded-md shadow-sm" placeholder="INV-0001" />
            </div>
            <div class="flex items-end">
              <button @click="generateMushak63" :disabled="loading" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded disabled:opacity-50">
                {{ loading ? 'Generating...' : 'Generate PDF' }}
              </button>
            </div>
            <div class="flex items-end">
              <button @click="downloadPdf" :disabled="!mushakData" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded disabled:opacity-50">
                Download PDF
              </button>
            </div>
          </div>
        </div>

        <!-- Preview -->
        <div v-if="mushakData" class="bg-white rounded-lg shadow">
          <div class="p-6 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">Mushak 6.3 Preview</h2>
          </div>
          <div class="p-6">
            <!-- Seller Info -->
            <div class="mb-6 border-b pb-4">
              <h3 class="text-sm font-semibold text-gray-900 mb-2">Seller Information</h3>
              <div class="grid grid-cols-2 gap-4 text-sm">
                <div>
                  <span class="text-gray-500">BIN:</span>
                  <span class="text-gray-900 ml-2">{{ mushakData.seller.bin }}</span>
                </div>
                <div>
                  <span class="text-gray-500">Name:</span>
                  <span class="text-gray-900 ml-2">{{ mushakData.seller.name }}</span>
                </div>
                <div class="col-span-2">
                  <span class="text-gray-500">Address:</span>
                  <span class="text-gray-900 ml-2">{{ mushakData.seller.address }}</span>
                </div>
              </div>
            </div>

            <!-- Buyer Info -->
            <div class="mb-6 border-b pb-4">
              <h3 class="text-sm font-semibold text-gray-900 mb-2">Buyer Information</h3>
              <div class="grid grid-cols-2 gap-4 text-sm">
                <div>
                  <span class="text-gray-500">BIN:</span>
                  <span class="text-gray-900 ml-2">{{ mushakData.buyer.bin || 'N/A' }}</span>
                </div>
                <div>
                  <span class="text-gray-500">Name:</span>
                  <span class="text-gray-900 ml-2">{{ mushakData.buyer.name }}</span>
                </div>
                <div class="col-span-2">
                  <span class="text-gray-500">Address:</span>
                  <span class="text-gray-900 ml-2">{{ mushakData.buyer.address }}</span>
                </div>
              </div>
            </div>

            <!-- Invoice Info -->
            <div class="mb-6 border-b pb-4">
              <h3 class="text-sm font-semibold text-gray-900 mb-2">Invoice Information</h3>
              <div class="grid grid-cols-2 gap-4 text-sm">
                <div>
                  <span class="text-gray-500">Number:</span>
                  <span class="text-gray-900 ml-2">{{ mushakData.invoice.number }}</span>
                </div>
                <div>
                  <span class="text-gray-500">Date:</span>
                  <span class="text-gray-900 ml-2">{{ mushakData.invoice.date }}</span>
                </div>
              </div>
            </div>

            <!-- Items -->
            <div class="mb-6">
              <h3 class="text-sm font-semibold text-gray-900 mb-2">Invoice Items</h3>
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Qty</th>
                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Unit</th>
                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Price</th>
                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Value</th>
                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">VAT Rate</th>
                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">VAT</th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                  <tr v-for="(item, index) in mushakData.items" :key="index">
                    <td class="px-4 py-2 text-sm text-gray-900">{{ item.description }}</td>
                    <td class="px-4 py-2 text-right text-sm text-gray-900">{{ item.quantity }}</td>
                    <td class="px-4 py-2 text-right text-sm text-gray-900">{{ item.unit }}</td>
                    <td class="px-4 py-2 text-right text-sm text-gray-900">{{ formatCurrency(item.unit_price) }}</td>
                    <td class="px-4 py-2 text-right text-sm text-gray-900">{{ formatCurrency(item.value) }}</td>
                    <td class="px-4 py-2 text-right text-sm text-gray-900">{{ item.vat_rate }}%</td>
                    <td class="px-4 py-2 text-right text-sm text-gray-900">{{ formatCurrency(item.vat_amount) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Totals -->
            <div class="bg-gray-50 p-4 rounded-lg">
              <div class="grid grid-cols-4 gap-4 text-sm">
                <div class="flex justify-between">
                  <span class="text-gray-600">Subtotal:</span>
                  <span class="text-gray-900">{{ formatCurrency(mushakData.totals.subtotal) }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-gray-600">VAT:</span>
                  <span class="text-gray-900">{{ formatCurrency(mushakData.totals.vat_amount) }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-gray-600">Discount:</span>
                  <span class="text-gray-900">{{ formatCurrency(mushakData.totals.discount) }}</span>
                </div>
                <div class="flex justify-between font-bold">
                  <span class="text-gray-900">Grand Total:</span>
                  <span class="text-gray-900">{{ formatCurrency(mushakData.totals.grand_total) }}</span>
                </div>
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

const invoiceNumber = ref('');
const loading = ref(false);
const mushakData = ref(null);

const generateMushak63 = async () => {
  if (!invoiceNumber.value) {
    alert('Please enter an invoice number');
    return;
  }

  loading.value = true;
  try {
    const response = await fetch('/api/v1/reports/mushak63', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ invoice_number: invoiceNumber.value }),
    });
    mushakData.value = await response.json();
  } catch (error) {
    console.error('Error generating Mushak 6.3:', error);
    alert('Error generating Mushak 6.3. Please check the invoice number.');
  } finally {
    loading.value = false;
  }
};

const downloadPdf = () => {
  window.open(`/api/v1/reports/mushak63/pdf?invoice_number=${invoiceNumber.value}`, '_blank');
};

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('en-BD', { style: 'currency', currency: 'BDT' }).format(amount / 100);
};
</script>
