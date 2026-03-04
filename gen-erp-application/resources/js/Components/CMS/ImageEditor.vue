<template>
  <div class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
      <!-- Background overlay -->
      <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="$emit('cancel')"></div>

      <!-- Modal panel -->
      <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
        <!-- Header -->
        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
          <div class="flex items-center justify-between">
            <h3 class="text-lg leading-6 font-medium text-gray-900">
              Image Editor
            </h3>
            <button
              @click="$emit('cancel')"
              class="text-gray-400 hover:text-gray-600 transition-colors"
            >
              <Icon name="heroicons:x-mark" class="w-6 h-6" />
            </button>
          </div>
        </div>

        <!-- Editor Content -->
        <div class="px-4 pb-4 sm:px-6 sm:pb-6">
          <div class="flex space-x-6">
            <!-- Tools Panel -->
            <div class="w-64 bg-gray-50 rounded-lg p-4">
              <h4 class="text-sm font-medium text-gray-900 mb-4">Tools</h4>
              
              <!-- Crop -->
              <div class="mb-6">
                <h5 class="text-xs font-medium text-gray-700 mb-2">Crop</h5>
                <div class="grid grid-cols-2 gap-2">
                  <button
                    v-for="ratio in cropRatios"
                    :key="ratio.label"
                    @click="setCropRatio(ratio)"
                    :class="{
                      'bg-blue-100 border-blue-300': currentCropRatio?.label === ratio.label,
                      'bg-white border-gray-300': currentCropRatio?.label !== ratio.label
                    }"
                    class="px-2 py-1 text-xs border rounded hover:bg-gray-50 transition-colors"
                  >
                    {{ ratio.label }}
                  </button>
                </div>
              </div>

              <!-- Filters -->
              <div class="mb-6">
                <h5 class="text-xs font-medium text-gray-700 mb-2">Filters</h5>
                <div class="space-y-2">
                  <div>
                    <label class="block text-xs text-gray-600 mb-1">Brightness</label>
                    <input
                      v-model="filters.brightness"
                      @input="applyFilters"
                      type="range"
                      min="0"
                      max="200"
                      class="w-full h-1 bg-gray-200 rounded-lg appearance-none cursor-pointer"
                    />
                    <div class="text-xs text-gray-500 text-center">{{ filters.brightness }}%</div>
                  </div>
                  
                  <div>
                    <label class="block text-xs text-gray-600 mb-1">Contrast</label>
                    <input
                      v-model="filters.contrast"
                      @input="applyFilters"
                      type="range"
                      min="0"
                      max="200"
                      class="w-full h-1 bg-gray-200 rounded-lg appearance-none cursor-pointer"
                    />
                    <div class="text-xs text-gray-500 text-center">{{ filters.contrast }}%</div>
                  </div>
                  
                  <div>
                    <label class="block text-xs text-gray-600 mb-1">Saturation</label>
                    <input
                      v-model="filters.saturation"
                      @input="applyFilters"
                      type="range"
                      min="0"
                      max="200"
                      class="w-full h-1 bg-gray-200 rounded-lg appearance-none cursor-pointer"
                    />
                    <div class="text-xs text-gray-500 text-center">{{ filters.saturation }}%</div>
                  </div>
                  
                  <div>
                    <label class="block text-xs text-gray-600 mb-1">Blur</label>
                    <input
                      v-model="filters.blur"
                      @input="applyFilters"
                      type="range"
                      min="0"
                      max="10"
                      step="0.1"
                      class="w-full h-1 bg-gray-200 rounded-lg appearance-none cursor-pointer"
                    />
                    <div class="text-xs text-gray-500 text-center">{{ filters.blur }}px</div>
                  </div>
                </div>
                
                <button
                  @click="resetFilters"
                  class="mt-3 w-full px-3 py-1 text-xs bg-gray-200 hover:bg-gray-300 rounded transition-colors"
                >
                  Reset Filters
                </button>
              </div>

              <!-- Rotate -->
              <div class="mb-6">
                <h5 class="text-xs font-medium text-gray-700 mb-2">Rotate</h5>
                <div class="flex space-x-2">
                  <button
                    @click="rotate(-90)"
                    class="flex-1 px-2 py-1 text-xs bg-white border border-gray-300 rounded hover:bg-gray-50 transition-colors"
                  >
                    ↺ 90°
                  </button>
                  <button
                    @click="rotate(90)"
                    class="flex-1 px-2 py-1 text-xs bg-white border border-gray-300 rounded hover:bg-gray-50 transition-colors"
                  >
                    ↻ 90°
                  </button>
                </div>
              </div>

              <!-- Flip -->
              <div>
                <h5 class="text-xs font-medium text-gray-700 mb-2">Flip</h5>
                <div class="flex space-x-2">
                  <button
                    @click="flip('horizontal')"
                    class="flex-1 px-2 py-1 text-xs bg-white border border-gray-300 rounded hover:bg-gray-50 transition-colors"
                  >
                    ↔ Horizontal
                  </button>
                  <button
                    @click="flip('vertical')"
                    class="flex-1 px-2 py-1 text-xs bg-white border border-gray-300 rounded hover:bg-gray-50 transition-colors"
                  >
                    ↕ Vertical
                  </button>
                </div>
              </div>
            </div>

            <!-- Canvas Area -->
            <div class="flex-1">
              <div class="bg-gray-100 rounded-lg p-4 min-h-96 flex items-center justify-center">
                <canvas
                  ref="canvas"
                  class="max-w-full max-h-96 border border-gray-300 rounded shadow-sm"
                ></canvas>
              </div>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
          <button
            @click="saveImage"
            :disabled="saving"
            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50"
          >
            {{ saving ? 'Saving...' : 'Save Changes' }}
          </button>
          <button
            @click="$emit('cancel')"
            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
          >
            Cancel
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, nextTick } from 'vue'
import Icon from '@/Components/UI/Icon.vue'

const props = defineProps({
  image: {
    type: String,
    required: true
  }
})

const emit = defineEmits(['save', 'cancel'])

const canvas = ref(null)
const saving = ref(false)
const currentCropRatio = ref(null)

// Image editing state
const filters = ref({
  brightness: 100,
  contrast: 100,
  saturation: 100,
  blur: 0
})

const transform = ref({
  rotation: 0,
  scaleX: 1,
  scaleY: 1
})

// Crop ratios
const cropRatios = [
  { label: 'Free', ratio: null },
  { label: '1:1', ratio: 1 },
  { label: '4:3', ratio: 4/3 },
  { label: '16:9', ratio: 16/9 },
  { label: '3:2', ratio: 3/2 }
]

let originalImage = null
let ctx = null

onMounted(async () => {
  await nextTick()
  initializeCanvas()
})

const initializeCanvas = () => {
  if (!canvas.value) return

  ctx = canvas.value.getContext('2d')
  
  // Load the original image
  originalImage = new Image()
  originalImage.crossOrigin = 'anonymous'
  originalImage.onload = () => {
    canvas.value.width = originalImage.width
    canvas.value.height = originalImage.height
    drawImage()
  }
  originalImage.src = props.image
}

const drawImage = () => {
  if (!ctx || !originalImage) return

  // Clear canvas
  ctx.clearRect(0, 0, canvas.value.width, canvas.value.height)

  // Save context
  ctx.save()

  // Apply transformations
  ctx.translate(canvas.value.width / 2, canvas.value.height / 2)
  ctx.rotate((transform.value.rotation * Math.PI) / 180)
  ctx.scale(transform.value.scaleX, transform.value.scaleY)

  // Apply filters
  ctx.filter = `
    brightness(${filters.value.brightness}%)
    contrast(${filters.value.contrast}%)
    saturate(${filters.value.saturation}%)
    blur(${filters.value.blur}px)
  `

  // Draw image
  ctx.drawImage(
    originalImage,
    -originalImage.width / 2,
    -originalImage.height / 2,
    originalImage.width,
    originalImage.height
  )

  // Restore context
  ctx.restore()
}

const applyFilters = () => {
  drawImage()
}

const resetFilters = () => {
  filters.value = {
    brightness: 100,
    contrast: 100,
    saturation: 100,
    blur: 0
  }
  drawImage()
}

const rotate = (degrees) => {
  transform.value.rotation += degrees
  drawImage()
}

const flip = (direction) => {
  if (direction === 'horizontal') {
    transform.value.scaleX *= -1
  } else {
    transform.value.scaleY *= -1
  }
  drawImage()
}

const setCropRatio = (ratio) => {
  currentCropRatio.value = ratio
  // Implement crop functionality here
  console.log('Crop ratio set:', ratio)
}

const saveImage = async () => {
  saving.value = true

  try {
    // Convert canvas to blob
    const blob = await new Promise(resolve => {
      canvas.value.toBlob(resolve, 'image/png', 0.9)
    })

    // Create form data
    const formData = new FormData()
    formData.append('file', blob, 'edited-image.png')

    // Upload edited image
    const response = await fetch('/api/v1/cms/media/upload', {
      method: 'POST',
      body: formData,
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
        'Accept': 'application/json',
      }
    })

    if (!response.ok) {
      throw new Error('Upload failed')
    }

    const data = await response.json()
    emit('save', data.url)
  } catch (error) {
    console.error('Save error:', error)
    alert('Failed to save image. Please try again.')
  } finally {
    saving.value = false
  }
}
</script>

<style scoped>
/* Custom range slider styles */
input[type="range"] {
  -webkit-appearance: none;
  appearance: none;
}

input[type="range"]::-webkit-slider-thumb {
  -webkit-appearance: none;
  appearance: none;
  width: 12px;
  height: 12px;
  border-radius: 50%;
  background: #3b82f6;
  cursor: pointer;
}

input[type="range"]::-moz-range-thumb {
  width: 12px;
  height: 12px;
  border-radius: 50%;
  background: #3b82f6;
  cursor: pointer;
  border: none;
}
</style>