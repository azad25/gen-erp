<template>
  <div class="border border-gray-300 rounded-md">
    <!-- Toolbar -->
    <div class="border-b border-gray-200 p-2 flex flex-wrap gap-1">
      <button
        @click="toggleBold"
        :class="{ 'bg-gray-200': isBold }"
        class="p-2 rounded hover:bg-gray-100"
        type="button"
      >
        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
          <path d="M3 4a1 1 0 011-1h4.586A2 2 0 0110 3.586L14.414 8A2 2 0 0115 9.414V16a1 1 0 01-1 1H4a1 1 0 01-1-1V4z" />
        </svg>
      </button>
      
      <button
        @click="toggleItalic"
        :class="{ 'bg-gray-200': isItalic }"
        class="p-2 rounded hover:bg-gray-100"
        type="button"
      >
        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
          <path d="M7 3a1 1 0 000 2h1.586l-3.293 3.293a1 1 0 001.414 1.414L10 6.414V8a1 1 0 102 0V4a1 1 0 00-1-1H7z" />
        </svg>
      </button>
      
      <button
        @click="toggleUnderline"
        :class="{ 'bg-gray-200': isUnderline }"
        class="p-2 rounded hover:bg-gray-100"
        type="button"
      >
        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
          <path d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM6.293 6.707a1 1 0 010-1.414l3-3a1 1 0 011.414 0l3 3a1 1 0 01-1.414 1.414L11 5.414V13a1 1 0 11-2 0V5.414L7.707 6.707a1 1 0 01-1.414 0z" />
        </svg>
      </button>
      
      <div class="w-px bg-gray-300 mx-1"></div>
      
      <button
        @click="insertLink"
        class="p-2 rounded hover:bg-gray-100"
        type="button"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
        </svg>
      </button>
      
      <button
        @click="insertList"
        class="p-2 rounded hover:bg-gray-100"
        type="button"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
        </svg>
      </button>
    </div>
    
    <!-- Editor -->
    <div
      ref="editor"
      contenteditable="true"
      @input="handleInput"
      @keydown="handleKeydown"
      class="p-4 min-h-32 focus:outline-none"
      v-html="modelValue"
    ></div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'

const props = defineProps({
  modelValue: {
    type: String,
    default: ''
  }
})

const emit = defineEmits(['update:modelValue', 'update'])

const editor = ref(null)
const isBold = ref(false)
const isItalic = ref(false)
const isUnderline = ref(false)

const handleInput = () => {
  const content = editor.value.innerHTML
  emit('update:modelValue', content)
  emit('update')
  updateToolbarState()
}

const handleKeydown = (event) => {
  // Handle common keyboard shortcuts
  if (event.ctrlKey || event.metaKey) {
    switch (event.key) {
      case 'b':
        event.preventDefault()
        toggleBold()
        break
      case 'i':
        event.preventDefault()
        toggleItalic()
        break
      case 'u':
        event.preventDefault()
        toggleUnderline()
        break
    }
  }
}

const toggleBold = () => {
  document.execCommand('bold')
  updateToolbarState()
  handleInput()
}

const toggleItalic = () => {
  document.execCommand('italic')
  updateToolbarState()
  handleInput()
}

const toggleUnderline = () => {
  document.execCommand('underline')
  updateToolbarState()
  handleInput()
}

const insertLink = () => {
  const url = prompt('Enter URL:')
  if (url) {
    document.execCommand('createLink', false, url)
    handleInput()
  }
}

const insertList = () => {
  document.execCommand('insertUnorderedList')
  handleInput()
}

const updateToolbarState = () => {
  isBold.value = document.queryCommandState('bold')
  isItalic.value = document.queryCommandState('italic')
  isUnderline.value = document.queryCommandState('underline')
}

watch(() => props.modelValue, (newValue) => {
  if (editor.value && editor.value.innerHTML !== newValue) {
    editor.value.innerHTML = newValue || ''
  }
})

onMounted(() => {
  if (editor.value) {
    editor.value.innerHTML = props.modelValue || ''
  }
})
</script>