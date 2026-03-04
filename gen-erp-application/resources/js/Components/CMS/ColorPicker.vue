<template>
  <div class="color-picker">
    <label v-if="label" class="block text-sm font-medium text-gray-700 mb-2">
      {{ label }}
    </label>
    
    <div class="flex items-center space-x-3">
      <!-- Color Preview -->
      <div
        @click="showPicker = !showPicker"
        class="w-10 h-10 rounded-lg border-2 border-gray-300 cursor-pointer shadow-sm hover:shadow-md transition-shadow"
        :style="{ backgroundColor: modelValue }"
      ></div>
      
      <!-- Hex Input -->
      <input
        v-model="hexValue"
        @input="updateFromHex"
        type="text"
        placeholder="#000000"
        class="flex-1 px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-blue-500 focus:border-blue-500"
      />
      
      <!-- Clear Button -->
      <button
        v-if="allowClear && modelValue"
        @click="clearColor"
        class="p-2 text-gray-400 hover:text-gray-600 transition-colors"
        title="Clear color"
      >
        <Icon name="heroicons:x-mark" class="w-4 h-4" />
      </button>
    </div>

    <!-- Color Picker Popup -->
    <div
      v-if="showPicker"
      v-click-outside="closePicker"
      class="absolute z-50 mt-2 p-4 bg-white border border-gray-200 rounded-lg shadow-lg"
    >
      <!-- Preset Colors -->
      <div class="mb-4">
        <h4 class="text-sm font-medium text-gray-700 mb-2">Preset Colors</h4>
        <div class="grid grid-cols-8 gap-2">
          <div
            v-for="color in presetColors"
            :key="color"
            @click="selectColor(color)"
            class="w-8 h-8 rounded cursor-pointer border-2 hover:scale-110 transition-transform"
            :class="{
              'border-gray-400': modelValue !== color,
              'border-blue-500 ring-2 ring-blue-200': modelValue === color
            }"
            :style="{ backgroundColor: color }"
          ></div>
        </div>
      </div>

      <!-- Color Sliders -->
      <div class="space-y-3">
        <!-- Hue -->
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">Hue</label>
          <input
            v-model="hue"
            @input="updateFromHSL"
            type="range"
            min="0"
            max="360"
            class="w-full h-2 bg-gradient-to-r from-red-500 via-yellow-500 via-green-500 via-cyan-500 via-blue-500 via-purple-500 to-red-500 rounded-lg appearance-none cursor-pointer"
          />
        </div>

        <!-- Saturation -->
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">Saturation</label>
          <input
            v-model="saturation"
            @input="updateFromHSL"
            type="range"
            min="0"
            max="100"
            class="w-full h-2 rounded-lg appearance-none cursor-pointer"
            :style="{ background: `linear-gradient(to right, hsl(${hue}, 0%, ${lightness}%), hsl(${hue}, 100%, ${lightness}%))` }"
          />
        </div>

        <!-- Lightness -->
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">Lightness</label>
          <input
            v-model="lightness"
            @input="updateFromHSL"
            type="range"
            min="0"
            max="100"
            class="w-full h-2 rounded-lg appearance-none cursor-pointer"
            :style="{ background: `linear-gradient(to right, hsl(${hue}, ${saturation}%, 0%), hsl(${hue}, ${saturation}%, 50%), hsl(${hue}, ${saturation}%, 100%))` }"
          />
        </div>

        <!-- Alpha -->
        <div v-if="allowAlpha">
          <label class="block text-xs font-medium text-gray-600 mb-1">Opacity</label>
          <input
            v-model="alpha"
            @input="updateFromHSLA"
            type="range"
            min="0"
            max="100"
            class="w-full h-2 rounded-lg appearance-none cursor-pointer"
            :style="{ background: `linear-gradient(to right, hsla(${hue}, ${saturation}%, ${lightness}%, 0), hsla(${hue}, ${saturation}%, ${lightness}%, 1))` }"
          />
        </div>
      </div>

      <!-- Color Values -->
      <div class="mt-4 pt-4 border-t border-gray-200">
        <div class="grid grid-cols-2 gap-2 text-xs">
          <div>
            <span class="text-gray-600">HEX:</span>
            <span class="font-mono ml-1">{{ hexValue }}</span>
          </div>
          <div>
            <span class="text-gray-600">RGB:</span>
            <span class="font-mono ml-1">{{ rgbValue }}</span>
          </div>
          <div>
            <span class="text-gray-600">HSL:</span>
            <span class="font-mono ml-1">{{ hslValue }}</span>
          </div>
          <div v-if="allowAlpha">
            <span class="text-gray-600">HSLA:</span>
            <span class="font-mono ml-1">{{ hslaValue }}</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import Icon from '@/Components/UI/Icon.vue'

const props = defineProps({
  modelValue: {
    type: String,
    default: '#000000'
  },
  label: {
    type: String,
    default: ''
  },
  allowClear: {
    type: Boolean,
    default: false
  },
  allowAlpha: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['update:modelValue'])

const showPicker = ref(false)
const hue = ref(0)
const saturation = ref(100)
const lightness = ref(50)
const alpha = ref(100)

// Preset colors
const presetColors = [
  '#000000', '#ffffff', '#f3f4f6', '#6b7280',
  '#ef4444', '#f97316', '#f59e0b', '#eab308',
  '#84cc16', '#22c55e', '#10b981', '#14b8a6',
  '#06b6d4', '#0ea5e9', '#3b82f6', '#6366f1',
  '#8b5cf6', '#a855f7', '#d946ef', '#ec4899',
  '#f43f5e', '#dc2626', '#ea580c', '#d97706',
  '#ca8a04', '#65a30d', '#16a34a', '#059669',
  '#0d9488', '#0891b2', '#0284c7', '#2563eb'
]

// Computed values
const hexValue = computed({
  get: () => props.modelValue || '#000000',
  set: (value) => emit('update:modelValue', value)
})

const rgbValue = computed(() => {
  const hex = hexValue.value.replace('#', '')
  const r = parseInt(hex.substr(0, 2), 16)
  const g = parseInt(hex.substr(2, 2), 16)
  const b = parseInt(hex.substr(4, 2), 16)
  return `${r}, ${g}, ${b}`
})

const hslValue = computed(() => {
  return `${hue.value}, ${saturation.value}%, ${lightness.value}%`
})

const hslaValue = computed(() => {
  return `${hue.value}, ${saturation.value}%, ${lightness.value}%, ${alpha.value / 100}`
})

// Methods
const selectColor = (color) => {
  hexValue.value = color
  updateHSLFromHex(color)
}

const clearColor = () => {
  emit('update:modelValue', '')
  showPicker.value = false
}

const closePicker = () => {
  showPicker.value = false
}

const updateFromHex = () => {
  let hex = hexValue.value
  if (!hex.startsWith('#')) {
    hex = '#' + hex
  }
  if (hex.length === 7) {
    updateHSLFromHex(hex)
    emit('update:modelValue', hex)
  }
}

const updateFromHSL = () => {
  const hex = hslToHex(hue.value, saturation.value, lightness.value)
  emit('update:modelValue', hex)
}

const updateFromHSLA = () => {
  if (props.allowAlpha) {
    const hsla = `hsla(${hue.value}, ${saturation.value}%, ${lightness.value}%, ${alpha.value / 100})`
    emit('update:modelValue', hsla)
  } else {
    updateFromHSL()
  }
}

const updateHSLFromHex = (hex) => {
  const r = parseInt(hex.substr(1, 2), 16) / 255
  const g = parseInt(hex.substr(3, 2), 16) / 255
  const b = parseInt(hex.substr(5, 2), 16) / 255

  const max = Math.max(r, g, b)
  const min = Math.min(r, g, b)
  let h, s, l = (max + min) / 2

  if (max === min) {
    h = s = 0 // achromatic
  } else {
    const d = max - min
    s = l > 0.5 ? d / (2 - max - min) : d / (max + min)
    switch (max) {
      case r: h = (g - b) / d + (g < b ? 6 : 0); break
      case g: h = (b - r) / d + 2; break
      case b: h = (r - g) / d + 4; break
    }
    h /= 6
  }

  hue.value = Math.round(h * 360)
  saturation.value = Math.round(s * 100)
  lightness.value = Math.round(l * 100)
}

const hslToHex = (h, s, l) => {
  l /= 100
  const a = s * Math.min(l, 1 - l) / 100
  const f = n => {
    const k = (n + h / 30) % 12
    const color = l - a * Math.max(Math.min(k - 3, 9 - k, 1), -1)
    return Math.round(255 * color).toString(16).padStart(2, '0')
  }
  return `#${f(0)}${f(8)}${f(4)}`
}

// Initialize HSL values from initial hex value
watch(() => props.modelValue, (newValue) => {
  if (newValue && newValue.startsWith('#') && newValue.length === 7) {
    updateHSLFromHex(newValue)
  }
}, { immediate: true })

// Click outside directive
const vClickOutside = {
  beforeMount(el, binding) {
    el.clickOutsideEvent = (event) => {
      if (!(el === event.target || el.contains(event.target))) {
        binding.value()
      }
    }
    document.addEventListener('click', el.clickOutsideEvent)
  },
  unmounted(el) {
    document.removeEventListener('click', el.clickOutsideEvent)
  }
}
</script>

<style scoped>
.color-picker {
  position: relative;
}

/* Custom range slider styles */
input[type="range"] {
  -webkit-appearance: none;
  appearance: none;
}

input[type="range"]::-webkit-slider-thumb {
  -webkit-appearance: none;
  appearance: none;
  width: 16px;
  height: 16px;
  border-radius: 50%;
  background: #ffffff;
  border: 2px solid #d1d5db;
  cursor: pointer;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

input[type="range"]::-moz-range-thumb {
  width: 16px;
  height: 16px;
  border-radius: 50%;
  background: #ffffff;
  border: 2px solid #d1d5db;
  cursor: pointer;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}
</style>