<template>
  <div class="section-renderer">
    <!-- Hero Banner -->
    <HeroBannerSection
      v-if="section.type === 'hero_banner'"
      :content="section.content"
      :is-editing="isEditing"
    />
    
    <!-- Text Block -->
    <TextBlockSection
      v-else-if="section.type === 'text_block'"
      :content="section.content"
      :is-editing="isEditing"
    />
    
    <!-- Image & Text -->
    <ImageTextSection
      v-else-if="section.type === 'image_text'"
      :content="section.content"
      :is-editing="isEditing"
    />
    
    <!-- Product Grid -->
    <ProductGridSection
      v-else-if="section.type === 'product_grid'"
      :content="section.content"
      :is-editing="isEditing"
    />
    
    <!-- Team Grid -->
    <TeamGridSection
      v-else-if="section.type === 'team_grid'"
      :content="section.content"
      :is-editing="isEditing"
    />
    
    <!-- Portfolio Grid -->
    <PortfolioGridSection
      v-else-if="section.type === 'portfolio_grid'"
      :content="section.content"
      :is-editing="isEditing"
    />
    
    <!-- Statistics -->
    <StatsSection
      v-else-if="section.type === 'stats'"
      :content="section.content"
      :is-editing="isEditing"
    />
    
    <!-- Testimonials -->
    <TestimonialsSection
      v-else-if="section.type === 'testimonials'"
      :content="section.content"
      :is-editing="isEditing"
    />
    
    <!-- FAQ -->
    <FaqSection
      v-else-if="section.type === 'faq'"
      :content="section.content"
      :is-editing="isEditing"
    />
    
    <!-- CTA Banner -->
    <CtaBannerSection
      v-else-if="section.type === 'cta_banner'"
      :content="section.content"
      :is-editing="isEditing"
    />
    
    <!-- Contact Form -->
    <ContactFormSection
      v-else-if="section.type === 'contact_form'"
      :content="section.content"
      :is-editing="isEditing"
    />
    
    <!-- Gallery -->
    <GallerySection
      v-else-if="section.type === 'gallery'"
      :content="section.content"
      :is-editing="isEditing"
    />
    
    <!-- E-commerce Sections -->
    <ProductDetailSection
      v-else-if="section.type === 'product_detail'"
      :content="section.content"
      :is-editing="isEditing"
    />
    
    <ShoppingCartSection
      v-else-if="section.type === 'shopping_cart_page'"
      :content="section.content"
      :is-editing="isEditing"
    />
    
    <CheckoutFormSection
      v-else-if="section.type === 'checkout_form'"
      :content="section.content"
      :is-editing="isEditing"
    />
    
    <ProductReviewsSection
      v-else-if="section.type === 'product_reviews'"
      :content="section.content"
      :is-editing="isEditing"
    />
    
    <CustomerAccountSection
      v-else-if="section.type === 'customer_account'"
      :content="section.content"
      :is-editing="isEditing"
    />
    
    <!-- Custom HTML -->
    <CustomHtmlSection
      v-else-if="section.type === 'custom_html'"
      :content="section.content"
      :is-editing="isEditing"
    />
    
    <!-- Blog Posts -->
    <BlogPostsSection
      v-else-if="section.type === 'blog_posts'"
      :content="section.content"
      :is-editing="isEditing"
    />
    
    <!-- Fallback for unknown section types -->
    <div
      v-else
      class="p-8 bg-gray-100 border-2 border-dashed border-gray-300 text-center"
    >
      <div class="text-gray-500">
        <Icon name="heroicons:question-mark-circle" class="w-12 h-12 mx-auto mb-4" />
        <h3 class="text-lg font-medium text-gray-900 mb-2">Unknown Section Type</h3>
        <p class="text-sm">{{ section.type }}</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import Icon from '@/Components/UI/Icon.vue'

// Import section components
import HeroBannerSection from './Sections/HeroBannerSection.vue'
import TextBlockSection from './Sections/TextBlockSection.vue'
import ImageTextSection from './Sections/ImageTextSection.vue'
import ProductGridSection from './Sections/ProductGridSection.vue'
import TeamGridSection from './Sections/TeamGridSection.vue'
import PortfolioGridSection from './Sections/PortfolioGridSection.vue'
import StatsSection from './Sections/StatsSection.vue'
import TestimonialsSection from './Sections/TestimonialsSection.vue'
import FaqSection from './Sections/FaqSection.vue'
import CtaBannerSection from './Sections/CtaBannerSection.vue'
import ContactFormSection from './Sections/ContactFormSection.vue'
import GallerySection from './Sections/GallerySection.vue'
import ProductDetailSection from './Sections/ProductDetailSection.vue'
import ShoppingCartSection from './Sections/ShoppingCartSection.vue'
import CheckoutFormSection from './Sections/CheckoutFormSection.vue'
import ProductReviewsSection from './Sections/ProductReviewsSection.vue'
import CustomerAccountSection from './Sections/CustomerAccountSection.vue'
import CustomHtmlSection from './Sections/CustomHtmlSection.vue'
import BlogPostsSection from './Sections/BlogPostsSection.vue'

defineProps({
  section: {
    type: Object,
    required: true
  },
  isEditing: {
    type: Boolean,
    default: false
  }
})
</script>