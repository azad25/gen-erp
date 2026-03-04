<template>
  <div class="py-12 bg-gray-50">
    <div class="max-w-7xl mx-auto px-6">
      <div class="text-center mb-12">
        <h2 class="text-3xl font-bold text-gray-900 mb-4">
          {{ content.heading || 'Our Products' }}
        </h2>
      </div>
      
      <!-- Products Grid -->
      <div
        v-if="products.length > 0"
        :class="{
          'grid-cols-2': content.layout === '2-col',
          'grid-cols-3': content.layout === '3-col',
          'grid-cols-4': content.layout === '4-col'
        }"
        class="grid gap-6 md:gap-8"
      >
        <div
          v-for="product in products"
          :key="product.id"
          class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow duration-200"
          :class="{ 'pointer-events-none': isEditing }"
        >
          <!-- Product Image -->
          <div class="aspect-w-1 aspect-h-1 bg-gray-200">
            <img
              v-if="product.image"
              :src="product.image"
              :alt="product.name"
              class="w-full h-48 object-cover"
            />
            <div
              v-else
              class="w-full h-48 bg-gray-200 flex items-center justify-center"
            >
              <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
            </div>
          </div>
          
          <!-- Product Info -->
          <div class="p-4">
            <h3 class="text-lg font-semibold text-gray-900 mb-2">
              {{ product.name }}
            </h3>
            
            <p
              v-if="product.description"
              class="text-sm text-gray-600 mb-3 line-clamp-2"
            >
              {{ product.description }}
            </p>
            
            <div class="flex items-center justify-between">
              <div v-if="content.show_price && product.selling_price">
                <span class="text-lg font-bold text-gray-900">
                  ${{ product.selling_price }}
                </span>
              </div>
              
              <div v-if="product.is_featured" class="flex items-center">
                <svg class="w-4 h-4 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                  <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                </svg>
                <span class="text-xs text-gray-500 ml-1">Featured</span>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Loading State -->
      <div v-else-if="loading" class="text-center py-12">
        <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
        <p class="mt-2 text-gray-600">Loading products...</p>
      </div>
      
      <!-- Empty State -->
      <div v-else class="text-center py-12">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900">No products found</h3>
        <p class="mt-1 text-sm text-gray-500">
          {{ isEditing ? 'Products will appear here when available' : 'Check back later for new products' }}
        </p>
      </div>
    </div>
    
    <!-- Editing Overlay -->
    <div
      v-if="isEditing"
      class="absolute inset-0 bg-blue-500 bg-opacity-5 border border-blue-300 rounded"
    ></div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import axios from 'axios'

const props = defineProps({
  content: {
    type: Object,
    default: () => ({})
  },
  isEditing: {
    type: Boolean,
    default: false
  }
})

const products = ref([])
const loading = ref(false)

const fetchProducts = async () => {
  if (props.isEditing) {
    // In editing mode, show mock data
    products.value = [
      {
        id: 1,
        name: 'Sample Product 1',
        description: 'This is a sample product description',
        selling_price: 99.99,
        image: null,
        is_featured: true
      },
      {
        id: 2,
        name: 'Sample Product 2',
        description: 'Another sample product description',
        selling_price: 149.99,
        image: null,
        is_featured: false
      },
      {
        id: 3,
        name: 'Sample Product 3',
        description: 'Yet another sample product description',
        selling_price: 79.99,
        image: null,
        is_featured: false
      }
    ]
    return
  }
  
  loading.value = true
  
  try {
    const params = {
      limit: props.content.limit || 6,
      featured_only: props.content.featured_only || false,
      sort_by: 'name',
      sort_order: 'asc'
    }
    
    if (props.content.filter_tag) {
      params.tag = props.content.filter_tag
    }
    
    const response = await axios.get('/api/v1/cms/erp/products', { params })
    products.value = response.data.data || []
  } catch (error) {
    console.error('Error fetching products:', error)
    products.value = []
  } finally {
    loading.value = false
  }
}

// Watch for content changes
watch(() => props.content, fetchProducts, { deep: true })

onMounted(() => {
  fetchProducts()
})
</script>