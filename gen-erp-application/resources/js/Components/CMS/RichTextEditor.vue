<template>
  <div class="rich-text-editor">
    <div v-if="editor" class="border border-gray-300 rounded-lg overflow-hidden">
      <!-- Toolbar -->
      <div class="bg-gray-50 border-b border-gray-300 p-2 flex flex-wrap gap-1">
        <!-- Text Formatting -->
        <div class="flex border-r border-gray-300 pr-2 mr-2">
          <button
            @click="editor.chain().focus().toggleBold().run()"
            :class="{ 'bg-blue-100 text-blue-700': editor.isActive('bold') }"
            class="p-2 rounded hover:bg-gray-200 transition-colors"
            title="Bold"
          >
            <Icon name="heroicons:bold" class="w-4 h-4" />
          </button>
          <button
            @click="editor.chain().focus().toggleItalic().run()"
            :class="{ 'bg-blue-100 text-blue-700': editor.isActive('italic') }"
            class="p-2 rounded hover:bg-gray-200 transition-colors"
            title="Italic"
          >
            <Icon name="heroicons:italic" class="w-4 h-4" />
          </button>
          <button
            @click="editor.chain().focus().toggleUnderline().run()"
            :class="{ 'bg-blue-100 text-blue-700': editor.isActive('underline') }"
            class="p-2 rounded hover:bg-gray-200 transition-colors"
            title="Underline"
          >
            <Icon name="heroicons:underline" class="w-4 h-4" />
          </button>
        </div>

        <!-- Headings -->
        <div class="flex border-r border-gray-300 pr-2 mr-2">
          <select
            @change="setHeading($event.target.value)"
            class="px-2 py-1 text-sm border border-gray-300 rounded"
          >
            <option value="">Paragraph</option>
            <option value="1">Heading 1</option>
            <option value="2">Heading 2</option>
            <option value="3">Heading 3</option>
            <option value="4">Heading 4</option>
            <option value="5">Heading 5</option>
            <option value="6">Heading 6</option>
          </select>
        </div>

        <!-- Lists -->
        <div class="flex border-r border-gray-300 pr-2 mr-2">
          <button
            @click="editor.chain().focus().toggleBulletList().run()"
            :class="{ 'bg-blue-100 text-blue-700': editor.isActive('bulletList') }"
            class="p-2 rounded hover:bg-gray-200 transition-colors"
            title="Bullet List"
          >
            <Icon name="heroicons:list-bullet" class="w-4 h-4" />
          </button>
          <button
            @click="editor.chain().focus().toggleOrderedList().run()"
            :class="{ 'bg-blue-100 text-blue-700': editor.isActive('orderedList') }"
            class="p-2 rounded hover:bg-gray-200 transition-colors"
            title="Numbered List"
          >
            <Icon name="heroicons:numbered-list" class="w-4 h-4" />
          </button>
        </div>

        <!-- Alignment -->
        <div class="flex border-r border-gray-300 pr-2 mr-2">
          <button
            @click="editor.chain().focus().setTextAlign('left').run()"
            :class="{ 'bg-blue-100 text-blue-700': editor.isActive({ textAlign: 'left' }) }"
            class="p-2 rounded hover:bg-gray-200 transition-colors"
            title="Align Left"
          >
            <Icon name="heroicons:bars-3-bottom-left" class="w-4 h-4" />
          </button>
          <button
            @click="editor.chain().focus().setTextAlign('center').run()"
            :class="{ 'bg-blue-100 text-blue-700': editor.isActive({ textAlign: 'center' }) }"
            class="p-2 rounded hover:bg-gray-200 transition-colors"
            title="Align Center"
          >
            <Icon name="heroicons:bars-3" class="w-4 h-4" />
          </button>
          <button
            @click="editor.chain().focus().setTextAlign('right').run()"
            :class="{ 'bg-blue-100 text-blue-700': editor.isActive({ textAlign: 'right' }) }"
            class="p-2 rounded hover:bg-gray-200 transition-colors"
            title="Align Right"
          >
            <Icon name="heroicons:bars-3-bottom-right" class="w-4 h-4" />
          </button>
        </div>

        <!-- Links -->
        <div class="flex border-r border-gray-300 pr-2 mr-2">
          <button
            @click="setLink"
            :class="{ 'bg-blue-100 text-blue-700': editor.isActive('link') }"
            class="p-2 rounded hover:bg-gray-200 transition-colors"
            title="Add Link"
          >
            <Icon name="heroicons:link" class="w-4 h-4" />
          </button>
          <button
            @click="editor.chain().focus().unsetLink().run()"
            :disabled="!editor.isActive('link')"
            class="p-2 rounded hover:bg-gray-200 transition-colors disabled:opacity-50"
            title="Remove Link"
          >
            <Icon name="heroicons:link-slash" class="w-4 h-4" />
          </button>
        </div>

        <!-- Media -->
        <div class="flex">
          <button
            @click="addImage"
            class="p-2 rounded hover:bg-gray-200 transition-colors"
            title="Add Image"
          >
            <Icon name="heroicons:photo" class="w-4 h-4" />
          </button>
        </div>
      </div>

      <!-- Editor Content -->
      <div
        ref="editorElement"
        class="prose max-w-none p-4 min-h-[200px] focus:outline-none"
        :class="editorClass"
      ></div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, watch } from 'vue'
import { Editor } from '@tiptap/vue-3'
import StarterKit from '@tiptap/starter-kit'
import Underline from '@tiptap/extension-underline'
import TextAlign from '@tiptap/extension-text-align'
import Link from '@tiptap/extension-link'
import Image from '@tiptap/extension-image'
import Icon from '@/Components/UI/Icon.vue'

const props = defineProps({
  modelValue: {
    type: String,
    default: ''
  },
  placeholder: {
    type: String,
    default: 'Start typing...'
  },
  editorClass: {
    type: String,
    default: ''
  }
})

const emit = defineEmits(['update:modelValue'])

const editor = ref(null)
const editorElement = ref(null)

onMounted(() => {
  editor.value = new Editor({
    element: editorElement.value,
    extensions: [
      StarterKit,
      Underline,
      TextAlign.configure({
        types: ['heading', 'paragraph'],
      }),
      Link.configure({
        openOnClick: false,
      }),
      Image.configure({
        HTMLAttributes: {
          class: 'max-w-full h-auto rounded-lg',
        },
      }),
    ],
    content: props.modelValue,
    onUpdate: ({ editor }) => {
      emit('update:modelValue', editor.getHTML())
    },
    editorProps: {
      attributes: {
        class: 'prose max-w-none focus:outline-none',
        placeholder: props.placeholder,
      },
    },
  })
})

onBeforeUnmount(() => {
  if (editor.value) {
    editor.value.destroy()
  }
})

watch(() => props.modelValue, (newValue) => {
  if (editor.value && editor.value.getHTML() !== newValue) {
    editor.value.commands.setContent(newValue)
  }
})

const setHeading = (level) => {
  if (level === '') {
    editor.value.chain().focus().setParagraph().run()
  } else {
    editor.value.chain().focus().toggleHeading({ level: parseInt(level) }).run()
  }
}

const setLink = () => {
  const previousUrl = editor.value.getAttributes('link').href
  const url = window.prompt('URL', previousUrl)

  if (url === null) {
    return
  }

  if (url === '') {
    editor.value.chain().focus().extendMarkRange('link').unsetLink().run()
    return
  }

  editor.value.chain().focus().extendMarkRange('link').setLink({ href: url }).run()
}

const addImage = () => {
  const url = window.prompt('Image URL')

  if (url) {
    editor.value.chain().focus().setImage({ src: url }).run()
  }
}
</script>

<style scoped>
.rich-text-editor :deep(.ProseMirror) {
  outline: none;
}

.rich-text-editor :deep(.ProseMirror p.is-editor-empty:first-child::before) {
  content: attr(placeholder);
  float: left;
  color: #adb5bd;
  pointer-events: none;
  height: 0;
}
</style>