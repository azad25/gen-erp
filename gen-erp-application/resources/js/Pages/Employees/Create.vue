<template>
  <FormPage
    title="New Employee"
    subtitle="Add a new employee"
    cancel-route="/employees"
    submit-label="Create Employee"
    :on-submit="handleSubmit"
  >
    <FormGroup label="Employee Name" required>
      <TextInput v-model="form.name" placeholder="Enter employee name" />
    </FormGroup>

    <FormGroup label="Email Address" required>
      <TextInput v-model="form.email" type="email" placeholder="employee@example.com" />
    </FormGroup>

    <FormGroup label="Phone Number">
      <TextInput v-model="form.phone" placeholder="01712345678" />
    </FormGroup>

    <div class="grid grid-cols-2 gap-4">
      <FormGroup label="Department" required>
        <SelectInput v-model="form.department_id" :options="departments" placeholder="Select department" />
      </FormGroup>
      <FormGroup label="Designation">
        <SelectInput v-model="form.designation_id" :options="designations" placeholder="Select designation" />
      </FormGroup>
    </div>

    <FormGroup label="Address">
      <TextareaInput v-model="form.address" placeholder="Enter full address" />
    </FormGroup>

    <FormGroup label="District">
      <TextInput v-model="form.district" placeholder="Dhaka" />
    </FormGroup>
  </FormPage>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { usePage } from '@inertiajs/vue3'
import FormPage from '../Shared/FormPage.vue'
import FormGroup from '../../Components/Forms/FormGroup.vue'
import TextInput from '../../Components/Forms/TextInput.vue'
import SelectInput from '../../Components/Forms/SelectInput.vue'
import TextareaInput from '../../Components/Forms/TextareaInput.vue'
import { useApi } from '../../Composables/useApi.js'

const page = usePage()
const { post, get } = useApi()

const form = ref({
  name: '',
  email: '',
  phone: '',
  department_id: '',
  designation_id: '',
  address: '',
  district: '',
})

const departments = ref([])
const designations = ref([])

onMounted(async () => {
  const [deptRes, desigRes] = await Promise.all([
    get('/departments'),
    get('/designations'),
  ])
  departments.value = deptRes.data.map(d => ({ value: d.id, label: d.name }))
  designations.value = desigRes.data.map(d => ({ value: d.id, label: d.name }))
})

const handleSubmit = async () => {
  await post('/employees', form.value)
  page.props.flash = { success: 'Employee created successfully' }
  window.location.href = '/employees'
}
</script>
