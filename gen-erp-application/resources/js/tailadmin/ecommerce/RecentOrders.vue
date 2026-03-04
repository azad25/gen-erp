<template>
  <div
    class="overflow-hidden rounded-2xl border border-gray-200 bg-white px-4 pb-3 pt-4 dark:border-gray-800 dark:bg-white/[0.03] sm:px-6"
  >
    <div class="flex flex-col gap-2 mb-4 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Recent Invoices</h3>
      </div>

      <div class="flex items-center gap-3">
        <a
          href="/sales/invoices"
          class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-theme-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50 hover:text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03] dark:hover:text-gray-200"
        >
          See all
        </a>
      </div>
    </div>

    <div class="max-w-full overflow-x-auto custom-scrollbar">
      <table class="min-w-full">
        <thead>
          <tr class="border-t border-gray-100 dark:border-gray-800">
            <th class="py-3 text-left">
              <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Customer</p>
            </th>
            <th class="py-3 text-left">
              <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Invoice #</p>
            </th>
            <th class="py-3 text-left">
              <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Amount</p>
            </th>
            <th class="py-3 text-left">
              <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Status</p>
            </th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="invoice in displayData"
            :key="invoice.id || invoice.invoice_number"
            class="border-t border-gray-100 dark:border-gray-800"
          >
            <td class="py-3 whitespace-nowrap">
              <p class="font-medium text-gray-800 text-theme-sm dark:text-white/90">
                {{ invoice.customer_name }}
              </p>
            </td>
            <td class="py-3 whitespace-nowrap">
              <p class="text-gray-500 text-theme-sm dark:text-gray-400">{{ invoice.invoice_number }}</p>
            </td>
            <td class="py-3 whitespace-nowrap">
              <p class="font-medium text-gray-800 text-theme-sm dark:text-white/90">৳{{ (invoice.total_amount / 100).toLocaleString() }}</p>
            </td>
            <td class="py-3 whitespace-nowrap">
              <span
                :class="{
                  'rounded-full px-2 py-0.5 text-theme-xs font-medium': true,
                  'bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-500':
                    ['paid', 'Delivered'].includes(invoice.status),
                  'bg-warning-50 text-warning-600 dark:bg-warning-500/15 dark:text-orange-400':
                    ['pending', 'Pending'].includes(invoice.status),
                  'bg-error-50 text-error-600 dark:bg-error-500/15 dark:text-error-500':
                    ['overdue', 'Canceled'].includes(invoice.status),
                  'bg-blue-50 text-blue-600 dark:bg-blue-500/15 dark:text-blue-500':
                    invoice.status === 'sent',
                  'bg-gray-50 text-gray-600 dark:bg-gray-500/15 dark:text-gray-400':
                    invoice.status === 'draft',
                }"
              >
                {{ formatStatus(invoice.status) }}
              </span>
            </td>
          </tr>
          <tr v-if="displayData.length === 0">
            <td colspan="4" class="py-8 text-center text-gray-500">
              No recent activity
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  invoices: {
    type: Array,
    default: () => [],
  },
})

// Use dummy data if proper invoices aren't passed yet for visual testing
const dummyData = [
  { customer_name: 'Acme Corp', invoice_number: 'INV-2026-001', total_amount: 239900, status: 'paid' },
  { customer_name: 'Global Tech', invoice_number: 'INV-2026-002', total_amount: 87900, status: 'pending' },
  { customer_name: 'Stark Industries', invoice_number: 'INV-2026-003', total_amount: 186900, status: 'overdue' },
]

const displayData = computed(() => {
  return props.invoices && props.invoices.length > 0 ? props.invoices : dummyData
})

const formatStatus = (status) => {
  if (!status) return 'Unknown'
  return status.charAt(0).toUpperCase() + status.slice(1)
}
</script>
