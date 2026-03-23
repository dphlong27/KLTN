<script setup>
import { computed, onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { employerApplicationService, employerCompanyService, employerJobService } from '@/services/api'

const loading = ref(true)
const error = ref('')
const company = ref({})
const jobs = ref([])
const applications = ref([])

const unwrap = (payload) => payload?.data ?? payload ?? []
const asArray = (payload) => {
  const data = unwrap(payload)
  if (Array.isArray(data?.data)) return data.data
  if (Array.isArray(data)) return data
  return []
}

const activeJobs = computed(() => jobs.value.filter((item) => Number(item.trang_thai) === 1 || String(item.trang_thai).toLowerCase() === 'active'))
const pendingApplications = computed(() => applications.value.filter((item) => String(item.trang_thai || '').toLowerCase() === 'cho_duyet').length)
const upcomingInterviews = computed(() => applications.value.filter((item) => item.ngay_hen_phong_van))

const formatDate = (value) => {
  if (!value) return 'Chua cap nhat'
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return 'Chua cap nhat'
  return new Intl.DateTimeFormat('vi-VN').format(date)
}

const loadDashboard = async () => {
  loading.value = true
  error.value = ''
  try {
    const [companyResponse, jobsResponse, applicationsResponse] = await Promise.all([
      employerCompanyService.getMyCompany(),
      employerJobService.getJobs({ per_page: 50 }),
      employerApplicationService.getApplications()
    ])

    company.value = unwrap(companyResponse)
    jobs.value = asArray(jobsResponse)
    applications.value = asArray(applicationsResponse)
  } catch (err) {
    error.value = err.message || 'Khong the tai du lieu nha tuyen dung'
  } finally {
    loading.value = false
  }
}

onMounted(loadDashboard)
</script>

<template>
  <div class="space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
      <div>
        <h1 class="text-3xl font-black tracking-tight text-slate-900 dark:text-white">Employer Dashboard</h1>
        <p class="mt-1 text-sm text-slate-500">Tong quan cong ty, tin tuyen dung va ung vien dang duoc xu ly.</p>
      </div>
      <RouterLink to="/employer/jobs" class="inline-flex items-center gap-2 rounded-xl bg-[#2463eb] px-5 py-3 text-sm font-bold text-white shadow-lg shadow-[#2463eb]/20 transition hover:bg-[#1d4fcc]">
        <span class="material-symbols-outlined text-[20px]">add</span>
        Dang tin moi
      </RouterLink>
    </div>

    <div v-if="error" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900/40 dark:bg-red-950/30 dark:text-red-300">
      {{ error }}
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
      <div class="rounded-xl border border-slate-200 bg-white p-6 dark:border-slate-800 dark:bg-slate-900">
        <p class="text-sm text-slate-500">Tin dang hoat dong</p>
        <p class="mt-2 text-3xl font-bold text-slate-900 dark:text-white">{{ activeJobs.length }}</p>
        <p class="mt-2 text-xs text-slate-400">Tong cong {{ jobs.length }} tin da tao</p>
      </div>
      <div class="rounded-xl border border-slate-200 bg-white p-6 dark:border-slate-800 dark:bg-slate-900">
        <p class="text-sm text-slate-500">Ung vien cho duyet</p>
        <p class="mt-2 text-3xl font-bold text-amber-600">{{ pendingApplications }}</p>
        <p class="mt-2 text-xs text-slate-400">{{ applications.length }} don ung tuyen trong he thong</p>
      </div>
      <div class="rounded-xl border border-slate-200 bg-white p-6 dark:border-slate-800 dark:bg-slate-900">
        <p class="text-sm text-slate-500">Lich phong van</p>
        <p class="mt-2 text-3xl font-bold text-[#2463eb]">{{ upcomingInterviews.length }}</p>
        <p class="mt-2 text-xs text-slate-400">Da len lich phong van voi ung vien</p>
      </div>
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-[1.5fr,1fr]">
      <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="mb-5 flex items-center justify-between gap-3">
          <div>
            <h2 class="text-xl font-bold text-slate-900 dark:text-white">Tin tuyen dung gan day</h2>
            <p class="text-sm text-slate-500">Cac bai dang moi nhat cua cong ty</p>
          </div>
          <RouterLink to="/employer/jobs" class="text-sm font-medium text-[#2463eb] hover:underline">Quan ly tat ca</RouterLink>
        </div>

        <div v-if="loading" class="py-12 text-center text-sm text-slate-500">Dang tai danh sach tin...</div>
        <div v-else-if="!jobs.length" class="rounded-xl border border-dashed border-slate-300 px-4 py-10 text-center text-sm text-slate-500 dark:border-slate-700">
          Ban chua tao tin tuyen dung nao.
        </div>
        <div v-else class="space-y-3">
          <div v-for="item in jobs.slice(0, 5)" :key="item.id" class="rounded-xl border border-slate-200 px-4 py-4 dark:border-slate-800">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
              <div>
                <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ item.tieu_de }}</p>
                <p class="mt-1 text-xs text-slate-500">{{ item.dia_diem_lam_viec || 'Dang cap nhat dia diem' }} · Han nop {{ formatDate(item.ngay_het_han) }}</p>
              </div>
              <span class="rounded-full px-3 py-1 text-xs font-semibold" :class="Number(item.trang_thai) === 1 || String(item.trang_thai).toLowerCase() === 'active' ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300'">
                {{ Number(item.trang_thai) === 1 || String(item.trang_thai).toLowerCase() === 'active' ? 'Dang hoat dong' : 'Tam an' }}
              </span>
            </div>
          </div>
        </div>
      </section>

      <section class="space-y-6">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
          <h2 class="text-xl font-bold text-slate-900 dark:text-white">Thong tin cong ty</h2>
          <p class="mt-1 text-sm text-slate-500">{{ company.ten_cong_ty || 'Chua tao thong tin cong ty' }}</p>
          <div class="mt-4 space-y-3 text-sm text-slate-600 dark:text-slate-300">
            <div><span class="font-medium text-slate-900 dark:text-white">Website:</span> {{ company.website || 'Chua cap nhat' }}</div>
            <div><span class="font-medium text-slate-900 dark:text-white">Dia chi:</span> {{ company.dia_chi || 'Chua cap nhat' }}</div>
            <div><span class="font-medium text-slate-900 dark:text-white">Quy mo:</span> {{ company.quy_mo || 'Chua cap nhat' }}</div>
          </div>
          <RouterLink to="/employer/company" class="mt-5 inline-flex items-center gap-2 text-sm font-medium text-[#2463eb] hover:underline">
            Cap nhat cong ty
          </RouterLink>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
          <div class="flex items-center justify-between">
            <h2 class="text-xl font-bold text-slate-900 dark:text-white">Ung vien gan day</h2>
            <RouterLink to="/employer/candidates" class="text-sm font-medium text-[#2463eb] hover:underline">Xem tat ca</RouterLink>
          </div>
          <div class="mt-4 space-y-3">
            <div v-for="item in applications.slice(0, 4)" :key="item.id" class="rounded-xl bg-slate-50 px-4 py-3 dark:bg-slate-800/60">
              <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ item.ho_so?.tieu_de_ho_so || item.ung_vien?.ho_ten || 'Ung vien' }}</p>
              <p class="mt-1 text-xs text-slate-500">{{ item.tin_tuyen_dung?.tieu_de || 'Ung tuyen moi' }}</p>
            </div>
            <p v-if="!applications.length" class="text-sm text-slate-500">Chua co ung vien nao ung tuyen.</p>
          </div>
        </div>
      </section>
    </div>
  </div>
</template>
