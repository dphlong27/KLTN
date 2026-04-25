<script setup>
import AppLogo from '@/components/AppLogo.vue'
import { computed } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { getStoredEmployer } from '@/utils/authStorage'
import { useEmployerCompanyPermissions } from '@/composables/useEmployerCompanyPermissions'
import { useNotify } from '@/composables/useNotify'

defineProps({
  collapsed: {
    type: Boolean,
    default: false,
  },
})

const companyLabel = computed(() => {
  const employer = getStoredEmployer()
  return employer?.ho_ten || employer?.email || 'Nhà tuyển dụng'
})

const companyLetter = computed(() => companyLabel.value.trim().charAt(0).toUpperCase() || 'N')
const router = useRouter()
const notify = useNotify()
const {
  hasCompany,
  ensurePermissionsLoaded,
} = useEmployerCompanyPermissions()

ensurePermissionsLoaded().catch(() => {})

const companyRequiredMessage = 'Bạn cần tạo hoặc tham gia công ty trước khi sử dụng chức năng này.'

const navItems = computed(() => [
  {
    key: 'home',
    to: '/employer/home',
    icon: 'home',
    label: 'Trang chủ',
    exact: true,
  },
  {
    key: 'dashboard',
    to: '/employer',
    icon: 'dashboard',
    label: 'Dashboard',
    exact: true,
  },
  {
    key: 'jobs',
    to: '/employer/jobs',
    icon: 'work',
    label: 'Tin tuyển dụng',
    guarded: true,
    locked: !hasCompany.value,
    lockedReason: companyRequiredMessage,
  },
  {
    key: 'candidates',
    to: '/employer/candidates',
    icon: 'group',
    label: 'Ứng viên',
    guarded: true,
    locked: !hasCompany.value,
    lockedReason: companyRequiredMessage,
  },
  {
    key: 'interviews',
    to: '/employer/interviews',
    icon: 'calendar_today',
    label: 'Phỏng vấn',
    guarded: true,
    locked: !hasCompany.value,
    lockedReason: companyRequiredMessage,
  },
  {
    key: 'company',
    to: '/employer/company',
    icon: 'domain',
    label: 'Công ty',
  },
  {
    key: 'hr-management',
    to: '/employer/hr-management',
    icon: 'groups',
    label: 'Nhân sự HR',
  },
  {
    key: 'audit-logs',
    to: '/employer/audit-logs',
    icon: 'history',
    label: 'Nhật ký công ty',
  },
])

const handleNavClick = async (event, item) => {
  if (!item.guarded) return

  event.preventDefault()

  await ensurePermissionsLoaded({ force: true }).catch(() => null)

  const latestItem = navItems.value.find((navItem) => navItem.key === item.key) || item

  if (latestItem.locked) {
    notify.warning(latestItem.lockedReason)
    return
  }

  await router.push(latestItem.to)
}
</script>

<template>
  <aside
    class="sticky top-0 hidden h-screen shrink-0 flex-col border-r border-slate-200/80 bg-white/95 backdrop-blur transition-all duration-200 dark:border-slate-800 dark:bg-slate-950/90 lg:flex"
    :class="collapsed ? 'w-24' : 'w-64'"
  >
    <div class="flex items-center gap-3 px-6 pb-4 pt-6" :class="collapsed ? 'justify-center' : ''">
      <AppLogo
        :show-text="!collapsed"
        size="sm"
        title="AI Recruitment"
        subtitle="Employer Space"
      />
    </div>

    <nav class="flex-1 space-y-1 overflow-y-auto px-3">
      <RouterLink
        v-for="item in navItems"
        :key="item.key"
        :to="item.to"
        :active-class="item.exact ? '' : 'active-nav'"
        :exact-active-class="item.exact ? 'active-nav' : ''"
        class="nav-link flex items-center gap-3 rounded-lg px-3 py-2 text-slate-600 transition-colors font-medium hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-800"
        :class="[
          collapsed ? 'justify-center' : '',
          item.locked ? 'locked-nav cursor-not-allowed opacity-60 hover:bg-transparent dark:hover:bg-transparent' : '',
        ]"
        :title="collapsed ? item.label : item.locked ? item.lockedReason : ''"
        @click="handleNavClick($event, item)"
      >
        <span class="material-symbols-outlined">{{ item.icon }}</span>
        <span v-if="!collapsed" class="text-sm">{{ item.label }}</span>
        <span
          v-if="!collapsed && item.locked"
          class="material-symbols-outlined ml-auto text-[17px] text-slate-400"
        >
          lock
        </span>
      </RouterLink>
    </nav>
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

.nav-link.locked-nav.active-nav {
  background-color: transparent;
  color: rgb(100 116 139);
}
</style>
