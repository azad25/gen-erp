<template>
  <div class="py-24 bg-white relative border-b border-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="text-center max-w-3xl mx-auto mb-16">
        <h2 class="text-primary font-bold tracking-widest uppercase text-sm mb-3">ফিচার</h2>
        <p class="text-4xl font-extrabold text-black mb-4">শক্তিশালী বৈশিষ্ট্যসমূহ</p>
        <p class="text-xl text-gray-600 font-medium">আপনার ব্যবসায়ের জন্য সব প্রয়োজনীয় টুলস</p>
      </div>

      <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
        <div v-for="(feature, index) in features" :key="index" class="bg-white rounded-[2rem] p-8 shadow-sm border border-gray-100 hover:border-primary/30 hover:shadow-xl hover:shadow-primary/5 transition-all duration-300 hover:-translate-y-2 group">
          <div class="w-16 h-16 rounded-2xl bg-gray-100 flex items-center justify-center text-3xl mb-6 shadow-sm border border-white group-hover:scale-110 group-hover:bg-primary group-hover:text-white transition-all">
            {{ feature.icon }}
          </div>
          <h3 class="text-xl font-bold text-black mb-3">{{ feature.title }}</h3>
          <p class="text-gray-600 font-medium leading-relaxed">{{ feature.description }}</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
const features = [
  { icon: '📊', title: 'ড্যাশবোর্ড', description: 'বাস্তব সময়ে আপনার ব্যবসার সব তথ্য দেখুন' },
  { icon: '📦', title: 'ইনভেন্টরি', description: 'স্টক লেভেল ট্র্যাক করুন এবং অটোমেটিক অর্ডার করুন' },
  { icon: '📝', title: 'ইনভয়েস', description: 'সহজেই ইনভয়েস তৈরি এবং পরিচালনা করুন' },
  { icon: '👥', title: 'কাস্টমার', description: 'কাস্টমার রিলেশনশিপ ম্যানেজ করুন' },
  { icon: '💰', title: 'হিসাব', description: 'সহজ হিসাব এবং মুশক ৬.৩ জেনারেশন' },
  { icon: '📱', title: 'মোবাইল', description: 'যেকোনো জায়গা থেকে অ্যাক্সেস করুন' },
]
</script>
