<template>
  <section class="py-16" :style="{ backgroundColor: content.background_color || 'transparent' }">
    <div class="container mx-auto px-4">
      <!-- Section Header -->
      <div v-if="content.title || content.subtitle" class="text-center mb-12">
        <h2 
          v-if="content.title" 
          class="text-3xl md:text-4xl font-bold mb-4"
          :style="{ color: content.title_color || tenant?.settings?.primary_color || '#1f2937' }"
        >
          {{ content.title }}
        </h2>
        <p v-if="content.subtitle" class="text-xl text-gray-600 max-w-3xl mx-auto">
          {{ content.subtitle }}
        </p>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        <div 
          v-for="i in 3" 
          :key="i" 
          class="bg-white rounded-lg shadow-md overflow-hidden animate-pulse"
        >
          <div class="h-48 bg-gray-300"></div>
          <div class="p-6">
            <div class="h-4 bg-gray-300 rounded mb-2"></div>
            <div class="h-3 bg-gray-300 rounded w-3/4 mb-4"></div>
            <div class="flex items-center space-x-4">
              <div class="w-8 h-8 bg-gray-300 rounded-full"></div>
              <div class="h-3 bg-gray-300 rounded w-24"></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="text-center py-12">
        <div class="text-red-500 mb-4">
          <svg class="w-16 h-16 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
          </svg>
        </div>
        <p class="text-gray-600">Failed to load blog posts</p>
      </div>

      <!-- Blog Posts Grid -->
      <div v-else-if="blogPosts.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        <article 
          v-for="post in blogPosts" 
          :key="post.id"
          class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow duration-300 group"
        >
          <!-- Featured Image -->
          <div class="relative h-48 overflow-hidden">
            <NuxtLink :to="`/blog/${post.slug}`">
              <NuxtImg
                v-if="post.featured_image"
                :src="post.featured_image"
                :alt="post.title"
                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                loading="lazy"
                format="webp"
                quality="80"
              />
              <div v-else class="w-full h-full bg-gray-200 flex items-center justify-center">
                <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9.5a2.5 2.5 0 00-2.5-2.5H15"></path>
                </svg>
              </div>
            </NuxtLink>
            
            <!-- Category Badge -->
            <div v-if="post.category" class="absolute top-4 left-4">
              <span 
                class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium text-white"
                :style="{ backgroundColor: tenant?.settings?.primary_color || '#3b82f6' }"
              >
                {{ post.category }}
              </span>
            </div>
          </div>

          <!-- Post Content -->
          <div class="p-6">
            <!-- Title -->
            <h3 class="text-xl font-semibold mb-3 text-gray-900 group-hover:text-blue-600 transition-colors duration-200">
              <NuxtLink :to="`/blog/${post.slug}`" class="hover:underline">
                {{ post.title }}
              </NuxtLink>
            </h3>
            
            <!-- Excerpt -->
            <p v-if="post.excerpt" class="text-gray-600 mb-4 line-clamp-3">
              {{ post.excerpt }}
            </p>

            <!-- Meta Information -->
            <div class="flex items-center justify-between text-sm text-gray-500">
              <div class="flex items-center space-x-2">
                <!-- Author Avatar -->
                <div class="flex-shrink-0">
                  <NuxtImg
                    v-if="post.author_avatar"
                    :src="post.author_avatar"
                    :alt="post.author_name"
                    class="w-8 h-8 rounded-full object-cover"
                    loading="lazy"
                    format="webp"
                    quality="80"
                  />
                  <div 
                    v-else
                    class="w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-semibold"
                    :style="{ backgroundColor: tenant?.settings?.primary_color || '#3b82f6' }"
                  >
                    {{ getAuthorInitials(post.author_name) }}
                  </div>
                </div>
                
                <!-- Author & Date -->
                <div>
                  <p class="font-medium text-gray-700">{{ post.author_name }}</p>
                  <p class="text-xs">{{ formatDate(post.published_at) }}</p>
                </div>
              </div>

              <!-- Read Time -->
              <div v-if="post.read_time" class="flex items-center text-xs text-gray-400">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                {{ post.read_time }} min read
              </div>
            </div>

            <!-- Tags -->
            <div v-if="post.tags && post.tags.length > 0" class="flex flex-wrap gap-2 mt-4">
              <span 
                v-for="tag in post.tags.slice(0, 3)" 
                :key="tag"
                class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-gray-100 text-gray-700 hover:bg-gray-200 transition-colors duration-200"
              >
                #{{ tag }}
              </span>
              <span 
                v-if="post.tags.length > 3"
                class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-gray-100 text-gray-700"
              >
                +{{ post.tags.length - 3 }} more
              </span>
            </div>
          </div>
        </article>
      </div>

      <!-- Empty State -->
      <div v-else class="text-center py-12">
        <div class="text-gray-400 mb-4">
          <svg class="w-16 h-16 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9.5a2.5 2.5 0 00-2.5-2.5H15"></path>
          </svg>
        </div>
        <p class="text-gray-600">No blog posts available</p>
      </div>

      <!-- View All Button -->
      <div v-if="content.show_view_all && blogPosts.length >= content.limit" class="text-center mt-12">
        <NuxtLink
          to="/blog"
          class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white transition-colors duration-200"
          :style="{ backgroundColor: tenant?.settings?.primary_color || '#3b82f6' }"
        >
          View All Posts
          <svg class="ml-2 w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
          </svg>
        </NuxtLink>
      </div>
    </div>
  </section>
</template>

<script setup>
interface BlogPost {
  id: string
  title: string
  slug: string
  excerpt?: string
  featured_image?: string
  category?: string
  author_name: string
  author_avatar?: string
  published_at: string
  read_time?: number
  tags?: string[]
}

interface Content {
  title?: string
  subtitle?: string
  background_color?: string
  title_color?: string
  limit?: number
  category_filter?: string
  show_view_all?: boolean
}

interface Tenant {
  id: string
  name: string
  slug: string
  settings: Record<string, any>
}

const props = defineProps<{
  content: Content
  tenant?: Tenant
}>()

const { $fetch } = useNuxtApp()
const blogPosts = ref<BlogPost[]>([])
const loading = ref(true)
const error = ref(false)

// Fetch blog posts
const fetchBlogPosts = async () => {
  try {
    loading.value = true
    error.value = false
    
    const params = new URLSearchParams()
    if (props.content.limit) params.append('limit', props.content.limit.toString())
    if (props.content.category_filter) params.append('category', props.content.category_filter)
    
    const response = await $fetch(`/api/public/${props.tenant?.slug}/blog?${params.toString()}`)
    
    blogPosts.value = response.data || []
  } catch (err) {
    console.error('Failed to fetch blog posts:', err)
    error.value = true
    // Use mock data in development
    if (process.dev) {
      blogPosts.value = getMockBlogPosts()
    }
  } finally {
    loading.value = false
  }
}

// Mock blog posts for development
const getMockBlogPosts = (): BlogPost[] => [
  {
    id: '1',
    title: 'The Future of Web Development: Trends to Watch in 2026',
    slug: 'future-web-development-trends-2026',
    excerpt: 'Explore the latest trends shaping the future of web development, from AI-powered tools to advanced frameworks that are revolutionizing how we build applications.',
    featured_image: 'https://images.unsplash.com/photo-1461749280684-dccba630e2f6?w=800&h=600&fit=crop',
    category: 'Technology',
    author_name: 'John Doe',
    published_at: '2026-03-01',
    read_time: 8,
    tags: ['Web Development', 'AI', 'Trends', 'Technology']
  },
  {
    id: '2',
    title: 'Building Scalable E-commerce Solutions with Laravel',
    slug: 'scalable-ecommerce-laravel',
    excerpt: 'Learn how to build robust and scalable e-commerce platforms using Laravel, with best practices for performance, security, and user experience.',
    featured_image: 'https://images.unsplash.com/photo-1556742049-0cfed4f6a45d?w=800&h=600&fit=crop',
    category: 'Development',
    author_name: 'Jane Smith',
    published_at: '2026-02-28',
    read_time: 12,
    tags: ['Laravel', 'E-commerce', 'PHP', 'Scalability']
  },
  {
    id: '3',
    title: 'UI/UX Design Principles for Modern Web Applications',
    slug: 'ui-ux-design-principles-modern-web',
    excerpt: 'Discover essential UI/UX design principles that create engaging and user-friendly web applications that convert visitors into customers.',
    featured_image: 'https://images.unsplash.com/photo-1561070791-2526d30994b5?w=800&h=600&fit=crop',
    category: 'Design',
    author_name: 'Mike Johnson',
    published_at: '2026-02-25',
    read_time: 6,
    tags: ['UI/UX', 'Design', 'User Experience', 'Web Design']
  },
  {
    id: '4',
    title: 'Mobile-First Development: Best Practices and Tools',
    slug: 'mobile-first-development-best-practices',
    excerpt: 'Master mobile-first development with proven strategies, tools, and techniques for creating responsive applications that work seamlessly across all devices.',
    featured_image: 'https://images.unsplash.com/photo-1512941937669-90a1b58e7e9c?w=800&h=600&fit=crop',
    category: 'Mobile',
    author_name: 'Sarah Wilson',
    published_at: '2026-02-20',
    read_time: 10,
    tags: ['Mobile Development', 'Responsive Design', 'React Native', 'Flutter']
  },
  {
    id: '5',
    title: 'Database Optimization Techniques for High-Performance Apps',
    slug: 'database-optimization-high-performance',
    excerpt: 'Optimize your database performance with advanced techniques including indexing, query optimization, and caching strategies for faster applications.',
    featured_image: 'https://images.unsplash.com/photo-1544383835-bda2bc66a55d?w=800&h=600&fit=crop',
    category: 'Database',
    author_name: 'David Brown',
    published_at: '2026-02-15',
    read_time: 15,
    tags: ['Database', 'Performance', 'MySQL', 'Optimization']
  },
  {
    id: '6',
    title: 'Security Best Practices for Web Applications',
    slug: 'security-best-practices-web-applications',
    excerpt: 'Protect your web applications with comprehensive security measures, from authentication and authorization to data encryption and vulnerability prevention.',
    featured_image: 'https://images.unsplash.com/photo-1563013544-824ae1b704d3?w=800&h=600&fit=crop',
    category: 'Security',
    author_name: 'Lisa Chen',
    published_at: '2026-02-10',
    read_time: 9,
    tags: ['Security', 'Web Security', 'Authentication', 'Encryption']
  }
]

// Helper functions
const getAuthorInitials = (name: string) => {
  return name
    .split(' ')
    .map(word => word.charAt(0))
    .join('')
    .toUpperCase()
    .slice(0, 2)
}

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

// Initialize
onMounted(() => {
  fetchBlogPosts()
})
</script>

<style scoped>
.line-clamp-3 {
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

/* Hover effects */
.group:hover .group-hover\:scale-105 {
  transform: scale(1.05);
}

.group:hover .group-hover\:text-blue-600 {
  color: #2563eb;
}
</style>