<template>
  <div class="py-12 bg-white border-y border-gray-100 overflow-hidden relative group">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mb-8 text-center relative z-20">
      <p class="text-sm font-bold text-gray-500 uppercase tracking-widest">Trusted by 500+ Companies</p>
    </div>
    
    <!-- Fading Edges for smooth scroll disappearing effect -->
    <div class="absolute left-0 top-0 bottom-0 w-32 bg-gradient-to-r from-white to-transparent z-10 pointer-events-none"></div>
    <div class="absolute right-0 top-0 bottom-0 w-32 bg-gradient-to-l from-white to-transparent z-10 pointer-events-none"></div>
    
    <div class="flex w-max animate-marquee whitespace-nowrap items-center pt-2 pb-4">
      <!-- Partner Logo 1 -->
      <div class="flex items-center gap-3 px-12 opacity-50 grayscale hover:grayscale-0 hover:opacity-100 hover:-translate-y-1 transition-all duration-300 cursor-pointer">
        <svg class="w-8 h-8 text-primary" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2L2 22h20L12 2zm0 6l5.5 11h-11L12 8z"/></svg>
        <span class="text-2xl font-extrabold text-gray-400 hover:text-gray-800 transition-colors tracking-tight">ApexCloud</span>
      </div>
      <!-- Partner Logo 2 -->
      <div class="flex items-center gap-3 px-12 opacity-50 grayscale hover:grayscale-0 hover:opacity-100 hover:-translate-y-1 transition-all duration-300 cursor-pointer">
        <svg class="w-8 h-8 text-green-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><circle cx="12" cy="12" r="8"/><path d="M12 8v8m-4-4h8"/></svg>
        <span class="text-2xl font-black text-gray-400 hover:text-gray-800 transition-colors tracking-tighter">Nexus<span class="text-green-500 opacity-50 hover:opacity-100">Flow</span></span>
      </div>
      <!-- Partner Logo 3 -->
      <div class="flex items-center gap-3 px-12 opacity-50 grayscale hover:grayscale-0 hover:opacity-100 hover:-translate-y-1 transition-all duration-300 cursor-pointer">
        <svg class="w-8 h-8 text-teal-400" viewBox="0 0 24 24" fill="currentColor"><rect x="3" y="3" width="18" height="18" rx="4"/><circle cx="12" cy="12" r="4" fill="white"/></svg>
        <span class="text-2xl font-bold text-gray-400 hover:text-gray-800 transition-colors">BoxMatrix</span>
      </div>
      <!-- Partner Logo 4 -->
      <div class="flex items-center gap-3 px-12 opacity-50 grayscale hover:grayscale-0 hover:opacity-100 hover:-translate-y-1 transition-all duration-300 cursor-pointer">
        <svg class="w-8 h-8 text-yellow-500" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
        <span class="text-2xl font-extrabold text-gray-400 hover:text-gray-800 transition-colors tracking-wide uppercase">Lumina</span>
      </div>
      <!-- Partner Logo 5 -->
      <div class="flex items-center gap-3 px-12 opacity-50 grayscale hover:grayscale-0 hover:opacity-100 hover:-translate-y-1 transition-all duration-300 cursor-pointer">
        <svg class="w-8 h-8 text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1v-7s-1 1-4 1-5-2-8-2-4 1-4 1v7z"/><path d="M4 22v-7"/></svg>
        <span class="text-2xl font-bold text-gray-400 hover:text-gray-800 transition-colors">Wave<span class="font-normal italic">Data</span></span>
      </div>
    </div>
  </div>
</template>

<script setup>
// No props needed
</script>
