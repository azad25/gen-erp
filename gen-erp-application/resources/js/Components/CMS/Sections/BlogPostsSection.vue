<template>
  <div class="py-12 bg-gray-50">
    <div class="max-w-7xl mx-auto px-6">
      <div class="text-center mb-12">
        <h2 class="text-3xl font-bold text-gray-900 mb-4">
          {{ content.heading || 'Latest Blog Posts' }}
        </h2>
        <p v-if="content.subheading" class="text-lg text-gray-600">
          {{ content.subheading }}
        </p>
      </div>
      
      <!-- Blog Posts Grid -->
      <div
        v-if="blogPosts.length > 0"
        :class="{
          'grid-cols-1': content.layout === '1-col',
          'grid-cols-2': content.layout === '2-col',
          'grid-cols-3': content.layout === '3-col'
        }"
        class="grid gap-8"
      >
        <article
          v-for="post in displayPosts"
          :key="post.id"
          class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow duration-200"
          :class="{ 'pointer-events-none': isEditing }"
        >
          <!-- Featured Image -->
          <div class="aspect-w-16 aspect-h-9 bg-gray-200">
            <img
              v-if="post.featured_image"
              :src="post.featured_image"
              :alt="post.title"
              class="w-full h-48 object-cover"
            />
            <div
              v-else
              class="w-full h-48 bg-gray-200 flex items-center justify-center"
            >
              <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
              </svg>
            </div>
          </div>
          
          <!-- Post Content -->
          <div class="p-6">
            <!-- Category & Date -->
            <div class="flex items-center justify-between mb-3">
              <span
                v-if="post.category"
                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800"
              >
                {{ post.category.name }}
              </span>
              <time class="text-sm text-gray-500">
                {{ formatDate(post.published_at || post.created_at) }}
              </time>
            </div>
            
            <!-- Title -->
            <h3 class="text-xl font-semibold text-gray-900 mb-3 line-clamp-2">
              <a
                :href="isEditing ? '#' : `/blog/${post.slug}`"
                class="hover:text-blue-600 transition-colors"
                :class="{ 'pointer-events-none': isEditing }"
              >
                {{ post.title }}
              </a>
            </h3>
            
            <!-- Excerpt -->
            <p class="text-gray-600 mb-4 line-clamp-3">
              {{ post.excerpt || stripHtml(post.content).substring(0, 150) + '...' }}
            </p>
            
            <!-- Author & Read More -->
            <div class="flex items-center justify-between">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <div class="w-8 h-8 bg-gray-300 rounded-full flex items-center justify-center">
                    <span class="text-xs font-medium text-gray-600">
                      {{ post.author?.first_name?.charAt(0) || 'A' }}{{ post.author?.last_name?.charAt(0) || 'U' }}
                    </span>
                  </div>
                </div>
                <div class="ml-3">
                  <p class="text-sm font-medium text-gray-900">
                    {{ post.author?.full_name || 'Anonymous' }}
                  </p>
                </div>
              </div>
              
              <a
                :href="isEditing ? '#' : `/blog/${post.slug}`"
                class="text-blue-600 hover:text-blue-800 text-sm font-medium"
                :class="{ 'pointer-events-none': isEditing }"
              >
                Read More →
              </a>
            </div>
            
            <!-- Tags -->
            <div v-if="post.tags && post.tags.length > 0" class="mt-4 pt-4 border-t border-gray-200">
              <div class="flex flex-wrap gap-2">
                <span
                  v-for="tag in post.tags.slice(0, 3)"
                  :key="tag"
                  class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-gray-100 text-gray-800"
                >
                  #{{ tag }}
                </span>
                <span
                  v-if="post.tags.length > 3"
                  class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-gray-100 text-gray-800"
                >
                  +{{ post.tags.length - 3 }} more
                </span>
              </div>
            </div>
          </div>
        </article>
      </div>
      
      <!-- View All Button -->
      <div v-if="content.show_view_all && blogPosts.length > displayPosts.length" class="text-center mt-12">
        <a
          :href="isEditing ? '#' : '/blog'"
          class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 transition-colors"
          :class="{ 'pointer-events-none': isEditing }"
        >
          View All Posts
        </a>
      </div>
      
      <!-- Empty State -->
      <div v-if="blogPosts.length === 0" class="text-center py-12">
        <div class="text-gray-500">
          <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
          </svg>
          <h3 class="mt-2 text-sm font-medium text-gray-900">No blog posts</h3>
          <p class="mt-1 text-sm text-gray-500">
            {{ isEditing ? 'Blog posts will appear here when published.' : 'Check back soon for new content!' }}
          </p>
        </div>
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
import { computed, onMounted, ref } from 'vue'

const props = defineProps({
  content: {
    type: Object,
    default: () => ({
      heading: 'Latest Blog Posts',
      subheading: '',
      layout: '3-col',
      posts_count: 6,
      category_filter: '',
      show_view_all: true,
      show_featured_only: false
    })
  },
  isEditing: {
    type: Boolean,
    default: false
  }
})

const blogPosts = ref([])
const loading = ref(true)

// Computed property for posts to display
const displayPosts = computed(() => {
  let posts = blogPosts.value

  // Filter by category if specified
  if (props.content.category_filter) {
    posts = posts.filter(post => 
      post.category && post.category.slug === props.content.category_filter
    )
  }

  // Filter featured only if specified
  if (props.content.show_featured_only) {
    posts = posts.filter(post => post.is_featured)
  }

  // Limit number of posts
  return posts.slice(0, props.content.posts_count || 6)
})

// Fetch blog posts
const fetchBlogPosts = async () => {
  try {
    loading.value = true
    const response = await fetch('/api/public/blog-posts')
    const data = await response.json()
    blogPosts.value = data.data || []
  } catch (error) {
    console.error('Error fetching blog posts:', error)
    blogPosts.value = []
  } finally {
    loading.value = false
  }
}

// Utility functions
const formatDate = (dateString) => {
  if (!dateString) return ''
  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

const stripHtml = (html) => {
  if (!html) return ''
  const tmp = document.createElement('div')
  tmp.innerHTML = html
  return tmp.textContent || tmp.innerText || ''
}

// Load posts on mount
onMounted(() => {
  if (!props.isEditing) {
    fetchBlogPosts()
  } else {
    // Mock data for editing mode
    blogPosts.value = [
      {
        id: 1,
        title: 'Getting Started with Our Platform',
        slug: 'getting-started-with-our-platform',
        excerpt: 'Learn how to make the most of our platform with this comprehensive guide.',
        content: '<p>This is a sample blog post content...</p>',
        featured_image: null,
        published_at: '2024-01-15T10:00:00Z',
        category: { name: 'Tutorials', slug: 'tutorials' },
        author: { first_name: 'John', last_name: 'Doe', full_name: 'John Doe' },
        tags: ['tutorial', 'getting-started', 'guide'],
        is_featured: true
      },
      {
        id: 2,
        title: 'New Features Released',
        slug: 'new-features-released',
        excerpt: 'We are excited to announce several new features that will enhance your experience.',
        content: '<p>This is another sample blog post...</p>',
        featured_image: null,
        published_at: '2024-01-10T14:00:00Z',
        category: { name: 'News', slug: 'news' },
        author: { first_name: 'Jane', last_name: 'Smith', full_name: 'Jane Smith' },
        tags: ['news', 'features', 'updates'],
        is_featured: false
      },
      {
        id: 3,
        title: 'Best Practices for Success',
        slug: 'best-practices-for-success',
        excerpt: 'Discover the best practices that will help you achieve success with our platform.',
        content: '<p>Here are some best practices...</p>',
        featured_image: null,
        published_at: '2024-01-05T09:00:00Z',
        category: { name: 'Tips', slug: 'tips' },
        author: { first_name: 'Mike', last_name: 'Johnson', full_name: 'Mike Johnson' },
        tags: ['tips', 'best-practices', 'success'],
        is_featured: false
      }
    ]
    loading.value = false
  }
})
</script>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.line-clamp-3 {
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>