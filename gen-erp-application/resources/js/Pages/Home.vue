<template>
  <div class="min-h-screen bg-white">
    <!-- Organic Ambient Background Blobs -->
    <div class="fixed top-0 left-0 w-full h-full overflow-hidden -z-10 pointer-events-none">
      <div class="absolute -top-[10%] -left-[10%] w-[500px] h-[500px] rounded-full bg-primary/10 blur-[90px] animated-blob"></div>
      <div class="absolute top-[20%] -right-[10%] w-[600px] h-[600px] rounded-full bg-green-500/10 blur-[100px] animated-blob" style="animation-delay: -5s"></div>
    </div>

    <!-- Navigation -->
    <nav class="fixed w-full z-50 glass-panel border-b border-gray-100 transition-all duration-300" :class="scrolled ? 'py-4 shadow-sm' : 'py-6'">
      
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center">
          
          <a href="/" class="flex items-center gap-3 group">
            <HomeLogo class="w-10 h-10 drop-shadow-md group-hover:scale-105 transition-transform" />
            <span class="text-xl font-extrabold text-black tracking-tight">GenERP BD</span>
          </a>

          <!-- Desktop Menu -->
          <div class="hidden md:flex items-center space-x-8">
            <a href="#features" class="text-gray-600 hover:text-primary font-bold transition-colors">ফিচার</a>
            <a href="#modules" class="text-gray-600 hover:text-primary font-bold transition-colors">মডিউল</a>
            <a href="#pricing" class="text-gray-600 hover:text-primary font-bold transition-colors">মূল্য</a>
            <a href="#testimonials" class="text-gray-600 hover:text-primary font-bold transition-colors">রিভিউ</a>
          </div>

          <div class="flex items-center gap-4">
            <div class="hidden sm:flex items-center bg-gray-100 rounded-lg p-1">
              <a href="/locale/bn" class="px-3 py-1 rounded-md text-sm font-bold transition-all bg-white text-primary shadow-sm">BN</a>
              <a href="/locale/en" class="px-3 py-1 rounded-md text-sm font-bold transition-all text-gray-500 hover:text-black">EN</a>
            </div>

            <template v-if="auth.user">
              <a href="/dashboard" class="hidden md:inline-flex items-center justify-center px-6 py-2.5 border border-transparent text-sm font-bold rounded-xl text-white bg-primary hover:bg-teal-700 shadow-sm hover:shadow-md transition-all">
                ড্যাশবোর্ড
              </a>
            </template>
            <template v-else>
              <a href="/login" class="text-gray-600 hover:text-primary font-bold px-4">লগইন</a>
              <a href="/register" class="hidden md:inline-flex items-center justify-center px-6 py-2.5 border border-transparent text-sm font-bold rounded-xl text-white bg-gradient-premium shadow-lg shadow-primary/25 hover:scale-105 transition-all">
                শুরু করুন
              </a>
            </template>
          </div>
        </div>
      </div>
    </nav>

    <!-- Hero Section -->
    <HeroSection />

    <!-- Trusted Companies Marquee -->
    <CompaniesMarquee />

    <!-- Stats Section -->
    <StatsSection />

    <!-- Features Section -->
    <div id="features">
      <FeaturesSection />
    </div>

    <!-- Modules Section -->
    <ModulesSection />

    <!-- Parallax Showcase Sections -->
    <div class="py-32 bg-white border-y border-gray-100 relative overflow-hidden">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="lg:grid lg:grid-cols-2 lg:gap-16 items-center mb-32">
          <div class="text-4xl mb-4 text-primary bg-primary/10 w-20 h-20 flex items-center justify-center rounded-full leading-none">📊</div>
          <h2 class="text-3xl font-extrabold text-black mb-6">স্মার্ট ড্যাশবোর্ড</h2>
          <p class="text-lg text-gray-600 mb-8 font-medium leading-relaxed">বাস্তবালিক সময়ে আপনার ব্যবসারের সব তথ্য এক জাযগায় দেখুন</p>
          <ul class="space-y-4 font-bold text-gray-600">
            <li class="flex items-center gap-3">
              <div class="bg-green-500/10 text-green-500 rounded-full p-1 shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
              </div>
              <span class="text-black">বিক্রয় ট্র্যাকিং</span>
            </li>
            <li class="flex items-center gap-3">
              <div class="bg-green-500/10 text-green-500 rounded-full p-1 shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
              </div>
              <span class="text-black">বিক্রয় পরিচালনা</span>
            </li>
            <li class="flex items-center gap-3">
              <div class="bg-green-500/10 text-green-500 rounded-full p-1 shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
              </div>
              <span class="text-black">পেমেন্ট ট্র্যাকিং</span>
            </li>
          </ul>
        </div>
        <div class="glass-panel border border-white rounded-[2rem] p-6 shadow-xl relative">
          <div class="absolute -top-6 -right-6 w-32 h-32 bg-primary rounded-full blur-[60px] opacity-20 z-0"></div>
          <div class="relative z-10 rounded-2xl overflow-hidden border border-gray-100 bg-white aspect-video flex items-center justify-center">
            <svg viewBox="0 0 400 250" class="w-full text-gray-100" fill="none">
              <rect width="400" height="40" fill="#F3F4F6"></rect>
              <rect x="20" y="60" width="360" height="170" rx="12" fill="#FAFAFA"></rect>
              <rect x="40" y="80" width="100" height="15" rx="8" fill="#0F766E" opacity="0.6"></rect>
              <rect x="40" y="110" width="320" height="100" rx="8" fill="#D1D5DB" opacity="0.4"></rect>
              <circle cx="90" cy="160" r="30" fill="#16A34A" opacity="0.5"></circle>
              <rect x="140" y="140" width="180" height="10" rx="5" fill="#14B8A6"></rect>
              <rect x="140" y="160" width="140" height="10" rx="5" fill="#CA8A04" opacity="0.5"></rect>
            </svg>
          </div>
        </div>
      </div>
    </div>

    <!-- Testimonials -->
    <div id="testimonials" class="py-24 bg-white">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-3xl mx-auto mb-16">
          <h2 class="text-primary font-bold tracking-widest uppercase text-sm mb-3">কাস্টমার</h2>
          <p class="text-4xl font-extrabold text-black mb-4">আমাদের মতামত</p>
          <p class="text-xl text-gray-600 font-medium">আমাদের কথা শুনুন</p>
        </div>

        <div class="grid md:grid-cols-3 gap-8">
          <div class="bg-gray-50 p-8 rounded-[2rem] border border-gray-100 relative group hover:shadow-xl transition-all duration-300">
            <div class="text-yellow-500 flex gap-1 mb-6 text-xl">★★★★★</div>
            <p class="text-gray-700 font-medium text-lg leading-relaxed mb-8">"এইআরপি একটি মাস্টিত সিস্টেম। সব ফিচার আছে এবং সব সময় ঠিকভাবে কাজ করতে পারছি।"</p>
            <div class="flex items-center gap-4 mt-auto">
              <div class="w-12 h-12 rounded-full bg-primary/10 flex items-center justify-center text-primary font-bold text-xl border border-primary/20">আ</div>
              <div>
                <p class="font-bold text-black">আবদুল্লাহ</p>
                <p class="text-sm font-medium text-gray-500">মালিক</p>
              </div>
            </div>
          </div>
          <div class="bg-gray-50 p-8 rounded-[2rem] border border-gray-100 relative group hover:shadow-xl transition-all duration-300">
            <div class="text-yellow-500 flex gap-1 mb-6 text-xl">★★★★★</div>
            <p class="text-gray-700 font-medium text-lg leading-relaxed mb-8">"ইনভেন্টরি ম্যানেজমেন্ট এবং অটোমেটিক অর্ডারিং অসাধারণ করতে পারছি।"</p>
            <div class="flex items-center gap-4 mt-auto">
              <div class="w-12 h-12 rounded-full bg-green-500/10 flex items-center justify-center text-green-500 font-bold text-xl border border-green-500/20">ক</div>
              <div>
                <p class="font-bold text-black">করিম</p>
                <p class="text-sm font-medium text-gray-500">ফার্মেসি</p>
              </div>
            </div>
          </div>
          <div class="bg-gray-50 p-8 rounded-[2rem] border border-gray-100 relative group hover:shadow-xl transition-all duration-300">
            <div class="text-yellow-500 flex gap-1 mb-6 text-xl">★★★★★</div>
            <p class="text-gray-700 font-medium text-lg leading-relaxed mb-8">"হিসাব রিপোর্ট এবং মুশক জেনারেশন সহজে সব কাজ করতে পারছি।"</p>
            <div class="flex items-center gap-4 mt-auto">
              <div class="w-12 h-12 rounded-full bg-primary/10 flex items-center justify-center text-primary font-bold text-xl border border-primary/20">স</div>
              <div>
                <p class="font-bold text-black">সাদিয়া</p>
                <p class="text-sm font-medium text-gray-500">হোলসেলিং</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Pricing -->
    <div id="pricing" class="py-24 bg-gray-900 relative overflow-hidden">
      <div class="absolute inset-0">
           <div class="absolute top-[20%] left-1/2 -translate-x-1/2 w-[700px] h-[700px] bg-primary rounded-full blur-[150px] opacity-20 pointer-events-none"></div>
           <div class="absolute bottom-[0%] right-[10%] w-[500px] h-[500px] bg-green-500 rounded-full blur-[150px] opacity-10 pointer-events-none"></div>
      </div>
      
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="text-center max-w-3xl mx-auto mb-16">
          <h2 class="text-teal-300 font-bold tracking-widest uppercase text-sm mb-3">মূল্য</h2>
          <p class="text-4xl font-extrabold text-white mb-4">প্যাকেজ প্ল্যান</p>
          <p class="text-xl text-gray-100/70 font-medium">আপনার ব্যবসারের জন্য সঠিক্ত প্ল্যান</p>
        </div>

        <div class="grid md:grid-cols-3 gap-8 items-center max-w-6xl mx-auto">
          <div class="relative bg-gray-800/80 backdrop-blur-xl border border-gray-500/30 rounded-[2rem] p-8">
            <h3 class="text-2xl font-bold text-white mb-2">স্টার্টার</h3>
            <p class="text-gray-100/70 font-medium mb-8">ছোট ব্যববায়ের জন্য</p>
            <div class="mb-8">
              <span class="text-5xl font-extrabold text-white">বিনামূল্য</span>
            </div>
            <ul class="space-y-4 mb-8">
              <li class="flex items-start gap-3">
                <svg class="w-5 h-5 text-gray-100/50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                <span class="text-gray-100/80 font-medium">৫ ইউউজার</span>
              </li>
              <li class="flex items-start gap-3">
                <svg class="w-5 h-5 text-gray-100/50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                <span class="text-gray-100/80 font-medium">সীমিত ফিচার</span>
              </li>
              <li class="flex items-start gap-3">
                <svg class="w-5 h-5 text-gray-100/50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                <span class="text-gray-100/80 font-medium">ইমেইল সাপোর্ট</span>
              </li>
            </ul>
            <a href="/register" class="block w-full text-center py-4 rounded-xl font-bold text-lg bg-gray-500/20 text-white hover:bg-gray-500/40">শুরু করুন</a>
          </div>
          <div class="relative bg-gray-800/80 backdrop-blur-xl border border-primary shadow-2xl shadow-primary/20 scale-105 z-20 rounded-[2rem] p-8">
            <div class="absolute -top-4 left-1/2 -translate-x-1/2 bg-gradient-premium text-white px-5 py-1.5 rounded-full text-sm font-bold shadow-lg">
              জনপ্রিয়
            </div>
            <h3 class="text-2xl font-bold text-white mb-2">প্রোফেশনাল</h3>
            <p class="text-gray-100/70 font-medium mb-8">বড় ব্যবসায়ের জন্য</p>
            <div class="mb-8">
              <span class="text-5xl font-extrabold text-white">৳২ ৯৯৯</span><span class="text-gray-100/70 font-bold text-xl">/মাস</span>
            </div>
            <ul class="space-y-4 mb-8">
              <li class="flex items-start gap-3">
                <svg class="w-5 h-5 text-teal-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                <span class="text-gray-100/80 font-medium">আনলিমিট ফিচার</span>
              </li>
              <li class="flex items-start gap-3">
                <svg class="w-5 h-5 text-teal-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                <span class="text-gray-100/80 font-medium">প্রগ্রাম রিপোর্ট</span>
              </li>
              <li class="flex items-start gap-3">
                <svg class="w-5 h-5 text-teal-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                <span class="text-gray-100/80 font-medium">প্রয়ারিটি সাপোর্ট</span>
              </li>
            </ul>
            <a href="/register" class="block w-full text-center py-4 rounded-xl font-bold text-lg bg-primary text-white hover:bg-teal-700 shadow-lg shadow-primary/25">শুরু করুন</a>
          </div>
          <div class="relative bg-gray-800/80 backdrop-blur-xl border border-gray-500/30 rounded-[2rem] p-8">
            <h3 class="text-2xl font-bold text-white mb-2">এন্টারপ্রাইজ</h3>
            <p class="text-gray-100/70 font-medium mb-8">বড় ব্যবসায়ের জন্য</p>
            <div class="mb-8">
              <span class="text-5xl font-extrabold text-white">কাস্টম</span>
            </div>
            <ul class="space-y-4 mb-8">
              <li class="flex items-start gap-3">
                <svg class="w-5 h-5 text-gray-100/50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                <span class="text-gray-100/80 font-medium">সব ফিচার</span>
              </li>
              <li class="flex items-start gap-3">
                <svg class="w-5 h-5 text-gray-100/50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                <span class="text-gray-100/80 font-medium">প্রয়ারিটি সাপোর্ট</span>
              </li>
              <li class="flex items-start gap-3">
                <svg class="w-5 h-5 text-gray-100/50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                <span class="text-gray-100/80 font-medium">ডেডিকেটেড সাপোর্ট</span>
              </li>
            </ul>
            <a href="/register" class="block w-full text-center py-4 rounded-xl font-bold text-lg bg-gray-500/20 text-white hover:bg-gray-500/40">যোগাযোগ করুন</a>
          </div>
        </div>
      </div>
    </div>

    <!-- CTA Section -->
    <div class="relative py-24 bg-white border-b border-gray-100">
      <div class="max-w-5xl mx-auto px-4 relative z-10 text-center">
        <h2 class="text-4xl sm:text-5xl font-extrabold text-black mb-6">আপনার ব্যবসাকে স্মার্ট করুন</h2>
        <p class="text-xl text-gray-600 mb-10 font-medium max-w-3xl mx-auto">আজকাউ ব্যবসায়ের জন্য সঠিক প্ল্যান এবং আজ শুরু করুন</p>
        
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
          <a href="/register" class="inline-flex items-center justify-center px-10 py-5 text-lg font-bold rounded-2xl text-white bg-gradient-premium shadow-xl shadow-primary/25 transition-all hover:scale-105">
            বিনামূল্য শুরু করুন
          </a>
          <a href="/login" class="inline-flex items-center justify-center px-10 py-5 text-lg font-bold rounded-2xl text-black bg-gray-50 border border-gray-100 hover:bg-gray-100 transition-all">
            লগইন করুন
          </a>
        </div>
      </div>
    </div>

    <!-- Footer -->
    <footer class="bg-black pt-20 pb-10 border-t-8 border-primary">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-5 gap-12 mb-16">
          
          <div class="md:col-span-2 pr-8">
            <a href="/" class="flex items-center gap-3 mb-6">
              <HomeLogo />
              <span class="text-2xl font-extrabold text-white tracking-tight">GenERP BD</span>
            </a>
            <p class="text-white/70 text-base font-medium leading-relaxed mb-6">বাংলাদেশের জন্য তৈরি করা প্রথম ক্লাউড ERP সলিউশন। আপনার ব্যবসায়ের সবকিছু এক জায়গায় থেকে পরিচালনা করুন।</p>
            <div class="flex space-x-4">
              <div class="w-10 h-10 rounded-full bg-gray-500/20 flex items-center justify-center text-white/70 hover:bg-primary hover:text-white transition-all cursor-pointer">
                <span>in</span>
              </div>
              <div class="w-10 h-10 rounded-full bg-gray-500/20 flex items-center justify-center text-white/70 hover:bg-primary hover:text-white transition-all cursor-pointer">
                <span>fb</span>
              </div>
            </div>
          </div>
          
          <div>
            <h3 class="text-white font-bold text-lg mb-6">প্রোডাক্ট</h3>
            <ul class="space-y-4 text-white/70 font-medium list-none p-0">
              <li><a href="#" class="hover:text-teal-300 transition-colors">ফিচার</a></li>
              <li><a href="#" class="hover:text-teal-300 transition-colors">মডিউল</a></li>
              <li><a href="#" class="hover:text-teal-300 transition-colors">মূল্য</a></li>
              <li><a href="#" class="hover:text-teal-300 transition-colors">সাপোর্ট</a></li>
            </ul>
          </div>
          
          <div>
            <h3 class="text-white font-bold text-lg mb-6">কোম্পানি</h3>
            <ul class="space-y-4 text-white/70 font-medium list-none p-0">
              <li><a href="#" class="hover:text-teal-300 transition-colors">আমাদের সম্পর্কে</a></li>
              <li><a href="#" class="hover:text-teal-300 transition-colors">ক্যারিয়ার</a></li>
              <li><a href="#" class="hover:text-teal-300 transition-colors">ব্লগ</a></li>
            </ul>
          </div>
          
          <div>
            <h3 class="text-white font-bold text-lg mb-6">সাপোর্ট</h3>
            <ul class="space-y-4 text-white/70 font-medium list-none p-0">
              <li><a href="#" class="hover:text-teal-300 transition-colors">সাহায্য কেন্দ্র</a></li>
              <li><a href="#" class="hover:text-teal-300 transition-colors">ডকুমেন্টেশন</a></li>
              <li><a href="#" class="hover:text-teal-300 transition-colors">যোগাযোগ</a></li>
            </ul>
          </div>
        </div>
        
        <div class="pt-8 border-t border-gray-500/20 flex flex-col md:flex-row justify-between items-center gap-4 text-white/60 font-medium">
          <div>© {{ new Date().getFullYear() }} GenERP BD. সর্বস্বত্ব সংরক্ষিত।</div>
          <div class="flex gap-6 text-sm">
            <a href="#" class="hover:text-white/90">গোপনীয়তা নীতি</a>
            <a href="#" class="hover:text-white/90">শর্তাবলী</a>
          </div>
        </div>
      </div>
    </footer>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { usePage } from '@inertiajs/vue3'
import HomeLogo from '@/Components/Home/Logo.vue'
import HeroSection from '@/Components/Home/HeroSection.vue'
import StatsSection from '@/Components/Home/StatsSection.vue'
import FeaturesSection from '@/Components/Home/FeaturesSection.vue'
import CompaniesMarquee from '@/Components/Home/CompaniesMarquee.vue'
import ModulesSection from '@/Components/Home/ModulesSection.vue'

const page = usePage()
const auth = page.props.auth || { user: null }

// Handle scroll state
const scrolled = ref(false)

const handleScroll = () => {
  scrolled.value = window.pageYOffset > 20
}

onMounted(() => {
  window.addEventListener('scroll', handleScroll)
})

onUnmounted(() => {
  window.removeEventListener('scroll', handleScroll)
})
</script>
