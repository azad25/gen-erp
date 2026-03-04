<template>
  <div class="space-y-6">
    <!-- Hero Banner Properties -->
    <div v-if="section.type === 'hero_banner'">
      <div class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Title</label>
          <input
            v-model="localContent.title"
            type="text"
            class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm"
            @input="updateContent"
          />
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Subtitle</label>
          <RichTextEditor
            v-model="localContent.subtitle"
            placeholder="Enter subtitle..."
            @update:modelValue="updateContent"
          />
        </div>
        
        <div>
          <ImageUpload
            v-model="localContent.background_image"
            label="Background Image"
            alt="Hero background"
            @update:modelValue="updateContent"
          />
        </div>
        
        <div>
          <ColorPicker
            v-model="localContent.background_color"
            label="Background Color"
            allow-clear
            @update:modelValue="updateContent"
          />
        </div>
        
        <div>
          <ColorPicker
            v-model="localContent.overlay_color"
            label="Overlay Color"
            allow-clear
            allow-alpha
            @update:modelValue="updateContent"
          />
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Overlay Opacity</label>
          <input
            v-model.number="localContent.overlay_opacity"
            type="range"
            min="0"
            max="1"
            step="0.1"
            class="w-full"
            @input="updateContent"
          />
          <div class="text-xs text-gray-500 mt-1">{{ Math.round(localContent.overlay_opacity * 100) }}%</div>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">CTA Button Text</label>
          <input
            v-model="localContent.cta_text"
            type="text"
            class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm"
            @input="updateContent"
          />
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">CTA Button Link</label>
          <input
            v-model="localContent.cta_link"
            type="text"
            class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm"
            @input="updateContent"
          />
        </div>
        
        <div>
          <ColorPicker
            v-model="localContent.cta_button_color"
            label="CTA Button Color"
            placeholder="Choose button color"
            @update:modelValue="updateContent"
          />
        </div>
        
        <div>
          <ColorPicker
            v-model="localContent.cta_text_color"
            label="CTA Text Color"
            placeholder="Choose text color"
            @update:modelValue="updateContent"
          />
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Text Alignment</label>
          <select
            v-model="localContent.text_align"
            class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm"
            @change="updateContent"
          >
            <option value="left">Left</option>
            <option value="center">Center</option>
            <option value="right">Right</option>
          </select>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Height</label>
          <select
            v-model="localContent.height"
            class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm"
            @change="updateContent"
          >
            <option value="small">Small</option>
            <option value="medium">Medium</option>
            <option value="large">Large</option>
            <option value="full">Full Screen</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Text Block Properties -->
    <div v-else-if="section.type === 'text_block'">
      <div class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Heading</label>
          <input
            v-model="localContent.heading"
            type="text"
            class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm"
            @input="updateContent"
          />
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Content</label>
          <RichTextEditor
            v-model="localContent.body"
            @update="updateContent"
          />
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Alignment</label>
          <select
            v-model="localContent.align"
            class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm"
            @change="updateContent"
          >
            <option value="left">Left</option>
            <option value="center">Center</option>
            <option value="right">Right</option>
          </select>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Max Width</label>
          <select
            v-model="localContent.max_width"
            class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm"
            @change="updateContent"
          >
            <option value="small">Small</option>
            <option value="normal">Normal</option>
            <option value="large">Large</option>
            <option value="full">Full Width</option>
          </select>
        </div>
        
        <div>
          <ColorPicker
            v-model="localContent.background_color"
            label="Background Color"
            placeholder="Choose background color"
            @update:modelValue="updateContent"
          />
        </div>
        
        <div>
          <ColorPicker
            v-model="localContent.text_color"
            label="Text Color"
            placeholder="Choose text color"
            @update:modelValue="updateContent"
          />
        </div>
      </div>
    </div>

    <!-- Product Grid Properties -->
    <div v-else-if="section.type === 'product_grid'">
      <div class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Heading</label>
          <input
            v-model="localContent.heading"
            type="text"
            class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm"
            @input="updateContent"
          />
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Number of Products</label>
          <input
            v-model.number="localContent.limit"
            type="number"
            min="1"
            max="20"
            class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm"
            @input="updateContent"
          />
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Filter by Tag</label>
          <input
            v-model="localContent.filter_tag"
            type="text"
            placeholder="Enter tag name"
            class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm"
            @input="updateContent"
          />
        </div>
        
        <div>
          <label class="flex items-center space-x-2">
            <input
              v-model="localContent.show_price"
              type="checkbox"
              class="rounded border-gray-300"
              @change="updateContent"
            />
            <span class="text-sm font-medium text-gray-700">Show Prices</span>
          </label>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Layout</label>
          <select
            v-model="localContent.layout"
            class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm"
            @change="updateContent"
          >
            <option value="2-col">2 Columns</option>
            <option value="3-col">3 Columns</option>
            <option value="4-col">4 Columns</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Stats Properties -->
    <div v-else-if="section.type === 'stats'">
      <div class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Heading</label>
          <input
            v-model="localContent.heading"
            type="text"
            class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm"
            @input="updateContent"
          />
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Statistics</label>
          <div class="space-y-3">
            <div
              v-for="(item, index) in localContent.items"
              :key="index"
              class="p-3 border border-gray-200 rounded-lg"
            >
              <div class="flex justify-between items-start mb-2">
                <span class="text-sm font-medium text-gray-700">Stat {{ index + 1 }}</span>
                <button
                  @click="removeStatItem(index)"
                  class="text-red-500 hover:text-red-700"
                >
                  <Icon name="heroicons:trash" class="w-4 h-4" />
                </button>
              </div>
              <div class="space-y-2">
                <input
                  v-model="item.number"
                  type="text"
                  placeholder="Number (e.g., 500+)"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm"
                  @input="updateContent"
                />
                <input
                  v-model="item.label"
                  type="text"
                  placeholder="Label (e.g., Happy Customers)"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm"
                  @input="updateContent"
                />
              </div>
            </div>
            
            <button
              @click="addStatItem"
              class="w-full py-2 border-2 border-dashed border-gray-300 rounded-lg text-sm text-gray-500 hover:border-gray-400 hover:text-gray-600"
            >
              Add Statistic
            </button>
          </div>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Background</label>
          <select
            v-model="localContent.background"
            class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm"
            @change="updateContent"
          >
            <option value="light">Light</option>
            <option value="dark">Dark</option>
            <option value="brand">Brand Color</option>
          </select>
        </div>
      </div>
    </div>

    <!-- FAQ Properties -->
    <div v-else-if="section.type === 'faq'">
      <div class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Heading</label>
          <input
            v-model="localContent.heading"
            type="text"
            class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm"
            @input="updateContent"
          />
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">FAQ Items</label>
          <div class="space-y-3">
            <div
              v-for="(item, index) in localContent.items"
              :key="index"
              class="p-3 border border-gray-200 rounded-lg"
            >
              <div class="flex justify-between items-start mb-2">
                <span class="text-sm font-medium text-gray-700">FAQ {{ index + 1 }}</span>
                <button
                  @click="removeFaqItem(index)"
                  class="text-red-500 hover:text-red-700"
                >
                  <Icon name="heroicons:trash" class="w-4 h-4" />
                </button>
              </div>
              <div class="space-y-2">
                <input
                  v-model="item.question"
                  type="text"
                  placeholder="Question"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm"
                  @input="updateContent"
                />
                <textarea
                  v-model="item.answer"
                  rows="3"
                  placeholder="Answer"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm"
                  @input="updateContent"
                ></textarea>
              </div>
            </div>
            
            <button
              @click="addFaqItem"
              class="w-full py-2 border-2 border-dashed border-gray-300 rounded-lg text-sm text-gray-500 hover:border-gray-400 hover:text-gray-600"
            >
              Add FAQ Item
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Contact Form Properties -->
    <div v-else-if="section.type === 'contact_form'">
      <div class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Heading</label>
          <input
            v-model="localContent.heading"
            type="text"
            class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm"
            @input="updateContent"
          />
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Form Fields</label>
          <div class="space-y-2">
            <label v-for="field in availableFields" :key="field.value" class="flex items-center space-x-2">
              <input
                :checked="localContent.fields.includes(field.value)"
                type="checkbox"
                class="rounded border-gray-300"
                @change="toggleField(field.value)"
              />
              <span class="text-sm text-gray-700">{{ field.label }}</span>
            </label>
          </div>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Button Text</label>
          <input
            v-model="localContent.button_text"
            type="text"
            class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm"
            @input="updateContent"
          />
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Success Message</label>
          <textarea
            v-model="localContent.success_message"
            rows="3"
            class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm"
            @input="updateContent"
          ></textarea>
        </div>
        
        <div>
          <ColorPicker
            v-model="localContent.button_color"
            label="Button Color"
            placeholder="Choose button color"
            @update:modelValue="updateContent"
          />
        </div>
        
        <div>
          <ColorPicker
            v-model="localContent.form_background"
            label="Form Background"
            placeholder="Choose form background"
            @update:modelValue="updateContent"
          />
        </div>
      </div>
    </div>

    <!-- Blog Posts Properties -->
    <div v-else-if="section.type === 'blog_posts'">
      <div class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Heading</label>
          <input
            v-model="localContent.heading"
            type="text"
            class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm"
            @input="updateContent"
          />
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Subheading</label>
          <textarea
            v-model="localContent.subheading"
            rows="2"
            class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm"
            @input="updateContent"
          ></textarea>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Layout</label>
          <select
            v-model="localContent.layout"
            class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm"
            @change="updateContent"
          >
            <option value="1-col">1 Column</option>
            <option value="2-col">2 Columns</option>
            <option value="3-col">3 Columns</option>
          </select>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Number of Posts</label>
          <input
            v-model.number="localContent.posts_count"
            type="number"
            min="1"
            max="12"
            class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm"
            @input="updateContent"
          />
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Category Filter</label>
          <select
            v-model="localContent.category_filter"
            class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm"
            @change="updateContent"
          >
            <option value="">All Categories</option>
            <option value="news">News</option>
            <option value="tutorials">Tutorials</option>
            <option value="tips">Tips</option>
            <option value="updates">Updates</option>
          </select>
        </div>
        
        <div class="space-y-2">
          <label class="flex items-center space-x-2">
            <input
              v-model="localContent.show_featured_only"
              type="checkbox"
              class="rounded border-gray-300"
              @change="updateContent"
            />
            <span class="text-sm text-gray-700">Show Featured Posts Only</span>
          </label>
          
          <label class="flex items-center space-x-2">
            <input
              v-model="localContent.show_view_all"
              type="checkbox"
              class="rounded border-gray-300"
              @change="updateContent"
            />
            <span class="text-sm text-gray-700">Show "View All" Button</span>
          </label>
        </div>
      </div>
    </div>

    <!-- Generic Properties for other section types -->
    <div v-else>
      <div class="text-center py-8 text-gray-500">
        <Icon name="heroicons:cog-6-tooth" class="w-8 h-8 mx-auto mb-2" />
        <p class="text-sm">Properties panel for {{ section.type }} coming soon</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, computed } from 'vue'
import Icon from '@/Components/UI/Icon.vue'
import ImageUpload from '@/Components/CMS/ImageUpload.vue'
import RichTextEditor from '@/Components/CMS/RichTextEditor.vue'
import ColorPicker from '@/Components/CMS/ColorPicker.vue'

const props = defineProps({
  section: Object,
  sectionIndex: Number
})

const emit = defineEmits(['update'])

const localContent = ref({ ...props.section.content })

// Available form fields for contact form
const availableFields = [
  { value: 'name', label: 'Name' },
  { value: 'email', label: 'Email' },
  { value: 'phone', label: 'Phone' },
  { value: 'company', label: 'Company' },
  { value: 'subject', label: 'Subject' },
  { value: 'message', label: 'Message' }
]

// Watch for section changes
watch(() => props.section.content, (newContent) => {
  localContent.value = { ...newContent }
}, { deep: true })

// Methods
const updateContent = () => {
  emit('update', localContent.value)
}

const addStatItem = () => {
  if (!localContent.value.items) {
    localContent.value.items = []
  }
  localContent.value.items.push({ number: '', label: '' })
  updateContent()
}

const removeStatItem = (index) => {
  localContent.value.items.splice(index, 1)
  updateContent()
}

const addFaqItem = () => {
  if (!localContent.value.items) {
    localContent.value.items = []
  }
  localContent.value.items.push({ question: '', answer: '' })
  updateContent()
}

const removeFaqItem = (index) => {
  localContent.value.items.splice(index, 1)
  updateContent()
}

const toggleField = (fieldValue) => {
  if (!localContent.value.fields) {
    localContent.value.fields = []
  }
  
  const index = localContent.value.fields.indexOf(fieldValue)
  if (index > -1) {
    localContent.value.fields.splice(index, 1)
  } else {
    localContent.value.fields.push(fieldValue)
  }
  updateContent()
}
</script>