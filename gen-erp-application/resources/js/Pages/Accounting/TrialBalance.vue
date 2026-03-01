<template>
  <ThemeProvider>
    <SidebarProvider>
      <AdminLayout>
        <div class="space-y-6">
          <div class="flex items-center justify-between">
            <div>
              <h1 class="text-xl font-bold text-black">Trial Balance</h1>
              <p class="text-sm text-gray-1">View trial balance for your business</p>
            </div>
            <div class="flex items-center gap-2">
              <Button variant="secondary" size="sm" @click="exportData">Export</Button>
              <Button size="sm" @click="generateReport">Generate</Button>
            </div>
          </div>

          <div v-if="loading" class="flex items-center justify-center py-16">
            <p class="text-sm text-gray-1">Loading...</p>
          </div>

          <div v-else-if="trialBalance" class="space-y-6">
            <div class="grid grid-cols-2 gap-4">
              <Card>
                <h3 class="font-semibold mb-4">Total Debit</h3>
                <p class="text-2xl font-bold text-green-600">{{ formatCurrency(trialBalance.total_debit || 0) }}</p>
              </Card>
              <Card>
                <h3 class="font-semibold mb-4">Total Credit</h3>
                <p class="text-2xl font-bold text-red-600">{{ formatCurrency(trialBalance.total_credit || 0) }}</p>
              </Card>
            </div>

            <Card>
              <h3 class="font-semibold mb-4">Accounts</h3>
              <table class="w-full">
                <thead>
                  <tr class="border-b">
                    <th class="text-left py-2">Code</th>
                    <th class="text-left py-2">Name</th>
                    <th class="text-right py-2">Debit</th>
                    <th class="text-right py-2">Credit</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="account in trialBalance.accounts" :key="account.id" class="border-b">
                    <td class="py-2">{{ account.code }}</td>
                    <td class="py-2">{{ account.name }}</td>
                    <td class="text-right py-2">{{ formatCurrency(account.debit || 0) }}</td>
                    <td class="text-right py-2">{{ formatCurrency(account.credit || 0) }}</td>
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

const trialBalance = ref(null)
const loading = ref(false)

const generateReport = async () => {
  loading.value = true
  try {
    const response = await axios.get('/api/v1/accounting/trial-balance')
    trialBalance.value = response.data
  } catch (error) {
    console.error('Failed to generate trial balance:', error)
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
