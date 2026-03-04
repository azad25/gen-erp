<template>
  <div>
    <Head title="Blog Posts" />
    
    <div class="py-6">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="md:flex md:items-center md:justify-between mb-6">
          <div class="flex-1 min-w-0">
            <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
              Blog Posts
            </h2>
            <p class="mt-1 text-sm text-gray-500">
              Manage your blog posts and content
            </p>
          </div>
          <div class="mt-4 flex md:mt-0 md:ml-4">
            <Link
              :href="route('cms.blog.create')"
              class="ml-3 inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
            >
              <Icon name="heroicons:plus" class="w-4 h-4 mr-2" />
              New Post
            </Link>
          </div>
        </div>

        <!-- Filters -->
        <div class="bg-white shadow rounded-lg mb-6">
          <div class="px-4 py-5 sm:p-6">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-4">
              <!-- Search -->
              <div class="sm:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                <input
                  v-model="filters.search"
                  @input="debouncedSearch"
                  type="text"
                  placeholder="Search posts..."
                  class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                />
              </div>

              <!-- Status Filter -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select
                  v-model="filters.status"
                  @change="applyFilters"
                  class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                >
                  <option value="">All Status</option>
                  <option value="draft">Draft</option>
                  <option value="published">Published</option>
                  <option value="scheduled">Scheduled</option>
                </select>
              </div>

              <!-- Category Filter -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                <select
                  v-model="filters.category"
                  @change="applyFilters"
                  class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                >
                  <option value="">All Categories</option>
                  <option
                    v-for="category in categories"
                    :key="category.id"
                    :value="category.id"
                  >
                    {{ category.name }}
                  </option>
                </select>
              </div>
            </div>
          </div>
        </div>

        <!-- Posts Grid -->
        <div class="bg-white shadow overflow-hidden sm:rounded-md">
          <ul class="divide-y divide-gray-200">
            <li v-for="post in posts.data" :key="post.id">
              <div class="px-4 py-4 flex items-center justify-between">
                <div class="flex items-center min-w-0 flex-1">
                  <!-- Featured Image -->
                  <div class="flex-shrink-0 mr-4">
                    <img
                      v-if="post.featured_image"
                      :src="post.featured_image"
                      :alt="post.title"
                      class="h-16 w-16 rounded-lg object-cover"
                    />
                    <div
                      v-else
                      class="h-16 w-16 rounded-lg bg-gray-200 flex items-center justify-center"
                    >
                      <Icon name="heroicons:photo" class="w-6 h-6 text-gray-400" />
                    </div>
                  </div>

                  <!-- Post Info -->
                  <div class="min-w-0 flex-1">
                    <div class="flex items-center justify-between">
                      <div>
                        <Link
                          :href="route('cms.blog.edit', post.id)"
                          class="text-lg font-medium text-gray-900 hover:text-blue-600 transition-colors"
                        >
                          {{ post.title }}
                        </Link>
                        <p class="text-sm text-gray-500 mt-1 line-clamp-2">
                          {{ post.excerpt }}
                        </p>
                      </div>
                      
                      <!-- Status Badge -->
                      <div class="ml-4">
                        <span
                          :class="{
                            'bg-green-100 text-green-800': post.status === 'published',
                            'bg-yellow-100 text-yellow-800': post.status === 'draft',
                            'bg-blue-100 text-blue-800': post.status === 'scheduled'
                          }"
                          class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                        >
                          {{ post.status }}
                        </span>
                      </div>
                    </div>

                    <!-- Meta Info -->
                    <div class="mt-2 flex items-center text-sm text-gray-500 space-x-4">
                      <div class="flex items-center">
                        <Icon name="heroicons:calendar" class="w-4 h-4 mr-1" />
                        {{ formatDate(post.created_at) }}
                      </div>
                      <div v-if="post.category" class="flex items-center">
                        <Icon name="heroicons:tag" class="w-4 h-4 mr-1" />
                        {{ post.category.name }}
                      </div>
                      <div class="flex items-center">
                        <Icon name="heroicons:eye" class="w-4 h-4 mr-1" />
                        {{ post.views_count || 0 }} views
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center space-x-2 ml-4">
                  <Link
                    :href="route('cms.blog.edit', post.id)"
                    class="text-gray-400 hover:text-blue-600 transition-colors"
                    title="Edit"
                  >
                    <Icon name="heroicons:pencil" class="w-5 h-5" />
                  </Link>
                  
                  <button
                    v-if="post.status === 'published'"
                    @click="previewPost(post)"
                    class="text-gray-400 hover:text-green-600 transition-colors"
                    title="Preview"
                  >
                    <Icon name="heroicons:eye" class="w-5 h-5" />
                  </button>
                  
                  <button
                    @click="duplicatePost(post)"
                    class="text-gray-400 hover:text-purple-600 transition-colors"
                    title="Duplicate"
                  >
                    <Icon name="heroicons:document-duplicate" class="w-5 h-5" />
                  </button>
                  
                  <button
                    @click="deletePost(post)"
                    class="text-gray-400 hover:text-red-600 transition-colors"
                    title="Delete"
                  >
                    <Icon name="heroicons:trash" class="w-5 h-5" />
                  </button>
                </div>
              </div>
            </li>
          </ul>

          <!-- Empty State -->
          <div v-if="posts.data.length === 0" class="text-center py-12">
            <Icon name="heroicons:document-text" class="w-12 h-12 mx-auto text-gray-400 mb-4" />
            <h3 class="text-lg font-medium text-gray-900 mb-2">No blog posts</h3>
            <p class="text-gray-500 mb-4">Get started by creating your first blog post.</p>
            <Link
              :href="route('cms.blog.create')"
              class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700"
            >
              <Icon name="heroicons:plus" class="w-4 h-4 mr-2" />
              Create Post
            </Link>
          </div>
        </div>

        <!-- Pagination -->
        <div v-if="posts.data.length > 0" class="mt-6">
          <Pagination :links="posts.links" />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { debounce } from 'lodash'
import Icon from '@/Components/UI/Icon.vue'
import Pagination from '@/Components/UI/Pagination.vue'

const props = defineProps({
  posts: Object,
  categories: Array,
  filters: Object
})

const filters = ref({
  search: props.filters.search || '',
  status: props.filters.status || '',
  category: props.filters.category || ''
})

// Methods
const applyFilters = () => {
  router.get(route('cms.blog.index'), filters.value, {
    preserveState: true,
    preserveScroll: true
  })
}

const debouncedSearch = debounce(() => {
  applyFilters()
}, 300)

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const previewPost = (post) => {
  const previewUrl = route('cms.blog.preview', post.slug)
  window.open(previewUrl, '_blank')
}

const duplicatePost = (post) => {
  if (confirm('Are you sure you want to duplicate this post?')) {
    router.post(route('cms.blog.duplicate', post.id), {}, {
      preserveState: true
    })
  }
}

const deletePost = (post) => {
  if (confirm('Are you sure you want to delete this post? This action cannot be undone.')) {
    router.delete(route('cms.blog.destroy', post.id), {
      preserveState: true
    })
  }
}
</script>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>