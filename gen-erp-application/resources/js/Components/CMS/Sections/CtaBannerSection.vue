<template>
  <div
    :class="{
      'bg-white': content.background === 'light',
      'bg-gray-900': content.background === 'dark',
      'bg-blue-600': content.background === 'brand'
    }"
    class="py-16"
  >
    <div class="max-w-4xl mx-auto text-center px-6">
      <h2
        :class="{
          'text-gray-900': content.background === 'light',
          'text-white': content.background === 'dark' || content.background === 'brand'
        }"
        class="text-3xl font-bold mb-4"
      >
        {{ content.title || 'Ready to get started?' }}
      </h2>
      
      <p
        :class="{
          'text-gray-600': content.background === 'light',
          'text-gray-300': content.background === 'dark' || content.background === 'brand'
        }"
        class="text-xl mb-8"
      >
        {{ content.subtitle || 'Join thousands of businesses already using our platform.' }}
      </p>
      
      <a
        :href="content.button_link || '#'"
        :class="[
          'inline-block font-semibold py-3 px-8 rounded-lg transition-colors duration-200',
          {
            'bg-blue-600 hover:bg-blue-700 text-white': content.background === 'light',
            'bg-white hover:bg-gray-100 text-gray-900': content.background === 'dark' || content.background === 'brand',
            'pointer-events-none': isEditing
          }
        ]"
      >
        {{ content.button_text || 'Contact Us' }}
      </a>
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