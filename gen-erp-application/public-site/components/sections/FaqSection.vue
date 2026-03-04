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

      <!-- FAQ Items -->
      <div v-if="content.faqs && content.faqs.length > 0" class="max-w-4xl mx-auto">
        <div class="space-y-4">
          <div 
            v-for="(faq, index) in content.faqs" 
            :key="index"
            class="bg-white rounded-lg shadow-md overflow-hidden"
          >
            <button
              @click="toggleFaq(index)"
              class="w-full px-6 py-4 text-left flex items-center justify-between hover:bg-gray-50 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2"
              :style="{ 'focus:ring-color': tenant?.settings?.primary_color || '#3b82f6' }"
              :aria-expanded="openFaqs.includes(index)"
              :aria-controls="`faq-answer-${index}`"
            >
              <h3 class="text-lg font-semibold text-gray-900 pr-4">
                {{ faq.question }}
              </h3>
              <div 
                class="flex-shrink-0 w-6 h-6 rounded-full flex items-center justify-center transition-transform duration-200"
                :class="openFaqs.includes(index) ? 'rotate-180' : ''"
                :style="{ backgroundColor: tenant?.settings?.primary_color || '#3b82f6' }"
              >
                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
              </div>
            </button>
            
            <Transition
              name="faq"
              @enter="onEnter"
              @after-enter="onAfterEnter"
              @leave="onLeave"
              @after-leave="onAfterLeave"
            >
              <div 
                v-show="openFaqs.includes(index)"
                :id="`faq-answer-${index}`"
                class="faq-content"
              >
                <div class="px-6 pb-4 text-gray-700 leading-relaxed">
                  <div v-html="formatAnswer(faq.answer)"></div>
                </div>
              </div>
            </Transition>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-else class="text-center py-12">
        <div class="text-gray-400 mb-4">
          <svg class="w-16 h-16 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
          </svg>
        </div>
        <p class="text-gray-600">No frequently asked questions available</p>
      </div>

      <!-- Contact CTA -->
      <div v-if="content.show_contact_cta" class="text-center mt-12">
        <p class="text-lg text-gray-600 mb-6">
          {{ content.contact_cta_text || "Still have questions? We're here to help!" }}
        </p>
        <NuxtLink
          :to="content.contact_link || '/contact'"
          class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white transition-colors duration-200"
          :style="{ backgroundColor: tenant?.settings?.primary_color || '#3b82f6' }"
        >
          <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
          </svg>
          {{ content.contact_button_text || 'Contact Us' }}
        </NuxtLink>
      </div>
    </div>
  </section>
</template>

<script setup>
interface FAQ {
  question: string
  answer: string
}

interface Content {
  title?: string
  subtitle?: string
  background_color?: string
  title_color?: string
  faqs?: FAQ[]
  show_contact_cta?: boolean
  contact_cta_text?: string
  contact_button_text?: string
  contact_link?: string
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

const openFaqs = ref<number[]>([])

// Toggle FAQ open/close
const toggleFaq = (index: number) => {
  const isOpen = openFaqs.value.includes(index)
  
  if (isOpen) {
    openFaqs.value = openFaqs.value.filter(i => i !== index)
  } else {
    openFaqs.value.push(index)
  }
}

// Format answer text (convert line breaks to HTML)
const formatAnswer = (answer: string) => {
  return answer
    .replace(/\n\n/g, '</p><p>')
    .replace(/\n/g, '<br>')
    .replace(/^(.*)$/, '<p>$1</p>')
    .replace(/<p><\/p>/g, '')
}

// Transition handlers
const onEnter = (el: Element) => {
  const element = el as HTMLElement
  element.style.height = '0'
  element.style.overflow = 'hidden'
}

const onAfterEnter = (el: Element) => {
  const element = el as HTMLElement
  element.style.height = 'auto'
  element.style.overflow = 'visible'
}

const onLeave = (el: Element) => {
  const element = el as HTMLElement
  element.style.height = element.scrollHeight + 'px'
  element.style.overflow = 'hidden'
  
  // Force reflow
  element.offsetHeight
  
  element.style.height = '0'
}

const onAfterLeave = (el: Element) => {
  const element = el as HTMLElement
  element.style.height = 'auto'
  element.style.overflow = 'visible'
}

// Initialize with first FAQ open if only one FAQ
onMounted(() => {
  if (props.content.faqs && props.content.faqs.length === 1) {
    openFaqs.value = [0]
  }
})
</script>

<style scoped>
.faq-enter-active,
.faq-leave-active {
  transition: height 0.3s ease;
}

.faq-content {
  overflow: hidden;
}

/* Ensure proper styling for formatted answers */
:deep(p) {
  margin-bottom: 1rem;
}

:deep(p:last-child) {
  margin-bottom: 0;
}

:deep(br) {
  line-height: 1.5;
}

/* Focus styles for accessibility */
button:focus {
  outline: none;
}

button:focus-visible {
  outline: 2px solid;
  outline-offset: 2px;
}
</style>