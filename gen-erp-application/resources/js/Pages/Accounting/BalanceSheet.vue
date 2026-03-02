<template>
  <ThemeProvider>
    <SidebarProvider>
      <AdminLayout>
        <div class="space-y-6">
          <div class="flex items-center justify-between">
            <div>
              <h1 class="text-xl font-bold text-black">Balance Sheet</h1>
              <p class="text-sm text-gray-1">View balance sheet for your business</p>
            </div>
            <div class="flex items-center gap-2">
              <Button variant="secondary" size="sm" @click="exportData">Export</Button>
              <Button size="sm" @click="generateReport">Generate</Button>
            </div>
          </div>

          <div v-if="loading" class="flex items-center justify-center py-16">
            <p class="text-sm text-gray-1">Loading...</p>
          </div>

          <div v-else-if="balanceSheet" class="space-y-6">
            <div class="grid grid-cols-2 gap-6">
              <Card>
                <h3 class="font-semibold mb-4">Assets</h3>
                <div class="space-y-4">
                  <div class="flex justify-between">
                    <span class="text-sm">Total Assets</span>
                    <span class="font-bold">{{ formatCurrency(balanceSheet.total_assets || 0) }}</span>
                  </div>
                  <div v-for="section in balanceSheet.asset_sections" :key="section.id">
                    <div class="font-medium text-sm mb-2 mt-4">{{ section.name }}</div>
                    <div v-for="account in section.accounts" :key="account.id" class="flex justify-between text-sm py-1">
                      <span>{{ account.name }}</span>
                      <span>{{ formatCurrency(account.balance) }}</span>
                    </div>
                  </div>
                </div>
              </Card>

              <Card>
                <h3 class="font-semibold mb-4">Liabilities & Equity</h3>
                <div class="space-y-4">
                  <div class="flex justify-between">
                    <span class="text-sm">Total Liabilities & Equity</span>
                    <span class="font-bold">{{ formatCurrency(balanceSheet.total_liabilities_equity || 0) }}</span>
                  </div>
                  <div v-for="section in balanceSheet.liability_sections" :key="section.id">
                    <div class="font-medium text-sm mb-2 mt-4">{{ section.name }}</div>
                    <div v-for="account in section.accounts" :key="account.id" class="flex justify-between text-sm py-1">
                      <span>{{ account.name }}</span>
                      <span>{{ formatCurrency(account.balance) }}</span>
                    </div>
                  </div>
                </div>
              </Card>
            </div>

            <Card>
              <h3 class="font-semibold mb-4">Summary</h3>
              <div class="grid grid-cols-3 gap-4">
                <div class="text-center">
                  <p class="text-sm text-gray-1">Total Assets</p>
                  <p class="text-2xl font-bold">{{ formatCurrency(balanceSheet.total_assets || 0) }}</p>
                </div>
                <div class="text-center">
                  <p class="text text-gray-1">Total Liabilities</p>
                  <p class="text-2xl font-bold">{{ formatCurrency(balanceSheet.total_liabilities || 0) }}</p>
                </div>
                <div class="text-center">
                  <p class="text-sm text-gray-1">Total Equity</p>
                  <p class="text-2xl font-bold">{{ formatCurrency(balanceSheet.total_equity || 0) }}</p>
                </div>
              </div>
            </Card>
          </div>
        </div>
      </AdminLayout>
    </SidebarProvider>
  </ThemeProvider>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '@/Services/api.js'
import ThemeProvider from '@/Components/Layout/ThemeProvider.vue'
import SidebarProvider from '@/Components/Layout/SidebarProvider.vue'
import AdminLayout from '@/Components/layout/AdminLayout.vue'
import Card from '@/Components/ui/Card.vue'
import Button from '@/Components/ui/Button.vue'

const balanceSheet = ref(null)
const loading = ref(false)

const generateReport = async () => {
  loading.value = true
  try {
    const response = await api.get('/accounting/balance-sheet')
    balanceSheet.value = response.data
  } catch (error) {
    console.error('Failed to generate balance sheet:', error)
  } finally {
    loading.value = false
  }
}

const exportData = () => {
  window.print()
}

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(amount / 100)
}

onMounted(() => {
  generateReport()
})
</script>
