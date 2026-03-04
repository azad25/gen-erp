<template>
  <section class="py-16" :style="{ backgroundColor: content.background_color || 'transparent' }">
    <div class="container mx-auto px-4">
      <div 
        class="flex flex-col gap-8 items-center"
        :class="{
          'lg:flex-row': content.layout === 'left' || content.layout === 'right',
          'lg:flex-row-reverse': content.layout === 'right',
          'text-center': content.layout === 'center'
        }"
      >
        <!-- Image Section -->
        <div 
          class="flex-1"
          :class="{
            'lg:max-w-md xl:max-w-lg': content.layout === 'left' || content.layout === 'right',
            'max-w-2xl mx-auto': content.layout === 'center'
          }"
        >
          <div class="relative">
            <NuxtImg
              v-if="content.image_url"
              :src="content.image_url"
              :alt="content.image_alt || content.title || 'Section image'"
              class="w-full h-auto rounded-lg shadow-lg"
              :class="{
                'hover:scale-105 transition-transform duration-300': content.hover_effect
              }"
              loading="lazy"
              format="webp"
              quality="80"
            />
            
            <!-- Placeholder if no image -->
            <div v-else class="w-full h-64 bg-gray-200 rounded-lg flex items-center justify-center">
              <svg class="w-16 h-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
              </svg>
            </div>

            <!-- Image Overlay -->
            <div 
              v-if="content.image_overlay"
              class="absolute inset-0 rounded-lg"
              :style="{ backgroundColor: content.image_overlay }"
            ></div>

            <!-- Badge/Label -->
            <div v-if="content.badge_text" class="absolute top-4 left-4">
              <span 
                class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium text-white"
                :style="{ backgroundColor: content.badge_color || tenant?.settings?.primary_color || '#3b82f6' }"
              >
                {{ content.badge_text }}
              </span>
            </div>
          </div>
        </div>

        <!-- Content Section -->
        <div 
          class="flex-1 space-y-6"
          :class="{
            'lg:pl-8': content.layout === 'left',
            'lg:pr-8': content.layout === 'right'
          }"
        >
          <!-- Subtitle/Eyebrow -->
          <div v-if="content.eyebrow" class="text-sm font-semibold tracking-wide uppercase">
            <span 
              :style="{ color: content.eyebrow_color || tenant?.settings?.primary_color || '#3b82f6' }"
            >
              {{ content.eyebrow }}
            </span>
          </div>

          <!-- Title -->
          <h2 
            v-if="content.title"
            class="text-3xl md:text-4xl font-bold leading-tight"
            :style="{ color: content.title_color || '#1f2937' }"
          >
            {{ content.title }}
          </h2>

          <!-- Subtitle -->
          <p 
            v-if="content.subtitle"
            class="text-xl text-gray-600"
            :style="{ color: content.subtitle_color || '#6b7280' }"
          >
            {{ content.subtitle }}
          </p>

          <!-- Description -->
          <div 
            v-if="content.description"
            class="prose prose-lg max-w-none"
            :style="{ color: content.description_color || '#374151' }"
            v-html="formatDescription(content.description)"
          ></div>

          <!-- Features List -->
          <ul v-if="content.features && content.features.length > 0" class="space-y-3">
            <li 
              v-for="(feature, index) in content.features" 
              :key="index"
              class="flex items-start"
            >
              <div 
                class="flex-shrink-0 w-6 h-6 rounded-full flex items-center justify-center mr-3 mt-0.5"
                :style="{ backgroundColor: content.feature_icon_color || tenant?.settings?.primary_color || '#3b82f6' }"
              >
                <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
              </div>
              <span class="text-gray-700 leading-relaxed">{{ feature }}</span>
            </li>
          </ul>

          <!-- Statistics -->
          <div v-if="content.stats && content.stats.length > 0" class="grid grid-cols-2 gap-6 py-6">
            <div 
              v-for="(stat, index) in content.stats" 
              :key="index"
              class="text-center lg:text-left"
            >
              <div 
                class="text-3xl font-bold mb-1"
                :style="{ color: content.stats_color || tenant?.settings?.primary_color || '#3b82f6' }"
              >
                {{ stat.value }}{{ stat.suffix || '' }}
              </div>
              <div class="text-sm text-gray-600">{{ stat.label }}</div>
            </div>
          </div>

          <!-- Buttons -->
          <div 
            class="flex flex-col sm:flex-row gap-4"
            :class="{
              'justify-center': content.layout === 'center',
              'justify-start': content.layout !== 'center'
            }"
          >
            <!-- Primary Button -->
            <component
              v-if="content.primary_button_text"
              :is="content.primary_button_link ? 'NuxtLink' : 'button'"
              :to="content.primary_button_link"
              :href="content.primary_button_external_link"
              :target="content.primary_button_external_link ? '_blank' : undefined"
              :rel="content.primary_button_external_link ? 'noopener noreferrer' : undefined"
              class="inline-flex items-center justify-center px-6 py-3 text-base font-medium rounded-lg transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2"
              :style="{
                backgroundColor: content.primary_button_color || tenant?.settings?.primary_color || '#3b82f6',
                color: content.primary_button_text_color || '#ffffff',
                'focus:ring-color': content.primary_button_color || tenant?.settings?.primary_color || '#3b82f6'
              }"
            >
              <svg 
                v-if="content.primary_button_icon" 
                class="w-5 h-5 mr-2" 
                fill="none" 
                stroke="currentColor" 
                viewBox="0 0 24 24"
              >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
              </svg>
              {{ content.primary_button_text }}
            </component>

            <!-- Secondary Button -->
            <component
              v-if="content.secondary_button_text"
              :is="content.secondary_button_link ? 'NuxtLink' : 'button'"
              :to="content.secondary_button_link"
              :href="content.secondary_button_external_link"
              :target="content.secondary_button_external_link ? '_blank' : undefined"
              :rel="content.secondary_button_external_link ? 'noopener noreferrer' : undefined"
              class="inline-flex items-center justify-center px-6 py-3 text-base font-medium rounded-lg border-2 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2"
              :style="{
                borderColor: content.secondary_button_border_color || tenant?.settings?.primary_color || '#3b82f6',
                color: content.secondary_button_text_color || tenant?.settings?.primary_color || '#3b82f6',
                backgroundColor: content.secondary_button_background || 'transparent',
                'focus:ring-color': content.secondary_button_border_color || tenant?.settings?.primary_color || '#3b82f6'
              }"
            >
              <svg 
                v-if="content.secondary_button_icon" 
                class="w-5 h-5 mr-2" 
                fill="none" 
                stroke="currentColor" 
                viewBox="0 0 24 24"
              >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
              </svg>
              {{ content.secondary_button_text }}
            </component>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
interface Stat {
  value: string | number
  label: string
  suffix?: string
}

interface Content {
  layout?: 'left' | 'right' | 'center'
  background_color?: string
  image_url?: string
  image_alt?: string
  image_overlay?: string
  hover_effect?: boolean
  badge_text?: string
  badge_color?: string
  eyebrow?: string
  eyebrow_color?: string
  title?: string
  title_color?: string
  subtitle?: string
  subtitle_color?: string
  description?: string
  description_color?: string
  features?: string[]
  feature_icon_color?: string
  stats?: Stat[]
  stats_color?: string
  primary_button_text?: string
  primary_button_link?: string
  primary_button_external_link?: string
  primary_button_color?: string
  primary_button_text_color?: string
  primary_button_icon?: boolean
  secondary_button_text?: string
  secondary_button_link?: string
  secondary_button_external_link?: string
  secondary_button_background?: string
  secondary_button_border_color?: string
  secondary_button_text_color?: string
  secondary_button_icon?: boolean
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

// Format description text (convert line breaks to HTML)
const formatDescription = (description: string) => {
  return description
    .replace(/\n\n/g, '</p><p>')
    .replace(/\n/g, '<br>')
    .replace(/^(.*)$/, '<p>$1</p>')
    .replace(/<p><\/p>/g, '')
}
</script>

<style scoped>
/* Prose styling for description */
.prose {
  max-width: none;
}

.prose :deep(p) {
  margin-bottom: 1rem;
  line-height: 1.7;
}

.prose :deep(p:last-child) {
  margin-bottom: 0;
}

.prose :deep(br) {
  line-height: 1.5;
}

/* Hover effects */
button:hover,
a:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

/* Focus styles for accessibility */
button:focus,
a:focus {
  outline: none;
}

button:focus-visible,
a:focus-visible {
  outline: 2px solid;
  outline-offset: 2px;
}
</style>