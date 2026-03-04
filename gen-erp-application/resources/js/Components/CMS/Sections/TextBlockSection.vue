<template>
  <div 
    class="py-12"
    :style="{
      backgroundColor: content.background_color || '#ffffff',
      color: content.text_color || '#374151'
    }"
  >
    <div
      :class="{
        'max-w-2xl': content.max_width === 'small',
        'max-w-4xl': content.max_width === 'normal',
        'max-w-6xl': content.max_width === 'large',
        'max-w-full': content.max_width === 'full'
      }"
      class="mx-auto px-6"
    >
      <div
        :class="{
          'text-left': content.align === 'left',
          'text-center': content.align === 'center',
          'text-right': content.align === 'right'
        }"
      >
        <h2
          v-if="content.heading"
          class="text-3xl font-bold mb-6"
          :style="{ color: content.text_color || '#111827' }"
        >
          {{ content.heading }}
        </h2>
        
        <div
          v-if="content.body"
          class="prose prose-lg max-w-none"
          :style="{ color: content.text_color || '#4b5563' }"
          v-html="content.body"
        ></div>
        
        <!-- Placeholder for empty content -->
        <div
          v-if="!content.heading && !content.body && isEditing"
          class="text-gray-400 text-center py-8 border-2 border-dashed border-gray-300 rounded-lg"
        >
          <p>Add heading and content using the properties panel</p>
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