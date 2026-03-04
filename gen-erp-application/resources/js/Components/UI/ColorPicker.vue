<template>
  <div class="color-picker">
    <label v-if="label" class="block text-sm font-medium text-gray-700 mb-2">
      {{ label }}
    </label>
    
    <div class="flex items-center space-x-3">
      <!-- Color Preview -->
      <div
        class="w-10 h-10 rounded-lg border-2 border-gray-300 cursor-pointer shadow-sm hover:shadow-md transition-shadow"
        :style="{ backgroundColor: modelValue }"
        @click="togglePicker"
      >
        <div
          v-if="!modelValue"
          class="w-full h-full rounded-lg bg-gradient-to-br from-gray-100 to-gray-200 flex items-center justify-center"
        >
          <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zM21 5a2 2 0 00-2-2h-4a2 2 0 00-2 2v12a4 4 0 004 4h4a2 2 0 002-2V5z" />
          </svg>
        </div>
      </div>
      
      <!-- Color Input -->
      <div class="flex-1">
        <input
          :value="modelValue"
          @input="updateColor($event.target.value)"
          type="text"
          :placeholder="placeholder"
          class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm"
        />
      </div>
      
      <!-- Clear Button -->
      <button
        v-if="modelValue && clearable"
        @click="clearColor"
        type="button"
        class="p-2 text-gray-400 hover:text-gray-600 transition-colors"
        title="Clear color"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div>
    
    <!-- Color Picker Panel -->
    <div
      v-if="showPicker"
      class="absolute z-50 mt-2 bg-white border border-gray-200 rounded-lg shadow-lg p-4"
      style="min-width: 280px"
    >
      <!-- Preset Colors -->
      <div class="mb-4">
        <h4 class="text-sm font-medium text-gray-700 mb-2">Preset Colors</h4>
        <div class="grid grid-cols-8 gap-2">
          <button
            v-for="color in presetColors"
            :key="color"
            @click="selectColor(color)"
            :style="{ backgroundColor: color }"
            class="w-8 h-8 rounded border-2 hover:scale-110 transition-transform"
            :class="{
              'border-blue-500': modelValue === color,
              'border-gray-300': modelValue !== color
            }"
            :title="color"
          />
        </div>
      </div>
      
      <!-- Color Input Section -->
      <div class="space-y-3">
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">Hex Color</label>
          <div class="flex">
            <span class="inline-flex items-center px-3 py-2 border border-r-0 border-gray-300 bg-gray-50 text-gray-500 text-sm rounded-l-md">
              #
            </span>
            <input
              :value="hexValue"
              @input="updateHex($event.target.value)"
              type="text"
              maxlength="6"
              class="flex-1 px-3 py-2 border border-gray-300 rounded-r-md focus:ring-blue-500 focus:border-blue-500 text-sm"
              placeholder="000000"
            />
          </div>
        </div>
        
        <div class="grid grid-cols-3 gap-3">
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">R</label>
            <input
              :value="rgbValues.r"
              @input="updateRGB('r', $event.target.value)"
              type="number"
              min="0"
              max="255"
              class="w-full px-2 py-1 border border-gray-300 rounded text-sm focus:ring-blue-500 focus:border-blue-500"
            />
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">G</label>
            <input
              :value="rgbValues.g"
              @input="updateRGB('g', $event.target.value)"
              type="number"
              min="0"
              max="255"
              class="w-full px-2 py-1 border border-gray-300 rounded text-sm focus:ring-blue-500 focus:border-blue-500"
            />
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">B</label>
            <input
              :value="rgbValues.b"
              @input="updateRGB('b', $event.target.value)"
              type="number"
              min="0"
              max="255"
              class="w-full px-2 py-1 border border-gray-300 rounded text-sm focus:ring-blue-500 focus:border-blue-500"
            />
          </div>
        </div>
      </div>
      
      <!-- Actions -->
      <div class="flex justify-end space-x-2 mt-4 pt-3 border-t border-gray-200">
        <button
          @click="closePicker"
          type="button"
          class="px-3 py-1 text-sm text-gray-600 hover:text-gray-800 transition-colors"
        >
          Cancel
        </button>
        <button
          @click="applyColor"
          type="button"
          class="px-3 py-1 bg-blue-600 text-white text-sm rounded hover:bg-blue-700 transition-colors"
        >
          Apply
        </button>
      </div>
    </div>
    
    <!-- Backdrop -->
    <div
      v-if="showPicker"
      @click="closePicker"
      class="fixed inset-0 z-40"
    />
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'

const props = defineProps({
  modelValue: {
    type: String,
    default: ''
  },
  label: {
    type: String,
    default: ''
  },
  placeholder: {
    type: String,
    default: 'Enter color (hex, rgb, or name)'
  },
  clearable: {
    type: Boolean,
    default: true
  },
  presetColors: {
    type: Array,
    default: () => [
      '#000000', '#ffffff', '#f3f4f6', '#6b7280',
      '#ef4444', '#f97316', '#eab308', '#22c55e',
      '#06b6d4', '#3b82f6', '#8b5cf6', '#ec4899',
      '#dc2626', '#ea580c', '#ca8a04', '#16a34a',
      '#0891b2', '#2563eb', '#7c3aed', '#db2777',
      '#991b1b', '#c2410c', '#a16207', '#15803d',
      '#0e7490', '#1d4ed8', '#6d28d9', '#be185d'
    ]
  }
})

const emit = defineEmits(['update:modelValue'])

const showPicker = ref(false)
const tempColor = ref('')

// Computed values for color conversion
const hexValue = computed(() => {
  if (!props.modelValue) return ''
  return props.modelValue.replace('#', '').toUpperCase()
})

const rgbValues = computed(() => {
  if (!props.modelValue) return { r: 0, g: 0, b: 0 }
  
  const hex = props.modelValue.replace('#', '')
  if (hex.length !== 6) return { r: 0, g: 0, b: 0 }
  
  return {
    r: parseInt(hex.substr(0, 2), 16),
    g: parseInt(hex.substr(2, 2), 16),
    b: parseInt(hex.substr(4, 2), 16)
  }
})

// Methods
const togglePicker = () => {
  showPicker.value = !showPicker.value
  if (showPicker.value) {
    tempColor.value = props.modelValue
  }
}

const closePicker = () => {
  showPicker.value = false
  tempColor.value = ''
}

const applyColor = () => {
  if (tempColor.value !== props.modelValue) {
    emit('update:modelValue', tempColor.value)
  }
  closePicker()
}

const selectColor = (color) => {
  tempColor.value = color
  emit('update:modelValue', color)
  closePicker()
}

const updateColor = (value) => {
  emit('update:modelValue', value)
}

const clearColor = () => {
  emit('update:modelValue', '')
}

const updateHex = (value) => {
  // Remove any non-hex characters
  const cleanValue = value.replace(/[^0-9A-Fa-f]/g, '').substr(0, 6)
  if (cleanValue.length === 6) {
    tempColor.value = `#${cleanValue}`
  }
}

const updateRGB = (component, value) => {
  const numValue = Math.max(0, Math.min(255, parseInt(value) || 0))
  const currentRgb = { ...rgbValues.value }
  currentRgb[component] = numValue
  
  // Convert RGB to hex
  const hex = [currentRgb.r, currentRgb.g, currentRgb.b]
    .map(x => x.toString(16).padStart(2, '0'))
    .join('')
  
  tempColor.value = `#${hex}`
}

// Validate and normalize color values
const normalizeColor = (color) => {
  if (!color) return ''
  
  // Handle hex colors
  if (color.startsWith('#')) {
    const hex = color.replace('#', '')
    if (hex.length === 3) {
      // Convert 3-digit hex to 6-digit
      return `#${hex.split('').map(c => c + c).join('')}`
    }
    if (hex.length === 6 && /^[0-9A-Fa-f]{6}$/.test(hex)) {
      return `#${hex.toUpperCase()}`
    }
  }
  
  // Handle rgb/rgba colors
  if (color.startsWith('rgb')) {
    return color
  }
  
  // Handle named colors (basic validation)
  const namedColors = ['red', 'blue', 'green', 'yellow', 'orange', 'purple', 'pink', 'black', 'white', 'gray', 'brown']
  if (namedColors.includes(color.toLowerCase())) {
    return color.toLowerCase()
  }
  
  return color
}

// Watch for external changes
watch(() => props.modelValue, (newValue) => {
  if (newValue !== tempColor.value) {
    tempColor.value = normalizeColor(newValue)
  }
})

// Handle escape key
const handleEscape = (event) => {
  if (event.key === 'Escape' && showPicker.value) {
    closePicker()
  }
}

onMounted(() => {
  document.addEventListener('keydown', handleEscape)
})

onUnmounted(() => {
  document.removeEventListener('keydown', handleEscape)
})
</script>

<style scoped>
.color-picker {
  position: relative;
}
</style>