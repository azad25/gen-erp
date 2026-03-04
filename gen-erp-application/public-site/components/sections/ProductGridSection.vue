<template>
  <section class="section-padding bg-gray-50">
    <div class="container-custom">
      <div class="text-center mb-12">
        <h2 class="text-3xl font-bold text-gray-900 mb-4">
          {{ content.heading || 'Our Products' }}
        </h2>
        <p v-if="content.subheading" class="text-lg text-gray-600">
          {{ content.subheading }}
        </p>
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
          class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow duration-200 group"
        >
          <!-- Product Image -->
          <div class="aspect-w-1 aspect-h-1 bg-gray-200 overflow-hidden">
            <NuxtImg
              v-if="product.image"
              :src="product.image"
              :alt="product.name"
              class="w-full h-48 object-cover group-hover:scale-105 transition-transform duration-200"
              loading="lazy"
              format="webp"
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
                  ${{ formatPrice(product.selling_price) }}
                </span>
                <span v-if="product.compare_price && product.compare_price > product.selling_price" class="text-sm text-gray-500 line-through ml-2">
                  ${{ formatPrice(product.compare_price) }}
                </span>
              </div>
              
              <div class="flex items-center space-x-2">
                <div v-if="product.is_featured" class="flex items-center">
                  <svg class="w-4 h-4 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                  </svg>
                </div>
                
                <button
                  v-if="content.show_add_to_cart"
                  @click="addToCart(product)"
                  class="px-3 py-1 bg-blue-600 text-white text-sm rounded hover:bg-blue-700 transition-colors"
                >
                  Add to Cart
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Loading State -->
      <div v-else-if="loading" class="text-center py-12">
        <div class="spinner w-8 h-8 mx-auto mb-4"></div>
        <p class="text-gray-600">Loading products...</p>
      </div>
      
      <!-- Empty State -->
      <div v-else class="text-center py-12">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900">No products found</h3>
        <p class="mt-1 text-sm text-gray-500">Check back later for new products</p>
      </div>
      
      <!-- View All Button -->
      <div v-if="content.show_view_all && products.length > 0" class="text-center mt-12">
        <NuxtLink
          to="/products"
          class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 transition-colors"
        >
          View All Products
        </NuxtLink>
      </div>
    </div>
  </section>
</template>

<script setup>
interface ProductGridContent {
  heading?: string
  subheading?: string
  layout?: '2-col' | '3-col' | '4-col'
  limit?: number
  featured_only?: boolean
  show_price?: boolean
  show_add_to_cart?: boolean
  show_view_all?: boolean
  filter_tag?: string
}

interface Product {
  id: string
  name: string
  description?: string
  selling_price?: number
  compare_price?: number
  image?: string
  is_featured?: boolean
}

const props = defineProps<{
  content: ProductGridContent
  tenant?: any
}>()

const config = useRuntimeConfig()
const products = ref<Product[]>([])
const loading = ref(true)

const fetchProducts = async () => {
  try {
    loading.value = true
    
    const params = new URLSearchParams({
      limit: (props.content.limit || 6).toString(),
      sort_by: 'name',
      sort_order: 'asc'
    })
    
    if (props.content.featured_only) {
      params.append('featured_only', 'true')
    }
    
    if (props.content.filter_tag) {
      params.append('tag', props.content.filter_tag)
    }
    
    const headers: Record<string, string> = {}
    if (props.tenant?.id) {
      headers['X-Tenant-ID'] = props.tenant.id
    }
    
    const response = await $fetch<{ data: Product[] }>(`${config.public.apiBase}/public/products?${params}`, {
      headers
    })
    
    products.value = response.data || []
  } catch (error) {
    console.error('Error fetching products:', error)
    products.value = []
  } finally {
    loading.value = false
  }
}

const formatPrice = (price: number) => {
  return new Intl.NumberFormat('en-US', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(price)
}

const addToCart = (product: Product) => {
  // TODO: Implement add to cart functionality
  console.log('Add to cart:', product)
  // This would typically dispatch to a cart store or make an API call
}

// Fetch products on mount
onMounted(() => {
  fetchProducts()
})
</script>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>