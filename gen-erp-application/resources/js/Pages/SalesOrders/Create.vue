<template>
  <FormPage
    title="New Sales Order"
    subtitle="Create a new sales order"
    cancel-route="/sales-orders"
    submit-label="Create Sales Order"
    :on-submit="handleSubmit"
  >
    <FormGroup label="Customer" required>
      <SelectInput v-model="form.customer_id" :options="customers" placeholder="Select customer" />
    </FormGroup>

    <FormGroup label="Order Date" required>
      <DateInput v-model="form.order_date" />
    </FormGroup>

    <Card>
      <template #header>
        <h3 class="text-sm font-semibold text-black">Line Items</h3>
      </template>
      <div class="p-5 space-y-3">
        <div class="text-xs text-gray-1">Line items will be added here</div>
      </div>
    </Card>

    <FormGroup label="Notes">
      <TextareaInput v-model="form.notes" placeholder="Add any notes..." />
    </FormGroup>

    <div class="grid grid-cols-2 gap-4">
      <FormGroup label="Subtotal">
        <NumberInput v-model="form.subtotal" prefix="৳" readonly />
      </FormGroup>
      <FormGroup label="Tax">
        <NumberInput v-model="form.tax" prefix="৳" readonly />
      </FormGroup>
    </div>

    <FormGroup label="Total Amount" required>
      <NumberInput v-model="form.total_amount" prefix="৳" readonly />
    </FormGroup>
  </FormPage>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { usePage } from '@inertiajs/vue3'
import FormPage from '../Shared/FormPage.vue'
import FormGroup from '../../Components/Forms/FormGroup.vue'
import SelectInput from '../../Components/Forms/SelectInput.vue'
import DateInput from '../../Components/Forms/DateInput.vue'
import TextareaInput from '../../Components/Forms/TextareaInput.vue'
import NumberInput from '../../Components/Forms/NumberInput.vue'
import Card from '../../Components/UI/Card.vue'
import { useApi } from '../../Composables/useApi.js'

const page = usePage()
const { post } = useApi()

const form = ref({
  customer_id: '',
  order_date: '',
  subtotal: 0,
  tax: 0,
  total_amount: 0,
  notes: '',
})

const customers = ref([])

onMounted(async () => {
  const response = await get('/customers')
  customers.value = response.data.map(c => ({ value: c.id, label: c.name }))
})

const handleSubmit = async () => {
  await post('/sales-orders', form.value)
  page.props.flash = { success: 'Sales order created successfully' }
  window.location.href = '/sales-orders'
}
</script>
