<template>
  <section 
    class="py-16"
    :style="{ backgroundColor: content.background_color || 'transparent' }"
  >
    <div 
      :class="content.full_width ? 'w-full' : 'container mx-auto px-4'"
    >
      <!-- Custom HTML Content -->
      <div 
        v-if="content.html"
        class="custom-html-content"
        :class="content.css_classes"
        v-html="sanitizedHtml"
      ></div>

      <!-- Fallback for empty content -->
      <div v-else class="text-center py-12 text-gray-500">
        <svg class="w-16 h-16 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path>
        </svg>
        <p>No custom HTML content provided</p>
      </div>
    </div>

    <!-- Custom CSS -->
    <style v-if="content.custom_css" v-html="sanitizedCss"></style>
  </section>
</template>

<script setup>
interface Content {
  html?: string
  custom_css?: string
  background_color?: string
  full_width?: boolean
  css_classes?: string
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

// Sanitize HTML content to prevent XSS attacks
const sanitizedHtml = computed(() => {
  if (!props.content.html) return ''
  
  // Basic HTML sanitization - remove dangerous elements and attributes
  let html = props.content.html
  
  // Remove script tags
  html = html.replace(/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi, '')
  
  // Remove dangerous event handlers
  html = html.replace(/\s*on\w+\s*=\s*["'][^"']*["']/gi, '')
  
  // Remove javascript: links
  html = html.replace(/href\s*=\s*["']javascript:[^"']*["']/gi, '')
  
  // Remove dangerous attributes
  html = html.replace(/\s*(srcdoc|formaction)\s*=\s*["'][^"']*["']/gi, '')
  
  // Replace tenant variables
  if (props.tenant) {
    html = html.replace(/\{\{tenant\.name\}\}/g, props.tenant.name)
    html = html.replace(/\{\{tenant\.slug\}\}/g, props.tenant.slug)
    
    // Replace theme colors
    if (props.tenant.settings?.primary_color) {
      html = html.replace(/\{\{theme\.primary_color\}\}/g, props.tenant.settings.primary_color)
    }
    if (props.tenant.settings?.accent_color) {
      html = html.replace(/\{\{theme\.accent_color\}\}/g, props.tenant.settings.accent_color)
    }
  }
  
  return html
})

// Sanitize CSS content
const sanitizedCss = computed(() => {
  if (!props.content.custom_css) return ''
  
  let css = props.content.custom_css
  
  // Remove dangerous CSS properties
  css = css.replace(/expression\s*\(/gi, '')
  css = css.replace(/javascript\s*:/gi, '')
  css = css.replace//@import\s+url\s*\(/gi, '')
  css = css.replace(/behavior\s*:/gi, '')
  
  // Replace tenant variables in CSS
  if (props.tenant) {
    if (props.tenant.settings?.primary_color) {
      css = css.replace(/var\(--tenant-primary-color\)/g, props.tenant.settings.primary_color)
    }
    if (props.tenant.settings?.accent_color) {
      css = css.replace(/var\(--tenant-accent-color\)/g, props.tenant.settings.accent_color)
    }
  }
  
  return css
})
</script>

<style scoped>
/* Scope custom HTML content styles */
.custom-html-content {
  /* Reset some default styles for better control */
  line-height: 1.6;
  color: inherit;
}

/* Common HTML elements styling */
.custom-html-content :deep(h1) {
  font-size: 2.5rem;
  font-weight: bold;
  margin-bottom: 1rem;
  line-height: 1.2;
}

.custom-html-content :deep(h2) {
  font-size: 2rem;
  font-weight: bold;
  margin-bottom: 0.875rem;
  line-height: 1.3;
}

.custom-html-content :deep(h3) {
  font-size: 1.5rem;
  font-weight: bold;
  margin-bottom: 0.75rem;
  line-height: 1.4;
}

.custom-html-content :deep(h4) {
  font-size: 1.25rem;
  font-weight: bold;
  margin-bottom: 0.625rem;
  line-height: 1.4;
}

.custom-html-content :deep(h5) {
  font-size: 1.125rem;
  font-weight: bold;
  margin-bottom: 0.5rem;
  line-height: 1.4;
}

.custom-html-content :deep(h6) {
  font-size: 1rem;
  font-weight: bold;
  margin-bottom: 0.5rem;
  line-height: 1.4;
}

.custom-html-content :deep(p) {
  margin-bottom: 1rem;
  line-height: 1.6;
}

.custom-html-content :deep(ul),
.custom-html-content :deep(ol) {
  margin-bottom: 1rem;
  padding-left: 1.5rem;
}

.custom-html-content :deep(li) {
  margin-bottom: 0.25rem;
}

.custom-html-content :deep(a) {
  color: #3b82f6;
  text-decoration: underline;
  transition: color 0.2s;
}

.custom-html-content :deep(a:hover) {
  color: #1d4ed8;
}

.custom-html-content :deep(img) {
  max-width: 100%;
  height: auto;
  border-radius: 0.5rem;
  margin: 1rem 0;
}

.custom-html-content :deep(blockquote) {
  border-left: 4px solid #e5e7eb;
  padding-left: 1rem;
  margin: 1.5rem 0;
  font-style: italic;
  color: #6b7280;
}

.custom-html-content :deep(code) {
  background-color: #f3f4f6;
  padding: 0.125rem 0.25rem;
  border-radius: 0.25rem;
  font-family: 'Courier New', monospace;
  font-size: 0.875rem;
}

.custom-html-content :deep(pre) {
  background-color: #1f2937;
  color: #f9fafb;
  padding: 1rem;
  border-radius: 0.5rem;
  overflow-x: auto;
  margin: 1rem 0;
}

.custom-html-content :deep(pre code) {
  background-color: transparent;
  padding: 0;
  color: inherit;
}

.custom-html-content :deep(table) {
  width: 100%;
  border-collapse: collapse;
  margin: 1rem 0;
}

.custom-html-content :deep(th),
.custom-html-content :deep(td) {
  border: 1px solid #e5e7eb;
  padding: 0.5rem;
  text-align: left;
}

.custom-html-content :deep(th) {
  background-color: #f9fafb;
  font-weight: bold;
}

.custom-html-content :deep(hr) {
  border: none;
  border-top: 1px solid #e5e7eb;
  margin: 2rem 0;
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .custom-html-content :deep(h1) {
    font-size: 2rem;
  }
  
  .custom-html-content :deep(h2) {
    font-size: 1.75rem;
  }
  
  .custom-html-content :deep(h3) {
    font-size: 1.5rem;
  }
}
</style>