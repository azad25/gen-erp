<template>
  <div class="relative pt-32 pb-20 sm:pt-40 sm:pb-24 lg:pb-32 overflow-hidden">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative">
      <div class="lg:grid lg:grid-cols-12 lg:gap-16 items-center">
        
        <div class="lg:col-span-6 text-center lg:text-left mb-16 lg:mb-0">
          <div class="inline-flex items-center gap-2 px-5 py-2 rounded-full bg-primary/10 text-primary font-bold text-sm mb-8 border border-primary/20">
            <span class="flex h-2 w-2 relative">
              <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-teal-400 opacity-75"></span>
              <span class="relative inline-flex rounded-full h-2 w-2 bg-primary"></span>
            </span>
            {{ badge }}
          </div>
          
          <h1 class="text-5xl sm:text-6xl font-extrabold tracking-tight text-black mb-6 leading-[1.1]">
            {{ titlePart1 }} <br/>
            <span class="text-gradient">{{ titlePart2 }}</span>
          </h1>
          
          <p class="text-lg text-gray-600 mb-8 max-w-2xl mx-auto lg:mx-0 font-medium">
            {{ description }}
          </p>
          
          <div class="flex flex-col sm:flex-row gap-4 justify-center lg:justify-start">
            <a href="/register" class="inline-flex items-center justify-center px-8 py-4 text-base font-extrabold rounded-xl text-white bg-gradient-premium shadow-xl shadow-primary/25 transition-all hover:-translate-y-1">
              {{ ctaPrimary }}
              <svg class="ml-2 w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
            </a>
            <a href="#features" class="inline-flex items-center justify-center px-8 py-4 text-base font-bold rounded-xl text-black bg-white border border-gray-100 shadow-sm transition-all hover:-translate-y-1">
              {{ ctaSecondary }}
            </a>
          </div>
        </div>
        
        <div class="lg:col-span-6 relative">
          <div class="glass-panel rounded-[2rem] p-6 shadow-xl relative">
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

          <div class="absolute -right-8 top-[15%] glass-panel rounded-2xl p-4 shadow-xl z-20 float-animation border-l-4 border-green-500">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-full bg-green-500/10 flex items-center justify-center text-green-500">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
              </div>
              <div>
                <p class="text-xs font-bold text-gray-500 uppercase tracking-wide">Monthly Revenue</p>
                <p class="text-lg font-extrabold text-black">৳ 2.4M <span class="text-sm text-green-500">+14%</span></p>
              </div>
            </div>
          </div>

          <div class="absolute -left-12 bottom-[20%] glass-panel rounded-2xl p-4 shadow-xl z-20 float-animation-delayed border border-gray-100">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center text-primary">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
              </div>
              <div>
                <p class="text-xs font-bold text-gray-500 uppercase tracking-wide">Mushak 6.3</p>
                <p class="text-lg font-extrabold text-green-500">Generated ✓</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
const badge = 'বাংলাদেশের জন্য তৈরি করা প্রথম ক্লাউড ERP'
const titlePart1 = 'আপনার ব্যবসাকে'
const titlePart2 = 'স্মার্ট করুন'
const description = 'GenERP BD আপনাকে সব ধরনের ব্যবসায়িক কাজ এক জায়গায় থেকে পরিচালনা করতে সাহায্য করে। বিক্রয়, ক্রয়, ইনভেন্টরি, হিসাব, এইচআর এবং আরও অনেক কিছু।'
const ctaPrimary = 'বিনামূল্যে শুরু করুন'
const ctaSecondary = 'আরও জানুন'
</script>
