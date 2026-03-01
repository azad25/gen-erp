<template>
  <div class="relative p-6 bg-white z-1 dark:bg-gray-900 sm:p-0">
    <div class="relative flex flex-col justify-center w-full h-screen lg:flex-row dark:bg-gray-900">
      <div class="flex flex-col flex-1 w-full lg:w-1/2">
        <div class="w-full max-w-md pt-10 mx-auto">
          <router-link to="/" class="inline-flex items-center text-sm text-gray-500 transition-colors hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
            <svg class="stroke-current" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" fill="none">
              <path d="M12.7083 5L7.5 10.2083L12.7083 15.4167" stroke="" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
            ড্যাশবোর্ডে ফিরে যান
          </router-link>
        </div>
        <div class="flex flex-col justify-center flex-1 w-full max-w-md mx-auto">
          <div>
            <div class="mb-5 sm:mb-8">
              <h1 class="mb-2 font-semibold text-gray-800 text-2xl sm:text-3xl dark:text-white/90">
                দ্বি-ফ্যাক্টর প্রমাণীকরণ
              </h1>
              <p class="text-sm text-gray-500 dark:text-gray-400">
                আপনার Google Authenticator অ্যাপ থেকে কোড লিখুন
              </p>
            </div>
            <form @submit.prevent="handleSubmit">
              <div class="space-y-5">
                <!-- 2FA Code -->
                <div>
                  <label for="code" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                    কোড<span class="text-red-500">*</span>
                  </label>
                  <input
                    v-model="code"
                    type="text"
                    id="code"
                    name="code"
                    placeholder="000 000"
                    maxlength="6"
                    class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:border-primary focus:outline-none focus:ring-3 focus:ring-primary/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 text-center text-2xl tracking-widest font-mono"
                  />
                </div>

                <!-- Button -->
                <div>
                  <button type="submit" :disabled="loading" class="flex items-center justify-center w-full px-4 py-3 text-sm font-medium text-white transition rounded-lg bg-primary shadow-sm hover:bg-teal-700 disabled:opacity-50 disabled:cursor-not-allowed">
                    <span v-if="loading">যাচাই করা হচ্ছে...</span>
                    <span v-else>যাচাই করুন</span>
                  </button>
                </div>

                <!-- Error Message -->
                <div v-if="error" class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">
                  {{ error }}
                </div>
              </div>
            </form>
            <div class="mt-5">
              <p class="text-sm font-normal text-center text-gray-700 dark:text-gray-400">
                কোড ভুলে গেছেন?
                <a href="#" class="text-primary hover:text-teal-700 dark:text-teal-400">পুনরায় পাঠান</a>
              </p>
            </div>
          </div>
        </div>
      </div>
      <div class="relative items-center hidden w-full h-full lg:w-1/2 bg-gray-900 dark:bg-white/5 lg:grid">
        <div class="flex items-center justify-center z-1">
          <div class="flex flex-col items-center max-w-xs">
            <a href="/" class="block mb-4">
              <HomeLogo />
            </a>
            <p class="text-center text-gray-400 dark:text-white/60">
              বাংলাদেশের জন্য তৈরি করা প্রথম ক্লাউড ERP সলিউশন
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import HomeLogo from '@/Components/Home/Logo.vue'
import axios from 'axios'

const code = ref('')
const loading = ref(false)
const error = ref('')

const handleSubmit = async () => {
  loading.value = true
  error.value = ''
  
  try {
    // Step 1: Get CSRF cookie
    await axios.get('/sanctum/csrf-cookie')
    
    // Step 2: Submit 2FA code
    const response = await axios.post('/auth/two-factor/challenge', {
      code: code.value
    })
    
    // Redirect to dashboard
    router.visit('/dashboard')
  } catch (err) {
    error.value = err.response?.data?.message || 'Invalid code. Please try again.'
  } finally {
    loading.value = false
  }
}
</script>
