<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { useAuth } from '@/composables/useAuth'

const { logout } = useAuth()
const currentEmployer = ref(null)

const loadStoredEmployer = () => {
  const storedEmployer = localStorage.getItem('employer') || localStorage.getItem('user')

  if (!storedEmployer) {
    currentEmployer.value = null
    return
  }

  try {
    currentEmployer.value = JSON.parse(storedEmployer)
  } catch (_) {
    currentEmployer.value = null
  }
}

const displayName = computed(() =>
  currentEmployer.value?.ho_ten ||
  currentEmployer.value?.fullName ||
  currentEmployer.value?.ten ||
  'Nha tuyen dung'
)

const initials = computed(() => {
  const words = String(displayName.value).trim().split(/\s+/).filter(Boolean)
  return words.slice(0, 2).map((word) => word[0]?.toUpperCase()).join('') || 'NTD'
})

const handleLogout = async () => {
  await logout()
}

onMounted(() => {
  loadStoredEmployer()
  window.addEventListener('auth-user-updated', loadStoredEmployer)
})

onUnmounted(() => {
  window.removeEventListener('auth-user-updated', loadStoredEmployer)
})
</script>

<template>
  <aside class="w-64 border-r border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 hidden lg:flex flex-col p-4 gap-6">
    <div class="flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-3 py-3 dark:border-slate-800 dark:bg-slate-800/40">
      <div class="flex h-11 w-11 items-center justify-center rounded-full bg-[#2463eb] text-sm font-bold text-white">
        {{ initials }}
      </div>
      <div class="min-w-0">
        <p class="truncate text-sm font-semibold text-slate-900 dark:text-white">{{ displayName }}</p>
        <p class="truncate text-xs text-slate-500 dark:text-slate-400">Nha tuyen dung</p>
      </div>
    </div>
    <div class="flex flex-col gap-1">
      <p class="px-3 text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Main Menu</p>
      <RouterLink to="/employer" exact-active-class="active-nav" class="nav-link flex items-center gap-3 px-3 py-2 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors font-medium">
        <span class="material-symbols-outlined">dashboard</span>
        <span class="text-sm">Dashboard</span>
      </RouterLink>
      <RouterLink to="/employer/jobs" active-class="active-nav" class="nav-link flex items-center gap-3 px-3 py-2 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors font-medium">
        <span class="material-symbols-outlined">work</span>
        <span class="text-sm">Jobs</span>
      </RouterLink>
      <RouterLink to="/employer/candidates" active-class="active-nav" class="nav-link flex items-center gap-3 px-3 py-2 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors font-medium">
        <span class="material-symbols-outlined">group</span>
        <span class="text-sm">Candidates</span>
      </RouterLink>
      <RouterLink to="/employer/interviews" active-class="active-nav" class="nav-link flex items-center gap-3 px-3 py-2 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors font-medium">
        <span class="material-symbols-outlined">calendar_today</span>
        <span class="text-sm">Interviews</span>
      </RouterLink>
      <RouterLink to="/employer/company" active-class="active-nav" class="nav-link flex items-center gap-3 px-3 py-2 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors font-medium">
        <span class="material-symbols-outlined">domain</span>
        <span class="text-sm">Công ty</span>
      </RouterLink>
    </div>
    <div class="flex flex-col gap-1 mt-auto">
      <div class="p-4 bg-[#2463eb]/5 rounded-xl border border-[#2463eb]/20">
        <p class="text-xs font-bold text-[#2463eb] mb-1 uppercase">Pro Plan</p>
        <p class="text-xs text-slate-500 dark:text-slate-400 mb-3">You have 5 premium job slots remaining.</p>
        <button class="w-full py-2 bg-[#2463eb] text-white text-xs font-bold rounded-lg hover:bg-[#2463eb]/90">Upgrade Now</button>
      </div>
      <button type="button" @click="handleLogout" class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-left text-red-500 transition-colors hover:bg-red-50 dark:hover:bg-red-900/10">
        <span class="material-symbols-outlined">logout</span>
        <span class="text-sm font-medium">Dang xuat</span>
      </button>
    </div>
  </aside>
</template>

<style scoped>
.nav-link.active-nav {
  background-color: rgb(36 99 235 / 0.1);
  color: #2463eb;
}

.nav-link.active-nav:hover {
  background-color: rgb(36 99 235 / 0.15);
}
</style>
