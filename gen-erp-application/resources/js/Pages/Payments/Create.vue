<template>
  
    
      <AppLayout>
        <div class="space-y-6">
          <div class="flex items-center justify-between">
            <div>
              <h1 class="text-xl font-bold text-black">Receive Payment</h1>
              <p class="text-sm text-gray-1">Record a payment from a customer</p>
            </div>
            <Button variant="secondary" @click="$inertia.visit('/payments')">
              Cancel
            </Button>
          </div>

          <form @submit.prevent="submitPayment">
            <Card>
              <div class="p-6 space-y-6">
                <!-- Customer Selection -->
                <div>
                  <label class="block text-sm font-medium mb-2">Customer *</label>
                  <select
                    v-model="form.customer_id"
                    class="w-full px-4 py-2 border rounded-lg"
                    required
                    @change="loadCustomerInvoices"
                  >
                    <option value="">Select a customer</option>
                    <option v-for="customer in customers" :key="customer.id" :value="customer.id">
                      {{ customer.name }} (Balance: {{ formatAmount(customer.balance) }})
                    </option>
                  </select>
                  <p v-if="errors.customer_id" class="text-red-600 text-sm mt-1">{{ errors.customer_id }}</p>
                </div>

                <div class="grid grid-cols-2 gap-6">
                  <!-- Payment Date -->
                  <div>
                    <label class="block text-sm font-medium mb-2">Payment Date *</label>
                    <input
                      v-model="form.payment_date"
                      type="date"
                      class="w-full px-4 py-2 border rounded-lg"
                      required
                    />
                    <p v-if="errors.payment_date" class="text-red-600 text-sm mt-1">{{ errors.payment_date }}</p>
                  </div>

                  <!-- Amount -->
                  <div>
                    <label class="block text-sm font-medium mb-2">Amount *</label>
                    <input
                      v-model.number="form.amount"
                      type="number"
                      step="0.01"
                      class="w-full px-4 py-2 border rounded-lg"
                      placeholder="0.00"
                      required
                    />
                    <p v-if="errors.amount" class="text-red-600 text-sm mt-1">{{ errors.amount }}</p>
                  </div>
                </div>

                <div class="grid grid-cols-2 gap-6">
                  <!-- Payment Method -->
                  <div>
                    <label class="block text-sm font-medium mb-2">Payment Method</label>
                    <select
                      v-model="form.payment_method_id"
                      class="w-full px-4 py-2 border rounded-lg"
                    >
                      <option value="">Cash</option>
                      <option v-for="method in paymentMethods" :key="method.id" :value="method.id">
                        {{ method.name }}
                      </option>
                    </select>
                  </div>

                  <!-- Reference Number -->
                  <div>
                    <label class="block text-sm font-medium mb-2">Reference Number</label>
                    <input
                      v-model="form.reference_number"
                      type="text"
                      class="w-full px-4 py-2 border rounded-lg"
                      placeholder="Check #, Transaction ID, etc."
                    />
                  </div>
                </div>

                <!-- Notes -->
                <div>
                  <label class="block text-sm font-medium mb-2">Notes</label>
                  <textarea
                    v-model="form.notes"
                    rows="3"
                    class="w-full px-4 py-2 border rounded-lg"
                    placeholder="Additional notes about this payment"
                  />
                </div>

                <!-- Invoice Allocation -->
                <div v-if="form.customer_id && customerInvoices.length > 0" class="border-t pt-6">
                  <h3 class="text-lg font-semibold mb-4">Allocate to Invoices</h3>
                  <div class="space-y-3">
                    <div
                      v-for="invoice in customerInvoices"
                      :key="invoice.id"
                      class="flex items-center gap-4 p-3 border rounded-lg"
                    >
                      <input
                        type="checkbox"
                        :checked="isInvoiceSelected(invoice.id)"
                        @change="toggleInvoice(invoice)"
                      />
                      <div class="flex-1">
                        <div class="font-medium">{{ invoice.invoice_number }}</div>
                        <div class="text-sm text-gray-600">
                          Date: {{ formatDate(invoice.invoice_date) }} | 
                          Balance: {{ formatAmount(invoice.total_amount - invoice.amount_paid) }}
                        </div>
                      </div>
                      <div v-if="isInvoiceSelected(invoice.id)" class="w-32">
                        <input
                          v-model.number="getAllocation(invoice.id).amount"
                          type="number"
                          step="0.01"
                          class="w-full px-3 py-1 border rounded"
                          placeholder="Amount"
                          :max="invoice.total_amount - invoice.amount_paid"
                        />
                      </div>
                    </div>
                  </div>
                  <div class="mt-4 p-3 bg-gray-50 rounded-lg">
                    <div class="flex justify-between text-sm">
                      <span>Total Allocated:</span>
                      <span class="font-semibold">{{ formatAmount(totalAllocated) }}</span>
                    </div>
                    <div class="flex justify-between text-sm mt-1">
                      <span>Unallocated:</span>
                      <span class="font-semibold">{{ formatAmount(form.amount - totalAllocated) }}</span>
                    </div>
                  </div>
                  <p v-if="errors.allocations" class="text-red-600 text-sm mt-2">{{ errors.allocations }}</p>
                </div>
              </div>

              <div class="px-6 py-4 bg-gray-50 border-t flex justify-end gap-3">
                <Button type="button" variant="secondary" @click="$inertia.visit('/payments')">
                  Cancel
                </Button>
                <Button type="submit" :disabled="processing">
                  {{ processing ? 'Saving...' : 'Receive Payment' }}
                </Button>
              </div>
            </Card>
          </form>
        </div>
      </AppLayout>
    
  
</template>

<script setup>
import { ref, computed } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import AppLayout from "@/Layouts/AppLayout.vue"
import Card from '@/Components/ui/Card.vue'
import Button from '@/Components/ui/Button.vue'
import { formatDate, formatCurrency } from '@/utils/formatters'
import axios from 'axios'

const props = defineProps({
  customers: Array,
  paymentMethods: Array,
  errors: {
    type: Object,
    default: () => ({})
  }
})

const form = useForm({
  customer_id: '',
  payment_date: new Date().toISOString().split('T')[0],
  amount: 0,
  payment_method_id: '',
  reference_number: '',
  notes: '',
  allocations: []
})

const processing = ref(false)
const customerInvoices = ref([])

const formatAmount = (amount) => {
  return formatCurrency(amount / 100)
}

const loadCustomerInvoices = async () => {
  if (!form.customer_id) {
    customerInvoices.value = []
    return
  }

  try {
    const response = await axios.get(`/api/v1/invoices`, {
      params: {
        customer_id: form.customer_id,
        status: 'unpaid,partial'
      }
    })
    customerInvoices.value = response.data.data || []
  } catch (error) {
    console.error('Failed to load invoices:', error)
  }
}

const isInvoiceSelected = (invoiceId) => {
  return form.allocations.some(a => a.invoice_id === invoiceId)
}

const toggleInvoice = (invoice) => {
  const index = form.allocations.findIndex(a => a.invoice_id === invoice.id)
  if (index >= 0) {
    form.allocations.splice(index, 1)
  } else {
    form.allocations.push({
      invoice_id: invoice.id,
      amount: invoice.total_amount - invoice.amount_paid
    })
  }
}

const getAllocation = (invoiceId) => {
  return form.allocations.find(a => a.invoice_id === invoiceId) || { amount: 0 }
}

const totalAllocated = computed(() => {
  return form.allocations.reduce((sum, a) => sum + (a.amount || 0), 0)
})

const submitPayment = () => {
  processing.value = true
  form.post('/payments', {
    onFinish: () => {
      processing.value = false
    }
  })
}
</script>
