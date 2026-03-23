<script setup>
import { computed, onMounted, ref } from 'vue'
import { employerApplicationService } from '@/services/api'

const loading = ref(true)
const error = ref('')
const applications = ref([])

const unwrap = (payload) => payload?.data ?? payload ?? []
const asArray = (payload) => {
  const data = unwrap(payload)
  if (Array.isArray(data?.data)) return data.data
  if (Array.isArray(data)) return data
  return []
}

const interviews = computed(() => applications.value
  .filter((item) => item.ngay_hen_phong_van)
  .sort((a, b) => new Date(a.ngay_hen_phong_van) - new Date(b.ngay_hen_phong_van)))

const formatDateTime = (value) => {
  if (!value) return 'Chua xep lich'
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return 'Chua xep lich'
  return new Intl.DateTimeFormat('vi-VN', { dateStyle: 'full', timeStyle: 'short' }).format(date)
}

const loadInterviews = async () => {
  loading.value = true
  error.value = ''
  try {
    const response = await employerApplicationService.getApplications()
    applications.value = asArray(response)
  } catch (err) {
    error.value = err.message || 'Khong the tai lich phong van'
  } finally {
    loading.value = false
  }
}

onMounted(loadInterviews)
</script>

<template>
  <div class="space-y-6">
    <div>
      <h1 class="text-3xl font-bold text-slate-900 dark:text-white">Interviews</h1>
      <p class="mt-1 text-sm text-slate-500">Danh sach cac ung vien da duoc dat lich phong van tu he thong ung tuyen.</p>
    </div>

    <div v-if="error" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900/40 dark:bg-red-950/30 dark:text-red-300">{{ error }}</div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
      <div class="rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900">
        <p class="text-sm text-slate-500">Tong lich phong van</p>
        <p class="mt-2 text-3xl font-bold text-slate-900 dark:text-white">{{ interviews.length }}</p>
      </div>
      <div class="rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900">
        <p class="text-sm text-slate-500">Hom nay</p>
        <p class="mt-2 text-3xl font-bold text-[#2463eb]">{{ interviews.filter((item) => new Date(item.ngay_hen_phong_van).toDateString() === new Date().toDateString()).length }}</p>
      </div>
      <div class="rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900">
        <p class="text-sm text-slate-500">Sap toi</p>
        <p class="mt-2 text-3xl font-bold text-green-600">{{ interviews.filter((item) => new Date(item.ngay_hen_phong_van) > new Date()).length }}</p>
      </div>
    </div>

    <div v-if="loading" class="rounded-2xl border border-slate-200 bg-white px-4 py-12 text-center text-sm text-slate-500 shadow-sm dark:border-slate-800 dark:bg-slate-900">Dang tai lich phong van...</div>
    <div v-else-if="!interviews.length" class="rounded-2xl border border-dashed border-slate-300 bg-white px-4 py-12 text-center text-sm text-slate-500 shadow-sm dark:border-slate-700 dark:bg-slate-900">Chua co ung vien nao duoc hen phong van.</div>

    <div v-else class="space-y-4">
      <article v-for="item in interviews" :key="item.id" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
          <div>
            <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ item.ho_so?.tieu_de_ho_so || 'Ung vien' }}</p>
            <p class="mt-1 text-sm text-slate-500">{{ item.tin_tuyen_dung?.tieu_de || 'Vi tri dang cap nhat' }}</p>
            <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">{{ formatDateTime(item.ngay_hen_phong_van) }}</p>
          </div>
          <div class="rounded-xl bg-slate-50 px-4 py-3 text-sm text-slate-600 dark:bg-slate-800/60 dark:text-slate-300">
            <p class="font-medium text-slate-900 dark:text-white">Ket qua / ghi chu</p>
            <p class="mt-1">{{ item.ket_qua_phong_van || item.ghi_chu || 'Chua co cap nhat sau phong van.' }}</p>
          </div>
        </div>
      </article>
    </div>
  </div>
</template>
