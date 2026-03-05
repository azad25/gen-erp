<template>
  <ThemeProvider>
    <SidebarProvider>
      <AdminLayout>
        <div class="py-6">
          <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-6">
              <h1 class="text-2xl font-semibold text-gray-900">Reports Dashboard</h1>
              <p class="text-gray-600 mt-1">Generate and manage your business reports</p>
            </div>

        <!-- Quick Stats -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
          <div class="bg-white rounded-lg shadow p-4">
            <div class="text-sm text-gray-500">Financial Reports</div>
            <div class="text-2xl font-bold text-gray-900">{{ stats.financial }}</div>
          </div>
          <div class="bg-white rounded-lg shadow p-4">
            <div class="text-sm text-gray-500">Aging Reports</div>
            <div class="text-2xl font-bold text-gray-900">{{ stats.aging }}</div>
          </div>
          <div class="bg-white rounded-lg shadow p-4">
            <div class="text-sm text-gray-500">VAT Reports</div>
            <div class="text-2xl font-bold text-gray-900">{{ stats.vat }}</div>
          </div>
          <div class="bg-white rounded-lg shadow p-4">
            <div class="text-sm text-gray-500">Custom Reports</div>
            <div class="text-2xl font-bold text-gray-900">{{ stats.custom }}</div>
          </div>
        </div>

        <!-- Quick Access -->
        <div class="bg-white rounded-lg shadow mb-6">
          <div class="p-6 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">Quick Access</h2>
          </div>
          <div class="p-6">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
              <Link :href="route('reports.financial.trial-balance')" class="bg-blue-50 hover:bg-blue-100 p-4 rounded-lg text-center">
                <div class="text-blue-600 font-semibold">Trial Balance</div>
              </Link>
              <Link :href="route('reports.financial.profit-loss')" class="bg-green-50 hover:bg-green-100 p-4 rounded-lg text-center">
                <div class="text-green-600 font-semibold">Profit & Loss</div>
              </Link>
              <Link :href="route('reports.financial.balance-sheet')" class="bg-purple-50 hover:bg-purple-100 p-4 rounded-lg text-center">
                <div class="text-purple-600 font-semibold">Balance Sheet</div>
              </Link>
              <Link :href="route('reports.financial.cash-flow')" class="bg-orange-50 hover:bg-orange-100 p-4 rounded-lg text-center">
                <div class="text-orange-600 font-semibold">Cash Flow</div>
              </Link>
              <Link :href="route('reports.aging.ar')" class="bg-red-50 hover:bg-red-100 p-4 rounded-lg text-center">
                <div class="text-red-600 font-semibold">AR Aging</div>
              </Link>
              <Link :href="route('reports.aging.ap')" class="bg-yellow-50 hover:bg-yellow-100 p-4 rounded-lg text-center">
                <div class="text-yellow-600 font-semibold">AP Aging</div>
              </Link>
              <Link :href="route('reports.vat.liability')" class="bg-indigo-50 hover:bg-indigo-100 p-4 rounded-lg text-center">
                <div class="text-indigo-600 font-semibold">VAT Liability</div>
              </Link>
              <Link :href="route('reports.builder.index')" class="bg-gray-50 hover:bg-gray-100 p-4 rounded-lg text-center">
                <div class="text-gray-600 font-semibold">Report Builder</div>
              </Link>
            </div>
          </div>
        </div>

        <!-- Recent Reports -->
        <div class="bg-white rounded-lg shadow">
          <div class="p-6 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">Recent Reports</h2>
          </div>
          <div class="p-6">
            <div v-if="recentReports.length === 0" class="text-center text-gray-500 py-8">
              No reports generated yet
            </div>
            <div v-else class="space-y-4">
              <div v-for="report in recentReports" :key="report.id" class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                <div>
                  <div class="font-medium text-gray-900">{{ report.name }}</div>
                  <div class="text-sm text-gray-500">{{ report.generated_at }}</div>
                </div>
                <div class="flex space-x-2">
                  <button @click="viewReport(report)" class="text-blue-600 hover:text-blue-900">View</button>
                  <button @click="downloadReport(report)" class="text-green-600 hover:text-green-900">Download</button>
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
import { Link } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import ThemeProvider from '@/Components/Layout/ThemeProvider.vue';
import SidebarProvider from '@/Components/Layout/SidebarProvider.vue';
import AdminLayout from '@/Components/Layout/AdminLayout.vue';

const stats = ref({
  financial: 4,
  aging: 2,
  vat: 5,
  custom: 0,
});

const recentReports = ref([]);

onMounted(async () => {
  // Fetch recent reports
  const response = await fetch('/api/v1/reports/recent');
  recentReports.value = await response.json();
});

const viewReport = (report) => {
  window.open(report.url, '_blank');
};

const downloadReport = (report) => {
  window.open(report.download_url, '_blank');
};
</script>
