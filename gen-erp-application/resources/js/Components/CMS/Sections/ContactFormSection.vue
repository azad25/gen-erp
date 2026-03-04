<template>
  <div class="py-12 bg-gray-50">
    <div class="max-w-2xl mx-auto px-6">
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
          <div v-if="content.fields?.includes('name')" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">First Name</label>
              <input
                v-model="form.first_name"
                type="text"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                :disabled="isEditing"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Last Name</label>
              <input
                v-model="form.last_name"
                type="text"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                :disabled="isEditing"
              />
            </div>
          </div>
          
          <div v-if="content.fields?.includes('email')">
            <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
            <input
              v-model="form.email"
              type="email"
              required
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              :disabled="isEditing"
            />
          </div>
          
          <div v-if="content.fields?.includes('phone')">
            <label class="block text-sm font-medium text-gray-700 mb-2">Phone</label>
            <input
              v-model="form.phone"
              type="tel"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              :disabled="isEditing"
            />
          </div>
          
          <div v-if="content.fields?.includes('company')">
            <label class="block text-sm font-medium text-gray-700 mb-2">Company</label>
            <input
              v-model="form.company"
              type="text"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              :disabled="isEditing"
            />
          </div>
          
          <div v-if="content.fields?.includes('subject')">
            <label class="block text-sm font-medium text-gray-700 mb-2">Subject</label>
            <input
              v-model="form.subject"
              type="text"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              :disabled="isEditing"
            />
          </div>
          
          <div v-if="content.fields?.includes('message')">
            <label class="block text-sm font-medium text-gray-700 mb-2">Message</label>
            <textarea
              v-model="form.message"
              rows="4"
              required
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              :disabled="isEditing"
            ></textarea>
          </div>
          
          <div>
            <button
              type="submit"
              :disabled="isEditing || submitting"
              class="w-full font-semibold py-3 px-6 rounded-lg transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
              :style="{
                backgroundColor: content.button_color || '#2563eb',
                color: '#ffffff'
              }"
            >
              {{ submitting ? 'Sending...' : (content.button_text || 'Send Message') }}
            </button>
          </div>
        </form>
        
        <!-- Success Message -->
        <div
          v-if="showSuccess"
          class="mt-6 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg"
        >
          {{ content.success_message || 'Thank you! We will be in touch soon.' }}
        </div>
        
        <!-- Error Message -->
        <div
          v-if="showError"
          class="mt-6 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg"
        >
          There was an error sending your message. Please try again.
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
import { ref, reactive } from 'vue'
import axios from 'axios'

defineProps({
  content: {
    type: Object,
    default: () => ({})
  },
  isEditing: {
    type: Boolean,
    default: false
  }
})

const form = reactive({
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
  submitting.value = true
  showSuccess.value = false
  showError.value = false
  
  try {
    await axios.post('/api/public/contact', form)
    showSuccess.value = true
    
    // Reset form
    Object.keys(form).forEach(key => {
      form[key] = ''
    })
  } catch (error) {
    console.error('Error submitting form:', error)
    showError.value = true
  } finally {
    submitting.value = false
  }
}
</script>