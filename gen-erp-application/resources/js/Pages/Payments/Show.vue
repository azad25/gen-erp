<template>
  <ThemeProvider>
    <SidebarProvider>
      <AdminLayout>
        <div class="space-y-6">
          <div class="flex items-center justify-between">
            <div>
              <h1 class="text-xl font-bold text-black">Payment Details</h1>
              <p class="text-sm text-gray-1">{{ payment.receipt_number }}</p>
            </div>
            <div class="flex gap-2">
              <Button variant="secondary" @click="$inertia.visit(`/payments/${payment.id}/allocate`)">
                Allocate
              </Button>
              <Button variant="secondary" @click="$inertia.visit('/payments')">
                Back to List
              </Button>
            </div>
          </div>

          <div class="grid grid-cols-3 gap-6">
            <Card class="col-span-2">
              <div class="p-6 space-y-6">
                <div class="grid grid-cols-2 gap-6">
                  <div>
                    <label class="text-sm text-gray-600">Customer</label>
                    <p class="font-medium">{{ payment.customer?.name }}</p>
                  </div>
                  <div>
                    <label class="text-sm text-gray-600">Payment Date</label>
                    <p class="font-medium">{{ formatDate(payment.payment_date) }}</p>
                  </div>
                  <div>
                    <label class="text-sm text-gray-600">Amount</label>
                    <p class="font-medium text-lg"><BanglaAmount :amount="payment.amount" /></p>
                  </div>
                  <div>
                    <label class="text-sm text-gray-600">Payment Method</label>
                    <p class="font-medium">{{ payment.payment_method?.name || 'Cash' }}</p>
                  </div>
                  <div v-if="payment.reference_number">
                    <label class="text-sm text-gray-600">Reference</label>
                    <p class="font-medium">{{ payment.reference_number }}</p>
                  </div>
                </div>

                <div v-if="payment.notes">
                  <label class="text-sm text-gray-600">Notes</label>
                  <p class="mt-1">{{ payment.notes }}</p>
                </div>

                <!-- Allocations -->
                <div class="border-t pt-6">
                  <h3 class="text-lg font-semibold mb-4">Invoice Allocations</h3>
                  <div v-if="payment.allocations && payment.allocations.length > 0" class="space-y-2">
                    <div
                      v-for="allocation in payment.allocations"
                      :key="allocation.id"
                      class="flex justify-between items-center p-3 bg-gray-50 rounded-lg"
                    >
                      <div>
                        <div class="font-medium">{{ allocation.invoice?.invoice_number }}</div>
                        <div class="text-sm text-gray-600">{{ formatDate(allocation.invoice?.invoice_date) }}</div>
                      </div>
                      <div class="font-semibold"><BanglaAmount :amount="allocation.allocated_amount" /></div>
                    </div>
                  </div>
                  <p v-else class="text-gray-600 text-sm">No allocations yet</p>
                </div>
              </div>
            </Card>

            <div class="space-y-6">
              <Card>
                <div class="p-6">
                  <h3 class="font-semibold mb-4">Summary</h3>
                  <div class="space-y-3">
                    <div class="flex justify-between">
                      <span class="text-sm text-gray-600">Total Amount</span>
                      <span class="font-medium"><BanglaAmount :amount="payment.amount" /></span>
                    </div>
                    <div class="flex justify-between">
                      <span class="text-sm text-gray-600">Allocated</span>
                      <span class="font-medium"><BanglaAmount :amount="payment.amount - unallocatedAmount" /></span>
                    </div>
                    <div class="flex justify-between border-t pt-3">
                      <span class="text-sm font-semibold">Unallocated</span>
                      <span class="font-semibold"><BanglaAmount :amount="unallocatedAmount" /></span>
                    </div>
                  </div>
                </div>
              </Card>
            </div>
          </div>
        </div>
      </AdminLayout>
    </SidebarProvider>
  </ThemeProvider>
</template>

<script setup>
import ThemeProvider from '@/Components/Layout/ThemeProvider.vue'
import SidebarProvider from '@/Components/Layout/SidebarProvider.vue'
import AdminLayout from '@/Components/Layout/AdminLayout.vue'
import Card from '@/Components/ui/Card.vue'
import Button from '@/Components/ui/Button.vue'
import BanglaAmount from '@/Components/Bangla/BanglaAmount.vue'
import { formatDate } from '@/utils/formatters'

defineProps({
  payment: Object,
  unallocatedAmount: Number
})
</script>
