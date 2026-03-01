<template>
  <FormPage
    title="New Supplier"
    subtitle="Add a new supplier"
    cancel-route="/suppliers"
    submit-label="Create Supplier"
    :on-submit="handleSubmit"
  >
    <FormGroup label="Supplier Name" required>
      <TextInput v-model="form.name" placeholder="Enter supplier name" />
    </FormGroup>

    <FormGroup label="Email Address">
      <TextInput v-model="form.email" type="email" placeholder="supplier@example.com" />
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
  </FormPage>
</template>

<script setup>
import { ref } from 'vue'
import { usePage } from '@inertiajs/vue3'
import FormPage from '../Shared/FormPage.vue'
import FormGroup from '../../Components/Forms/FormGroup.vue'
import TextInput from '../../Components/Forms/TextInput.vue'
import TextareaInput from '../../Components/Forms/TextareaInput.vue'
import { useApi } from '../../Composables/useApi.js'

const page = usePage()
const { post } = useApi()

const form = ref({
  name: '',
  email: '',
  phone: '',
  address: '',
  district: '',
})

const handleSubmit = async () => {
  await post('/suppliers', form.value)
  page.props.flash = { success: 'Supplier created successfully' }
  window.location.href = '/suppliers'
}
</script>
