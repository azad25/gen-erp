<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-xl font-bold text-black">Create Expense</h1>
        <p class="text-sm text-gray-1">Add a new expense to your business</p>
      </div>
      <Button variant="secondary" @click="$inertia.visit('/expenses')">Cancel</Button>
    </div>

    <Card>
      <form @submit.prevent="submit" class="space-y-4">
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium mb-1">Expense Date *</label>
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
            <label class="block text-sm font-medium mb-1">Amount (BDT) *</label>
            <input
              v-model="form.amount"
              type="number"
              step="0.01"
              required
              placeholder="0.00"
              class="w-full border rounded-lg px-3 py-2"
              :class="{ 'border-red-500': errors.amount }"
            />
            <p v-if="errors.amount" class="text-red-500 text-xs mt-1">{{ errors.amount[0] }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium mb-1">Tax Amount (BDT)</label>
            <input
              v-model="form.tax_amount"
              type="number"
              step="0.01"
              placeholder="0.00"
              class="w-full border rounded-lg px-3 py-2"
              :class="{ 'border-red-500': errors.tax_amount }"
            />
            <p v-if="errors.tax_amount" class="text-red-500 text-xs mt-1">{{ errors.tax_amount[0] }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium mb-1">Total Amount (BDT) *</label>
            <input
              v-model="form.total_amount"
              type="number"
              step="0.01"
              required
              placeholder="0.00"
              class="w-full border rounded-lg px-3 py-2"
              :class="{ 'border-red-500': errors.total_amount }"
            />
            <p v-if="errors.total_amount" class="text-red-500 text-xs mt-1">{{ errors.total_amount[0] }}</p>
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

          <div>
            <label class="block text-sm font-medium mb-1">Expense Account</label>
            <select
              v-model="form.account_id"
              class="w-full border rounded-lg px-3 py-2"
              :class="{ 'border-red-500': errors.account_id }"
            >
              <option value="">Select Account</option>
              <option v-for="account in expenseAccounts" :key="account.id" :value="account.id">
                {{ account.code }} - {{ account.name }}
              </option>
            </select>
            <p v-if="errors.account_id" class="text-red-500 text-xs mt-1">{{ errors.account_id[0] }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium mb-1">Payment Account</label>
            <select
              v-model="form.payment_account_id"
              class="w-full border rounded-lg px-3 py-2"
              :class="{ 'border-red-500': errors.payment_account_id }"
            >
              <option value="">Select Account</option>
              <option v-for="account in paymentAccounts" :key="account.id" :value="account.id">
                {{ account.code }} - {{ account.name }}
              </option>
            </select>
            <p v-if="errors.payment_account_id" class="text-red-500 text-xs mt-1">{{ errors.payment_account_id[0] }}</p>
          </div>
        </div>

        <div class="flex justify-end gap-2 pt-4">
          <Button type="button" variant="secondary" @click="$inertia.visit('/expenses')">Cancel</Button>
          <Button type="submit" :disabled="loading">{{ loading ? 'Creating...' : 'Create Expense' }}</Button>
        </div>
      </form>
    </Card>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useApi } from '@/Composables/useApi.js'
import Card from '@/Components/ui/Card.vue'
import Button from '@/Components/ui/Button.vue'

const { post } = useApi()

const loading = ref(false)
const errors = ref({})
const expenseAccounts = ref([])
const paymentAccounts = ref([])

const form = ref({
  expense_date: new Date().toISOString().split('T')[0],
  category: '',
  description: '',
  amount: '',
  tax_amount: '',
  total_amount: '',
  reference_number: '',
  account_id: '',
  payment_account_id: '',
})

const loadAccounts = async () => {
  try {
    const [expenseResp, paymentResp] = await Promise.all([
      fetch('/api/v1/accounts?type=expense'),
      fetch('/api/v1/accounts?type=asset')
    ])
    
    const expenseData = await expenseResp.json()
    const paymentData = await paymentResp.json()
    
    expenseAccounts.value = expenseData.data || []
    paymentAccounts.value = paymentData.data || []
  } catch (error) {
    console.error('Error loading accounts:', error)
  }
}

const submit = async () => {
  loading.value = true
  errors.value = {}

  try {
    const data = {
      expense_date: form.value.expense_date,
      category: form.value.category,
      description: form.value.description,
      amount: Math.round(parseFloat(form.value.amount) * 100),
      tax_amount: form.value.tax_amount ? Math.round(parseFloat(form.value.tax_amount) * 100) : null,
      total_amount: Math.round(parseFloat(form.value.total_amount) * 100),
      reference_number: form.value.reference_number || null,
      account_id: form.value.account_id || null,
      payment_account_id: form.value.payment_account_id || null,
    }

    await post('/expenses', data)
    
    window.location.href = '/expenses'
  } catch (error) {
    if (error.response?.data?.errors) {
      errors.value = error.response.data.errors
    } else {
      console.error('Error creating expense:', error)
    }
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadAccounts()
})
</script>
