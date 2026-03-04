<template>
  <footer class="bg-gray-900 text-white">
    <div class="container-custom">
      <div class="py-12">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
          <!-- Company Info -->
          <div class="md:col-span-2">
            <div class="flex items-center mb-4">
              <NuxtImg
                v-if="tenant?.settings?.logo"
                :src="tenant.settings.logo"
                :alt="tenant.name"
                class="h-8 w-auto mr-3"
              />
              <span class="text-xl font-bold">
                {{ tenant?.name || 'Site Name' }}
              </span>
            </div>
            <p class="text-gray-300 mb-4 max-w-md">
              {{ tenant?.settings?.seo?.description || 'Your trusted partner for quality products and services.' }}
            </p>
            
            <!-- Social Links -->
            <div v-if="tenant?.settings?.social" class="flex space-x-4">
              <a
                v-if="tenant.settings.social.facebook"
                :href="tenant.settings.social.facebook"
                target="_blank"
                rel="noopener noreferrer"
                class="text-gray-400 hover:text-white transition-colors"
              >
                <span class="sr-only">Facebook</span>
                <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                </svg>
              </a>
              
              <a
                v-if="tenant.settings.social.twitter"
                :href="tenant.settings.social.twitter"
                target="_blank"
                rel="noopener noreferrer"
                class="text-gray-400 hover:text-white transition-colors"
              >
                <span class="sr-only">Twitter</span>
                <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/>
                </svg>
              </a>
              
              <a
                v-if="tenant.settings.social.linkedin"
                :href="tenant.settings.social.linkedin"
                target="_blank"
                rel="noopener noreferrer"
                class="text-gray-400 hover:text-white transition-colors"
              >
                <span class="sr-only">LinkedIn</span>
                <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                </svg>
              </a>
              
              <a
                v-if="tenant.settings.social.instagram"
                :href="tenant.settings.social.instagram"
                target="_blank"
                rel="noopener noreferrer"
                class="text-gray-400 hover:text-white transition-colors"
              >
                <span class="sr-only">Instagram</span>
                <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M12.017 0C5.396 0 .029 5.367.029 11.987c0 6.62 5.367 11.987 11.988 11.987 6.62 0 11.987-5.367 11.987-11.987C24.014 5.367 18.637.001 12.017.001zM8.449 16.988c-1.297 0-2.448-.49-3.323-1.297C4.198 14.895 3.708 13.744 3.708 12.447s.49-2.448 1.297-3.323C5.902 8.198 7.053 7.708 8.35 7.708s2.448.49 3.323 1.297c.897.875 1.387 2.026 1.387 3.323s-.49 2.448-1.297 3.323c-.875.897-2.026 1.387-3.323 1.387zm7.718 0c-1.297 0-2.448-.49-3.323-1.297-.897-.875-1.387-2.026-1.387-3.323s.49-2.448 1.297-3.323c.875-.897 2.026-1.387 3.323-1.387s2.448.49 3.323 1.297c.897.875 1.387 2.026 1.387 3.323s-.49 2.448-1.297 3.323c-.875.897-2.026 1.387-3.323 1.387z"/>
                </svg>
              </a>
            </div>
          </div>
          
          <!-- Quick Links -->
          <div>
            <h3 class="text-lg font-semibold mb-4">Quick Links</h3>
            <ul class="space-y-2">
              <template v-if="footerMenu?.items && footerMenu.items.length > 0">
                <li v-for="item in footerMenu.items.slice(0, 6)" :key="item.id">
                  <component
                    :is="item.url.startsWith('http') ? 'a' : 'NuxtLink'"
                    :to="item.url.startsWith('http') ? undefined : item.url"
                    :href="item.url.startsWith('http') ? item.url : undefined"
                    :target="item.target"
                    :rel="item.target === '_blank' ? 'noopener noreferrer' : undefined"
                    class="text-gray-300 hover:text-white transition-colors"
                  >
                    {{ item.label }}
                  </component>
                </li>
              </template>
              <template v-else>
                <li>
                  <NuxtLink to="/about" class="text-gray-300 hover:text-white transition-colors">
                    About Us
                  </NuxtLink>
                </li>
                <li>
                  <NuxtLink to="/services" class="text-gray-300 hover:text-white transition-colors">
                    Services
                  </NuxtLink>
                </li>
                <li>
                  <NuxtLink to="/portfolio" class="text-gray-300 hover:text-white transition-colors">
                    Portfolio
                  </NuxtLink>
                </li>
                <li>
                  <NuxtLink to="/blog" class="text-gray-300 hover:text-white transition-colors">
                    Blog
                  </NuxtLink>
                </li>
                <li>
                  <NuxtLink to="/contact" class="text-gray-300 hover:text-white transition-colors">
                    Contact
                  </NuxtLink>
                </li>
                <li>
                  <NuxtLink to="/privacy" class="text-gray-300 hover:text-white transition-colors">
                    Privacy Policy
                  </NuxtLink>
                </li>
              </template>
            </ul>
          </div>
          
          <!-- Contact Info -->
          <div>
            <h3 class="text-lg font-semibold mb-4">Contact Info</h3>
            <div class="space-y-2 text-gray-300">
              <p v-if="tenant?.settings?.contact?.email">
                <span class="font-medium">Email:</span>
                <a
                  :href="`mailto:${tenant.settings.contact.email}`"
                  class="hover:text-white transition-colors ml-1"
                >
                  {{ tenant.settings.contact.email }}
                </a>
              </p>
              <p v-if="tenant?.settings?.contact?.phone">
                <span class="font-medium">Phone:</span>
                <a
                  :href="`tel:${tenant.settings.contact.phone}`"
                  class="hover:text-white transition-colors ml-1"
                >
                  {{ tenant.settings.contact.phone }}
                </a>
              </p>
              <p v-if="tenant?.settings?.contact?.address">
                <span class="font-medium">Address:</span>
                <span class="ml-1">{{ tenant.settings.contact.address }}</span>
              </p>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Bottom Bar -->
      <div class="border-t border-gray-800 py-6">
        <div class="flex flex-col md:flex-row justify-between items-center">
          <p class="text-gray-400 text-sm">
            © {{ new Date().getFullYear() }} {{ tenant?.name || 'Site Name' }}. All rights reserved.
          </p>
          <div class="flex space-x-6 mt-4 md:mt-0">
            <NuxtLink to="/privacy" class="text-gray-400 hover:text-white text-sm transition-colors">
              Privacy Policy
            </NuxtLink>
            <NuxtLink to="/terms" class="text-gray-400 hover:text-white text-sm transition-colors">
              Terms of Service
            </NuxtLink>
          </div>
        </div>
      </div>
    </div>
  </footer>
</template>

<script setup>
interface MenuItem {
  id: string
  label: string
  url: string
  target: string
  sort_order: number
  parent_id?: string
  children?: MenuItem[]
}

interface Menu {
  id: string
  name: string
  location: string
  items: MenuItem[]
}

defineProps<{
  tenant?: any
}>()

const { getFooterMenu } = useMenu()

// Get footer menu
const footerMenu = computed(() => getFooterMenu())
</script>