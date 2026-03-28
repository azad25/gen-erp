<template>
  
    
      <AppLayout>
        <div class="space-y-6 p-6">
          <!-- Header -->
          <div class="flex justify-between items-center">
            <div>
              <h1 class="text-3xl font-bold text-gray-900 dark:text-white">POS Sales</h1>
              <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">View and manage all point of sale transactions</p>
            </div>
            <div class="flex gap-3">
              <Link href="/pos/dashboard" class="px-4 py-2 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors">
                Dashboard
              </Link>
              <Link href="/pos/terminal" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium shadow-sm">
                Open Terminal
              </Link>
            </div>
          </div>

          <!-- Sales Table -->
          <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="overflow-x-auto">
              <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700">
                  <tr>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Sale #</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Date</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Customer</th>
                    <th class="px-6 py-4 text-center text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Items</th>
                    <th class="px-6 py-4 text-right text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Subtotal</th>
                    <th class="px-6 py-4 text-right text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Tax</th>
                    <th class="px-6 py-4 text-right text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Total</th>
                    <th class="px-6 py-4 text-right text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Tendered</th>
                    <th class="px-6 py-4 text-right text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Change</th>
                    <th class="px-6 py-4 text-center text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-4 text-center text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                  <tr v-if="loading">
                    <td colspan="11" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                      <div class="flex items-center justify-center">
                        <svg class="animate-spin h-8 w-8 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                      </div>
                    </td>
                  </tr>
                  <tr v-else-if="sales.length === 0">
                    <td colspan="11" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                      <div class="flex flex-col items-center">
                        <svg class="w-16 h-16 text-gray-300 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        <p class="text-lg font-medium">No sales found</p>
                        <p class="text-sm mt-1">Start making sales from the POS terminal</p>
                      </div>
                    </td>
                  </tr>
                  <tr v-else v-for="sale in sales" :key="sale.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                    <td class="px-6 py-4 whitespace-nowrap">
                      <span class="font-mono text-sm font-medium text-blue-600 dark:text-blue-400">{{ sale.sale_number }}</span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">{{ formatDateTime(sale.sale_date) }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">{{ sale.customer?.name || 'Walk-in' }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-center">
                      <span class="px-2 py-1 text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-full">
                        {{ sale.items?.length || 0 }}
                      </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-700 dark:text-gray-300">{{ formatCurrency(sale.subtotal) }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-700 dark:text-gray-300">{{ formatCurrency(sale.tax_amount) }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-bold text-gray-900 dark:text-white">{{ formatCurrency(sale.total_amount) }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-700 dark:text-gray-300">{{ formatCurrency(sale.amount_tendered) }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-green-600 dark:text-green-400 font-medium">{{ formatCurrency(sale.change_amount) }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-center">
                      <span
                        :class="{
                          'px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400': sale.status === 'completed',
                          'px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400': sale.status === 'voided',
                        }"
                      >
                        {{ sale.status.toUpperCase() }}
                      </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-center">
                      <div class="flex gap-2 justify-center">
                        <button
                          @click="viewSale(sale)"
                          class="px-3 py-1.5 bg-blue-600 text-white text-xs font-medium rounded-lg hover:bg-blue-700 transition-colors"
                        >
                          View
                        </button>
                        <button
                          v-if="sale.status === 'completed'"
                          @click="voidSale(sale)"
                          class="px-3 py-1.5 bg-red-600 text-white text-xs font-medium rounded-lg hover:bg-red-700 transition-colors"
                        >
                          Void
                        </button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Pagination -->
            <div v-if="pagination.total > 0" class="px-6 py-4 bg-gray-50 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700 flex justify-between items-center">
              <p class="text-sm text-gray-600 dark:text-gray-400">
                Showing <span class="font-medium">{{ pagination.from }}</span> to <span class="font-medium">{{ pagination.to }}</span> of <span class="font-medium">{{ pagination.total }}</span> sales
              </p>
              <div class="flex gap-2">
                <button
                  @click="changePage(pagination.current_page - 1)"
                  :disabled="pagination.current_page === 1"
                  class="px-4 py-2 text-sm font-medium rounded-lg transition-colors"
                  :class="pagination.current_page === 1 ? 'bg-gray-100 dark:bg-gray-800 text-gray-400 cursor-not-allowed' : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 border border-gray-300 dark:border-gray-600'"
                >
                  Previous
                </button>
                <button
                  @click="changePage(pagination.current_page + 1)"
                  :disabled="pagination.current_page === pagination.last_page"
                  class="px-4 py-2 text-sm font-medium rounded-lg transition-colors"
                  :class="pagination.current_page === pagination.last_page ? 'bg-gray-100 dark:bg-gray-800 text-gray-400 cursor-not-allowed' : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 border border-gray-300 dark:border-gray-600'"
                >
                  Next
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- View Sale Modal -->
        <Modal v-model="showSaleModal" title="Sale Details" size="lg">
          <div v-if="selectedSale" class="space-y-6">
            <div class="grid grid-cols-2 gap-6">
              <div class="space-y-1">
                <label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Sale Number</label>
                <p class="text-lg font-semibold text-blue-600 dark:text-blue-400 font-mono">{{ selectedSale.sale_number }}</p>
              </div>
              <div class="space-y-1">
                <label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Date</label>
                <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ formatDateTime(selectedSale.sale_date) }}</p>
              </div>
              <div class="space-y-1">
                <label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Customer</label>
                <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ selectedSale.customer?.name || 'Walk-in Customer' }}</p>
              </div>
              <div class="space-y-1">
                <label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</label>
                <p>
                  <span
                    :class="{
                      'px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400': selectedSale.status === 'completed',
                      'px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400': selectedSale.status === 'voided',
                    }"
                  >
                    {{ selectedSale.status.toUpperCase() }}
                  </span>
                </p>
              </div>
            </div>

            <!-- Items -->
            <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
              <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-4 uppercase tracking-wider">Items</h4>
              <div class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                <table class="w-full text-sm">
                  <thead class="bg-gray-50 dark:bg-gray-900">
                    <tr>
                      <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase">Product</th>
                      <th class="px-4 py-3 text-right text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase">Qty</th>
                      <th class="px-4 py-3 text-right text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase">Price</th>
                      <th class="px-4 py-3 text-right text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase">Total</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    <tr v-for="item in selectedSale.items" :key="item.id">
                      <td class="px-4 py-3 text-gray-900 dark:text-white">{{ item.description }}</td>
                      <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-300">{{ item.quantity }}</td>
                      <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-300">{{ formatCurrency(item.unit_price) }}</td>
                      <td class="px-4 py-3 text-right font-semibold text-gray-900 dark:text-white">{{ formatCurrency(item.line_total) }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- Totals -->
            <div class="border-t border-gray-200 dark:border-gray-700 pt-6 space-y-3">
              <div class="flex justify-between text-sm">
                <span class="text-gray-600 dark:text-gray-400">Subtotal:</span>
                <span class="font-semibold text-gray-900 dark:text-white">{{ formatCurrency(selectedSale.subtotal) }}</span>
              </div>
              <div class="flex justify-between text-sm">
                <span class="text-gray-600 dark:text-gray-400">Discount:</span>
                <span class="font-semibold text-gray-900 dark:text-white">{{ formatCurrency(selectedSale.discount_amount) }}</span>
              </div>
              <div class="flex justify-between text-sm">
                <span class="text-gray-600 dark:text-gray-400">Tax:</span>
                <span class="font-semibold text-gray-900 dark:text-white">{{ formatCurrency(selectedSale.tax_amount) }}</span>
              </div>
              <div class="flex justify-between text-lg font-bold border-t border-gray-200 dark:border-gray-700 pt-3">
                <span class="text-gray-900 dark:text-white">Total:</span>
                <span class="text-gray-900 dark:text-white">{{ formatCurrency(selectedSale.total_amount) }}</span>
              </div>
              <div class="flex justify-between text-sm bg-gray-50 dark:bg-gray-900 p-3 rounded-lg">
                <span class="text-gray-600 dark:text-gray-400">Amount Tendered:</span>
                <span class="font-semibold text-gray-900 dark:text-white">{{ formatCurrency(selectedSale.amount_tendered) }}</span>
              </div>
              <div class="flex justify-between text-sm bg-green-50 dark:bg-green-900/20 p-3 rounded-lg">
                <span class="text-green-700 dark:text-green-400 font-medium">Change:</span>
                <span class="font-bold text-green-700 dark:text-green-400">{{ formatCurrency(selectedSale.change_amount) }}</span>
              </div>
            </div>
          </div>
        </Modal>
      </AppLayout>
    
  
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { Link } from '@inertiajs/vue3';
import AppLayout from "@/Layouts/AppLayout.vue"
import Card from '@/Components/ui/Card.vue';
import Modal from '@/Components/ui/Modal.vue';
import { formatCurrency, formatDateTime } from '@/utils/formatters';
import api from '@/Services/api';

const sales = ref([]);
const loading = ref(false);
const showSaleModal = ref(false);
const selectedSale = ref(null);

const pagination = ref({
  current_page: 1,
  last_page: 1,
  per_page: 20,
  total: 0,
  from: 0,
  to: 0,
});

const loadSales = async (page = 1) => {
  loading.value = true;
  try {
    // Get active session first
    const sessionsResponse = await api.get('/v1/pos/sessions/active');
    if (sessionsResponse.data.data.length === 0) {
      sales.value = [];
      return;
    }
    
    const sessionId = sessionsResponse.data.data[0].id;
    const response = await api.get(`/v1/pos/sessions/${sessionId}/sales`, { params: { page } });
    sales.value = response.data.data;
    pagination.value = response.data.meta;
  } catch (error) {
    console.error('Failed to load sales:', error);
  } finally {
    loading.value = false;
  }
};

const viewSale = async (sale) => {
  try {
    const response = await api.get(`/v1/pos/sales/${sale.id}`);
    selectedSale.value = response.data.data;
    showSaleModal.value = true;
  } catch (error) {
    console.error('Failed to load sale details:', error);
  }
};

const voidSale = async (sale) => {
  if (!confirm('Are you sure you want to void this sale?')) return;
  
  try {
    await api.post(`/v1/pos/sales/${sale.id}/void`);
    await loadSales(pagination.value.current_page);
    alert('Sale voided successfully');
  } catch (error) {
    console.error('Failed to void sale:', error);
    alert('Failed to void sale');
  }
};

const changePage = (page) => {
  if (page >= 1 && page <= pagination.value.last_page) {
    loadSales(page);
  }
};

onMounted(() => {
  loadSales();
});
</script>
