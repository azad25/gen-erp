<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-lg w-full text-center">
      <!-- 404 Illustration -->
      <div class="mb-8">
        <div class="mx-auto h-32 w-32 text-blue-500 mb-6">
          <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="w-full h-full">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9.172 16.172a4 4 0 015.656 0M9 12h6m-6-4h6m2 5.291A7.962 7.962 0 0112 15c-2.34 0-4.291.94-5.709 2.291"/>
          </svg>
        </div>
        
        <!-- 404 Text -->
        <h1 class="text-8xl font-bold text-gray-900 mb-4">404</h1>
        <h2 class="text-3xl font-semibold text-gray-700 mb-4">Page Not Found</h2>
        <p class="text-lg text-gray-600 mb-8 max-w-md mx-auto">
          Sorry, we couldn't find the page you're looking for. It might have been moved, deleted, or you entered the wrong URL.
        </p>
      </div>
      
      <!-- Search Box -->
      <div class="mb-8">
        <div class="max-w-md mx-auto">
          <label for="search" class="sr-only">Search</label>
          <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
              </svg>
            </div>
            <input
              id="search"
              v-model="searchQuery"
              type="text"
              placeholder="Search for pages..."
              class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
              @keyup.enter="performSearch"
            />
          </div>
          <button
            @click="performSearch"
            class="mt-3 w-full inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200"
          >
            Search
          </button>
        </div>
      </div>
      
      <!-- Quick Links -->
      <div class="mb-8">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Popular Pages</h3>
        <div class="grid grid-cols-2 gap-4">
          <NuxtLink
            to="/"
            class="p-4 border border-gray-200 rounded-lg hover:border-blue-300 hover:bg-blue-50 transition-colors duration-200 group"
          >
            <div class="text-blue-600 group-hover:text-blue-700 mb-2">
              <svg class="w-6 h-6 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
              </svg>
            </div>
            <p class="text-sm font-medium text-gray-900 group-hover:text-blue-700">Home</p>
          </NuxtLink>
          
          <NuxtLink
            to="/about"
            class="p-4 border border-gray-200 rounded-lg hover:border-blue-300 hover:bg-blue-50 transition-colors duration-200 group"
          >
            <div class="text-blue-600 group-hover:text-blue-700 mb-2">
              <svg class="w-6 h-6 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
            </div>
            <p class="text-sm font-medium text-gray-900 group-hover:text-blue-700">About</p>
          </NuxtLink>
          
          <NuxtLink
            to="/services"
            class="p-4 border border-gray-200 rounded-lg hover:border-blue-300 hover:bg-blue-50 transition-colors duration-200 group"
          >
            <div class="text-blue-600 group-hover:text-blue-700 mb-2">
              <svg class="w-6 h-6 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m8 0H8m8 0v6a2 2 0 01-2 2H10a2 2 0 01-2-2V6m8 0V4a2 2 0 00-2-2H10a2 2 0 00-2 2v2"></path>
              </svg>
            </div>
            <p class="text-sm font-medium text-gray-900 group-hover:text-blue-700">Services</p>
          </NuxtLink>
          
          <NuxtLink
            to="/contact"
            class="p-4 border border-gray-200 rounded-lg hover:border-blue-300 hover:bg-blue-50 transition-colors duration-200 group"
          >
            <div class="text-blue-600 group-hover:text-blue-700 mb-2">
              <svg class="w-6 h-6 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
              </svg>
            </div>
            <p class="text-sm font-medium text-gray-900 group-hover:text-blue-700">Contact</p>
          </NuxtLink>
        </div>
      </div>
      
      <!-- Actions -->
      <div class="flex flex-col sm:flex-row gap-4 justify-center">
        <button
          @click="goBack"
          class="inline-flex items-center px-6 py-3 border border-gray-300 text-base font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200"
        >
          <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
          </svg>
          Go Back
        </button>
        
        <NuxtLink
          to="/"
          class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200"
        >
          <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
          </svg>
          Go Home
        </NuxtLink>
      </div>
    </div>
  </div>
</template>

<script setup>
const searchQuery = ref('')

// Go back to previous page
const goBack = () => {
  if (window.history.length > 1) {
    window.history.back()
  } else {
    navigateTo('/')
  }
}

// Perform search
const performSearch = () => {
  if (searchQuery.value.trim()) {
    // Navigate to search page or perform search
    navigateTo(`/search?q=${encodeURIComponent(searchQuery.value.trim())}`)
  }
}

// Set page meta
useHead({
  title: '404 - Page Not Found',
  meta: [
    {
      name: 'description',
      content: 'The page you are looking for could not be found.'
    },
    {
      name: 'robots',
      content: 'noindex, nofollow'
    }
  ]
})
</script>

<style scoped>
/* Focus styles for accessibility */
button:focus,
a:focus,
input:focus {
  outline: none;
}

button:focus-visible,
a:focus-visible {
  outline: 2px solid;
  outline-offset: 2px;
}

/* Hover animations */
.group:hover {
  transform: translateY(-1px);
}

/* Search input focus styles */
input:focus {
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}
</style>