<template>
  <div class="section-wrapper">
    <!-- Hero Banner -->
    <HeroBannerSection
      v-if="section.type === 'hero_banner'"
      :content="section.content"
      :tenant="tenant"
    />
    
    <!-- Text Block -->
    <TextBlockSection
      v-else-if="section.type === 'text_block'"
      :content="section.content"
      :tenant="tenant"
    />
    
    <!-- Image & Text -->
    <ImageTextSection
      v-else-if="section.type === 'image_text'"
      :content="section.content"
      :tenant="tenant"
    />
    
    <!-- Product Grid -->
    <ProductGridSection
      v-else-if="section.type === 'product_grid'"
      :content="section.content"
      :tenant="tenant"
    />
    
    <!-- Team Grid -->
    <TeamGridSection
      v-else-if="section.type === 'team_grid'"
      :content="section.content"
      :tenant="tenant"
    />
    
    <!-- Portfolio Grid -->
    <PortfolioGridSection
      v-else-if="section.type === 'portfolio_grid'"
      :content="section.content"
      :tenant="tenant"
    />
    
    <!-- Statistics -->
    <StatsSection
      v-else-if="section.type === 'stats'"
      :content="section.content"
      :tenant="tenant"
    />
    
    <!-- Testimonials -->
    <TestimonialsSection
      v-else-if="section.type === 'testimonials'"
      :content="section.content"
      :tenant="tenant"
    />
    
    <!-- FAQ -->
    <FaqSection
      v-else-if="section.type === 'faq'"
      :content="section.content"
      :tenant="tenant"
    />
    
    <!-- CTA Banner -->
    <CtaBannerSection
      v-else-if="section.type === 'cta_banner'"
      :content="section.content"
      :tenant="tenant"
    />
    
    <!-- Contact Form -->
    <ContactFormSection
      v-else-if="section.type === 'contact_form'"
      :content="section.content"
      :tenant="tenant"
    />
    
    <!-- Gallery -->
    <GallerySection
      v-else-if="section.type === 'gallery'"
      :content="section.content"
      :tenant="tenant"
    />
    
    <!-- Blog Posts -->
    <BlogPostsSection
      v-else-if="section.type === 'blog_posts'"
      :content="section.content"
      :tenant="tenant"
    />
    
    <!-- Custom HTML -->
    <CustomHtmlSection
      v-else-if="section.type === 'custom_html'"
      :content="section.content"
      :tenant="tenant"
    />
    
    <!-- Fallback for unknown section types -->
    <div
      v-else
      class="py-8 text-center text-gray-500"
    >
      <p class="text-sm">Unknown section type: {{ section.type }}</p>
      <p class="text-xs mt-2">Available types: hero_banner, text_block, image_text, product_grid, team_grid, portfolio_grid, stats, testimonials, faq, cta_banner, contact_form, gallery, blog_posts, custom_html</p>
    </div>
  </div>
</template>

<script setup>
interface Section {
  id: string
  type: string
  content: Record<string, any>
  order: number
}

interface Tenant {
  id: string
  name: string
  slug: string
  settings: Record<string, any>
}

defineProps<{
  section: Section
  tenant?: Tenant
  page?: any
}>()
</script>

<style scoped>
.section-wrapper {
  position: relative;
}
</style>