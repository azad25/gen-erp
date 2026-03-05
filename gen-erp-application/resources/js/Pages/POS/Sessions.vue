<template>
  <ThemeProvider>
    <SidebarProvider>
      <AdminLayout>
        <div class="space-y-6 p-6">
          <!-- Header -->
          <div class="flex justify-between items-center">
            <div>
              <h1 class="text-3xl font-bold text-gray-900 dark:text-white">POS Sessions</h1>
              <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">Manage and monitor point of sale sessions</p>
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

          <!-- Filters -->
          <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Filters</h3>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Branch</label>
                <select v-model="filters.branch_id" @change="loadSessions" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                  <option value="">All Branches</option>
                  <option v-for="branch in branches" :key="branch.id" :value="branch.id">
                    {{ branch.name }}
                  </option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Status</label>
                <select v-model="filters.status" @change="loadSessions" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                  <option value="">All Status</option>
                  <option value="open">Open</option>
                  <option value="closed">Closed</option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">From Date</label>
                <input
                  v-model="filters.date_from"
                  @change="loadSessions"
                  type="date"
                  class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">To Date</label>
                <input
                  v-model="filters.date_to"
                  @change="loadSessions"
                  type="date"
                  class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
              </div>
            </div>
          </div>

          <!-- Sessions Table -->
          <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="overflow-x-auto">
              <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700">
                  <tr>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Session ID</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Branch</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Opened By</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Opened At</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Closed At</th>
                    <th class="px-6 py-4 text-right text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Opening Cash</th>
                    <th class="px-6 py-4 text-right text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Closing Cash</th>
                    <th class="px-6 py-4 text-right text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Difference</th>
                    <th class="px-6 py-4 text-center text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-4 text-center text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                  <tr v-if="loading">
                    <td colspan="10" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                      <div class="flex items-center justify-center">
                        <svg class="animate-spin h-8 w-8 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                      </div>
                    </td>
                  </tr>
                  <tr v-else-if="sessions.length === 0">
                    <td colspan="10" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                      <div class="flex flex-col items-center">
                        <svg class="w-16 h-16 text-gray-300 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <p class="text-lg font-medium">No sessions found</p>
                        <p class="text-sm mt-1">Try adjusting your filters or create a new session</p>
                      </div>
                    </td>
                  </tr>
                  <tr v-else v-for="session in sessions" :key="session.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                    <td class="px-6 py-4 whitespace-nowrap">
                      <span class="font-mono text-sm font-medium text-gray-900 dark:text-white">#{{ session.id }}</span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">{{ session.branch.name }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">{{ session.opened_by.name }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">{{ formatDateTime(session.opened_at) }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">{{ session.closed_at ? formatDateTime(session.closed_at) : '-' }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-medium text-gray-900 dark:text-white">{{ formatCurrency(session.opening_cash) }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-medium text-gray-900 dark:text-white">{{ session.closing_cash ? formatCurrency(session.closing_cash) : '-' }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-semibold">
                      <span
                        v-if="session.cash_difference !== null"
                        :class="{
                          'text-green-600 dark:text-green-400': session.cash_difference === 0,
                          'text-red-600 dark:text-red-400': session.cash_difference !== 0,
                        }"
                      >
                        {{ formatCurrency(session.cash_difference) }}
                      </span>
                      <span v-else class="text-gray-400">-</span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-center">
                      <span
                        :class="{
                          'px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400': session.status === 'open',
                          'px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300': session.status === 'closed',
                        }"
                      >
                        {{ session.status.toUpperCase() }}
                      </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-center">
                      <button
                        @click="viewSession(session)"
                        class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors"
                      >
                        View Details
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Pagination -->
            <div v-if="pagination.total > 0" class="px-6 py-4 bg-gray-50 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700 flex justify-between items-center">
              <p class="text-sm text-gray-600 dark:text-gray-400">
                Showing <span class="font-medium">{{ pagination.from }}</span> to <span class="font-medium">{{ pagination.to }}</span> of <span class="font-medium">{{ pagination.total }}</span> sessions
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

        <!-- View Session Modal -->
        <Modal v-model="showSessionModal" title="Session Details" size="lg">
          <div v-if="selectedSession" class="space-y-6">
            <div class="grid grid-cols-2 gap-6">
              <div class="space-y-1">
                <label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Session ID</label>
                <p class="text-lg font-semibold text-gray-900 dark:text-white">#{{ selectedSession.id }}</p>
              </div>
              <div class="space-y-1">
                <label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Branch</label>
                <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ selectedSession.branch.name }}</p>
              </div>
              <div class="space-y-1">
                <label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Opened By</label>
                <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ selectedSession.opened_by.name }}</p>
              </div>
              <div class="space-y-1">
                <label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Opened At</label>
                <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ formatDateTime(selectedSession.opened_at) }}</p>
              </div>
              <div v-if="selectedSession.closed_by" class="space-y-1">
                <label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Closed By</label>
                <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ selectedSession.closed_by.name }}</p>
              </div>
              <div v-if="selectedSession.closed_at" class="space-y-1">
                <label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Closed At</label>
                <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ formatDateTime(selectedSession.closed_at) }}</p>
              </div>
            </div>

            <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
              <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-4 uppercase tracking-wider">Cash Summary</h4>
              <div class="grid grid-cols-2 gap-6">
                <div class="space-y-1">
                  <label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Opening Cash</label>
                  <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ formatCurrency(selectedSession.opening_cash) }}</p>
                </div>
                <div v-if="selectedSession.closing_cash" class="space-y-1">
                  <label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Closing Cash</label>
                  <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ formatCurrency(selectedSession.closing_cash) }}</p>
                </div>
                <div v-if="selectedSession.expected_cash" class="space-y-1">
                  <label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Expected Cash</label>
                  <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ formatCurrency(selectedSession.expected_cash) }}</p>
                </div>
                <div v-if="selectedSession.cash_difference !== null" class="space-y-1">
                  <label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Cash Difference</label>
                  <p
                    class="text-2xl font-bold"
                    :class="{
                      'text-green-600 dark:text-green-400': selectedSession.cash_difference === 0,
                      'text-red-600 dark:text-red-400': selectedSession.cash_difference !== 0,
                    }"
                  >
                    {{ formatCurrency(selectedSession.cash_difference) }}
                  </p>
                </div>
              </div>
            </div>

            <div v-if="selectedSession.notes" class="border-t border-gray-200 dark:border-gray-700 pt-6">
              <label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider block mb-2">Notes</label>
              <p class="text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-900 p-4 rounded-lg">{{ selectedSession.notes }}</p>
            </div>
          </div>
        </Modal>
      </AdminLayout>
    </SidebarProvider>
  </ThemeProvider>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { Link } from '@inertiajs/vue3';
import ThemeProvider from '@/Components/Layout/ThemeProvider.vue';
import SidebarProvider from '@/Components/Layout/SidebarProvider.vue';
import AdminLayout from '@/Components/Layout/AdminLayout.vue';
import Card from '@/Components/ui/Card.vue';
import Modal from '@/Components/ui/Modal.vue';
import { formatCurrency, formatDateTime } from '@/utils/formatters';
import api from '@/Services/api';

const sessions = ref([]);
const branches = ref([]);
const loading = ref(false);
const showSessionModal = ref(false);
const selectedSession = ref(null);

const filters = ref({
  branch_id: '',
  status: '',
  date_from: '',
  date_to: '',
});

const pagination = ref({
  current_page: 1,
  last_page: 1,
  per_page: 15,
  total: 0,
  from: 0,
  to: 0,
});

const loadSessions = async (page = 1) => {
  loading.value = true;
  try {
    const params = {
      page,
      ...filters.value,
    };
    const response = await api.get('/v1/pos/sessions', { params });
    sessions.value = response.data.data;
    pagination.value = response.data.meta;
  } catch (error) {
    console.error('Failed to load sessions:', error);
  } finally {
    loading.value = false;
  }
};

const loadBranches = async () => {
  try {
    const response = await api.get('/v1/branches');
    branches.value = response.data.data;
  } catch (error) {
    console.error('Failed to load branches:', error);
  }
};

const viewSession = (session) => {
  selectedSession.value = session;
  showSessionModal.value = true;
};

const changePage = (page) => {
  if (page >= 1 && page <= pagination.value.last_page) {
    loadSessions(page);
  }
};

onMounted(async () => {
  await Promise.all([
    loadSessions(),
    loadBranches(),
  ]);
});
</script>
