<template>
  <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-7xl w-full mx-4 max-h-[95vh] overflow-hidden">
      <!-- Header -->
      <div class="flex justify-between items-center p-4 border-b border-gray-200 dark:border-gray-700">
        <div class="flex items-center">
          <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
            {{ $t('documents.editing') }}: {{ document.name }}
          </h2>
          <div v-if="hasUnsavedChanges" class="ml-3 px-2 py-1 bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200 text-xs rounded">
            {{ $t('documents.unsaved_changes') }}
          </div>
        </div>
        <div class="flex items-center gap-2">
          <button
            @click="saveDocument"
            class="btn btn-primary btn-sm"
            :disabled="saving || !hasUnsavedChanges"
          >
            <div v-if="saving" class="flex items-center">
              <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></div>
              {{ $t('documents.saving') }}
            </div>
            <span v-else>
              <CheckIcon class="w-4 h-4 mr-2" />
              {{ $t('documents.save') }}
            </span>
          </button>
          <button
            @click="closeEditor"
            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 p-1"
          >
            <XMarkIcon class="w-6 h-6" />
          </button>
        </div>
      </div>

      <!-- Toolbar -->
      <div class="border-b border-gray-200 dark:border-gray-700 p-2">
        <div class="flex flex-wrap items-center gap-1">
          <!-- File Operations -->
          <div class="flex items-center gap-1 pr-2 border-r border-gray-200 dark:border-gray-700">
            <button
              @click="saveDocument"
              class="p-2 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 hover:bg-gray-100 dark:hover:bg-gray-700 rounded transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
              :disabled="saving || !hasUnsavedChanges"
              :title="$t('documents.save')"
            >
              <CheckIcon class="w-4 h-4" />
            </button>
            <button
              @click="exportDocument"
              class="p-2 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 hover:bg-gray-100 dark:hover:bg-gray-700 rounded transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
              :title="$t('documents.export')"
            >
              <ArrowDownTrayIcon class="w-4 h-4" />
            </button>
          </div>

          <!-- Undo/Redo -->
          <div class="flex items-center gap-1 pr-2 border-r border-gray-200 dark:border-gray-700">
            <button
              @click="undo"
              class="p-2 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 hover:bg-gray-100 dark:hover:bg-gray-700 rounded transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
              :disabled="!canUndo"
              :title="$t('documents.undo')"
            >
              <ArrowUturnLeftIcon class="w-4 h-4" />
            </button>
            <button
              @click="redo"
              class="p-2 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 hover:bg-gray-100 dark:hover:bg-gray-700 rounded transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
              :disabled="!canRedo"
              :title="$t('documents.redo')"
            >
              <ArrowUturnRightIcon class="w-4 h-4" />
            </button>
          </div>

          <!-- Text Formatting -->
          <div class="flex items-center gap-1 pr-2 border-r border-gray-200 dark:border-gray-700">
            <button
              @click="formatText('bold')"
              :class="[
                'p-2 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 hover:bg-gray-100 dark:hover:bg-gray-700 rounded transition-colors',
                { 'bg-blue-100 dark:bg-blue-900': isFormatActive('bold') }
              ]"
              :title="$t('documents.bold')"
            >
              <strong>B</strong>
            </button>
            <button
              @click="formatText('italic')"
              :class="[
                'p-2 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 hover:bg-gray-100 dark:hover:bg-gray-700 rounded transition-colors',
                { 'bg-blue-100 dark:bg-blue-900': isFormatActive('italic') }
              ]"
              :title="$t('documents.italic')"
            >
              <em>I</em>
            </button>
            <button
              @click="formatText('underline')"
              :class="[
                'p-2 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 hover:bg-gray-100 dark:hover:bg-gray-700 rounded transition-colors',
                { 'bg-blue-100 dark:bg-blue-900': isFormatActive('underline') }
              ]"
              :title="$t('documents.underline')"
            >
              <u>U</u>
            </button>
            <button
              @click="formatText('strikeThrough')"
              :class="[
                'p-2 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 hover:bg-gray-100 dark:hover:bg-gray-700 rounded transition-colors',
                { 'bg-blue-100 dark:bg-blue-900': isFormatActive('strikeThrough') }
              ]"
              :title="$t('documents.strikethrough')"
            >
              <s>S</s>
            </button>
          </div>

          <!-- Text Alignment -->
          <div class="flex items-center gap-1 pr-2 border-r border-gray-200 dark:border-gray-700">
            <button
              @click="formatText('justifyLeft')"
              class="p-2 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 hover:bg-gray-100 dark:hover:bg-gray-700 rounded transition-colors"
              :title="$t('documents.align_left')"
            >
              <Bars3BottomLeftIcon class="w-4 h-4" />
            </button>
            <button
              @click="formatText('justifyCenter')"
              class="p-2 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 hover:bg-gray-100 dark:hover:bg-gray-700 rounded transition-colors"
              :title="$t('documents.align_center')"
            >
              <Bars3Icon class="w-4 h-4" />
            </button>
            <button
              @click="formatText('justifyRight')"
              class="p-2 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 hover:bg-gray-100 dark:hover:bg-gray-700 rounded transition-colors"
              :title="$t('documents.align_right')"
            >
              <Bars3BottomRightIcon class="w-4 h-4" />
            </button>
            <button
              @click="formatText('justifyFull')"
              class="p-2 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 hover:bg-gray-100 dark:hover:bg-gray-700 rounded transition-colors"
              :title="$t('documents.justify')"
            >
              <Bars4Icon class="w-4 h-4" />
            </button>
          </div>

          <!-- Lists -->
          <div class="flex items-center gap-1 pr-2 border-r border-gray-200 dark:border-gray-700">
            <button
              @click="formatText('insertUnorderedList')"
              class="p-2 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 hover:bg-gray-100 dark:hover:bg-gray-700 rounded transition-colors"
              :title="$t('documents.bullet_list')"
            >
              <ListBulletIcon class="w-4 h-4" />
            </button>
            <button
              @click="formatText('insertOrderedList')"
              class="p-2 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 hover:bg-gray-100 dark:hover:bg-gray-700 rounded transition-colors"
              :title="$t('documents.numbered_list')"
            >
              <NumberedListIcon class="w-4 h-4" />
            </button>
          </div>

          <!-- Font Size -->
          <div class="flex items-center gap-1 pr-2 border-r border-gray-200 dark:border-gray-700">
            <select
              v-model="fontSize"
              @change="changeFontSize"
              class="text-xs border border-gray-300 dark:border-gray-600 rounded px-2 py-1 bg-white dark:bg-gray-700"
            >
              <option value="1">8pt</option>
              <option value="2">10pt</option>
              <option value="3">12pt</option>
              <option value="4">14pt</option>
              <option value="5">18pt</option>
              <option value="6">24pt</option>
              <option value="7">36pt</option>
            </select>
          </div>

          <!-- Text Color -->
          <div class="flex items-center gap-1">
            <input
              type="color"
              v-model="textColor"
              @change="changeTextColor"
              class="w-8 h-8 border border-gray-300 dark:border-gray-600 rounded cursor-pointer"
              :title="$t('documents.text_color')"
            />
            <input
              type="color"
              v-model="backgroundColor"
              @change="changeBackgroundColor"
              class="w-8 h-8 border border-gray-300 dark:border-gray-600 rounded cursor-pointer"
              :title="$t('documents.background_color')"
            />
          </div>
        </div>
      </div>

      <!-- Editor Content -->
      <div class="flex-1 overflow-hidden" style="height: calc(95vh - 140px);">
        <div class="h-full flex">
          <!-- Main Editor -->
          <div class="flex-1 p-6 overflow-auto">
            <div
              ref="editor"
              contenteditable="true"
              @input="handleInput"
              @keydown="handleKeydown"
              @paste="handlePaste"
              class="min-h-full p-6 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400"
              style="font-family: 'Times New Roman', serif; font-size: 12pt; line-height: 1.6;"
              :style="editorStyles"
            >
              <!-- Content will be loaded here -->
            </div>
          </div>

          <!-- Sidebar -->
          <div class="w-64 border-l border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-4">
            <!-- Document Info -->
            <div class="mb-6">
              <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-2">
                {{ $t('documents.document_info') }}
              </h3>
              <div class="space-y-2 text-xs text-gray-600 dark:text-gray-400">
                <div>{{ $t('documents.words') }}: {{ wordCount }}</div>
                <div>{{ $t('documents.characters') }}: {{ characterCount }}</div>
                <div>{{ $t('documents.paragraphs') }}: {{ paragraphCount }}</div>
              </div>
            </div>

            <!-- Quick Actions -->
            <div class="mb-6">
              <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-2">
                {{ $t('documents.quick_actions') }}
              </h3>
              <div class="space-y-2">
                <button
                  @click="insertTable"
                  class="w-full text-left px-3 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded"
                >
                  {{ $t('documents.insert_table') }}
                </button>
                <button
                  @click="insertImage"
                  class="w-full text-left px-3 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded"
                >
                  {{ $t('documents.insert_image') }}
                </button>
                <button
                  @click="insertLink"
                  class="w-full text-left px-3 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded"
                >
                  {{ $t('documents.insert_link') }}
                </button>
              </div>
            </div>

            <!-- Styles -->
            <div>
              <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-2">
                {{ $t('documents.styles') }}
              </h3>
              <div class="space-y-1">
                <button
                  @click="applyHeading(1)"
                  class="w-full text-left px-3 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-700 rounded"
                  style="font-size: 18px; font-weight: bold;"
                >
                  {{ $t('documents.heading_1') }}
                </button>
                <button
                  @click="applyHeading(2)"
                  class="w-full text-left px-3 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-700 rounded"
                  style="font-size: 16px; font-weight: bold;"
                >
                  {{ $t('documents.heading_2') }}
                </button>
                <button
                  @click="applyHeading(3)"
                  class="w-full text-left px-3 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-700 rounded"
                  style="font-size: 14px; font-weight: bold;"
                >
                  {{ $t('documents.heading_3') }}
                </button>
                <button
                  @click="applyNormalText"
                  class="w-full text-left px-3 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-700 rounded"
                >
                  {{ $t('documents.normal_text') }}
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, nextTick, watch } from 'vue'
import { useTranslations } from '@/Composables/useTranslations'
import {
  XMarkIcon,
  CheckIcon,
  ArrowDownTrayIcon,
  ArrowUturnLeftIcon,
  ArrowUturnRightIcon,
  Bars3BottomLeftIcon,
  Bars3Icon,
  Bars3BottomRightIcon,
  Bars4Icon,
  ListBulletIcon,
} from '@heroicons/vue/24/outline'

// Custom numbered list icon component
const NumberedListIcon = {
  template: `
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
      <circle cx="2" cy="6" r="1" fill="currentColor"/>
      <circle cx="2" cy="12" r="1" fill="currentColor"/>
      <circle cx="2" cy="18" r="1" fill="currentColor"/>
    </svg>
  `
}

const props = defineProps({
  document: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['close', 'saved'])

const { $t } = useTranslations()

// State
const editor = ref(null)
const content = ref('')
const originalContent = ref('')
const saving = ref(false)
const fontSize = ref('3')
const textColor = ref('#000000')
const backgroundColor = ref('#ffffff')

// History for undo/redo
const history = ref([])
const historyIndex = ref(-1)
const maxHistorySize = 50

// Computed
const hasUnsavedChanges = computed(() => content.value !== originalContent.value)
const canUndo = computed(() => historyIndex.value > 0)
const canRedo = computed(() => historyIndex.value < history.value.length - 1)

const wordCount = computed(() => {
  const text = editor.value?.textContent || ''
  return text.trim() ? text.trim().split(/\s+/).length : 0
})

const characterCount = computed(() => {
  return editor.value?.textContent?.length || 0
})

const paragraphCount = computed(() => {
  const text = editor.value?.textContent || ''
  return text.trim() ? text.split(/\n\s*\n/).length : 0
})

const editorStyles = computed(() => ({
  color: textColor.value,
  backgroundColor: backgroundColor.value
}))

// Methods
const loadContent = async () => {
  try {
    // For text files, load the content directly
    if (props.document.mime_type === 'text/plain' || props.document.mime_type === 'text/html') {
      const response = await axios.get(`/api/v1/documents/${props.document.id}/preview`)
      const previewUrl = response.data.data.preview_url
      const contentResponse = await axios.get(previewUrl)
      content.value = contentResponse.data
      originalContent.value = content.value
      
      if (editor.value) {
        if (props.document.mime_type === 'text/html') {
          editor.value.innerHTML = content.value
        } else {
          editor.value.textContent = content.value
        }
      }
    } else {
      // For other document types, start with empty content
      content.value = ''
      originalContent.value = ''
      if (editor.value) {
        editor.value.innerHTML = '<p>Start editing your document...</p>'
      }
    }
    
    // Initialize history
    addToHistory()
  } catch (error) {
    console.error('Error loading document content:', error)
  }
}

const handleInput = () => {
  content.value = editor.value.innerHTML
  addToHistory()
}

const handleKeydown = (e) => {
  // Handle keyboard shortcuts
  if (e.ctrlKey || e.metaKey) {
    switch (e.key) {
      case 's':
        e.preventDefault()
        saveDocument()
        break
      case 'z':
        e.preventDefault()
        if (e.shiftKey) {
          redo()
        } else {
          undo()
        }
        break
      case 'y':
        e.preventDefault()
        redo()
        break
      case 'b':
        e.preventDefault()
        formatText('bold')
        break
      case 'i':
        e.preventDefault()
        formatText('italic')
        break
      case 'u':
        e.preventDefault()
        formatText('underline')
        break
    }
  }
}

const handlePaste = (e) => {
  e.preventDefault()
  const text = e.clipboardData.getData('text/plain')
  document.execCommand('insertText', false, text)
}

const formatText = (command, value = null) => {
  document.execCommand(command, false, value)
  editor.value.focus()
  addToHistory()
}

const isFormatActive = (command) => {
  return document.queryCommandState(command)
}

const changeFontSize = () => {
  formatText('fontSize', fontSize.value)
}

const changeTextColor = () => {
  formatText('foreColor', textColor.value)
}

const changeBackgroundColor = () => {
  formatText('backColor', backgroundColor.value)
}

const applyHeading = (level) => {
  formatText('formatBlock', `h${level}`)
}

const applyNormalText = () => {
  formatText('formatBlock', 'p')
}

const insertTable = () => {
  const rows = prompt($t('documents.table_rows'), '3')
  const cols = prompt($t('documents.table_columns'), '3')
  
  if (rows && cols) {
    let tableHTML = '<table border="1" style="border-collapse: collapse; width: 100%;">'
    for (let i = 0; i < parseInt(rows); i++) {
      tableHTML += '<tr>'
      for (let j = 0; j < parseInt(cols); j++) {
        tableHTML += '<td style="padding: 8px; border: 1px solid #ccc;">&nbsp;</td>'
      }
      tableHTML += '</tr>'
    }
    tableHTML += '</table><p>&nbsp;</p>'
    
    formatText('insertHTML', tableHTML)
  }
}

const insertImage = () => {
  const url = prompt($t('documents.image_url'))
  if (url) {
    formatText('insertImage', url)
  }
}

const insertLink = () => {
  const url = prompt($t('documents.link_url'))
  if (url) {
    const text = window.getSelection().toString() || url
    formatText('createLink', url)
  }
}

const addToHistory = () => {
  const currentContent = editor.value?.innerHTML || ''
  
  // Don't add if content hasn't changed
  if (history.value.length > 0 && history.value[historyIndex.value] === currentContent) {
    return
  }
  
  // Remove any history after current index
  history.value = history.value.slice(0, historyIndex.value + 1)
  
  // Add new state
  history.value.push(currentContent)
  historyIndex.value = history.value.length - 1
  
  // Limit history size
  if (history.value.length > maxHistorySize) {
    history.value.shift()
    historyIndex.value--
  }
}

const undo = () => {
  if (canUndo.value) {
    historyIndex.value--
    editor.value.innerHTML = history.value[historyIndex.value]
    content.value = editor.value.innerHTML
  }
}

const redo = () => {
  if (canRedo.value) {
    historyIndex.value++
    editor.value.innerHTML = history.value[historyIndex.value]
    content.value = editor.value.innerHTML
  }
}

const saveDocument = async () => {
  if (!hasUnsavedChanges.value) return
  
  saving.value = true
  try {
    // Create a new document version or update existing
    const formData = new FormData()
    const blob = new Blob([content.value], { type: props.document.mime_type })
    formData.append('file', blob, props.document.name)
    formData.append('name', props.document.name)
    
    await axios.post('/api/v1/documents', formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })
    
    originalContent.value = content.value
    emit('saved')
  } catch (error) {
    console.error('Error saving document:', error)
    alert($t('documents.save_error'))
  } finally {
    saving.value = false
  }
}

const exportDocument = () => {
  const blob = new Blob([content.value], { type: props.document.mime_type })
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = props.document.name
  document.body.appendChild(a)
  a.click()
  document.body.removeChild(a)
  URL.revokeObjectURL(url)
}

const closeEditor = () => {
  if (hasUnsavedChanges.value) {
    if (confirm($t('documents.unsaved_changes_warning'))) {
      emit('close')
    }
  } else {
    emit('close')
  }
}

// Lifecycle
onMounted(async () => {
  await nextTick()
  await loadContent()
})
</script>