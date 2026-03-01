<template>
  <ThemeProvider>
    <SidebarProvider>
      <AdminLayout>
        <div class="space-y-6">
          <div class="flex items-center justify-between">
            <div>
              <h1 class="text-xl font-bold text-black">Products</h1>
              <p class="text-sm text-gray-1">Manage products for your business</p>
            </div>
            <div class="flex items-center gap-2">
              <Button variant="secondary" size="sm" @click="exportData">Export</Button>
              <Button size="sm" @click="openCreateModal">+ New Product</Button>
            </div>
          </div>

          <DataTable
            :columns="columns"
            :rows="products"
            :pagination="pagination"
            placeholder="Search products..."
            @search="handleSearch"
          >
            <template #cell-name="{ row }">
              <div class="flex items-center gap-2">
                <div class="h-8 w-8 rounded-full bg-gray-200 flex items-center justify-center text-xs font-semibold">
                  {{ row.name?.charAt(0) || 'P' }}
                </div>
                <div>
                  <p class="font-medium text-sm">{{ row.name }}</p>
                  <p class="text-xs text-gray-1">{{ row.sku || '—' }}</p>
                </div>
              </div>
            </template>

            <template #cell-price="{ row }">
              <span class="font-semibold">{{ formatCurrency(row.unit_price) }}</span>
            </template>

            <template #cell-stock="{ row }">
              <div class="flex items-center gap-2">
                <span class="text-sm">{{ row.stock_quantity || 0 }}</span>
                <Badge :variant="getStockVariant(row.stock_quantity || 0)">
                  {{ getStockLabel(row.stock_quantity || 0) }}
                </Badge>
              </div>
            </template>

            <template #cell-status="{ row }">
              <Badge :variant="row.is_active ? 'default' : 'secondary'">{{ row.is_active ? 'Active' : 'Inactive' }}</Badge>
            </template>

            <template #actions="{ row }">
              <Button variant="ghost" size="sm" @click="viewProduct(row)">View</Button>
              <Button variant="ghost" size="sm" @click="editProduct(row)">Edit</Button>
              <Button variant="ghost" size="sm" class="text-red-500" @click="deleteProduct(row)">Delete</Button>
            </template>
          </DataTable>
        </div>

        <Modal v-if="showModal" @close="closeModal" :title="isEditing ? 'Edit Product' : 'New Product'">
          <form @submit.prevent="handleSubmit" class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium mb-1">Name *</label>
                <input type="text" v-model="form.name" required class="w-full border rounded-lg px-3 py-2">
              </div>
              <div>
                <label class="block text-sm font-medium mb-1">SKU</label>
                <input type="text" v-model="form.sku" class="w-full border rounded-lg px-3 py-2">
              </div>
              <div>
                <label class="block text-sm font-medium mb-1">Category</label>
                <select v-model="form.category_id" class="w-full border rounded-lg px-3 py-2">
                  <option value="">Select Category</option>
                  <option v-for="category in categories" :key="category.id" :value="category.id">
                    {{ category.name }}
                  </option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium mb-1">Unit Price *</label>
                <input type="number" v-model="form.unit_price" required class="w-full border rounded-lg px-3 py-2">
              </div>
              <div>
                <label class="block text-sm font-medium mb-1">Cost Price</label>
                <input type="number" v-model="form.cost_price" class="w-full border rounded-lg px-3 py-2">
              </div>
              <div>
                <label class="block text-sm font-medium mb-1">Tax Group</label>
                <select v-model="form.tax_group_id" class="w-full border rounded-lg px-3 py-2">
                  <option value="">Select Tax Group</option>
                  <option v-for="taxGroup in taxGroups" :key="taxGroup.id" :value="taxGroup.id">
                    {{ taxGroup.name }}
                  </option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium mb-1">Status</label>
                <select v-model="form.is_active" class="w-full border rounded-lg px-3 py-2">
                  <option :value="true">Active</option>
                  <option :value="false">Inactive</option>
                </select>
              </div>
              <div class="col-span-2">
                <label class="block text-sm font-medium mb-1">Description</label>
                <textarea v-model="form.description" rows="3" class="w-full border rounded-lg px-3 py-2"></textarea>
              </div>
            </div>

            <div class="flex justify-end gap-2 pt-4">
              <Button type="button" variant="secondary" @click="closeModal">Cancel</Button>
              <Button type="submit">{{ isEditing ? 'Update' : 'Create' }}</Button>
            </div>
          </form>
        </Modal>

        <Modal v-if="showViewModal" @close="showViewModal = false" title="Product Details" size="lg">
          <div v-if="selectedProduct" class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
              <div>
                <p class="text-sm text-gray-1">Name</p>
                <p class="font-semibold">{{ selectedProduct.name }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-1">SKU</p>
                <p class="font-semibold">{{ selectedProduct.sku || '—' }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-1">Category</p>
                <p class="font-semibold">{{ selectedProduct.category?.name || '—' }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-1">Unit Price</p>
                <p class="font-semibold">{{ formatCurrency(selectedProduct.unit_price) }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-1">Cost Price</p>
                <p class="font-semibold">{{ formatCurrency(selectedProduct.cost_price || 0) }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-1">Stock</p>
                <p class="font-semibold">{{ selectedProduct.stock_quantity || 0 }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-1">Status</p>
                <Badge :variant="selectedProduct.is_active ? 'default' : 'secondary'">
                  {{ selectedProduct.is_active ? 'Active' : 'Inactive' }}
                </Badge>
              </div>
              <div class="col-span-2">
                <p class="text-sm text-gray-1">Description</p>
                <p class="font-semibold">{{ selectedProduct.description || '—' }}</p>
              </div>
            </div>
          </div>
        </Modal>
      </AdminLayout>
    </SidebarProvider>
  </ThemeProvider>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import ThemeProvider from '@/Components/Layout/ThemeProvider.vue'
import SidebarProvider from '@/Components/Layout/SidebarProvider.vue'
import AdminLayout from '@/Components/layout/AdminLayout.vue'
import Card from '@/Components/ui/Card.vue'
import Button from '@/Components/ui/Button.vue'
import DataTable from '@/Components/UI/DataTable.vue'
import Badge from '@/Components/UI/Badge.vue'
import Modal from '@/Components/UI/Modal.vue'

const products = ref([])
const categories = ref([])
const taxGroups = ref([])
const pagination = ref({ current_page: 1, per_page: 15, total: 0 })
const loading = ref(false)
const showModal = ref(false)
const showViewModal = ref(false)
const isEditing = ref(false)
const selectedProduct = ref(null)
const searchQuery = ref('')

const form = ref({
  name: '',
  sku: '',
  category_id: '',
  unit_price: 0,
  cost_price: 0,
  tax_group_id: '',
  is_active: true,
  description: ''
})

const columns = [
  { key: 'name', label: 'Product' },
  { key: 'price', label: 'Price', right: true },
  { key: 'stock', label: 'Stock' },
  { key: 'status', label: 'Status' }
]

const fetchProducts = async (page = 1) => {
  loading.value = true
  try {
    const response = await axios.get('/api/v1/products', {
      params: { page, per_page: 15, search: searchQuery.value }
    })
    products.value = response.data.data
    pagination.value = response.data
  } catch (error) {
    console.error('Failed to fetch products:', error)
  } finally {
    loading.value = false
  }
}

const fetchCategories = async () => {
  try {
    const response = await axios.get('/api/v1/product-categories', { params: { per_page: 100 } })
    categories.value = response.data.data
  } catch (error) {
    console.error('Failed to fetch categories:', error)
  }
}

const fetchTaxGroups = async () => {
  try {
    const response = await axios.get('/api/v1/tax-groups', { params: { per_page: 100 } })
    taxGroups.value = response.data.data
  } catch (error) {
    console.error('Failed to fetch tax groups:', error)
  }
}

const handleSearch = (query) => {
  searchQuery.value = query
  fetchProducts(1)
}

const openCreateModal = () => {
  isEditing.value = false
  form.value = {
    name: '',
    sku: '',
    category_id: '',
    unit_price: 0,
    cost_price: 0,
    tax_group_id: '',
    is_active: true,
    description: ''
  }
  showModal.value = true
}

const editProduct = (product) => {
  isEditing.value = true
  selectedProduct.value = product
  form.value = {
    name: product.name,
    sku: product.sku,
    category_id: product.category_id,
    unit_price: product.unit_price,
    cost_price: product.cost_price,
    tax_group_id: product.tax_group_id,
    is_active: product.is_active,
    description: product.description
  }
  showModal.value = true
}

const viewProduct = (product) => {
  selectedProduct.value = product
  showViewModal.value = true
}

const handleSubmit = async () => {
  try {
    if (isEditing.value) {
      await axios.put(`/api/v1/products/${selectedProduct.value.id}`, form.value)
    } else {
      await axios.post('/api/v1/products', form.value)
    }
    closeModal()
    fetchProducts(pagination.value.current_page)
  } catch (error) {
    console.error('Failed to save product:', error)
  }
}

const deleteProduct = async (product) => {
  if (!confirm('Are you sure you want to delete this product?')) return
  try {
    await axios.delete(`/api/v1/products/${product.id}`)
    fetchProducts(pagination.value.current_page)
  } catch (error) {
    console.error('Failed to delete product:', error)
  }
}

const closeModal = () => {
  showModal.value = false
  isEditing.value = false
  selectedProduct.value = null
}

const exportData = () => {
  window.print()
}

const getStockVariant = (stock) => {
  if (stock === 0) return 'destructive'
  if (stock < 10) return 'secondary'
  return 'default'
}

const getStockLabel = (stock) => {
  if (stock === 0) return 'Out of Stock'
  if (stock < 10) return 'Low Stock'
  return 'In Stock'
}

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(amount / 100)
}

onMounted(() => {
  fetchProducts()
  fetchCategories()
  fetchTaxGroups()
})
</script>
