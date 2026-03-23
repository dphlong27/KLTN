<script setup>
import { RouterLink } from 'vue-router'
</script>

<template>
  <aside class="w-64 border-r border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 flex flex-col sticky top-0 h-screen">
    <div class="p-6 flex items-center gap-3">
      <div class="size-10 bg-[#2463eb] rounded-xl flex items-center justify-center text-white">
        <span class="material-symbols-outlined">analytics</span>
      </div>
      <div>
        <h1 class="font-bold text-slate-900 dark:text-white leading-tight">AdminPanel</h1>
        <p class="text-xs text-slate-500 dark:text-slate-400">Management Console</p>
      </div>
    </div>
    <nav class="flex-1 px-4 space-y-1">
      <RouterLink to="/admin" exact-active-class="active-nav" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors font-medium">
        <span class="material-symbols-outlined text-[22px]">dashboard</span>
        <span class="text-sm">Dashboard</span>
      </RouterLink>
      <RouterLink to="/admin/users" active-class="active-nav" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors font-medium">
        <span class="material-symbols-outlined text-[22px]">group</span>
        <span class="text-sm">Users</span>
      </RouterLink>
      <RouterLink to="/admin/companies" active-class="active-nav" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors font-medium">
        <span class="material-symbols-outlined text-[22px]">domain</span>
        <span class="text-sm">Companies</span>
      </RouterLink>
      <RouterLink to="/admin/skills" active-class="active-nav" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors font-medium">
        <span class="material-symbols-outlined text-[22px]">bolt</span>
        <span class="text-sm">Skills</span>
      </RouterLink>
      <RouterLink to="/admin/industries" active-class="active-nav" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors font-medium">
        <span class="material-symbols-outlined text-[22px]">factory</span>
        <span class="text-sm">Industries</span>
      </RouterLink>
      <RouterLink to="/admin/jobs" active-class="active-nav" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors font-medium">
        <span class="material-symbols-outlined text-[22px]">work</span>
        <span class="text-sm">Jobs</span>
      </RouterLink>
      <RouterLink to="/admin/stats" active-class="active-nav" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors font-medium">
        <span class="material-symbols-outlined text-[22px]">leaderboard</span>
        <span class="text-sm">Stats</span>
      </RouterLink>
    </nav>
    <div class="p-4 border-t border-slate-200 dark:border-slate-800">
      <div class="flex items-center gap-3 p-2 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-800 cursor-pointer">
        <div class="size-8 rounded-full bg-slate-200 dark:bg-slate-700 overflow-hidden"></div>
        <div class="flex-1 overflow-hidden">
          <p class="text-sm font-semibold truncate">Alex Rivera</p>
          <p class="text-xs text-slate-500 truncate">Super Admin</p>
        </div>
        <span class="material-symbols-outlined text-slate-400">settings</span>
      </div>
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
