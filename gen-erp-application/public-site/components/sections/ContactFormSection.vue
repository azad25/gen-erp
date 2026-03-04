<template>
  <section class="section-padding bg-gray-50">
    <div class="container-custom">
      <div class="max-w-2xl mx-auto">
        <div class="text-center mb-8">
          <h2 class="text-3xl font-bold text-gray-900 mb-4">
            {{ content.heading || 'Get In Touch' }}
          </h2>
        </div>
        
        <div 
          class="rounded-lg shadow-md p-8"
          :style="{
            backgroundColor: content.form_background || '#ffffff'
          }"
        >
          <form @submit.prevent="submitForm" class="space-y-6">
            <!-- Name Fields -->
            <div v-if="content.fields?.includes('name')" class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">First Name</label>
                <input
                  v-model="form.first_name"
                  type="text"
                  required
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Last Name</label>
                <input
                  v-model="form.last_name"
                  type="text"
                  required
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
              </div>
            </div>
            
            <!-- Email -->
            <div v-if="content.fields?.includes('email')">
              <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
              <input
                v-model="form.email"
                type="email"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>
            
            <!-- Phone -->
            <div v-if="content.fields?.includes('phone')">
              <label class="block text-sm font-medium text-gray-700 mb-2">Phone</label>
              <input
                v-model="form.phone"
                type="tel"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>
            
            <!-- Company -->
            <div v-if="content.fields?.includes('company')">
              <label class="block text-sm font-medium text-gray-700 mb-2">Company</label>
              <input
                v-model="form.company"
                type="text"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>
            
            <!-- Subject -->
            <div v-if="content.fields?.includes('subject')">
              <label class="block text-sm font-medium text-gray-700 mb-2">Subject</label>
              <input
                v-model="form.subject"
                type="text"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>
            
            <!-- Message -->
            <div v-if="content.fields?.includes('message')">
              <label class="block text-sm font-medium text-gray-700 mb-2">Message</label>
              <textarea
                v-model="form.message"
                rows="4"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>
            
            <!-- Submit Button -->
            <div>
              <button
                type="submit"
                :disabled="submitting"
                class="w-full font-semibold py-3 px-6 rounded-lg transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
                :style="{
                  backgroundColor: content.button_color || 'var(--primary-color)',
                  color: '#ffffff'
                }"
              >
                <span v-if="submitting" class="flex items-center justify-center">
                  <div class="spinner mr-2"></div>
                  Sending...
                </span>
                <span v-else>
                  {{ content.button_text || 'Send Message' }}
                </span>
              </button>
            </div>
          </form>
          
          <!-- Success Message -->
          <div
            v-if="showSuccess"
            class="mt-6 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg fade-in"
          >
            {{ content.success_message || 'Thank you! We will be in touch soon.' }}
          </div>
          
          <!-- Error Message -->
          <div
            v-if="showError"
            class="mt-6 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg fade-in"
          >
            There was an error sending your message. Please try again.
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
interface ContactFormContent {
  heading?: string
  fields?: string[]
  button_text?: string
  button_color?: string
  form_background?: string
  success_message?: string
}

const props = defineProps<{
  content: ContactFormContent
  tenant?: any
}>()

const config = useRuntimeConfig()

// Form state
const form = ref({
  first_name: '',
  last_name: '',
  email: '',
  phone: '',
  company: '',
  subject: '',
  message: ''
})

const submitting = ref(false)
const showSuccess = ref(false)
const showError = ref(false)

const submitForm = async () => {
  try {
    submitting.value = true
    showError.value = false
    showSuccess.value = false

    await $fetch(`${config.public.apiBase}/public/contact`, {
      method: 'POST',
      body: {
        ...form.value,
        tenant_id: props.tenant?.id
      }
    })

    showSuccess.value = true
    
    // Reset form
    form.value = {
      first_name: '',
      last_name: '',
      email: '',
      phone: '',
      company: '',
      subject: '',
      message: ''
    }

    // Hide success message after 5 seconds
    setTimeout(() => {
      showSuccess.value = false
    }, 5000)

  } catch (error) {
    console.error('Contact form error:', error)
    showError.value = true
    
    // Hide error message after 5 seconds
    setTimeout(() => {
      showError.value = false
    }, 5000)
  } finally {
    submitting.value = false
  }
}
</script>