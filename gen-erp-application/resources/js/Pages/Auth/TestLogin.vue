<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-100">
    <div class="max-w-md w-full bg-white rounded-lg shadow-lg p-8">
      <h1 class="text-2xl font-bold mb-6">Test Login</h1>
      
      <form @submit.prevent="handleLogin">
        <div class="mb-4">
          <label class="block text-sm font-medium mb-2">Email</label>
          <input 
            v-model="form.email" 
            type="email" 
            class="w-full px-3 py-2 border rounded-lg"
            :class="error ? 'border-red-500' : 'border-gray-300'"
          />
        </div>
        
        <div class="mb-4">
          <label class="block text-sm font-medium mb-2">Password</label>
          <input 
            v-model="form.password" 
            type="password" 
            class="w-full px-3 py-2 border rounded-lg"
            :class="error ? 'border-red-500' : 'border-gray-300'"
          />
        </div>
        
        <button 
          type="submit" 
          :disabled="loading"
          class="w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 disabled:opacity-50"
        >
          {{ loading ? 'Logging in...' : 'Login' }}
        </button>
      </form>
      
      <div v-if="error" class="mt-4 p-3 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">
        {{ error }}
      </div>
      
      <div v-if="success" class="mt-4 p-3 bg-green-50 border border-green-200 rounded-lg text-green-700 text-sm">
        {{ success }}
      </div>
      
      <div class="mt-4 p-3 bg-gray-50 rounded-lg text-xs">
        <strong>Debug Info:</strong>
        <pre class="mt-2">{{ JSON.stringify(debugInfo, null, 2) }}</pre>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import axios from 'axios'

const form = reactive({
  email: 'dev@generp.test',
  password: 'DevAdmin@123'
})

const loading = ref(false)
const error = ref('')
const success = ref('')
const debugInfo = ref({})

const handleLogin = async () => {
  loading.value = true
  error.value = ''
  success.value = ''
  debugInfo.value = {}
  
  try {
    // Step 1: Get CSRF cookie
    debugInfo.value.step1 = 'Getting CSRF cookie...'
    await axios.get('/sanctum/csrf-cookie')
    debugInfo.value.step1 = 'CSRF cookie obtained'
    
    // Step 2: Login
    debugInfo.value.step2 = 'Sending login request...'
    const response = await axios.post('/auth/login', {
      email: form.email,
      password: form.password,
      remember: false
    })
    
    debugInfo.value.step2 = 'Login response received'
    debugInfo.value.response = {
      status: response.status,
      data: response.data
    }
    
    success.value = 'Login successful! Check debug info below.'
    
  } catch (err) {
    debugInfo.value.error = {
      message: err.message,
      response: err.response ? {
        status: err.response.status,
        data: err.response.data
      } : 'No response'
    }
    
    if (err.response) {
      const status = err.response.status
      const data = err.response.data
      
      if (status === 401) {
        error.value = 'Wrong email or password'
      } else if (status === 403) {
        error.value = data.message || 'Access forbidden'
      } else if (status === 429) {
        error.value = `Too many attempts. Try again in ${data.retry_after || 60} seconds`
      } else {
        error.value = data.message || 'Login failed'
      }
    } else if (err.request) {
      error.value = 'Network error - could not connect to server'
    } else {
      error.value = 'An error occurred: ' + err.message
    }
  } finally {
    loading.value = false
  }
}
</script>
