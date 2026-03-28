<template>
  <AppLayout title="Diagnostic Test">
    <div class="p-6">
      <h1 class="text-2xl font-bold mb-6">Navigation Diagnostic Test</h1>
      
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Test Results -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
          <h2 class="text-lg font-semibold mb-4">Test Results</h2>
          <div class="space-y-2 max-h-96 overflow-y-auto">
            <div
              v-for="(result, index) in testResults"
              :key="index"
              :class="[
                'p-3 rounded text-sm',
                {
                  'bg-green-100 text-green-800': result.type === 'success',
                  'bg-red-100 text-red-800': result.type === 'error',
                  'bg-yellow-100 text-yellow-800': result.type === 'warning',
                  'bg-blue-100 text-blue-800': result.type === 'info'
                }
              ]"
            >
              <strong>{{ result.time }}</strong>: {{ result.message }}
            </div>
          </div>
        </div>

        <!-- Test Controls -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
          <h2 class="text-lg font-semibold mb-4">Run Tests</h2>
          
          <div class="space-y-4">
            <div>
              <h3 class="font-medium mb-2">API Tests</h3>
              <div class="space-y-2">
                <button @click="testAPI('/api/v1/documents/storage-info')" class="btn btn-sm btn-primary w-full">
                  Test Storage Info API
                </button>
                <button @click="testAPI('/api/v1/documents')" class="btn btn-sm btn-primary w-full">
                  Test Documents API
                </button>
                <button @click="testAPI('/api/v1/auth/user')" class="btn btn-sm btn-primary w-full">
                  Test Auth API
                </button>
                <button @click="testAPI('/api/v1/debug/auth-status')" class="btn btn-sm btn-primary w-full">
                  Test Debug Auth Status
                </button>
                <button @click="testAPI('/api/v1/debug/company-context')" class="btn btn-sm btn-primary w-full">
                  Test Company Context
                </button>
              </div>
            </div>

            <div>
              <h3 class="font-medium mb-2">Navigation Tests</h3>
              <div class="space-y-2">
                <button @click="testNavigation('/documents/dashboard')" class="btn btn-sm btn-secondary w-full">
                  Test Documents Dashboard
                </button>
                <button @click="testNavigation('/documents')" class="btn btn-sm btn-secondary w-full">
                  Test Documents Index
                </button>
                <button @click="testNavigation('/sales/dashboard')" class="btn btn-sm btn-secondary w-full">
                  Test Sales Dashboard
                </button>
              </div>
            </div>

            <div>
              <h3 class="font-medium mb-2">Context Tests</h3>
              <div class="space-y-2">
                <button @click="checkAuth()" class="btn btn-sm btn-accent w-full">
                  Check Authentication
                </button>
                <button @click="checkCompany()" class="btn btn-sm btn-accent w-full">
                  Check Company Context
                </button>
                <button @click="checkSession()" class="btn btn-sm btn-accent w-full">
                  Check Session
                </button>
              </div>
            </div>

            <button @click="clearResults()" class="btn btn-sm btn-outline w-full">
              Clear Results
            </button>
          </div>
        </div>
      </div>

      <!-- Current Context Info -->
      <div class="mt-6 bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold mb-4">Current Context</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
          <div>
            <strong>User:</strong> {{ $page.props.auth?.user?.name || 'Not authenticated' }}
          </div>
          <div>
            <strong>Company:</strong> {{ $page.props.auth?.company?.name || 'No company' }}
          </div>
          <div>
            <strong>Current URL:</strong> {{ $page.url }}
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const testResults = ref([])

const addResult = (message, type = 'info') => {
  testResults.value.push({
    time: new Date().toLocaleTimeString(),
    message,
    type
  })
}

const testAPI = async (url) => {
  addResult(`Testing API: ${url}`, 'info')
  
  try {
    const response = await axios.get(url)
    addResult(`✅ API Success: ${url} (${response.status})`, 'success')
    
    if (response.data) {
      addResult(`Response: ${JSON.stringify(response.data).substring(0, 100)}...`, 'info')
    }
  } catch (error) {
    addResult(`❌ API Error: ${url} (${error.response?.status || 'Network Error'})`, 'error')
    
    if (error.response?.data) {
      addResult(`Error: ${JSON.stringify(error.response.data).substring(0, 100)}...`, 'error')
    }
  }
}

const testNavigation = (url) => {
  addResult(`Testing navigation: ${url}`, 'info')
  
  // Use Inertia router to navigate
  router.visit(url, {
    onStart: () => addResult(`Navigation started to ${url}`, 'info'),
    onSuccess: () => addResult(`✅ Navigation success to ${url}`, 'success'),
    onError: (errors) => addResult(`❌ Navigation error to ${url}: ${JSON.stringify(errors)}`, 'error'),
    onFinish: () => addResult(`Navigation finished to ${url}`, 'info')
  })
}

const checkAuth = async () => {
  addResult('Checking authentication...', 'info')
  
  try {
    const response = await axios.get('/api/v1/auth/user')
    const user = response.data.data
    addResult(`✅ Authenticated as: ${user.name} (${user.email})`, 'success')
    addResult(`Companies: ${user.companies?.length || 0}`, 'info')
  } catch (error) {
    addResult(`❌ Authentication check failed: ${error.response?.status || 'Network Error'}`, 'error')
  }
}

const checkCompany = () => {
  addResult('Checking company context...', 'info')
  
  const companyId = sessionStorage.getItem('active_company_id')
  if (companyId) {
    addResult(`✅ Company ID in sessionStorage: ${companyId}`, 'success')
  } else {
    addResult(`❌ No company ID in sessionStorage`, 'error')
  }
  
  const propsCompany = window.page?.props?.auth?.company
  if (propsCompany) {
    addResult(`✅ Company in props: ${propsCompany.name} (${propsCompany.id})`, 'success')
  } else {
    addResult(`❌ No company in page props`, 'error')
  }
}

const checkSession = () => {
  addResult('Checking session...', 'info')
  
  // Check cookies
  const cookies = document.cookie.split(';').map(c => c.trim())
  const sessionCookie = cookies.find(c => c.startsWith('laravel_session='))
  const xsrfCookie = cookies.find(c => c.startsWith('XSRF-TOKEN='))
  
  if (sessionCookie) {
    addResult(`✅ Session cookie found`, 'success')
  } else {
    addResult(`❌ No session cookie found`, 'error')
  }
  
  if (xsrfCookie) {
    addResult(`✅ XSRF token found`, 'success')
  } else {
    addResult(`❌ No XSRF token found`, 'error')
  }
  
  // Check CSRF token in meta
  const csrfMeta = document.querySelector('meta[name="csrf-token"]')
  if (csrfMeta) {
    addResult(`✅ CSRF meta token found`, 'success')
  } else {
    addResult(`❌ No CSRF meta token found`, 'error')
  }
}

const clearResults = () => {
  testResults.value = []
}

// Auto-run basic checks on mount
checkAuth()
checkCompany()
checkSession()
</script>