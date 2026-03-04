<template>
  <div>
    <!-- Loading State -->
    <div v-if="pending" class="min-h-screen flex items-center justify-center">
      <div class="text-center">
        <div class="spinner w-8 h-8 mx-auto mb-4"></div>
        <p class="text-gray-600">Loading page...</p>
      </div>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="min-h-screen flex items-center justify-center">
      <div class="text-center">
        <h1 class="text-4xl font-bold text-gray-900 mb-4">
          {{ error.statusCode === 404 ? 'Page Not Found' : 'Error' }}
        </h1>
        <p class="text-gray-600 mb-8">
          {{ error.statusCode === 404 
            ? 'The page you are looking for does not exist.' 
            : 'Something went wrong while loading this page.' 
          }}
        </p>
        <NuxtLink 
          to="/" 
          class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
        >
          Go Home
        </NuxtLink>
      </div>
    </div>

    <!-- Page Content -->
    <div v-else-if="page">
      <!-- SEO Meta Tags -->
      <Head>
        <Title>{{ pageTitle }}</Title>
        <Meta name="description" :content="page.meta_description || tenantMeta.description" />
        <Meta name="keywords" :content="page.meta_keywords || tenantMeta.keywords" />
        
        <!-- Open Graph -->
        <Meta property="og:title" :content="pageTitle" />
        <Meta property="og:description" :content="page.meta_description || tenantMeta.description" />
        <Meta property="og:type" content="website" />
        <Meta property="og:url" :content="canonicalUrl" />
        <Meta v-if="page.featured_image" property="og:image" :content="page.featured_image" />
        
        <!-- Twitter Card -->
        <Meta name="twitter:card" content="summary_large_image" />
        <Meta name="twitter:title" :content="pageTitle" />
        <Meta name="twitter:description" :content="page.meta_description || tenantMeta.description" />
        <Meta v-if="page.featured_image" name="twitter:image" :content="page.featured_image" />
        
        <!-- Canonical URL -->
        <Link rel="canonical" :href="canonicalUrl" />
        
        <!-- Structured Data -->
        <script type="application/ld+json">
          {{
            JSON.stringify({
              "@context": "https://schema.org",
              "@type": "WebPage",
              "name": pageTitle,
              "description": page.meta_description || tenantMeta.description,
              "url": canonicalUrl,
              "isPartOf": {
                "@type": "WebSite",
                "name": tenantMeta.title,
                "url": baseUrl
              }
            })
          }}
        </script>
      </Head>

      <!-- Render Page Sections -->
      <div class="page-content">
        <SectionRenderer
          v-for="(section, index) in page.sections"
          :key="`section-${index}`"
          :section="section"
          :page="page"
          :tenant="tenant"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
interface PageSection {
  id: string
  type: string
  content: Record<string, any>
  order: number
}

interface Page {
  id: string
  title: string
  slug: string
  content?: string
  meta_description?: string
  meta_keywords?: string
  featured_image?: string
  status: 'published' | 'draft' | 'archived'
  sections: PageSection[]
  created_at: string
  updated_at: string
}

// Composables
const { tenant, resolveTenant, getTenantMeta } = useTenant()
const route = useRoute()
const config = useRuntimeConfig()

// Resolve tenant first
await resolveTenant()

if (!tenant.value) {
  throw createError({
    statusCode: 404,
    statusMessage: 'Site not found'
  })
}

// Get page slug from route
const slug = Array.isArray(route.params.slug) 
  ? route.params.slug.join('/') 
  : route.params.slug || 'home'

// Fetch page data
const { data: page, pending, error } = await useFetch<Page>(`${config.public.apiBase}/public/pages/${slug}`, {
  headers: {
    'X-Tenant-ID': tenant.value.id
  },
  server: true
})

// Handle 404
if (error.value?.statusCode === 404) {
  throw createError({
    statusCode: 404,
    statusMessage: 'Page not found'
  })
}

// Computed properties
const tenantMeta = computed(() => getTenantMeta())
const pageTitle = computed(() => {
  if (!page.value) return tenantMeta.value.title
  return page.value.title ? `${page.value.title} | ${tenantMeta.value.title}` : tenantMeta.value.title
})

const baseUrl = computed(() => {
  if (!tenant.value) return config.public.siteUrl
  return tenant.value.domain 
    ? `https://${tenant.value.domain}`
    : `https://${tenant.value.subdomain}.${config.public.siteUrl.replace(/https?:\/\//, '')}`
})

const canonicalUrl = computed(() => {
  return `${baseUrl.value}/${slug === 'home' ? '' : slug}`
})

// Set page status for unpublished pages
if (page.value && page.value.status !== 'published') {
  // Only show unpublished pages in development or to authenticated users
  if (process.env.NODE_ENV === 'production') {
    throw createError({
      statusCode: 404,
      statusMessage: 'Page not found'
    })
  }
}
</script>

<style scoped>
.page-content {
  min-height: 50vh;
}
</style>