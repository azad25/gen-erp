<template>
  <ThemeProvider>
    <SidebarProvider>
      <AdminLayout>
        <div class="space-y-6">
          <div class="flex items-center justify-between">
            <div>
              <h1 class="text-xl font-bold text-black">Allocate Payment</h1>
              <p class="text-sm text-gray-1">{{ payment.receipt_number }} - Unallocated: <BanglaAmount :amount="unallocatedAmount" /></p>
            </div>
            <Button variant="secondary" @click="$inertia.visit(`/payments/${payment.id}`)">
              Back
            </Button>
          </div>

          <form @submit.prevent="submitAllocation">
            <Card>
              <div class="p-6 space-y-6">
                <div>
                  <label class="block text-sm font-medium mb-2">Select Invoice *</label>
                  <select
                    v-model="form.invoice_id"
                    class="w-full px-4 py-2 border rounded-lg"
                    required
                    @change="updateAmount"
                  >
                    <option value="">Choose an invoice</option>
                    <option v-for="invoice in invoices" :key="invoice.id" :value="invoice.id">
                      {{ invoice.invoice_number }} - Balance: {{ formatAmount(invoice.balance) }}
                    </option>
                  </select>
                </div>

                <div>
                  <label class="block text-sm font-medium mb-2">Amount to Allocate *</label>
                  <input
                    v-model.number="form.amount"
                    type="number"
                    step="0.01"
                    class="w-full px-4 py-2 border rounded-lg"
                    required
                    :max="unallocatedAmount"
                  />
                  <p class="text-sm text-gray-600 mt-1">Maximum: {{ formatAmount(unallocatedAmount) }}</p>
                </div>
              </div>

              <div class="px-6 py-4 bg-gray-50 border-t flex justify-end gap-3">
                <Button type="button" variant="secondary" @click="$inertia.visit(`/payments/${payment.id}`)">
                  Cancel
                </Button>
                <Button type="submit" :disabled="processing">
                  {{ processing ? 'Allocating...' : 'Allocate Payment' }}
                </Button>
              </div>
            </Card>
          </form>
        </div>
      </AdminLayout>
    </SidebarProvider>
  </ThemeProvider>
</template>

<script setup>
import { ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import ThemeProvider from '@/Components/Layout/ThemeProvider.vue'
import SidebarProvider from '@/Components/Layout/SidebarProvider.vue'
import AdminLayout from '@/Components/Layout/AdminLayout.vue'
import Card from '@/Components/ui/Card.vue'
import Button from '@/Components/ui/Button.vue'
import BanglaAmount from '@/Components/Bangla/BanglaAmount.vue'
import { formatCurrency } from '@/utils/formatters'

const props = defineProps({
  payment: Object,
  invoices: Array,
  unallocatedAmount: Number
})

const form = useForm({
  invoice_id: '',
  amount: 0
})

const processing = ref(false)

const formatAmount = (amount) => formatCurrency(amount / 100)

const updateAmount = () => {
  const invoice = props.invoices.find(inv => inv.id === form.invoice_id)
  if (invoice) {
    form.amount = Math.min(invoice.balance, props.unallocatedAmount)
  }
}

const submitAllocation = () => {
  processing.value = true
  form.post(`/payments/${props.payment.id}/allocate`, {
    onFinish: () => {
      processing.value = false
    }
  })
}
</script>
