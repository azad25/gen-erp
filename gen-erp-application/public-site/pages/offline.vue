<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full text-center">
      <!-- Offline Icon -->
      <div class="mx-auto h-24 w-24 text-gray-400 mb-6">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="w-full h-full">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192L5.636 18.364M12 2.25a9.75 9.75 0 100 19.5 9.75 9.75 0 000-19.5z"/>
        </svg>
      </div>
      
      <!-- Offline Message -->
      <h1 class="text-3xl font-bold text-gray-900 mb-4">You're Offline</h1>
      <p class="text-lg text-gray-600 mb-8">
        It looks like you've lost your internet connection. Don't worry, you can still browse some cached content.
      </p>
      
      <!-- Connection Status -->
      <div class="mb-8 p-4 rounded-lg" :class="isOnline ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200'">
        <div class="flex items-center justify-center">
          <div class="flex-shrink-0">
            <svg 
              v-if="isOnline"
              class="h-5 w-5 text-green-400" 
              fill="none" 
              stroke="currentColor" 
              viewBox="0 0 24 24"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <svg 
              v-else
              class="h-5 w-5 text-red-400" 
              fill="none" 
              stroke="currentColor" 
              viewBox="0 0 24 24"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
          </div>
          <div class="ml-3">
            <p class="text-sm font-medium" :class="isOnline ? 'text-green-800' : 'text-red-800'">
              {{ isOnline ? 'Connection restored!' : 'No internet connection' }}
            </p>
          </div>
        </div>
      </div>
      
      <!-- Actions -->
      <div class="space-y-4">
        <button
          @click="retryConnection"
          :disabled="isRetrying"
          class="w-full inline-flex justify-center items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          <svg 
            v-if="isRetrying"
            class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" 
            fill="none" 
            viewBox="0 0 24 24"
          >
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          <svg 
            v-else
            class="w-5 h-5 mr-2" 
            fill="none" 
            stroke="currentColor" 
            viewBox="0 0 24 24"
          >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
          </svg>
          {{ isRetrying ? 'Checking connection...' : 'Try Again' }}
        </button>
        
        <NuxtLink
          to="/"
          class="w-full inline-flex justify-center items-center px-6 py-3 border border-gray-300 text-base font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200"
        >
          <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
          </svg>
          Go to Homepage
        </NuxtLink>
      </div>
      
      <!-- Cached Content -->
      <div v-if="cachedPages.length > 0" class="mt-12">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Available Offline</h3>
        <div class="space-y-2">
          <NuxtLink
            v-for="page in cachedPages"
            :key="page.url"
            :to="page.url"
            class="block p-3 border border-gray-200 rounded-lg hover:border-blue-300 hover:bg-blue-50 transition-colors duration-200"
          >
            <p class="font-medium text-gray-900">{{ page.title }}</p>
            <p class="text-sm text-gray-500">{{ page.url }}</p>
          </NuxtLink>
        </div>
      </div>
      
      <!-- Tips -->
      <div class="mt-12 text-left">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">While you wait:</h3>
        <ul class="space-y-2 text-sm text-gray-600">
          <li class="flex items-start">
            <svg class="w-4 h-4 mt-0.5 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
            Check your internet connection
          </li>
          <li class="flex items-start">
            <svg class="w-4 h-4 mt-0.5 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
            Try refreshing the page
          </li>
          <li class="flex items-start">
            <svg class="w-4 h-4 mt-0.5 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
            Browse cached content above
          </li>
        </ul>
      </div>
    </div>
  </div>
</template>

<script setup>
interface CachedPage {
  url: string
  title: string
}

const isOnline = ref(navigator.onLine)
const isRetrying = ref(false)
const cachedPages = ref<CachedPage[]>([])

// Monitor online/offline status
const updateOnlineStatus = () => {
  isOnline.value = navigator.onLine
}

// Retry connection
const retryConnection = async () => {
  isRetrying.value = true
  
  try {
    // Try to fetch a small resource to test connection
    const response = await fetch('/favicon.ico', { 
      method: 'HEAD',
      cache: 'no-cache'
    })
    
    if (response.ok) {
      // Connection restored, redirect to homepage
      await navigateTo('/')
    }
  } catch (error) {
    console.log('Still offline')
  } finally {
    isRetrying.value = false
  }
}

// Get cached pages from service worker
const getCachedPages = async () => {
  if ('caches' in window) {
    try {
      const cacheNames = await caches.keys()
      const pages: CachedPage[] = []
      
      for (const cacheName of cacheNames) {
        const cache = await caches.open(cacheName)
        const requests = await cache.keys()
        
        for (const request of requests) {
          const url = new URL(request.url)
          
          // Only include page URLs (not assets)
          if (url.pathname !== '/' && 
              !url.pathname.startsWith('/_nuxt/') && 
              !url.pathname.includes('.')) {
            pages.push({
              url: url.pathname,
              title: url.pathname.split('/').pop()?.replace(/-/g, ' ') || 'Page'
            })
          }
        }
      }
      
      cachedPages.value = pages.slice(0, 5) // Show max 5 cached pages
    } catch (error) {
      console.error('Failed to get cached pages:', error)
    }
  }
}

// Set up event listeners
onMounted(() => {
  window.addEventListener('online', updateOnlineStatus)
  window.addEventListener('offline', updateOnlineStatus)
  
  getCachedPages()
  
  // Auto-retry when connection is restored
  watch(isOnline, (newValue) => {
    if (newValue) {
      setTimeout(() => {
        navigateTo('/')
      }, 1000)
    }
  })
})

onUnmounted(() => {
  window.removeEventListener('online', updateOnlineStatus)
  window.removeEventListener('offline', updateOnlineStatus)
})

// Set page meta
useHead({
  title: 'Offline - No Internet Connection',
  meta: [
    {
      name: 'description',
      content: 'You are currently offline. Please check your internet connection.'
    },
    {
      name: 'robots',
      content: 'noindex, nofollow'
    }
  ]
})
</script>

<style scoped>
/* Loading animation */
@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.animate-spin {
  animation: spin 1s linear infinite;
}

/* Focus styles for accessibility */
button:focus,
a:focus {
  outline: none;
}

button:focus-visible,
a:focus-visible {
  outline: 2px solid;
  outline-offset: 2px;
}
</style>