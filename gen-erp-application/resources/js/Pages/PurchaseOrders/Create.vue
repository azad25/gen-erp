<template>
  <FormPage
    title="New Purchase Order"
    subtitle="Create a new purchase order"
    cancel-route="/purchase-orders"
    submit-label="Create Purchase Order"
    :on-submit="handleSubmit"
  >
    <FormGroup label="Supplier" required>
      <SelectInput v-model="form.supplier_id" :options="suppliers" placeholder="Select supplier" />
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
const { post, get } = useApi()

const form = ref({
  supplier_id: '',
  order_date: '',
  subtotal: 0,
  tax: 0,
  total_amount: 0,
  notes: '',
})

const suppliers = ref([])

onMounted(async () => {
  const response = await get('/suppliers')
  suppliers.value = response.data.map(s => ({ value: s.id, label: s.name }))
})

const handleSubmit = async () => {
  await post('/purchase-orders', form.value)
  page.props.flash = { success: 'Purchase order created successfully' }
  window.location.href = '/purchase-orders'
}
</script>
