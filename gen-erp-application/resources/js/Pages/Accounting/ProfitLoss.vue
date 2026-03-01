<template>
  <ThemeProvider>
    <SidebarProvider>
      <AdminLayout>
        <div class="space-y-6">
          <div class="flex items-center justify-between">
            <div>
              <h1 class="text-xl font-bold text-black">Profit & Loss</h1>
              <p class="text-sm text-gray-1">View profit & loss statement for your business</p>
            </div>
            <div class="flex items-center gap-2">
              <Button variant="secondary" size="sm" @click="exportData">Export</Button>
              <Button size="sm" @click="generateReport">Generate</Button>
            </div>
          </div>

          <div v-if="loading" class="flex items-center justify-center py-16">
            <p class="text-sm text-gray-1">Loading...</p>
          </div>

          <div v-else-if="profitLoss" class="space-y-6">
            <Card>
              <h3 class="font-semibold mb-4">Summary</h3>
              <div class="grid grid-cols-3 gap-4">
                <div class="text-center">
                  <p class="text-sm text-gray-1">Revenue</p>
                  <p class="text-2xl font-bold text-green-600">{{ formatCurrency(profitLoss.revenue || 0) }}</p>
                </div>
                <div class="text-center">
                  <p class="text-sm text-gray-1">Expenses</p>
                  <p class="text-2xl font-bold text-red-600">{{ formatCurrency(profitLoss.expenses || 0) }}</p>
                </div>
                <div class="text-center">
                  <p class="text-sm text-gray-1">Net Profit</p>
                  <p class="text-2xl font-bold" :class="(profitLoss.revenue - profitLoss.expenses) >= 0 ? 'text-green-600' : 'text-red-600'">{{ formatCurrency(profitLoss.revenue - profitLoss.expenses) }}</p>
                </div>
              </div>
            </Card>

            <Card>
              <h3 class="font-semibold mb-4">Revenue Breakdown</h3>
              <table class="w-full">
                <thead>
                  <tr class="border-b">
                    <th class="text-left py-2">Account</th>
                    <th class="text-right py-2">Amount</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="item in profitLoss.revenue_items" :key="item.id" class="border-b">
                    <td class="py-2">{{ item.account?.name }}</td>
                    <td class="text-right py-2">{{ formatCurrency(item.amount) }}</td>
                  </tr>
                </tbody>
              </table>
            </Card>

            <Card>
              <h3 class="font-semibold mb-4">Expense Breakdown</h3>
              <table class="w-full">
                <thead>
                  <tr class="border-b">
                    <th class="text-left py-2">Account</th>
                    <th class="text-right py-2">Amount</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="item in profitLoss.expense_items" :key="item.id" class="border-b">
                    <td class="py-2">{{ item.account?.name }}</td>
                    <td class="text-right py-2">{{ formatCurrency(item.amount) }}</td>
                  </tr>
                </tbody>
              </table>
            </Card>
          </div>
        </div>
      </AdminLayout>
    </SidebarProvider>
  </ThemeProvider>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import ThemeProvider from '@/Components/Layout/ThemeProvider.vue'
import SidebarProvider from '@/Components/Layout/SidebarProvider.vue'
import AdminLayout from '@/Components/layout/AdminLayout.vue'
import Card from '@/Components/ui/Card.vue'
import Button from '@/Components/ui/Button.vue'

const profitLoss = ref(null)
const loading = ref(false)

const generateReport = async () => {
  loading.value = true
  try {
    const response = await axios.get('/api/v1/accounting/profit-loss')
    profitLoss.value = response.data
  } catch (error) {
    console.error('Failed to generate profit & loss:', error)
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
