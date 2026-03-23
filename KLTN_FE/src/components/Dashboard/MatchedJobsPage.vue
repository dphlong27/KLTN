<script setup>
import { computed, onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { matchingService, savedJobService } from '@/services/api'

const loading = ref(true)
const error = ref('')
const matchings = ref([])
const togglingId = ref(null)
const sortBy = ref('score')

const unwrap = (payload) => payload?.data ?? payload ?? []
const asArray = (payload) => {
  const data = unwrap(payload)
  if (Array.isArray(data?.data)) return data.data
  if (Array.isArray(data)) return data
  return []
}

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
  return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND', maximumFractionDigits: 0 }).format(amount)
}

const jobFromMatching = (item) => item.tin_tuyen_dung || item.job || {}
const companyFromJob = (job) => job.cong_ty || job.company || {}

const sortedMatchings = computed(() => {
  const items = [...matchings.value]

  if (sortBy.value === 'newest') {
    return items.sort((a, b) => new Date(b.thoi_gian_match || b.created_at || 0) - new Date(a.thoi_gian_match || a.created_at || 0))
  }

  if (sortBy.value === 'salary') {
    return items.sort((a, b) => Number(jobFromMatching(b).muc_luong || 0) - Number(jobFromMatching(a).muc_luong || 0))
  }

  return items.sort((a, b) => Number(b.diem_phu_hop || 0) - Number(a.diem_phu_hop || 0))
})

const averageScore = computed(() => {
  if (!matchings.value.length) return 0
  return Math.round(matchings.value.reduce((sum, item) => sum + Number(item.diem_phu_hop || 0), 0) / matchings.value.length * 100)
})

const loadMatchings = async () => {
  loading.value = true
  error.value = ''
  try {
    const response = await matchingService.getMatchings()
    matchings.value = asArray(response)
  } catch (err) {
    error.value = err.message || 'Khong the tai ket qua matching'
  } finally {
    loading.value = false
  }
}

const toggleSaved = async (jobId) => {
  togglingId.value = jobId
  error.value = ''
  try {
    await savedJobService.toggleSavedJob(jobId)
  } catch (err) {
    error.value = err.message || 'Khong the luu tin tuyen dung'
  } finally {
    togglingId.value = null
  }
}

onMounted(loadMatchings)
</script>

<template>
  <div class="space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
      <div>
        <h1 class="flex items-center gap-2 text-2xl font-bold text-slate-900 dark:text-white">
          <span class="material-symbols-outlined text-[#2463eb]">auto_awesome</span>
          Viec lam phu hop
        </h1>
        <p class="mt-1 text-sm text-slate-500">Danh sach viec duoc AI danh gia dua tren ho so va ky nang cua ban.</p>
      </div>
      <select v-model="sortBy" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">
        <option value="score">Matching cao nhat</option>
        <option value="newest">Moi nhat</option>
        <option value="salary">Luong cao nhat</option>
      </select>
    </div>

    <div v-if="error" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900/40 dark:bg-red-950/30 dark:text-red-300">
      {{ error }}
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
      <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <p class="text-sm text-slate-500">Tong viec duoc goi y</p>
        <p class="mt-2 text-3xl font-bold text-slate-900 dark:text-white">{{ matchings.length }}</p>
      </div>
      <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <p class="text-sm text-slate-500">Matching trung binh</p>
        <p class="mt-2 text-3xl font-bold text-[#2463eb]">{{ averageScore }}%</p>
      </div>
      <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <p class="text-sm text-slate-500">Top match tren 90%</p>
        <p class="mt-2 text-3xl font-bold text-green-600">{{ matchings.filter((item) => Number(item.diem_phu_hop || 0) >= 0.9).length }}</p>
      </div>
    </div>

    <div v-if="loading" class="rounded-2xl border border-slate-200 bg-white px-4 py-12 text-center text-sm text-slate-500 shadow-sm dark:border-slate-800 dark:bg-slate-900">
      Dang tai ket qua matching...
    </div>

    <div v-else-if="!sortedMatchings.length" class="rounded-2xl border border-dashed border-slate-300 bg-white px-4 py-12 text-center text-sm text-slate-500 shadow-sm dark:border-slate-700 dark:bg-slate-900">
      Chua co ket qua matching nao. Hay cap nhat CV va ky nang de nhan goi y chinh xac hon.
    </div>

    <div v-else class="space-y-4">
      <article v-for="item in sortedMatchings" :key="item.id" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-[#2463eb]/40 dark:border-slate-800 dark:bg-slate-900">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
          <div class="flex-1">
            <div class="flex flex-wrap items-center gap-2">
              <span class="rounded-full bg-[#2463eb]/10 px-2.5 py-1 text-xs font-semibold text-[#2463eb]">{{ formatPercent(item.diem_phu_hop) }} match</span>
              <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs text-slate-500 dark:bg-slate-800">{{ formatDate(item.thoi_gian_match || item.created_at) }}</span>
            </div>
            <h2 class="mt-3 text-xl font-bold text-slate-900 dark:text-white">{{ jobFromMatching(item).tieu_de || 'Tin tuyen dung' }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ companyFromJob(jobFromMatching(item)).ten_cong_ty || 'Cong ty dang cap nhat' }}</p>
            <div class="mt-4 h-2 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
              <div class="h-full rounded-full bg-gradient-to-r from-[#2463eb] to-indigo-500" :style="{ width: formatPercent(item.diem_phu_hop) }"></div>
            </div>
            <div class="mt-4 flex flex-wrap gap-2 text-xs text-slate-500">
              <span class="rounded-full bg-slate-100 px-2.5 py-1 dark:bg-slate-800">{{ jobFromMatching(item).dia_diem_lam_viec || 'Dia diem dang cap nhat' }}</span>
              <span class="rounded-full bg-slate-100 px-2.5 py-1 dark:bg-slate-800">{{ jobFromMatching(item).hinh_thuc_lam_viec || 'Dang cap nhat hinh thuc' }}</span>
              <span class="rounded-full bg-slate-100 px-2.5 py-1 dark:bg-slate-800">{{ formatCurrency(jobFromMatching(item).muc_luong) }}</span>
            </div>
            <p class="mt-4 text-sm text-slate-600 dark:text-slate-300">{{ item.danh_sach_ky_nang_thieu || jobFromMatching(item).mo_ta_cong_viec || 'Khong co ghi chu them tu AI.' }}</p>
          </div>
          <div class="flex items-center gap-2 lg:ml-6">
            <button class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800" :disabled="togglingId === jobFromMatching(item).id" @click="toggleSaved(jobFromMatching(item).id)">
              Luu tin
            </button>
            <RouterLink :to="`/jobs/${jobFromMatching(item).id}`" class="rounded-lg bg-[#2463eb] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#1d4fcc]">
              Xem chi tiet
            </RouterLink>
          </div>
        </div>
      </article>
    </div>
  </div>
</template>
