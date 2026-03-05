<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-xl font-bold text-black">Expense Details</h1>
        <p class="text-sm text-gray-1">View expense information</p>
      </div>
      <div class="flex items-center gap-2">
        <Button variant="secondary" @click="$inertia.visit('/expenses')">Back to List</Button>
        <Button @click="$inertia.visit(`/expenses/${expense.id}/edit`)">Edit</Button>
      </div>
    </div>

    <Card v-if="expense">
      <div class="grid grid-cols-2 gap-6">
        <div>
          <p class="text-sm text-gray-1">Expense Number</p>
          <p class="font-semibold">{{ expense.expense_number }}</p>
        </div>
        <div>
          <p class="text-sm text-gray-1">Status</p>
          <Badge :variant="expense.status">{{ expense.status }}</Badge>
        </div>

        <div>
          <p class="text-sm text-gray-1">Expense Date</p>
          <p class="font-semibold">{{ expense.expense_date }}</p>
        </div>
        <div>
          <p class="text-sm text-gray-1">Category</p>
          <p class="font-semibold">{{ expense.category || '—' }}</p>
        </div>

        <div>
          <p class="text-sm text-gray-1">Amount</p>
          <p class="font-semibold text-lg"><BanglaAmount :amount="expense.amount" /></p>
        </div>
        <div>
          <p class="text-sm text-gray-1">Tax Amount</p>
          <p class="font-semibold"><BanglaAmount :amount="expense.tax_amount || 0" /></p>
        </div>

        <div>
          <p class="text-sm text-gray-1">Total Amount</p>
          <p class="font-semibold text-lg text-green-600"><BanglaAmount :amount="expense.total_amount" /></p>
        </div>
        <div>
          <p class="text-sm text-gray-1">Reference Number</p>
          <p class="font-semibold">{{ expense.reference_number || '—' }}</p>
        </div>

        <div class="col-span-2">
          <p class="text-sm text-gray-1">Description</p>
          <p class="font-semibold">{{ expense.description }}</p>
        </div>

        <div v-if="expense.account">
          <p class="text-sm text-gray-1">Expense Account</p>
          <p class="font-semibold">{{ expense.account.code }} - {{ expense.account.name }}</p>
        </div>
        <div v-if="expense.payment_account">
          <p class="text-sm text-gray-1">Payment Account</p>
          <p class="font-semibold">{{ expense.payment_account.code }} - {{ expense.payment_account.name }}</p>
        </div>

        <div v-if="expense.receipt_url">
          <p class="text-sm text-gray-1">Receipt</p>
          <a :href="expense.receipt_url" target="_blank" class="text-blue-600 hover:underline">View Receipt</a>
        </div>

        <div>
          <p class="text-sm text-gray-1">Created By</p>
          <p class="font-semibold">{{ expense.creator?.name || '—' }}</p>
        </div>

        <div>
          <p class="text-sm text-gray-1">Created At</p>
          <p class="font-semibold">{{ new Date(expense.created_at).toLocaleString() }}</p>
        </div>
      </div>

      <div class="mt-6 pt-6 border-t">
        <h3 class="font-semibold mb-4">Journal Entry</h3>
        <div v-if="journalEntry" class="bg-gray-50 rounded-lg p-4">
          <div class="grid grid-cols-2 gap-4">
            <div>
              <p class="text-sm text-gray-1">Entry Number</p>
              <p class="font-mono text-sm">{{ journalEntry.entry_number }}</p>
            </div>
            <div>
              <p class="text-sm text-gray-1">Status</p>
              <Badge :variant="journalEntry.status">{{ journalEntry.status }}</Badge>
            </div>
            <div>
              <p class="text-sm text-gray-1">Posted At</p>
              <p class="font-mono text-sm">{{ new Date(journalEntry.posted_at).toLocaleString() }}</p>
            </div>
            <div>
              <p class="text-sm text-gray-1">Total</p>
              <p class="font-semibold"><BanglaAmount :amount="journalEntry.total_debit || journalEntry.total_credit" /></p>
            </div>
          </div>
          <div class="mt-4">
            <Button variant="secondary" size="sm" @click="$inertia.visit(`/journal-entries/${journalEntry.id}`)">
              View Journal Entry
            </Button>
          </div>
        </div>
        <div v-else class="text-sm text-gray-1">
          No journal entry created yet.
        </div>
      </div>
    </Card>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { useApi } from '@/Composables/useApi.js'
import Card from '@/Components/ui/Card.vue'
import Button from '@/Components/ui/Button.vue'
import Badge from '@/Components/UI/Badge.vue'
import BanglaAmount from '@/Components/Bangla/BanglaAmount.vue'

const page = usePage()
const { get } = useApi()

const expense = ref(page.props.expense || {})
const journalEntry = ref(null)

const loadJournalEntry = async () => {
  try {
    const response = await get(`/expenses/${expense.value.id}/journal-entry`)
    journalEntry.value = response.data
  } catch (error) {
    console.error('Error loading journal entry:', error)
  }
}

onMounted(() => {
  if (page.props.expense) {
    expense.value = page.props.expense
    loadJournalEntry()
  }
})
</script>
