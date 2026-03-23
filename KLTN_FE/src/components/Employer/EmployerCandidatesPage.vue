<script setup>
import { computed, onMounted, ref } from 'vue'
import { employerApplicationService } from '@/services/api'

const loading = ref(true)
const error = ref('')
const success = ref('')
const applications = ref([])
const activeFilter = ref('tat_ca')
const updatingId = ref(null)

const unwrap = (payload) => payload?.data ?? payload ?? []
const asArray = (payload) => {
  const data = unwrap(payload)
  if (Array.isArray(data?.data)) return data.data
  if (Array.isArray(data)) return data
  return []
}

const normalizeStatus = (value) => String(value || 'cho_duyet').toLowerCase()

const filteredApplications = computed(() => {
  if (activeFilter.value === 'tat_ca') return applications.value
  return applications.value.filter((item) => normalizeStatus(item.trang_thai) === activeFilter.value)
})

const formatDate = (value) => {
  if (!value) return 'Chua cap nhat'
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return 'Chua cap nhat'
  return new Intl.DateTimeFormat('vi-VN', { dateStyle: 'short', timeStyle: value.includes?.('T') ? 'short' : undefined }).format(date)
}

const statusLabel = (value) => {
  const status = normalizeStatus(value)
  if (status === 'cho_duyet') return 'Cho duyet'
  if (status === 'da_duyet') return 'Da duyet'
  if (status === 'tu_choi') return 'Tu choi'
  if (status === 'phong_van') return 'Phong van'
  return value || 'Dang xu ly'
}

const statusClass = (value) => {
  const status = normalizeStatus(value)
  if (status === 'da_duyet') return 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300'
  if (status === 'tu_choi') return 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300'
  if (status === 'phong_van') return 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300'
  return 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300'
}

const loadApplications = async () => {
  loading.value = true
  error.value = ''
  try {
    const response = await employerApplicationService.getApplications()
    applications.value = asArray(response)
  } catch (err) {
    error.value = err.message || 'Khong the tai danh sach ung vien'
  } finally {
    loading.value = false
  }
}

const updateStatus = async (application, trang_thai) => {
  updatingId.value = application.id
  error.value = ''
  success.value = ''
  try {
    await employerApplicationService.updateStatus(application.id, { trang_thai })
    success.value = 'Da cap nhat trang thai ung tuyen'
    await loadApplications()
  } catch (err) {
    error.value = err.message || 'Khong the cap nhat trang thai'
  } finally {
    updatingId.value = null
  }
}

onMounted(loadApplications)
</script>

<template>
  <div class="space-y-6">
    <div>
      <h1 class="text-3xl font-bold text-slate-900 dark:text-white">Applicants</h1>
      <p class="mt-1 text-sm text-slate-500">Xem nhanh ho so ung tuyen va cap nhat trang thai xu ly.</p>
    </div>

    <div v-if="error" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900/40 dark:bg-red-950/30 dark:text-red-300">{{ error }}</div>
    <div v-if="success" class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700 dark:border-green-900/40 dark:bg-green-950/30 dark:text-green-300">{{ success }}</div>

    <div class="flex flex-wrap gap-2">
      <button class="rounded-full px-3 py-1.5 text-sm transition" :class="activeFilter === 'tat_ca' ? 'bg-[#2463eb] text-white' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300'" @click="activeFilter = 'tat_ca'">Tat ca</button>
      <button class="rounded-full px-3 py-1.5 text-sm transition" :class="activeFilter === 'cho_duyet' ? 'bg-[#2463eb] text-white' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300'" @click="activeFilter = 'cho_duyet'">Cho duyet</button>
      <button class="rounded-full px-3 py-1.5 text-sm transition" :class="activeFilter === 'phong_van' ? 'bg-[#2463eb] text-white' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300'" @click="activeFilter = 'phong_van'">Phong van</button>
      <button class="rounded-full px-3 py-1.5 text-sm transition" :class="activeFilter === 'da_duyet' ? 'bg-[#2463eb] text-white' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300'" @click="activeFilter = 'da_duyet'">Da duyet</button>
      <button class="rounded-full px-3 py-1.5 text-sm transition" :class="activeFilter === 'tu_choi' ? 'bg-[#2463eb] text-white' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300'" @click="activeFilter = 'tu_choi'">Tu choi</button>
    </div>

    <div v-if="loading" class="rounded-2xl border border-slate-200 bg-white px-4 py-12 text-center text-sm text-slate-500 shadow-sm dark:border-slate-800 dark:bg-slate-900">Dang tai danh sach ung vien...</div>
    <div v-else-if="!filteredApplications.length" class="rounded-2xl border border-dashed border-slate-300 bg-white px-4 py-12 text-center text-sm text-slate-500 shadow-sm dark:border-slate-700 dark:bg-slate-900">Khong co ung vien nao theo bo loc hien tai.</div>

    <div v-else class="space-y-4">
      <article v-for="item in filteredApplications" :key="item.id" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
          <div class="flex-1">
            <div class="flex flex-wrap items-center gap-2">
              <span class="rounded-full px-3 py-1 text-xs font-semibold" :class="statusClass(item.trang_thai)">{{ statusLabel(item.trang_thai) }}</span>
              <span class="text-xs text-slate-400">Nop ngay {{ formatDate(item.created_at) }}</span>
            </div>
            <h2 class="mt-3 text-xl font-bold text-slate-900 dark:text-white">{{ item.ho_so?.tieu_de_ho_so || 'Ho so ung vien' }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ item.tin_tuyen_dung?.tieu_de || 'Vi tri dang cap nhat' }}</p>
            <div class="mt-3 grid gap-3 md:grid-cols-2">
              <div class="rounded-xl bg-slate-50 p-4 text-sm text-slate-600 dark:bg-slate-800/60 dark:text-slate-300">
                <p class="mb-1 font-medium text-slate-900 dark:text-white">Thu xin viec</p>
                {{ item.thu_xin_viec || 'Ung vien chua gui thu xin viec.' }}
              </div>
              <div class="rounded-xl bg-slate-50 p-4 text-sm text-slate-600 dark:bg-slate-800/60 dark:text-slate-300">
                <p class="mb-1 font-medium text-slate-900 dark:text-white">Ghi chu / lich phong van</p>
                <div>{{ item.ghi_chu || 'Chua co ghi chu.' }}</div>
                <div class="mt-2 text-xs text-slate-400">{{ item.ngay_hen_phong_van ? `Hen phong van: ${formatDate(item.ngay_hen_phong_van)}` : 'Chua hen phong van' }}</div>
              </div>
            </div>
          </div>
          <div class="flex flex-wrap gap-2 xl:w-[320px] xl:justify-end">
            <button class="rounded-lg border border-amber-200 px-3 py-2 text-sm font-medium text-amber-700 transition hover:bg-amber-50" :disabled="updatingId === item.id" @click="updateStatus(item, 'cho_duyet')">Cho duyet</button>
            <button class="rounded-lg border border-blue-200 px-3 py-2 text-sm font-medium text-blue-700 transition hover:bg-blue-50" :disabled="updatingId === item.id" @click="updateStatus(item, 'phong_van')">Phong van</button>
            <button class="rounded-lg border border-green-200 px-3 py-2 text-sm font-medium text-green-700 transition hover:bg-green-50" :disabled="updatingId === item.id" @click="updateStatus(item, 'da_duyet')">Duyet</button>
            <button class="rounded-lg border border-red-200 px-3 py-2 text-sm font-medium text-red-700 transition hover:bg-red-50" :disabled="updatingId === item.id" @click="updateStatus(item, 'tu_choi')">Tu choi</button>
          </div>
        </div>
      </article>
    </div>
  </div>
</template>
