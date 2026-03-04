<template>
  <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
      <div class="mt-3">
        <!-- Header -->
        <div class="flex items-center justify-between pb-4 border-b">
          <h3 class="text-lg font-medium text-gray-900">Create New Shipment</h3>
          <button
            @click="$emit('close')"
            class="text-gray-400 hover:text-gray-600"
          >
            <XMarkIcon class="h-6 w-6" />
          </button>
        </div>

        <!-- Form -->
        <form @submit.prevent="createShipment" class="mt-6 space-y-6">
          <!-- Carrier Selection -->
          <div>
            <label class="block text-sm font-medium text-gray-700">Carrier *</label>
            <select
              v-model="form.carrier_id"
              required
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            >
              <option value="">Select Carrier</option>
              <option v-for="carrier in carriers" :key="carrier.id" :value="carrier.id">
                {{ carrier.name }} - {{ carrier.code }}
              </option>
            </select>
          </div>

          <!-- Recipient Information -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700">Recipient Name *</label>
              <input
                v-model="form.recipient_name"
                type="text"
                required
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Recipient Phone *</label>
              <input
                v-model="form.recipient_phone"
                type="tel"
                required
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
              />
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700">Recipient Email</label>
            <input
              v-model="form.recipient_email"
              type="email"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            />
          </div>

          <!-- Addresses -->
          <div class="space-y-4">
            <h4 class="text-md font-medium text-gray-900">Pickup Address</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700">Address Line *</label>
                <textarea
                  v-model="form.pickup_address"
                  required
                  rows="2"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                ></textarea>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700">City *</label>
                <input
                  v-model="form.pickup_city"
                  type="text"
                  required
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700">Postal Code</label>
                <input
                  v-model="form.pickup_postal_code"
                  type="text"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                />
              </div>
            </div>
          </div>

          <div class="space-y-4">
            <h4 class="text-md font-medium text-gray-900">Delivery Address</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700">Address Line *</label>
                <textarea
                  v-model="form.delivery_address"
                  required
                  rows="2"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                ></textarea>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700">City *</label>
                <input
                  v-model="form.delivery_city"
                  type="text"
                  required
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700">Postal Code</label>
                <input
                  v-model="form.delivery_postal_code"
                  type="text"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                />
              </div>
            </div>
          </div>

          <!-- Package Details -->
          <div class="space-y-4">
            <h4 class="text-md font-medium text-gray-900">Package Details</h4>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700">Weight (kg) *</label>
                <input
                  v-model.number="form.weight"
                  type="number"
                  step="0.1"
                  min="0"
                  required
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700">Length (cm)</label>
                <input
                  v-model.number="form.length"
                  type="number"
                  step="0.1"
                  min="0"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700">Width (cm)</label>
                <input
                  v-model.number="form.width"
                  type="number"
                  step="0.1"
                  min="0"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                />
              </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700">Height (cm)</label>
                <input
                  v-model.number="form.height"
                  type="number"
                  step="0.1"
                  min="0"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700">Declared Value (৳)</label>
                <input
                  v-model.number="form.declared_value"
                  type="number"
                  step="0.01"
                  min="0"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                />
              </div>
            </div>
          </div>

          <!-- COD and Special Instructions -->
          <div class="space-y-4">
            <div class="flex items-center">
              <input
                v-model="form.is_cod"
                type="checkbox"
                class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
              />
              <label class="ml-2 block text-sm text-gray-900">Cash on Delivery (COD)</label>
            </div>

            <div v-if="form.is_cod">
              <label class="block text-sm font-medium text-gray-700">COD Amount (৳) *</label>
              <input
                v-model.number="form.cod_amount"
                type="number"
                step="0.01"
                min="0"
                :required="form.is_cod"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700">Special Instructions</label>
              <textarea
                v-model="form.special_instructions"
                rows="3"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                placeholder="Any special handling instructions..."
              ></textarea>
            </div>
          </div>

          <!-- Shipment Items -->
          <div class="space-y-4">
            <div class="flex items-center justify-between">
              <h4 class="text-md font-medium text-gray-900">Shipment Items</h4>
              <button
                type="button"
                @click="addItem"
                class="text-indigo-600 hover:text-indigo-900 text-sm font-medium"
              >
                + Add Item
              </button>
            </div>
            
            <div v-for="(item, index) in form.items" :key="index" class="border rounded-lg p-4">
              <div class="flex items-center justify-between mb-3">
                <h5 class="text-sm font-medium text-gray-700">Item {{ index + 1 }}</h5>
                <button
                  v-if="form.items.length > 1"
                  type="button"
                  @click="removeItem(index)"
                  class="text-red-600 hover:text-red-900"
                >
                  <XMarkIcon class="h-4 w-4" />
                </button>
              </div>
              
              <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700">Name *</label>
                  <input
                    v-model="item.name"
                    type="text"
                    required
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                  />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700">Quantity *</label>
                  <input
                    v-model.number="item.quantity"
                    type="number"
                    min="1"
                    required
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                  />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700">Value (৳)</label>
                  <input
                    v-model.number="item.value"
                    type="number"
                    step="0.01"
                    min="0"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                  />
                </div>
              </div>
              
              <div class="mt-3">
                <label class="block text-sm font-medium text-gray-700">Description</label>
                <input
                  v-model="item.description"
                  type="text"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                />
              </div>
            </div>
          </div>

          <!-- Form Actions -->
          <div class="flex items-center justify-end space-x-3 pt-6 border-t">
            <button
              type="button"
              @click="$emit('close')"
              class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50"
            >
              Cancel
            </button>
            <button
              type="submit"
              :disabled="creating"
              class="bg-indigo-600 hover:bg-indigo-700 text-white py-2 px-4 rounded-md text-sm font-medium disabled:opacity-50"
            >
              {{ creating ? 'Creating...' : 'Create Shipment' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, reactive } from 'vue'
import { XMarkIcon } from '@heroicons/vue/24/outline'
import { useToast } from '@/Composables/useToast'

const emit = defineEmits(['close', 'created'])
const { showToast } = useToast()

// Reactive data
const carriers = ref([])
const creating = ref(false)

const form = reactive({
  carrier_id: '',
  recipient_name: '',
  recipient_phone: '',
  recipient_email: '',
  pickup_address: '',
  pickup_city: '',
  pickup_postal_code: '',
  delivery_address: '',
  delivery_city: '',
  delivery_postal_code: '',
  weight: null,
  length: null,
  width: null,
  height: null,
  declared_value: null,
  is_cod: false,
  cod_amount: null,
  special_instructions: '',
  items: [
    {
      name: '',
      description: '',
      quantity: 1,
      value: null
    }
  ]
})

// Methods
const fetchCarriers = async () => {
  try {
    const response = await fetch('/api/v1/logistics/carriers', {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Accept': 'application/json',
      }
    })
    
    if (response.ok) {
      const data = await response.json()
      carriers.value = data.data
    }
  } catch (error) {
    console.error('Failed to fetch carriers:', error)
  }
}

const addItem = () => {
  form.items.push({
    name: '',
    description: '',
    quantity: 1,
    value: null
  })
}

const removeItem = (index) => {
  form.items.splice(index, 1)
}

const createShipment = async () => {
  creating.value = true
  
  try {
    const response = await fetch('/api/v1/logistics/shipments', {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: JSON.stringify(form)
    })
    
    if (response.ok) {
      emit('created')
    } else {
      const error = await response.json()
      showToast(error.message || 'Failed to create shipment', 'error')
    }
  } catch (error) {
    console.error('Failed to create shipment:', error)
    showToast('Failed to create shipment', 'error')
  } finally {
    creating.value = false
  }
}

// Lifecycle
onMounted(() => {
  fetchCarriers()
})
</script>