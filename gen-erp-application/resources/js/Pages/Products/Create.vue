<template>
  <FormPage
    title="New Product"
    subtitle="Add a new product to inventory"
    cancel-route="/products"
    submit-label="Create Product"
    :on-submit="handleSubmit"
  >
    <FormGroup label="Product Name" required>
      <TextInput v-model="form.name" placeholder="Enter product name" />
    </FormGroup>

    <FormGroup label="SKU" required>
      <TextInput v-model="form.sku" placeholder="PROD-001" />
    </FormGroup>

    <FormGroup label="Product Category" required>
      <SelectInput v-model="form.product_category_id" :options="categories" placeholder="Select category" />
    </FormGroup>

    <div class="grid grid-cols-2 gap-4">
      <FormGroup label="Selling Price" required>
        <NumberInput v-model="form.selling_price" prefix="৳" placeholder="0.00" />
      </FormGroup>
      <FormGroup label="Cost Price">
        <NumberInput v-model="form.cost_price" prefix="৳" placeholder="0.00" />
      </FormGroup>
    </div>

    <FormGroup label="Description">
      <TextareaInput v-model="form.description" placeholder="Enter product description" />
    </FormGroup>

    <FormGroup label="Stock Level">
      <NumberInput v-model="form.stock_level" placeholder="0" />
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
import NumberInput from '../../Components/Forms/NumberInput.vue'
import TextareaInput from '../../Components/Forms/TextareaInput.vue'
import { useApi } from '../../Composables/useApi.js'

const page = usePage()
const { post } = useApi()

const form = ref({
  name: '',
  sku: '',
  product_category_id: '',
  selling_price: 0,
  cost_price: 0,
  description: '',
  stock_level: 0,
})

const categories = ref([])

onMounted(async () => {
  const response = await get('/product-categories')
  categories.value = response.data.map(c => ({ value: c.id, label: c.name }))
})

const handleSubmit = async () => {
  await post('/products', form.value)
  page.props.flash = { success: 'Product created successfully' }
  window.location.href = '/products'
}
</script>
