<template>
  <section class="py-16" :style="{ backgroundColor: content.background_color || 'transparent' }">
    <div class="container mx-auto px-4">
      <!-- Section Header -->
      <div v-if="content.title || content.subtitle" class="text-center mb-12">
        <h2 
          v-if="content.title" 
          class="text-3xl md:text-4xl font-bold mb-4"
          :style="{ color: content.title_color || tenant?.settings?.primary_color || '#1f2937' }"
        >
          {{ content.title }}
        </h2>
        <p v-if="content.subtitle" class="text-xl text-gray-600 max-w-3xl mx-auto">
          {{ content.subtitle }}
        </p>
      </div>

      <!-- Gallery Grid -->
      <div v-if="content.images && content.images.length > 0">
        <!-- Masonry Layout -->
        <div 
          v-if="content.layout === 'masonry'"
          class="columns-1 md:columns-2 lg:columns-3 xl:columns-4 gap-4 space-y-4"
        >
          <div 
            v-for="(image, index) in content.images" 
            :key="index"
            class="break-inside-avoid mb-4 group cursor-pointer"
            @click="openLightbox(index)"
          >
            <div class="relative overflow-hidden rounded-lg shadow-md hover:shadow-lg transition-shadow duration-300">
              <NuxtImg
                :src="image.url"
                :alt="image.alt || `Gallery image ${index + 1}`"
                class="w-full h-auto object-cover group-hover:scale-105 transition-transform duration-300"
                loading="lazy"
                format="webp"
                quality="80"
              />
              <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-20 transition-all duration-300 flex items-center justify-center">
                <svg class="w-8 h-8 text-white opacity-0 group-hover:opacity-100 transition-opacity duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"></path>
                </svg>
              </div>
              <div v-if="image.caption" class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black to-transparent p-4">
                <p class="text-white text-sm">{{ image.caption }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Grid Layout -->
        <div 
          v-else
          class="grid gap-4"
          :class="{
            'grid-cols-1 md:grid-cols-2 lg:grid-cols-3': content.columns === 3,
            'grid-cols-1 md:grid-cols-2 lg:grid-cols-4': content.columns === 4,
            'grid-cols-1 md:grid-cols-2': content.columns === 2,
            'grid-cols-1 md:grid-cols-2 lg:grid-cols-3': !content.columns
          }"
        >
          <div 
            v-for="(image, index) in content.images" 
            :key="index"
            class="group cursor-pointer"
            @click="openLightbox(index)"
          >
            <div class="relative overflow-hidden rounded-lg shadow-md hover:shadow-lg transition-shadow duration-300 aspect-square">
              <NuxtImg
                :src="image.url"
                :alt="image.alt || `Gallery image ${index + 1}`"
                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                loading="lazy"
                format="webp"
                quality="80"
              />
              <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-20 transition-all duration-300 flex items-center justify-center">
                <svg class="w-8 h-8 text-white opacity-0 group-hover:opacity-100 transition-opacity duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"></path>
                </svg>
              </div>
              <div v-if="image.caption" class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black to-transparent p-4">
                <p class="text-white text-sm">{{ image.caption }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-else class="text-center py-12">
        <div class="text-gray-400 mb-4">
          <svg class="w-16 h-16 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
          </svg>
        </div>
        <p class="text-gray-600">No images in gallery</p>
      </div>
    </div>

    <!-- Lightbox Modal -->
    <Teleport to="body">
      <div 
        v-if="lightboxOpen" 
        class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-90"
        @click="closeLightbox"
        @keydown.esc="closeLightbox"
      >
        <div class="relative max-w-7xl max-h-full p-4">
          <!-- Close Button -->
          <button
            @click="closeLightbox"
            class="absolute top-4 right-4 z-10 w-10 h-10 rounded-full bg-black bg-opacity-50 text-white hover:bg-opacity-70 transition-colors duration-200 flex items-center justify-center"
          >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
          </button>

          <!-- Navigation Buttons -->
          <button
            v-if="content.images && content.images.length > 1"
            @click.stop="previousImage"
            class="absolute left-4 top-1/2 transform -translate-y-1/2 z-10 w-12 h-12 rounded-full bg-black bg-opacity-50 text-white hover:bg-opacity-70 transition-colors duration-200 flex items-center justify-center"
          >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
          </button>

          <button
            v-if="content.images && content.images.length > 1"
            @click.stop="nextImage"
            class="absolute right-4 top-1/2 transform -translate-y-1/2 z-10 w-12 h-12 rounded-full bg-black bg-opacity-50 text-white hover:bg-opacity-70 transition-colors duration-200 flex items-center justify-center"
          >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
            </svg>
          </button>

          <!-- Image -->
          <div class="flex items-center justify-center max-h-full">
            <NuxtImg
              v-if="currentImage"
              :src="currentImage.url"
              :alt="currentImage.alt || 'Gallery image'"
              class="max-w-full max-h-full object-contain"
              @click.stop
            />
          </div>

          <!-- Caption -->
          <div v-if="currentImage?.caption" class="absolute bottom-4 left-4 right-4 text-center">
            <p class="text-white bg-black bg-opacity-50 rounded-lg px-4 py-2 inline-block">
              {{ currentImage.caption }}
            </p>
          </div>

          <!-- Image Counter -->
          <div v-if="content.images && content.images.length > 1" class="absolute top-4 left-4 text-white bg-black bg-opacity-50 rounded-lg px-3 py-1 text-sm">
            {{ currentImageIndex + 1 }} / {{ content.images.length }}
          </div>
        </div>
      </div>
    </Teleport>
  </section>
</template>

<script setup>
interface GalleryImage {
  url: string
  alt?: string
  caption?: string
}

interface Content {
  title?: string
  subtitle?: string
  background_color?: string
  title_color?: string
  images?: GalleryImage[]
  layout?: 'grid' | 'masonry'
  columns?: 2 | 3 | 4
}

interface Tenant {
  id: string
  name: string
  slug: string
  settings: Record<string, any>
}

const props = defineProps<{
  content: Content
  tenant?: Tenant
}>()

// Lightbox state
const lightboxOpen = ref(false)
const currentImageIndex = ref(0)

// Computed current image
const currentImage = computed(() => {
  if (!props.content.images || props.content.images.length === 0) return null
  return props.content.images[currentImageIndex.value]
})

// Lightbox functions
const openLightbox = (index: number) => {
  currentImageIndex.value = index
  lightboxOpen.value = true
  document.body.style.overflow = 'hidden'
}

const closeLightbox = () => {
  lightboxOpen.value = false
  document.body.style.overflow = 'auto'
}

const nextImage = () => {
  if (!props.content.images) return
  currentImageIndex.value = (currentImageIndex.value + 1) % props.content.images.length
}

const previousImage = () => {
  if (!props.content.images) return
  currentImageIndex.value = currentImageIndex.value === 0 
    ? props.content.images.length - 1 
    : currentImageIndex.value - 1
}

// Keyboard navigation
const handleKeydown = (event: KeyboardEvent) => {
  if (!lightboxOpen.value) return
  
  switch (event.key) {
    case 'Escape':
      closeLightbox()
      break
    case 'ArrowLeft':
      previousImage()
      break
    case 'ArrowRight':
      nextImage()
      break
  }
}

// Lifecycle
onMounted(() => {
  document.addEventListener('keydown', handleKeydown)
})

onUnmounted(() => {
  document.removeEventListener('keydown', handleKeydown)
  document.body.style.overflow = 'auto'
})
</script>

<style scoped>
/* Masonry layout support */
.columns-1 {
  column-count: 1;
}

.columns-2 {
  column-count: 2;
}

.columns-3 {
  column-count: 3;
}

.columns-4 {
  column-count: 4;
}

@media (max-width: 768px) {
  .md\:columns-2 {
    column-count: 2;
  }
}

@media (min-width: 1024px) {
  .lg\:columns-3 {
    column-count: 3;
  }
}

@media (min-width: 1280px) {
  .xl\:columns-4 {
    column-count: 4;
  }
}

.break-inside-avoid {
  break-inside: avoid;
}

/* Smooth transitions */
.transition-all {
  transition-property: all;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
}

/* Aspect ratio for grid layout */
.aspect-square {
  aspect-ratio: 1 / 1;
}
</style>