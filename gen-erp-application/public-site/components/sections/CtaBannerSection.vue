<template>
  <section 
    class="py-16 relative overflow-hidden"
    :style="{ backgroundColor: content.background_color || tenant?.settings?.primary_color || '#3b82f6' }"
  >
    <!-- Background Pattern/Image -->
    <div v-if="content.background_image" class="absolute inset-0">
      <NuxtImg
        :src="content.background_image"
        :alt="content.title || 'Background'"
        class="w-full h-full object-cover"
        loading="lazy"
        format="webp"
        quality="80"
      />
      <div 
        class="absolute inset-0"
        :style="{ backgroundColor: content.overlay_color || 'rgba(0, 0, 0, 0.5)' }"
      ></div>
    </div>

    <!-- Background Pattern -->
    <div v-else-if="content.show_pattern" class="absolute inset-0 opacity-10">
      <svg class="w-full h-full" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
        <defs>
          <pattern id="cta-pattern" x="0" y="0" width="20" height="20" patternUnits="userSpaceOnUse">
            <circle cx="10" cy="10" r="2" fill="currentColor"/>
          </pattern>
        </defs>
        <rect width="100%" height="100%" fill="url(#cta-pattern)" class="text-white"/>
      </svg>
    </div>

    <div class="container mx-auto px-4 relative z-10">
      <div class="max-w-4xl mx-auto text-center">
        <!-- Icon -->
        <div v-if="content.icon" class="mb-6">
          <div 
            class="w-16 h-16 mx-auto rounded-full flex items-center justify-center"
            :style="{ backgroundColor: content.icon_background || 'rgba(255, 255, 255, 0.2)' }"
          >
            <component 
              :is="getIconComponent(content.icon)" 
              class="w-8 h-8"
              :style="{ color: content.icon_color || '#ffffff' }"
            />
          </div>
        </div>

        <!-- Title -->
        <h2 
          v-if="content.title"
          class="text-3xl md:text-4xl lg:text-5xl font-bold mb-4"
          :style="{ color: content.title_color || '#ffffff' }"
        >
          {{ content.title }}
        </h2>

        <!-- Subtitle -->
        <p 
          v-if="content.subtitle"
          class="text-xl md:text-2xl mb-8 opacity-90"
          :style="{ color: content.subtitle_color || '#ffffff' }"
        >
          {{ content.subtitle }}
        </p>

        <!-- Description -->
        <p 
          v-if="content.description"
          class="text-lg mb-8 opacity-80 max-w-2xl mx-auto"
          :style="{ color: content.description_color || '#ffffff' }"
        >
          {{ content.description }}
        </p>

        <!-- Buttons -->
        <div class="flex flex-col sm:flex-row gap-4 justify-center items-center">
          <!-- Primary Button -->
          <component
            :is="content.primary_button_link ? 'NuxtLink' : 'button'"
            :to="content.primary_button_link"
            :href="content.primary_button_external_link"
            :target="content.primary_button_external_link ? '_blank' : undefined"
            :rel="content.primary_button_external_link ? 'noopener noreferrer' : undefined"
            @click="content.primary_button_external_link ? null : handlePrimaryClick()"
            class="inline-flex items-center px-8 py-4 text-lg font-semibold rounded-lg transition-all duration-200 hover:scale-105 focus:outline-none focus:ring-4 focus:ring-offset-2 focus:ring-offset-transparent"
            :style="{
              backgroundColor: content.primary_button_color || '#ffffff',
              color: content.primary_button_text_color || tenant?.settings?.primary_color || '#3b82f6',
              'focus:ring-color': content.primary_button_color || '#ffffff'
            }"
          >
            <svg 
              v-if="content.primary_button_icon" 
              class="w-5 h-5 mr-2" 
              fill="none" 
              stroke="currentColor" 
              viewBox="0 0 24 24"
            >
              <component :is="getIconComponent(content.primary_button_icon)" />
            </svg>
            {{ content.primary_button_text || 'Get Started' }}
            <svg 
              v-if="!content.primary_button_icon"
              class="w-5 h-5 ml-2" 
              fill="none" 
              stroke="currentColor" 
              viewBox="0 0 24 24"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
            </svg>
          </component>

          <!-- Secondary Button -->
          <component
            v-if="content.show_secondary_button"
            :is="content.secondary_button_link ? 'NuxtLink' : 'button'"
            :to="content.secondary_button_link"
            :href="content.secondary_button_external_link"
            :target="content.secondary_button_external_link ? '_blank' : undefined"
            :rel="content.secondary_button_external_link ? 'noopener noreferrer' : undefined"
            @click="content.secondary_button_external_link ? null : handleSecondaryClick()"
            class="inline-flex items-center px-8 py-4 text-lg font-semibold rounded-lg border-2 transition-all duration-200 hover:scale-105 focus:outline-none focus:ring-4 focus:ring-offset-2 focus:ring-offset-transparent"
            :style="{
              borderColor: content.secondary_button_border_color || '#ffffff',
              color: content.secondary_button_text_color || '#ffffff',
              backgroundColor: content.secondary_button_background || 'transparent',
              'focus:ring-color': content.secondary_button_border_color || '#ffffff'
            }"
          >
            <svg 
              v-if="content.secondary_button_icon" 
              class="w-5 h-5 mr-2" 
              fill="none" 
              stroke="currentColor" 
              viewBox="0 0 24 24"
            >
              <component :is="getIconComponent(content.secondary_button_icon)" />
            </svg>
            {{ content.secondary_button_text || 'Learn More' }}
          </component>
        </div>

        <!-- Additional Info -->
        <div v-if="content.additional_info" class="mt-8">
          <p 
            class="text-sm opacity-70"
            :style="{ color: content.additional_info_color || '#ffffff' }"
          >
            {{ content.additional_info }}
          </p>
        </div>

        <!-- Trust Indicators -->
        <div v-if="content.show_trust_indicators" class="mt-8 flex flex-wrap justify-center items-center gap-6 opacity-80">
          <div v-if="content.trust_indicator_1" class="flex items-center text-sm">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
            {{ content.trust_indicator_1 }}
          </div>
          <div v-if="content.trust_indicator_2" class="flex items-center text-sm">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
            {{ content.trust_indicator_2 }}
          </div>
          <div v-if="content.trust_indicator_3" class="flex items-center text-sm">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
            {{ content.trust_indicator_3 }}
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
interface Content {
  title?: string
  subtitle?: string
  description?: string
  background_color?: string
  background_image?: string
  overlay_color?: string
  show_pattern?: boolean
  icon?: string
  icon_color?: string
  icon_background?: string
  title_color?: string
  subtitle_color?: string
  description_color?: string
  primary_button_text?: string
  primary_button_link?: string
  primary_button_external_link?: string
  primary_button_color?: string
  primary_button_text_color?: string
  primary_button_icon?: string
  show_secondary_button?: boolean
  secondary_button_text?: string
  secondary_button_link?: string
  secondary_button_external_link?: string
  secondary_button_background?: string
  secondary_button_border_color?: string
  secondary_button_text_color?: string
  secondary_button_icon?: string
  additional_info?: string
  additional_info_color?: string
  show_trust_indicators?: boolean
  trust_indicator_1?: string
  trust_indicator_2?: string
  trust_indicator_3?: string
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

const emit = defineEmits<{
  primaryClick: []
  secondaryClick: []
}>()

// Button click handlers
const handlePrimaryClick = () => {
  emit('primaryClick')
}

const handleSecondaryClick = () => {
  emit('secondaryClick')
}

// Icon components mapping
const getIconComponent = (iconName: string) => {
  const icons = {
    // Action icons
    rocket: 'IconRocket',
    star: 'IconStar',
    heart: 'IconHeart',
    lightning: 'IconLightning',
    fire: 'IconFire',
    
    // Business icons
    briefcase: 'IconBriefcase',
    chart: 'IconChart',
    target: 'IconTarget',
    trophy: 'IconTrophy',
    
    // Communication icons
    phone: 'IconPhone',
    mail: 'IconMail',
    chat: 'IconChat',
    
    // Tech icons
    code: 'IconCode',
    desktop: 'IconDesktop',
    mobile: 'IconMobile',
    
    // Default
    arrow: 'IconArrow'
  }
  
  return icons[iconName as keyof typeof icons] || 'IconStar'
}
</script>

<script>
// Icon components
const IconRocket = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
    </svg>
  `
}

const IconStar = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
    </svg>
  `
}

const IconHeart = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
    </svg>
  `
}

const IconLightning = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
    </svg>
  `
}

const IconFire = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 1-4 4-4 1 0 2.5.5 4 2.5 0 0 .5-1 1.5-1 0 2.5-2.5 5.5-5.5 7.5z"/>
    </svg>
  `
}

const IconBriefcase = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m8 0H8m8 0v6a2 2 0 01-2 2H10a2 2 0 01-2-2V6m8 0V4a2 2 0 00-2-2H10a2 2 0 00-2 2v2"/>
    </svg>
  `
}

const IconChart = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
    </svg>
  `
}

const IconTarget = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
    </svg>
  `
}

const IconTrophy = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
    </svg>
  `
}

const IconPhone = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
    </svg>
  `
}

const IconMail = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
    </svg>
  `
}

const IconChat = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
    </svg>
  `
}

const IconCode = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
    </svg>
  `
}

const IconDesktop = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
    </svg>
  `
}

const IconMobile = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
    </svg>
  `
}

const IconArrow = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
    </svg>
  `
}
</script>

<style scoped>
/* Hover effects */
button:hover,
a:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
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