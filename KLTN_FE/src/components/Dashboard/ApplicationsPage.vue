<script setup>
import { computed, onMounted, ref } from 'vue'
import { candidateApplicationService } from '@/services/api'

const loading = ref(true)
const error = ref('')
const applications = ref([])
const activeFilter = ref('tat_ca')

const unwrap = (payload) => payload?.data ?? payload ?? []
const asArray = (payload) => {
  const data = unwrap(payload)
  if (Array.isArray(data?.data)) return data.data
  if (Array.isArray(data)) return data
  return []
}

const normalizeStatus = (value) => String(value || 'cho_duyet').toLowerCase()

const statusLabel = (value) => {
  const status = normalizeStatus(value)
  if (status === 'cho_duyet') return 'Cho duyet'
  if (status === 'da_duyet' || status === 'chap_nhan' || status === 'accepted') return 'Da duyet'
  if (status === 'tu_choi' || status === 'rejected') return 'Tu choi'
  if (status === 'phong_van') return 'Phong van'
  return value || 'Dang xu ly'
}

const statusClass = (value) => {
  const status = normalizeStatus(value)
  if (status === 'da_duyet' || status === 'chap_nhan' || status === 'accepted') return 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300'
  if (status === 'tu_choi' || status === 'rejected') return 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300'
  if (status === 'phong_van') return 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300'
  return 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300'
}

const filteredApplications = computed(() => {
  if (activeFilter.value === 'tat_ca') return applications.value
  return applications.value.filter((item) => normalizeStatus(item.trang_thai) === activeFilter.value)
})

const totals = computed(() => ({
  all: applications.value.length,
  cho_duyet: applications.value.filter((item) => normalizeStatus(item.trang_thai) === 'cho_duyet').length,
  da_duyet: applications.value.filter((item) => ['da_duyet', 'chap_nhan', 'accepted'].includes(normalizeStatus(item.trang_thai))).length,
  tu_choi: applications.value.filter((item) => ['tu_choi', 'rejected'].includes(normalizeStatus(item.trang_thai))).length
}))

const formatDate = (value) => {
  if (!value) return 'Chua cap nhat'
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return 'Chua cap nhat'
  return new Intl.DateTimeFormat('vi-VN').format(date)
}

const loadApplications = async () => {
  loading.value = true
  error.value = ''

  try {
    const response = await candidateApplicationService.getApplications()
    applications.value = asArray(response)
  } catch (err) {
    error.value = err.message || 'Khong the tai danh sach ung tuyen'
  } finally {
    loading.value = false
  }
}

onMounted(loadApplications)
</script>

<template>
  <div class="space-y-6">
    <div>
      <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Viec da ung tuyen</h1>
      <p class="mt-1 text-sm text-slate-500">Theo doi trang thai tung don ung tuyen va lich phong van neu co.</p>
    </div>

    <div v-if="error" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900/40 dark:bg-red-950/30 dark:text-red-300">
      {{ error }}
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
      <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <p class="text-sm text-slate-500">Tong ung tuyen</p>
        <p class="mt-2 text-3xl font-bold text-slate-900 dark:text-white">{{ totals.all }}</p>
      </div>
      <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <p class="text-sm text-slate-500">Cho duyet</p>
        <p class="mt-2 text-3xl font-bold text-amber-600">{{ totals.cho_duyet }}</p>
      </div>
      <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <p class="text-sm text-slate-500">Da duyet</p>
        <p class="mt-2 text-3xl font-bold text-green-600">{{ totals.da_duyet }}</p>
      </div>
      <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <p class="text-sm text-slate-500">Tu choi</p>
        <p class="mt-2 text-3xl font-bold text-red-600">{{ totals.tu_choi }}</p>
      </div>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
      <div class="flex flex-wrap gap-2 border-b border-slate-200 px-4 py-3 dark:border-slate-800">
        <button class="rounded-full px-3 py-1.5 text-sm transition" :class="activeFilter === 'tat_ca' ? 'bg-[#2463eb] text-white' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300'" @click="activeFilter = 'tat_ca'">Tat ca</button>
        <button class="rounded-full px-3 py-1.5 text-sm transition" :class="activeFilter === 'cho_duyet' ? 'bg-[#2463eb] text-white' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300'" @click="activeFilter = 'cho_duyet'">Cho duyet</button>
        <button class="rounded-full px-3 py-1.5 text-sm transition" :class="activeFilter === 'da_duyet' ? 'bg-[#2463eb] text-white' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300'" @click="activeFilter = 'da_duyet'">Da duyet</button>
        <button class="rounded-full px-3 py-1.5 text-sm transition" :class="activeFilter === 'tu_choi' ? 'bg-[#2463eb] text-white' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300'" @click="activeFilter = 'tu_choi'">Tu choi</button>
      </div>

      <div v-if="loading" class="px-4 py-12 text-center text-sm text-slate-500">Dang tai danh sach ung tuyen...</div>
      <div v-else-if="!filteredApplications.length" class="px-4 py-12 text-center text-sm text-slate-500">Khong co don ung tuyen nao theo bo loc hien tai.</div>
      <div v-else class="divide-y divide-slate-100 dark:divide-slate-800">
        <article v-for="item in filteredApplications" :key="item.id" class="p-5">
          <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
              <h2 class="text-lg font-semibold text-slate-900 dark:text-white">{{ item.tin_tuyen_dung?.tieu_de || item.tieu_de || 'Don ung tuyen' }}</h2>
              <p class="mt-1 text-sm text-slate-500">{{ item.tin_tuyen_dung?.cong_ty?.ten_cong_ty || item.ten_cong_ty || 'Cong ty dang cap nhat' }}</p>
              <div class="mt-2 flex flex-wrap gap-3 text-xs text-slate-400">
                <span>Nop ngay {{ formatDate(item.created_at) }}</span>
                <span>Ho so: {{ item.ho_so?.tieu_de_ho_so || item.tieu_de_ho_so || 'Chua ro' }}</span>
                <span v-if="item.ngay_hen_phong_van">Phong van: {{ formatDate(item.ngay_hen_phong_van) }}</span>
              </div>
            </div>
            <div class="flex items-center gap-3">
              <span class="rounded-full px-3 py-1 text-xs font-semibold" :class="statusClass(item.trang_thai)">{{ statusLabel(item.trang_thai) }}</span>
            </div>
          </div>
          <div class="mt-4 grid gap-3 md:grid-cols-2" v-if="item.thu_xin_viec || item.ghi_chu">
            <div v-if="item.thu_xin_viec" class="rounded-xl bg-slate-50 p-4 text-sm text-slate-600 dark:bg-slate-800/60 dark:text-slate-300">
              <p class="mb-1 font-medium text-slate-900 dark:text-white">Thu xin viec</p>
              {{ item.thu_xin_viec }}
            </div>
            <div v-if="item.ghi_chu" class="rounded-xl bg-slate-50 p-4 text-sm text-slate-600 dark:bg-slate-800/60 dark:text-slate-300">
              <p class="mb-1 font-medium text-slate-900 dark:text-white">Ghi chu</p>
              {{ item.ghi_chu }}
            </div>
          </div>
        </article>
      </div>
    </div>
  </div>
</template>
