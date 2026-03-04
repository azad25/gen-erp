<template>
  <section 
    class="section-padding"
    :style="{
      backgroundColor: content.background_color || 'var(--background)',
      color: content.text_color || 'var(--text-primary)'
    }"
    data-testid="text-block-section"
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
          class="text-3xl font-bold mb-6 fade-in"
          :style="{ color: content.text_color || 'var(--text-primary)' }"
        >
          {{ content.heading }}
        </h2>
        
        <div
          v-if="content.body"
          class="prose prose-lg max-w-none fade-in"
          :style="{ color: content.text_color || 'var(--text-secondary)' }"
          v-html="content.body"
        />
      </div>
    </div>
  </section>
</template>

<script setup>
interface TextBlockContent {
  heading?: string
  body?: string
  align?: 'left' | 'center' | 'right'
  max_width?: 'small' | 'normal' | 'large' | 'full'
  background_color?: string
  text_color?: string
}

defineProps<{
  content: TextBlockContent
  tenant?: any
}>()
</script>

<style scoped>
.prose {
  color: inherit;
}

.prose h1,
.prose h2,
.prose h3,
.prose h4,
.prose h5,
.prose h6 {
  color: inherit;
}

.prose a {
  color: var(--primary-color);
  text-decoration: underline;
}

.prose a:hover {
  color: var(--primary-dark);
}
</style>