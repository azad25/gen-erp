<template>
  <section
    :class="{
      'h-64': content.height === 'small',
      'h-96': content.height === 'medium',
      'h-screen': content.height === 'large' || content.height === 'full'
    }"
    class="relative flex items-center justify-center text-white overflow-hidden"
    :style="{
      backgroundColor: content.background_color || 'var(--primary-color)',
      color: content.text_color || '#ffffff'
    }"
    data-testid="hero-banner-section"
  >
    <!-- Background Image -->
    <NuxtImg
      v-if="content.background_image"
      :src="content.background_image"
      :alt="content.title || 'Hero background'"
      class="absolute inset-0 w-full h-full object-cover"
      loading="eager"
      format="webp"
    />
    
    <!-- Overlay -->
    <div
      v-if="content.background_image || content.overlay_color"
      class="absolute inset-0"
      :style="{ 
        backgroundColor: content.overlay_color || 'rgba(0, 0, 0, 0.4)',
        opacity: content.overlay_opacity || 0.4 
      }"
    />
    
    <!-- Content -->
    <div
      :class="{
        'text-left': content.text_align === 'left',
        'text-center': content.text_align === 'center',
        'text-right': content.text_align === 'right'
      }"
      class="relative z-10 container-custom"
    >
      <div class="max-w-4xl mx-auto">
        <h1 
          class="text-4xl md:text-6xl font-bold mb-6 fade-in"
          :style="{ color: content.text_color || '#ffffff' }"
        >
          {{ content.title || 'Your Headline Here' }}
        </h1>
        
        <p 
          class="text-xl md:text-2xl mb-8 opacity-90 fade-in"
          :style="{ color: content.text_color || '#ffffff' }"
        >
          {{ content.subtitle || 'Supporting text that explains your value proposition' }}
        </p>
        
        <div v-if="content.cta_text" class="fade-in">
          <NuxtLink
            :to="content.cta_link || '#'"
            class="inline-block font-semibold py-3 px-8 rounded-lg transition-all duration-200 hover:transform hover:scale-105"
            :style="{
              backgroundColor: content.cta_button_color || 'var(--accent-color)',
              color: content.cta_text_color || '#ffffff'
            }"
          >
            {{ content.cta_text }}
          </NuxtLink>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
interface HeroContent {
  title?: string
  subtitle?: string
  background_image?: string
  background_color?: string
  overlay_color?: string
  overlay_opacity?: number
  text_color?: string
  text_align?: 'left' | 'center' | 'right'
  height?: 'small' | 'medium' | 'large' | 'full'
  cta_text?: string
  cta_link?: string
  cta_button_color?: string
  cta_text_color?: string
}

defineProps<{
  content: HeroContent
  tenant?: any
}>()
</script>