<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-xl font-bold text-black">Edit Expense</h1>
        <p class="text-sm text-gray-1">Update expense details</p>
      </div>
      <Button variant="secondary" @click="$inertia.visit('/expenses')">Cancel</Button>
    </div>

    <Card>
      <form @submit.prevent="submit" class="space-y-4">
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4">
          <p class="text-sm text-yellow-800">
            <strong>Note:</strong> Only non-financial fields can be updated. Amounts and tax cannot be changed.
          </p>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium mb-1">Expense Number</label>
            <input
              :value="expense.expense_number"
              type="text"
              disabled
              class="w-full border rounded-lg px-3 py-2 bg-gray-100"
            />
          </div>

          <div>
            <label class="block text-sm font-medium mb-1">Status</label>
            <input
              :value="expense.status"
              type="text"
              disabled
              class="w-full border rounded-lg px-3 py-2 bg-gray-100"
            />
          </div>

          <div>
            <label class="block text-sm font-medium mb-1">Expense Date</label>
            <input
              v-model="form.expense_date"
              type="date"
              required
              class="w-full border rounded-lg px-3 py-2"
              :class="{ 'border-red-500': errors.expense_date }"
            />
            <p v-if="errors.expense_date" class="text-red-500 text-xs mt-1">{{ errors.expense_date[0] }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium mb-1">Category *</label>
            <select
              v-model="form.category"
              required
              class="w-full border rounded-lg px-3 py-2"
              :class="{ 'border-red-500': errors.category }"
            >
              <option value="">Select Category</option>
              <option value="travel">Travel</option>
              <option value="meals">Meals</option>
              <option value="supplies">Supplies</option>
              <option value="utilities">Utilities</option>
              <option value="rent">Rent</option>
              <option value="salary">Salary</option>
              <option value="marketing">Marketing</option>
              <option value="other">Other</option>
            </select>
            <p v-if="errors.category" class="text-red-500 text-xs mt-1">{{ errors.category[0] }}</p>
          </div>

          <div class="col-span-2">
            <label class="block text-sm font-medium mb-1">Description *</label>
            <textarea
              v-model="form.description"
              required
              rows="3"
              class="w-full border rounded-lg px-3 py-2"
              :class="{ 'border-red-500': errors.description }"
            ></textarea>
            <p v-if="errors.description" class="text-red-500 text-xs mt-1">{{ errors.description[0] }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium mb-1">Amount (BDT)</label>
            <input
              :value="(expense.amount / 100).toFixed(2)"
              type="number"
              step="0.01"
              disabled
              class="w-full border rounded-lg px-3 py-2 bg-gray-100"
            />
          </div>

          <div>
            <label class="block text-sm font-medium mb-1">Tax Amount (BDT)</label>
            <input
              :value="expense.tax_amount ? (expense.tax_amount / 100).toFixed(2) : '0.00'"
              type="number"
              step="0.01"
              disabled
              class="w-full border rounded-lg px-3 py-2 bg-gray-100"
            />
          </div>

          <div>
            <label class="block text-sm font-medium mb-1">Total Amount (BDT)</label>
            <input
              :value="(expense.total_amount / 100).toFixed(2)"
              type="number"
              step="0.01"
              disabled
              class="w-full border rounded-lg px-3 py-2 bg-gray-100"
            />
          </div>

          <div>
            <label class="block text-sm font-medium mb-1">Reference Number</label>
            <input
              v-model="form.reference_number"
              type="text"
              placeholder="Receipt number"
              class="w-full border rounded-lg px-3 py-2"
              :class="{ 'border-red-500': errors.reference_number }"
            />
            <p v-if="errors.reference_number" class="text-red-500 text-xs mt-1">{{ errors.reference_number[0] }}</p>
          </div>
        </div>

        <div class="flex justify-end gap-2 pt-4">
          <Button type="button" variant="secondary" @click="$inertia.visit('/expenses')">Cancel</Button>
          <Button type="submit" :disabled="loading">{{ loading ? 'Updating...' : 'Update Expense' }}</Button>
        </div>
      </form>
    </Card>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { useApi } from '@/Composables/useApi.js'
import Card from '@/Components/ui/Card.vue'
import Button from '@/Components/ui/Button.vue'

const page = usePage()
const { put } = useApi()

const loading = ref(false)
const errors = ref({})
const expense = ref(page.props.expense || {})

const form = ref({
  expense_date: expense.value.expense_date || '',
  category: expense.value.category || '',
  description: expense.value.description || '',
  reference_number: expense.value.reference_number || '',
})

const submit = async () => {
  loading.value = true
  errors.value = {}

  try {
    const data = {
      expense_date: form.value.expense_date,
      category: form.value.category,
      description: form.value.description,
      reference_number: form.value.reference_number || null,
    }

    await put(`/expenses/${expense.value.id}`, data)
    
    window.location.href = `/expenses/${expense.value.id}`
  } catch (error) {
    if (error.response?.data?.errors) {
      errors.value = error.response.data.errors
    } else {
      console.error('Error updating expense:', error)
    }
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  if (page.props.expense) {
    expense.value = page.props.expense
    form.value = {
      expense_date: expense.value.expense_date || '',
      category: expense.value.category || '',
      description: expense.value.description || '',
      reference_number: expense.value.reference_number || '',
    }
  }
})
</script>
