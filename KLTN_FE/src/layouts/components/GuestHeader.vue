<script setup>
import { computed, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import { useAuth } from '@/composables/useAuth'

const { user, loadUser } = useAuth()

const sessionInfo = computed(() => {

  if (localStorage.getItem('employer')) {
    return {
      to: '/employer',
      label: 'Trang doanh nghiep',
      icon: 'business_center'
    }
  }

  if (localStorage.getItem('user')) {
    return {
      to: '/profile',
      label: 'Ho so cua toi',
      icon: 'person'
    }
  }

  return null
})

onMounted(() => {
  loadUser()
})
</script>

<template>
  <header class="sticky top-0 z-50 w-full border-b border-slate-200 dark:border-slate-800 bg-white/80 dark:bg-[#111621]/80 backdrop-blur-md">
    <div class="mx-auto flex max-w-7xl items-center justify-between px-6 py-4">
      <RouterLink to="/" class="flex items-center gap-2 text-[#2463eb]">
        <span class="material-symbols-outlined text-3xl font-bold">rocket_launch</span>
        <h2 class="text-slate-900 dark:text-white text-xl font-bold tracking-tight">AI Recruitment</h2>
      </RouterLink>
      <nav class="hidden lg:flex items-center gap-8">
        <RouterLink to="/" class="text-sm font-medium hover:text-[#2463eb] transition-colors">Trang chủ</RouterLink>
        <RouterLink to="/jobs" class="text-sm font-medium hover:text-[#2463eb] transition-colors">Việc làm</RouterLink>
        <RouterLink to="/ai-career" class="text-sm font-medium hover:text-[#2463eb] transition-colors">Tư vấn nghề nghiệp AI</RouterLink>
      </nav>
      <div class="flex items-center gap-3">
        <RouterLink
          v-if="user && sessionInfo"
          :to="sessionInfo.to"
          class="hidden sm:flex h-10 items-center justify-center gap-2 rounded-lg bg-[#2463eb] px-4 text-sm font-bold text-white hover:bg-[#2463eb]/90 transition-all"
        >
          <span class="material-symbols-outlined text-[18px]">{{ sessionInfo.icon }}</span>
          {{ sessionInfo.label }}
        </RouterLink>
        <RouterLink v-else to="/auth" class="hidden sm:flex h-10 items-center justify-center rounded-lg px-4 text-sm font-bold text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition-all">
          Đăng nhập
        </RouterLink>
      </div>
    </div>
  </header>
</template>
