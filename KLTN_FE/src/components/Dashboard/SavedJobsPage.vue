<script setup>
import { computed, onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { savedJobService } from '@/services/api'

const loading = ref(true)
const error = ref('')
const savedJobs = ref([])
const togglingId = ref(null)

const unwrap = (payload) => payload?.data ?? payload ?? []
const asArray = (payload) => {
  const data = unwrap(payload)
  if (Array.isArray(data?.data)) return data.data
  if (Array.isArray(data)) return data
  return []
}

const normalizeSavedJob = (item) => item.tin_tuyen_dung || item.job || item
const sortedJobs = computed(() => [...savedJobs.value].sort((a, b) => new Date(b.created_at || b.saved_at || 0) - new Date(a.created_at || a.saved_at || 0)))

const formatDate = (value) => {
  if (!value) return 'Chua xac dinh'
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return 'Chua xac dinh'
  return new Intl.DateTimeFormat('vi-VN').format(date)
}

const formatCurrency = (value) => {
  if (value === null || value === undefined || value === '') return 'Thoa thuan'
  const amount = Number(value)
  if (Number.isNaN(amount)) return String(value)
  return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND', maximumFractionDigits: 0 }).format(amount)
}

const loadSavedJobs = async () => {
  loading.value = true
  error.value = ''
  try {
    const response = await savedJobService.getSavedJobs()
    savedJobs.value = asArray(response)
  } catch (err) {
    error.value = err.message || 'Khong the tai danh sach tin da luu'
  } finally {
    loading.value = false
  }
}

const toggleSaved = async (jobId) => {
  togglingId.value = jobId
  error.value = ''
  try {
    await savedJobService.toggleSavedJob(jobId)
    await loadSavedJobs()
  } catch (err) {
    error.value = err.message || 'Khong the cap nhat tin da luu'
  } finally {
    togglingId.value = null
  }
}

onMounted(loadSavedJobs)
</script>

<template>
  <div class="space-y-6">
    <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
      <div>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Tin da luu</h1>
        <p class="mt-1 text-sm text-slate-500">Quan ly danh sach viec ban muon theo doi va nop nhanh khi can.</p>
      </div>
      <div class="rounded-lg bg-[#2463eb]/5 px-4 py-2 text-sm text-[#2463eb]">Tong cong {{ sortedJobs.length }} tin da luu</div>
    </div>

    <div v-if="error" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900/40 dark:bg-red-950/30 dark:text-red-300">
      {{ error }}
    </div>

    <div v-if="loading" class="rounded-2xl border border-slate-200 bg-white px-4 py-12 text-center text-sm text-slate-500 shadow-sm dark:border-slate-800 dark:bg-slate-900">
      Dang tai tin da luu...
    </div>

    <div v-else-if="!sortedJobs.length" class="rounded-2xl border border-dashed border-slate-300 bg-white px-4 py-12 text-center text-sm text-slate-500 shadow-sm dark:border-slate-700 dark:bg-slate-900">
      Ban chua luu tin nao. Hay quay lai trang viec lam de danh dau cac vi tri phu hop.
    </div>

    <div v-else class="grid grid-cols-1 gap-4 xl:grid-cols-2">
      <article v-for="entry in sortedJobs" :key="entry.id" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-[#2463eb]/40 dark:border-slate-800 dark:bg-slate-900">
        <div class="flex items-start justify-between gap-3">
          <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-[#2463eb]">Da luu ngay {{ formatDate(entry.created_at || entry.saved_at) }}</p>
            <h2 class="mt-1 text-lg font-bold text-slate-900 dark:text-white">{{ normalizeSavedJob(entry).tieu_de || 'Tin tuyen dung' }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ normalizeSavedJob(entry).cong_ty?.ten_cong_ty || normalizeSavedJob(entry).ten_cong_ty || 'Cong ty dang cap nhat' }}</p>
          </div>
          <button class="rounded-lg bg-slate-100 p-2 text-slate-500 transition hover:bg-red-50 hover:text-red-600 dark:bg-slate-800" :disabled="togglingId === normalizeSavedJob(entry).id" @click="toggleSaved(normalizeSavedJob(entry).id)">
            <span class="material-symbols-outlined text-[20px]">bookmark</span>
          </button>
        </div>

        <div class="mt-4 flex flex-wrap gap-2 text-xs text-slate-500">
          <span class="rounded-full bg-slate-100 px-2.5 py-1 dark:bg-slate-800">{{ normalizeSavedJob(entry).dia_diem_lam_viec || 'Dang cap nhat dia diem' }}</span>
          <span class="rounded-full bg-slate-100 px-2.5 py-1 dark:bg-slate-800">{{ normalizeSavedJob(entry).hinh_thuc_lam_viec || 'Dang cap nhat hinh thuc' }}</span>
          <span class="rounded-full bg-slate-100 px-2.5 py-1 dark:bg-slate-800">{{ formatCurrency(normalizeSavedJob(entry).muc_luong) }}</span>
        </div>

        <p class="mt-4 text-sm text-slate-600 dark:text-slate-300">{{ normalizeSavedJob(entry).mo_ta_cong_viec || normalizeSavedJob(entry).mo_ta_chi_tiet || 'Mo ta viec lam dang duoc cap nhat.' }}</p>

        <div class="mt-5 flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-4 dark:border-slate-800">
          <span class="text-xs text-slate-400">Han nop: {{ formatDate(normalizeSavedJob(entry).ngay_het_han) }}</span>
          <div class="flex items-center gap-2">
            <RouterLink :to="`/jobs/${normalizeSavedJob(entry).id}`" class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">
              Xem chi tiet
            </RouterLink>
          </div>
        </div>
      </article>
    </div>
  </div>
</template>
