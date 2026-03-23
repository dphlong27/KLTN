<script setup>
import { computed, onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import {
  authService,
  candidateApplicationService,
  candidateCvService,
  candidateSkillService,
  careerAdviceService,
  matchingService,
  savedJobService
} from '@/services/api'

const loading = ref(true)
const error = ref('')
const profile = ref({})
const cvs = ref([])
const applications = ref([])
const savedJobs = ref([])
const matchings = ref([])
const skills = ref([])
const adviceList = ref([])

const unwrap = (payload) => payload?.data ?? payload ?? []
const asArray = (payload) => {
  const data = unwrap(payload)
  if (Array.isArray(data?.data)) return data.data
  if (Array.isArray(data)) return data
  return []
}

const getDisplayName = computed(() => profile.value?.ho_ten || 'Ung vien')
const topMatchings = computed(() => [...matchings.value].sort((a, b) => Number(b.diem_phu_hop || 0) - Number(a.diem_phu_hop || 0)).slice(0, 4))
const pendingApplications = computed(() => applications.value.filter((item) => String(item.trang_thai || '').toLowerCase() === 'cho_duyet').length)
const averageMatch = computed(() => {
  if (!matchings.value.length) return 0
  const total = matchings.value.reduce((sum, item) => sum + Number(item.diem_phu_hop || 0), 0)
  return Math.round((total / matchings.value.length) * 100)
})

const formatPercent = (value) => `${Math.round(Number(value || 0) * 100)}%`
const formatDate = (value) => {
  if (!value) return 'Chua cap nhat'
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return 'Chua cap nhat'
  return new Intl.DateTimeFormat('vi-VN').format(date)
}

const formatCurrency = (value) => {
  if (value === null || value === undefined || value === '') return 'Thoa thuan'
  const amount = Number(value)
  if (Number.isNaN(amount)) return String(value)
  return new Intl.NumberFormat('vi-VN', {
    style: 'currency',
    currency: 'VND',
    maximumFractionDigits: 0
  }).format(amount)
}

const jobFromMatching = (item) => item.tin_tuyen_dung || item.job || {}
const companyFromJob = (job) => job.cong_ty || job.company || {}

const loadDashboard = async () => {
  loading.value = true
  error.value = ''

  try {
    const [profileResponse, cvResponse, applicationResponse, savedResponse, matchingResponse, skillResponse, adviceResponse] = await Promise.all([
      authService.getProfile(),
      candidateCvService.getCvs({ per_page: 50 }),
      candidateApplicationService.getApplications(),
      savedJobService.getSavedJobs(),
      matchingService.getMatchings(),
      candidateSkillService.getSkills(),
      careerAdviceService.getAdviceList()
    ])

    profile.value = unwrap(profileResponse)
    cvs.value = asArray(cvResponse)
    applications.value = asArray(applicationResponse)
    savedJobs.value = asArray(savedResponse)
    matchings.value = asArray(matchingResponse)
    skills.value = asArray(skillResponse)
    adviceList.value = asArray(adviceResponse)
  } catch (err) {
    error.value = err.message || 'Khong the tai du lieu dashboard'
  } finally {
    loading.value = false
  }
}

const toggleSavedJob = async (jobId) => {
  try {
    await savedJobService.toggleSavedJob(jobId)
    await loadDashboard()
  } catch (err) {
    error.value = err.message || 'Khong the cap nhat tin da luu'
  }
}

onMounted(loadDashboard)
</script>

<template>
  <div class="space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
      <div>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Xin chao, {{ getDisplayName }}</h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Theo doi CV, viec da luu, ung tuyen va goi y AI o mot noi.</p>
      </div>
      <RouterLink to="/my-cv" class="inline-flex items-center gap-2 rounded-lg bg-[#2463eb] px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-[#2463eb]/20 transition hover:bg-[#1d4fcc]">
        <span class="material-symbols-outlined text-[20px]">description</span>
        Quan ly ho so
      </RouterLink>
    </div>

    <div v-if="error" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900/40 dark:bg-red-950/30 dark:text-red-300">
      {{ error }}
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
      <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <p class="text-sm text-slate-500">Ho so dang co</p>
        <p class="mt-2 text-3xl font-bold text-slate-900 dark:text-white">{{ cvs.length }}</p>
        <p class="mt-2 text-xs text-slate-400">{{ cvs.filter((item) => Number(item.trang_thai) === 1).length }} ho so dang cong khai</p>
      </div>
      <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <p class="text-sm text-slate-500">Viec da luu</p>
        <p class="mt-2 text-3xl font-bold text-slate-900 dark:text-white">{{ savedJobs.length }}</p>
        <p class="mt-2 text-xs text-slate-400">San sang de nop nhanh khi can</p>
      </div>
      <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <p class="text-sm text-slate-500">Ung tuyen cho duyet</p>
        <p class="mt-2 text-3xl font-bold text-slate-900 dark:text-white">{{ pendingApplications }}</p>
        <p class="mt-2 text-xs text-slate-400">Tong cong {{ applications.length }} don ung tuyen</p>
      </div>
      <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <p class="text-sm text-slate-500">Matching trung binh</p>
        <p class="mt-2 text-3xl font-bold text-[#2463eb]">{{ averageMatch }}%</p>
        <p class="mt-2 text-xs text-slate-400">{{ skills.length }} ky nang, {{ adviceList.length }} bao cao AI</p>
      </div>
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-[2fr,1fr]">
      <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="mb-5 flex items-center justify-between gap-3">
          <div>
            <h2 class="text-lg font-bold text-slate-900 dark:text-white">Viec lam phu hop</h2>
            <p class="text-sm text-slate-500">Top goi y tu ket qua matching AI</p>
          </div>
          <RouterLink to="/matched-jobs" class="text-sm font-medium text-[#2463eb] hover:underline">Xem tat ca</RouterLink>
        </div>

        <div v-if="loading" class="py-12 text-center text-sm text-slate-500">Dang tai du lieu...</div>
        <div v-else-if="!topMatchings.length" class="rounded-xl border border-dashed border-slate-300 px-4 py-10 text-center text-sm text-slate-500 dark:border-slate-700">
          Chua co ket qua matching nao. Hay tao CV va cap nhat ky nang de AI goi y tot hon.
        </div>
        <div v-else class="grid grid-cols-1 gap-4 lg:grid-cols-2">
          <article v-for="item in topMatchings" :key="item.id" class="rounded-xl border border-slate-200 p-5 transition hover:border-[#2463eb]/40 dark:border-slate-800">
            <div class="flex items-start justify-between gap-3">
              <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-[#2463eb]">{{ formatPercent(item.diem_phu_hop) }} match</p>
                <h3 class="mt-1 text-lg font-semibold text-slate-900 dark:text-white">{{ jobFromMatching(item).tieu_de || 'Tin tuyen dung' }}</h3>
                <p class="mt-1 text-sm text-slate-500">{{ companyFromJob(jobFromMatching(item)).ten_cong_ty || 'Cong ty dang cap nhat' }}</p>
              </div>
              <button class="rounded-lg bg-slate-100 p-2 text-slate-500 transition hover:bg-slate-200 hover:text-[#2463eb] dark:bg-slate-800" @click="toggleSavedJob(jobFromMatching(item).id)" :disabled="!jobFromMatching(item).id">
                <span class="material-symbols-outlined text-[20px]">bookmark</span>
              </button>
            </div>
            <div class="mt-4 h-2 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
              <div class="h-full rounded-full bg-[#2463eb]" :style="{ width: formatPercent(item.diem_phu_hop) }"></div>
            </div>
            <div class="mt-4 flex flex-wrap gap-2 text-xs text-slate-500">
              <span class="rounded-full bg-slate-100 px-2.5 py-1 dark:bg-slate-800">{{ jobFromMatching(item).dia_diem_lam_viec || 'Dang cap nhat dia diem' }}</span>
              <span class="rounded-full bg-slate-100 px-2.5 py-1 dark:bg-slate-800">{{ jobFromMatching(item).hinh_thuc_lam_viec || 'Dang cap nhat hinh thuc' }}</span>
              <span class="rounded-full bg-slate-100 px-2.5 py-1 dark:bg-slate-800">{{ formatCurrency(jobFromMatching(item).muc_luong) }}</span>
            </div>
          </article>
        </div>
      </section>

      <section class="space-y-6">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
          <div class="flex items-center justify-between">
            <h2 class="text-lg font-bold text-slate-900 dark:text-white">Bao cao AI gan day</h2>
            <RouterLink to="/matched-jobs" class="text-sm font-medium text-[#2463eb] hover:underline">Chi tiet</RouterLink>
          </div>
          <div class="mt-4 space-y-4">
            <div v-for="item in adviceList.slice(0, 3)" :key="item.id" class="rounded-xl bg-slate-50 p-4 dark:bg-slate-800/60">
              <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ item.nghe_de_xuat || 'Goi y nghe nghiep' }}</p>
              <p class="mt-1 text-xs text-slate-500">Phu hop: {{ formatPercent(item.muc_do_phu_hop) }}</p>
              <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">{{ item.goi_y_ky_nang_bo_sung || 'Hay bo sung ky nang va cap nhat CV de nhan them goi y.' }}</p>
            </div>
            <p v-if="!adviceList.length" class="text-sm text-slate-500">Chua co bao cao tu van nao.</p>
          </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
          <div class="flex items-center justify-between">
            <h2 class="text-lg font-bold text-slate-900 dark:text-white">Hoat dong gan day</h2>
            <RouterLink to="/applications" class="text-sm font-medium text-[#2463eb] hover:underline">Xem don</RouterLink>
          </div>
          <div class="mt-4 space-y-3">
            <div v-for="item in applications.slice(0, 4)" :key="item.id" class="rounded-xl border border-slate-200 px-4 py-3 dark:border-slate-800">
              <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ item.tin_tuyen_dung?.tieu_de || item.tieu_de || 'Don ung tuyen' }}</p>
              <p class="mt-1 text-xs text-slate-500">{{ formatDate(item.created_at) }} · {{ item.trang_thai || 'cho_duyet' }}</p>
            </div>
            <p v-if="!applications.length" class="text-sm text-slate-500">Ban chua nop ho so nao.</p>
          </div>
        </div>
      </section>
    </div>
  </div>
</template>
