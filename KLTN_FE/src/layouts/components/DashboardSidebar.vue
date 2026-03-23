<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { useAuth } from '@/composables/useAuth'

const { logout } = useAuth()
const currentUser = ref(null)

const loadStoredUser = () => {
  const storedUser = localStorage.getItem('user')

  if (!storedUser) {
    currentUser.value = null
    return
  }

  try {
    currentUser.value = JSON.parse(storedUser)
  } catch (_) {
    currentUser.value = null
  }
}

const displayName = computed(() =>
  currentUser.value?.ho_ten ||
  currentUser.value?.fullName ||
  currentUser.value?.ten ||
  'Ung vien'
)

const displayRole = computed(() => {
  const role = Number(currentUser.value?.vai_tro)
  if (role === 2) return 'Admin'
  if (role === 3) return 'Employer'
  return 'Job Seeker'
})

const initials = computed(() => {
  const words = String(displayName.value).trim().split(/\s+/).filter(Boolean)
  return words.slice(0, 2).map((word) => word[0]?.toUpperCase()).join('') || 'UV'
})

const handleLogout = async () => {
  await logout()
}

onMounted(() => {
  loadStoredUser()
  window.addEventListener('auth-user-updated', loadStoredUser)
})

onUnmounted(() => {
  window.removeEventListener('auth-user-updated', loadStoredUser)
})
</script>

<template>
  <aside class="w-64 bg-white dark:bg-slate-900 border-r border-slate-200 dark:border-slate-800 flex flex-col shrink-0">
    <div class="p-6 flex items-center gap-3">
      <router-link to="/" class="size-8 bg-[#2463eb] rounded-lg flex items-center justify-center text-white hover:bg-blue-600 transition">
        <span class="material-symbols-outlined">rocket_launch</span>
      </router-link>
      <h2 class="text-xl font-bold tracking-tight text-[#2463eb]">HRTech</h2>
    </div>
    <div class="px-4 py-2 flex items-center gap-3 mb-6">
      <div class="size-10 rounded-full bg-slate-200 dark:bg-slate-800 shrink-0 flex items-center justify-center text-xs font-bold text-[#2463eb] dark:text-slate-200">
        {{ initials }}
      </div>
      <div class="overflow-hidden">
        <p class="font-semibold truncate text-sm">{{ displayName }}</p>
        <p class="text-xs text-slate-500 truncate">{{ displayRole }}</p>
      </div>
    </div>
    <nav class="flex-1 px-3 space-y-1 overflow-y-auto">
      <RouterLink to="/dashboard" exact-active-class="active-nav" class="nav-link flex items-center gap-3 px-3 py-2 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors font-medium">
        <span class="material-symbols-outlined">dashboard</span>
        <span class="text-sm">Dashboard</span>
      </RouterLink>
      <RouterLink to="/profile" active-class="active-nav" class="nav-link flex items-center gap-3 px-3 py-2 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors font-medium">
        <span class="material-symbols-outlined">account_circle</span>
        <span class="text-sm">Hồ sơ cá nhân</span>
      </RouterLink>
      <RouterLink to="/my-cv" active-class="active-nav" class="nav-link flex items-center gap-3 px-3 py-2 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors font-medium">
        <span class="material-symbols-outlined">description</span>
        <span class="text-sm">CV của tôi</span>
      </RouterLink>
      <RouterLink to="/matched-jobs" active-class="active-nav" class="nav-link flex items-center gap-3 px-3 py-2 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors font-medium">
        <span class="material-symbols-outlined">work</span>
        <span class="text-sm">Việc làm phù hợp</span>
      </RouterLink>
      <RouterLink to="/applications" active-class="active-nav" class="nav-link flex items-center gap-3 px-3 py-2 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors font-medium">
        <span class="material-symbols-outlined">send</span>
        <span class="text-sm">Việc đã ứng tuyển</span>
      </RouterLink>
      <RouterLink to="/saved-jobs" active-class="active-nav" class="nav-link flex items-center gap-3 px-3 py-2 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors font-medium">
        <span class="material-symbols-outlined">bookmark</span>
        <span class="text-sm">Tin đã lưu</span>
      </RouterLink>
      <div class="pt-4 pb-2 text-[10px] uppercase font-bold text-slate-400 px-3 tracking-widest">AI Services</div>
      <RouterLink to="/ai-career" active-class="active-nav" class="nav-link flex items-center gap-3 px-3 py-2 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors group font-medium">
        <span class="material-symbols-outlined text-[#2463eb] group-hover:scale-110 transition-transform">auto_awesome</span>
        <span class="text-sm">Tư vấn nghề nghiệp AI</span>
      </RouterLink>
    </nav>
    <div class="p-4 border-t border-slate-200 dark:border-slate-800">
      <a class="flex items-center gap-3 px-3 py-2 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors" href="#">
        <span class="material-symbols-outlined">settings</span>
        <span class="text-sm">Cài đặt</span>
      </a>
      <button type="button" @click="handleLogout" class="flex w-full items-center gap-3 px-3 py-2 rounded-lg text-left text-red-500 hover:bg-red-50 dark:hover:bg-red-900/10 transition-colors">
        <span class="material-symbols-outlined">logout</span>
        <span class="text-sm">Đăng xuất</span>
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
