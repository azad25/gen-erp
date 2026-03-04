<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
      <div class="text-center">
        <!-- Error Icon -->
        <div class="mx-auto h-24 w-24 text-red-500 mb-6">
          <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="w-full h-full">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
        </div>
        
        <!-- Error Code -->
        <h1 class="text-6xl font-bold text-gray-900 mb-4">
          {{ error.statusCode || '500' }}
        </h1>
        
        <!-- Error Title -->
        <h2 class="text-2xl font-semibold text-gray-700 mb-4">
          {{ getErrorTitle(error.statusCode) }}
        </h2>
        
        <!-- Error Message -->
        <p class="text-gray-600 mb-8 max-w-sm mx-auto">
          {{ getErrorMessage(error.statusCode) }}
        </p>
        
        <!-- Actions -->
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
          <button
            @click="handleError"
            class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200"
          >
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
            </svg>
            Try Again
          </button>
          
          <NuxtLink
            to="/"
            class="inline-flex items-center px-6 py-3 border border-gray-300 text-base font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200"
          >
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
            </svg>
            Go Home
          </NuxtLink>
        </div>
        
        <!-- Additional Help -->
        <div class="mt-8 pt-8 border-t border-gray-200">
          <p class="text-sm text-gray-500 mb-4">
            If this problem persists, please contact support.
          </p>
          <div class="flex justify-center space-x-6 text-sm">
            <NuxtLink to="/contact" class="text-blue-600 hover:text-blue-500">
              Contact Support
            </NuxtLink>
            <NuxtLink to="/help" class="text-blue-600 hover:text-blue-500">
              Help Center
            </NuxtLink>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
interface NuxtError {
  statusCode?: number
  statusMessage?: string
  message?: string
}

const props = defineProps<{
  error: NuxtError
}>()

// Get appropriate error title based on status code
const getErrorTitle = (statusCode?: number): string => {
  const titles: Record<number, string> = {
    400: 'Bad Request',
    401: 'Unauthorized',
    403: 'Forbidden',
    404: 'Page Not Found',
    405: 'Method Not Allowed',
    408: 'Request Timeout',
    410: 'Gone',
    422: 'Unprocessable Entity',
    429: 'Too Many Requests',
    500: 'Internal Server Error',
    502: 'Bad Gateway',
    503: 'Service Unavailable',
    504: 'Gateway Timeout'
  }
  
  return titles[statusCode || 500] || 'Something Went Wrong'
}

// Get appropriate error message based on status code
const getErrorMessage = (statusCode?: number): string => {
  const messages: Record<number, string> = {
    400: 'The request could not be understood by the server due to malformed syntax.',
    401: 'You need to be authenticated to access this resource.',
    403: 'You don\'t have permission to access this resource.',
    404: 'The page you\'re looking for doesn\'t exist or has been moved.',
    405: 'The request method is not allowed for this resource.',
    408: 'The server timed out waiting for the request.',
    410: 'The requested resource is no longer available.',
    422: 'The request was well-formed but contains semantic errors.',
    429: 'Too many requests have been made in a short period of time.',
    500: 'An unexpected error occurred on our servers. We\'re working to fix it.',
    502: 'The server received an invalid response from an upstream server.',
    503: 'The service is temporarily unavailable. Please try again later.',
    504: 'The server didn\'t receive a timely response from an upstream server.'
  }
  
  return messages[statusCode || 500] || 'An unexpected error occurred. Please try again.'
}

// Handle error action (retry)
const handleError = async () => {
  try {
    // Clear the error and try to reload the page
    await clearError({ redirect: '/' })
  } catch (err) {
    // If clearing error fails, just reload the page
    window.location.reload()
  }
}

// Set page meta
useHead({
  title: `Error ${props.error.statusCode || 500} - ${getErrorTitle(props.error.statusCode)}`,
  meta: [
    {
      name: 'robots',
      content: 'noindex, nofollow'
    }
  ]
})
</script>

<style scoped>
/* Ensure proper focus styles */
button:focus,
a:focus {
  outline: none;
}

button:focus-visible,
a:focus-visible {
  outline: 2px solid;
  outline-offset: 2px;
}

/* Animation for error icon */
@keyframes pulse {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0.5;
  }
}

.animate-pulse {
  animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
</style>