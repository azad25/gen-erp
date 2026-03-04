<template>
  <div
    :class="{
      'h-64': content.height === 'small',
      'h-96': content.height === 'medium',
      'h-screen': content.height === 'large' || content.height === 'full'
    }"
    class="relative flex items-center justify-center text-white overflow-hidden"
    :style="{
      backgroundColor: content.background_color || '#1f2937',
      color: content.text_color || '#ffffff'
    }"
  >
    <!-- Background Image -->
    <div
      v-if="content.background_image"
      class="absolute inset-0 bg-cover bg-center bg-no-repeat"
      :style="{ backgroundImage: `url(${content.background_image})` }"
    ></div>
    
    <!-- Overlay -->
    <div
      v-if="content.background_image || content.overlay_color"
      class="absolute inset-0"
      :style="{ 
        backgroundColor: content.overlay_color || '#000000',
        opacity: content.overlay_opacity || 0.4 
      }"
    ></div>
    
    <!-- Content -->
    <div
      :class="{
        'text-left': content.text_align === 'left',
        'text-center': content.text_align === 'center',
        'text-right': content.text_align === 'right'
      }"
      class="relative z-10 max-w-4xl mx-auto px-6"
    >
      <h1 class="text-4xl md:text-6xl font-bold mb-6">
        {{ content.title || 'Your Headline Here' }}
      </h1>
      
      <p class="text-xl md:text-2xl mb-8 opacity-90">
        {{ content.subtitle || 'Supporting text that explains your value' }}
      </p>
      
      <div v-if="content.cta_text">
        <a
          :href="content.cta_link || '#'"
          class="inline-block font-semibold py-3 px-8 rounded-lg transition-colors duration-200"
          :class="{ 'pointer-events-none': isEditing }"
          :style="{
            backgroundColor: content.cta_button_color || '#2563eb',
            color: content.cta_text_color || '#ffffff'
          }"
        >
          {{ content.cta_text }}
        </a>
      </div>
    </div>
    
    <!-- Editing Overlay -->
    <div
      v-if="isEditing"
      class="absolute inset-0 bg-blue-500 bg-opacity-10 border-2 border-dashed border-blue-400 flex items-center justify-center"
    >
      <div class="bg-white bg-opacity-90 px-4 py-2 rounded-lg text-gray-900 text-sm font-medium">
        Hero Banner Section
      </div>
    </div>
  </div>
</template>

<script setup>
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
</script>