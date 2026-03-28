<template>
  
    
      <AppLayout>
        <div class="space-y-6">
          <div class="flex items-center justify-between">
            <div>
              <h1 class="text-xl font-bold text-black">{{ $t('payments.title') }}</h1>
              <p class="text-sm text-gray-1">{{ $t('payments.subtitle') }}</p>
            </div>
            <Button @click="$inertia.visit('/payments/create')">
              {{ $t('payments.new_payment') }}
            </Button>
          </div>

          <Card>
            <div class="p-6">
              <!-- Search and Filters -->
              <div class="mb-6 flex gap-4">
                <div class="flex-1">
                  <input
                    v-model="searchQuery"
                    type="text"
                    :placeholder="$t('payments.search_placeholder')"
                    class="w-full px-4 py-2 border rounded-lg"
                    @input="debouncedSearch"
                  />
                </div>
              </div>

              <!-- Payments Table -->
              <div class="overflow-x-auto">
                <table class="w-full">
                  <thead class="bg-gray-50 border-b">
                    <tr>
                      <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Receipt #</th>
                      <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Customer</th>
                      <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                      <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Amount</th>
                      <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Method</th>
                      <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y">
                    <tr
                      v-for="payment in payments.data"
                      :key="payment.id"
                      class="hover:bg-gray-50 cursor-pointer"
                      @click="$inertia.visit(`/payments/${payment.id}`)"
                    >
                      <td class="px-4 py-3">
                        <div class="font-mono font-semibold text-black">{{ payment.receipt_number }}</div>
                      </td>
                      <td class="px-4 py-3">
                        <div class="font-medium">{{ payment.customer?.name }}</div>
                      </td>
                      <td class="px-4 py-3 text-sm text-gray-600">
                        {{ formatDate(payment.payment_date) }}
                      </td>
                      <td class="px-4 py-3 text-right">
                        <BanglaAmount :amount="payment.amount" />
                      </td>
                      <td class="px-4 py-3 text-sm">
                        {{ payment.payment_method?.name || 'Cash' }}
                      </td>
                      <td class="px-4 py-3 text-right" @click.stop>
                        <Button
                          size="sm"
                          variant="ghost"
                          @click="$inertia.visit(`/payments/${payment.id}/allocate`)"
                        >
                          Allocate
                        </Button>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <!-- Pagination -->
              <div v-if="payments.data.length > 0" class="mt-6 flex items-center justify-between">
                <div class="text-sm text-gray-600">
                  Showing {{ payments.from }} to {{ payments.to }} of {{ payments.total }} payments
                </div>
                <div class="flex gap-2">
                  <Button
                    v-for="link in payments.links"
                    :key="link.label"
                    size="sm"
                    :variant="link.active ? 'primary' : 'secondary'"
                    :disabled="!link.url"
                    @click="link.url && $inertia.visit(link.url)"
                    v-html="link.label"
                  />
                </div>
              </div>

              <!-- Empty State -->
              <div v-if="payments.data.length === 0" class="text-center py-12">
                <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                  <span class="text-3xl">💰</span>
                </div>
                <p class="text-gray-1 mb-4">No payments found</p>
                <Button size="sm" @click="$inertia.visit('/payments/create')">Record First Payment</Button>
              </div>
            </div>
          </Card>
        </div>
      </AppLayout>
    
  
</template>

<script setup>
import { ref } from 'vue'
import { usePage } from '@inertiajs/vue3'
import AppLayout from "@/Layouts/AppLayout.vue"
import Card from '@/Components/ui/Card.vue'
import Button from '@/Components/ui/Button.vue'
import BanglaAmount from '@/Components/Bangla/BanglaAmount.vue'
import { useTranslations } from '@/Composables/useTranslations'
import { formatDate } from '@/utils/formatters'

const { $t } = useTranslations()
const page = usePage()

defineProps({
  payments: {
    type: Object,
    required: true
  },
  filters: {
    type: Object,
    default: () => ({})
  }
})

const searchQuery = ref('')

let searchTimeout
const debouncedSearch = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    page.props.inertia.visit('/payments', {
      data: { search: searchQuery.value },
      preserveState: true
    })
  }, 300)
}
</script>
