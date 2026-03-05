<template>
  <div class="flex items-center justify-center min-h-screen p-4 bg-slate-50">
    <!-- Background Accents -->
    <div class="fixed top-0 left-0 w-full h-full overflow-hidden pointer-events-none z-0">
      <div class="absolute -top-40 -right-40 w-96 h-96 bg-teal-100 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-blob"></div>
      <div class="absolute -bottom-40 -left-40 w-96 h-96 bg-teal-500 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-blob animation-delay-4000"></div>
    </div>

    <div class="relative z-10 w-full max-w-3xl overflow-hidden bg-white border border-slate-100 shadow-xl rounded-2xl">
      <div class="flex flex-col md:flex-row">
        <!-- Sidebar / Steps Indicator -->
        <div class="p-8 text-white bg-slate-900 md:w-1/3">
          <div class="flex items-center gap-2 mb-10">
            <HomeLogo class="w-8 h-8" />
            <span class="text-xl font-bold tracking-tight">GenERP</span>
          </div>

          <h2 class="mb-2 text-2xl font-bold">Set Up Your Company</h2>
          <p class="mb-8 text-sm text-slate-400">Let's get your workspace customized and ready.</p>

          <div class="space-y-6">
            <div v-for="(step, index) in steps" :key="index" class="flex items-center gap-4">
              <div class="flex items-center justify-center w-8 h-8 font-semibold rounded-full border-2 transition-colors"
                :class="currentStep === index + 1 ? 'border-teal-300 text-teal-300' : (currentStep > index + 1 ? 'border-emerald-400 text-emerald-400 bg-emerald-400/10' : 'border-slate-700 text-slate-600')">
                <svg v-if="currentStep > index + 1" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                <span v-else>{{ index + 1 }}</span>
              </div>
              <span class="font-medium"
                :class="currentStep === index + 1 ? 'text-white' : (currentStep > index + 1 ? 'text-emerald-400' : 'text-slate-500')">
                {{ step }}
              </span>
            </div>
            <div v-if="index < steps.length - 1" class="w-0.5 h-6 bg-slate-800 ml-4 -my-2"></div>
          </div>
        </div>

        <!-- Content Area -->
        <div class="p-8 md:w-2/3 lg:p-12">
          <!-- Step 1: Basics -->
          <div v-if="currentStep === 1" class="animate-fadeIn">
            <h3 class="mb-6 text-xl font-bold text-slate-800">Company Basics</h3>

            <div class="space-y-5">
              <div>
                <label for="name" class="block mb-1.5 text-sm font-medium text-slate-700">Company Name <span class="text-red-500">*</span></label>
                <input id="name" v-model="form.name" type="text" required
                  class="block w-full px-4 py-3 text-slate-900 transition-colors bg-white border border-slate-200 rounded-lg focus:ring-2 focus:ring-teal-300 focus:border-teal-300 sm:text-sm"
                  placeholder="Acme Corporation">
                <p v-if="errors.name" class="mt-1 text-sm text-red-500">{{ errors.name }}</p>
              </div>

              <div>
                <label for="business_type" class="block mb-1.5 text-sm font-medium text-slate-700">Business Type <span class="text-red-500">*</span></label>
                <select id="business_type" v-model="form.business_type" required
                  class="block w-full px-4 py-3 text-slate-900 transition-colors bg-white border border-slate-200 rounded-lg focus:ring-2 focus:ring-teal-300 focus:border-teal-300 sm:text-sm">
                  <option value="">Select business type...</option>
                  <option v-for="(label, value) in businessTypes" :key="value" :value="value">{{ label }}</option>
                </select>
                <p v-if="errors.business_type" class="mt-1 text-sm text-red-500">{{ errors.business_type }}</p>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                  <label for="phone" class="block mb-1.5 text-sm font-medium text-slate-700">Phone</label>
                  <input id="phone" v-model="form.phone" type="text"
                    class="block w-full px-4 py-3 text-slate-900 transition-colors bg-white border border-slate-200 rounded-lg focus:ring-2 focus:ring-teal-300 focus:border-teal-300 sm:text-sm"
                    placeholder="01XXXXXXXXX">
                  <p v-if="errors.phone" class="mt-1 text-sm text-red-500">{{ errors.phone }}</p>
                </div>

                <div>
                  <label for="email" class="block mb-1.5 text-sm font-medium text-slate-700">Company Email</label>
                  <input id="email" v-model="form.email" type="email"
                    class="block w-full px-4 py-3 text-slate-900 transition-colors bg-white border border-slate-200 rounded-lg focus:ring-2 focus:ring-teal-300 focus:border-teal-300 sm:text-sm"
                    placeholder="contact@company.com">
                  <p v-if="errors.email" class="mt-1 text-sm text-red-500">{{ errors.email }}</p>
                </div>
              </div>
            </div>

            <div class="flex justify-end mt-10">
              <button @click="nextStep" type="button"
                class="px-6 py-2.5 text-sm font-medium text-white transition-all bg-teal-600 hover:bg-teal-700 rounded-lg shadow hover:shadow-md">
                Next Step →
              </button>
            </div>
          </div>

          <!-- Step 2: Location -->
          <div v-if="currentStep === 2" class="animate-fadeIn">
            <h3 class="mb-6 text-xl font-bold text-slate-800">Location & Details</h3>

            <div class="space-y-5">
              <div>
                <label for="address_line1" class="block mb-1.5 text-sm font-medium text-slate-700">Street Address</label>
                <input id="address_line1" v-model="form.address_line1" type="text"
                  class="block w-full px-4 py-3 text-slate-900 transition-colors bg-white border border-slate-200 rounded-lg focus:ring-2 focus:ring-teal-300 focus:border-teal-300 sm:text-sm"
                  placeholder="123 Business Rd">
                <p v-if="errors.address_line1" class="mt-1 text-sm text-red-500">{{ errors.address_line1 }}</p>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                  <label for="city" class="block mb-1.5 text-sm font-medium text-slate-700">City</label>
                  <input id="city" v-model="form.city" type="text"
                    class="block w-full px-4 py-3 text-slate-900 transition-colors bg-white border border-slate-200 rounded-lg focus:ring-2 focus:ring-teal-300 focus:border-teal-300 sm:text-sm"
                    placeholder="Dhaka">
                  <p v-if="errors.city" class="mt-1 text-sm text-red-500">{{ errors.city }}</p>
                </div>

                <div>
                  <label for="postal_code" class="block mb-1.5 text-sm font-medium text-slate-700">Postal Code</label>
                  <input id="postal_code" v-model="form.postal_code" type="text"
                    class="block w-full px-4 py-3 text-slate-900 transition-colors bg-white border border-slate-200 rounded-lg focus:ring-2 focus:ring-teal-300 focus:border-teal-300 sm:text-sm"
                    placeholder="1200">
                  <p v-if="errors.postal_code" class="mt-1 text-sm text-red-500">{{ errors.postal_code }}</p>
                </div>
              </div>

              <div>
                <label for="district" class="block mb-1.5 text-sm font-medium text-slate-700">District</label>
                <select id="district" v-model="form.district"
                  class="block w-full px-4 py-3 text-slate-900 transition-colors bg-white border border-slate-200 rounded-lg focus:ring-2 focus:ring-teal-300 focus:border-teal-300 sm:text-sm">
                  <option value="">Select district...</option>
                  <option v-for="district in districts" :key="district" :value="district">{{ district }}</option>
                </select>
                <p v-if="errors.district" class="mt-1 text-sm text-red-500">{{ errors.district }}</p>
              </div>

              <div class="p-4 mt-2 border border-slate-200 rounded-lg bg-slate-50">
                <label class="flex items-center cursor-pointer">
                  <div class="relative flex items-center">
                    <input v-model="form.vat_registered" type="checkbox"
                      class="w-5 h-5 text-teal-600 border-slate-300 rounded focus:ring-teal-300">
                  </div>
                  <div class="ml-3">
                    <span class="block text-sm font-medium text-slate-800">Company is VAT Registered</span>
                  </div>
                </label>

                <div v-if="form.vat_registered" class="mt-4 animate-fadeIn">
                  <label for="vat_bin" class="block mb-1.5 text-sm font-medium text-slate-700">VAT BIN <span class="text-red-500">*</span></label>
                  <input id="vat_bin" v-model="form.vat_bin" type="text"
                    class="block w-full px-4 py-3 text-slate-900 transition-colors bg-white border border-slate-200 rounded-lg focus:ring-2 focus:ring-teal-300 focus:border-teal-300 sm:text-sm"
                    placeholder="000123456-0101">
                  <p v-if="errors.vat_bin" class="mt-1 text-sm text-red-500">{{ errors.vat_bin }}</p>
                </div>
              </div>
            </div>

            <div class="flex justify-between mt-10">
              <button @click="previousStep" type="button"
                class="px-6 py-2.5 text-sm font-medium text-slate-600 transition-colors bg-white border border-slate-300 rounded-lg hover:bg-slate-50">
                ← Back
              </button>
              <button @click="nextStep" type="button"
                class="px-6 py-2.5 text-sm font-medium text-white transition-all bg-teal-600 hover:bg-teal-700 rounded-lg shadow hover:shadow-md">
                Next Step →
              </button>
            </div>
          </div>

          <!-- Step 3: Confirm -->
          <div v-if="currentStep === 3" class="animate-fadeIn">
            <div class="flex items-center gap-3 mb-6">
              <div class="flex items-center justify-center w-10 h-10 rounded-full bg-emerald-100 text-emerald-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
              </div>
              <h3 class="text-xl font-bold text-slate-800">Review & Confirm</h3>
            </div>

            <div class="p-6 mb-8 border border-slate-200 rounded-xl bg-slate-50">
              <dl class="space-y-4 text-sm">
                <div class="grid grid-cols-3 gap-4 pb-4 border-b border-slate-200">
                  <dt class="font-medium text-slate-500">Company Name</dt>
                  <dd class="col-span-2 font-semibold text-slate-900">{{ form.name }}</dd>
                </div>
                <div class="grid grid-cols-3 gap-4 pb-4 border-b border-slate-200">
                  <dt class="font-medium text-slate-500">Business Type</dt>
                  <dd class="col-span-2 text-slate-900">{{ businessTypes[form.business_type] || '-' }}</dd>
                </div>
                <div class="grid grid-cols-3 gap-4 pb-4 border-b border-slate-200">
                  <dt class="font-medium text-slate-500">Contact</dt>
                  <dd class="col-span-2 text-slate-900">
                    <div v-if="form.email">{{ form.email }}</div>
                    <div v-if="form.phone" class="text-slate-500">{{ form.phone }}</div>
                    <div v-if="!form.email && !form.phone">-</div>
                  </dd>
                </div>
                <div class="grid grid-cols-3 gap-4 pb-4 border-b border-slate-200">
                  <dt class="font-medium text-slate-500">Address</dt>
                  <dd class="col-span-2 text-slate-900">
                    <div v-if="form.address_line1">{{ form.address_line1 }}</div>
                    <div class="text-slate-500">
                      {{ [form.city, form.district, form.postal_code].filter(Boolean).join(', ') || '-' }}
                    </div>
                  </dd>
                </div>
                <div class="grid grid-cols-3 gap-4">
                  <dt class="font-medium text-slate-500">VAT Registration</dt>
                  <dd class="col-span-2 text-slate-900">
                    <span v-if="form.vat_registered" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-100 text-emerald-800">Registered</span>
                    <span v-else class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-800">Not Registered</span>
                    <div v-if="form.vat_registered" class="mt-1 text-sm text-slate-500">BIN: {{ form.vat_bin }}</div>
                  </dd>
                </div>
              </dl>
            </div>

            <div class="flex justify-between mt-10">
              <button @click="previousStep" type="button"
                class="px-6 py-2.5 text-sm font-medium text-slate-600 transition-colors bg-white border border-slate-300 rounded-lg hover:bg-slate-50">
                ← Back
              </button>
              <button @click="submit" type="button" :disabled="loading"
                class="px-8 py-2.5 text-sm font-medium text-white transition-all bg-teal-600 hover:bg-teal-700 rounded-lg shadow-lg hover:shadow-teal-600/30 flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                <span v-if="loading">Creating...</span>
                <span v-else>Create Workspace</span>
                <svg v-if="!loading" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
              </button>
            </div>
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

const currentStep = ref(1)
const loading = ref(false)
const errors = ref({})

const steps = ['Basics', 'Location', 'Confirm']

const form = ref({
  name: '',
  business_type: '',
  phone: '',
  email: '',
  address_line1: '',
  city: '',
  postal_code: '',
  district: '',
  vat_registered: false,
  vat_bin: ''
})

const businessTypes = {
  retail: 'Retail',
  wholesale: 'Wholesale',
  manufacturing: 'Manufacturing',
  service: 'Service Provider',
  restaurant: 'Restaurant/Food Service',
  ecommerce: 'E-commerce',
  other: 'Other'
}

const districts = [
  'Dhaka', 'Chittagong', 'Khulna', 'Rajshahi', 'Sylhet',
  'Rangpur', 'Barisal', 'Comilla', 'Gazipur', 'Narayanganj'
]

const nextStep = () => {
  if (currentStep.value === 1) {
    // Validate step 1
    if (!form.value.name) {
      errors.value = { name: 'Company name is required' }
      return
    }
    if (!form.value.business_type) {
      errors.value = { business_type: 'Business type is required' }
      return
    }
  }

  if (currentStep.value === 2) {
    // Validate step 2
    if (form.value.vat_registered && !form.value.vat_bin) {
      errors.value = { vat_bin: 'VAT BIN is required when VAT registered' }
      return
    }
  }

  errors.value = {}
  if (currentStep.value < 3) {
    currentStep.value++
  }
}

const previousStep = () => {
  if (currentStep.value > 1) {
    currentStep.value--
    errors.value = {}
  }
}

const submit = async () => {
  loading.value = true
  errors.value = {}

  try {
    // Get CSRF cookie
    await axios.get('/sanctum/csrf-cookie')

    // Submit company setup
    await axios.post('/api/v1/auth/setup-company', form.value)

    // Redirect to dashboard
    window.location.href = '/dashboard'
  } catch (err) {
    if (err.response?.data?.errors) {
      errors.value = err.response.data.errors
    } else {
      errors.value = { general: 'Failed to create company. Please try again.' }
    }
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.animate-fadeIn {
  animation: fadeIn 0.4s ease-out forwards;
}

@keyframes blob {
  0% {
    transform: translate(0px, 0px) scale(1);
  }
  33% {
    transform: translate(30px, -50px) scale(1.1);
  }
  66% {
    transform: translate(-20px, 20px) scale(0.9);
  }
  100% {
    transform: translate(0px, 0px) scale(1);
  }
}

.animate-blob {
  animation: blob 7s infinite;
}

.animation-delay-4000 {
  animation-delay: 4s;
}
</style>
