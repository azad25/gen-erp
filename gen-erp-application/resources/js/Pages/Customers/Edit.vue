<template>
  <FormPage
    title="Edit Customer"
    subtitle="Update customer information"
    cancel-route="/customers"
    submit-label="Update Customer"
    :on-submit="handleSubmit"
  >
    <FormGroup label="Customer Name" required>
      <TextInput v-model="form.name" placeholder="Enter customer name" />
    </FormGroup>

    <FormGroup label="Email Address">
      <TextInput v-model="form.email" type="email" placeholder="customer@example.com" />
    </FormGroup>

    <FormGroup label="Phone Number">
      <TextInput v-model="form.phone" placeholder="01712345678" />
    </FormGroup>

    <FormGroup label="Address">
      <TextareaInput v-model="form.address" placeholder="Enter full address" />
    </FormGroup>

    <FormGroup label="District">
      <TextInput v-model="form.district" placeholder="Dhaka" />
    </FormGroup>

    <FormGroup label="Credit Limit">
      <NumberInput v-model="form.credit_limit" prefix="৳" placeholder="0.00" />
    </FormGroup>
  </FormPage>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { usePage } from '@inertiajs/vue3'
import FormPage from '../Shared/FormPage.vue'
import FormGroup from '../../Components/Forms/FormGroup.vue'
import TextInput from '../../Components/Forms/TextInput.vue'
import TextareaInput from '../../Components/Forms/TextareaInput.vue'
import NumberInput from '../../Components/Forms/NumberInput.vue'
import { useApi } from '../../Composables/useApi.js'

const page = usePage()
const { put } = useApi()

const form = ref({
  name: '',
  email: '',
  phone: '',
  address: '',
  district: '',
  credit_limit: 0,
})

onMounted(async () => {
  const response = await get(`/customers/${page.props.customer.id}`)
  form.value = response.data
})

const handleSubmit = async () => {
  await put(`/customers/${page.props.customer.id}`, form.value)
  page.props.flash = { success: 'Customer updated successfully' }
  window.location.href = `/customers/${page.props.customer.id}`
}
</script>
