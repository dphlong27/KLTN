<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { API_DOMAIN } from '@/config/api'
import {
  candidateApplicationService,
  candidateCvService,
  publicCatalogService,
  savedJobService
} from '@/services/api'

const route = useRoute()
const router = useRouter()
const loading = ref(true)
const error = ref('')
const actionMessage = ref('')
const actionLoading = ref('')
const job = ref(null)
const company = ref(null)
const relatedJobs = ref([])
const currentUser = ref(null)
const candidateCvs = ref([])

const unwrap = (payload) => payload?.data ?? payload ?? null
const asArray = (payload) => {
  const data = payload?.data ?? payload ?? []
  if (Array.isArray(data?.data)) return data.data
  if (Array.isArray(data)) return data
  return []
}

const jobIndustries = computed(() => job.value?.chi_tiet_nganh_nghes || job.value?.nganh_nghes || [])
const isCandidateAuthenticated = computed(() => Boolean(currentUser.value))
const defaultCv = computed(() => candidateCvs.value.find((item) => Number(item.trang_thai) === 1) || candidateCvs.value[0] || null)
const logoUrl = computed(() => {
  const path = company.value?.logo
  if (!path) return ''
  if (String(path).startsWith('http')) return path
  return `${API_DOMAIN}${String(path).startsWith('/') ? path : `/storage/${path}`}`
})

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

const loadCandidateCvs = async () => {
  if (!isCandidateAuthenticated.value) {
    candidateCvs.value = []
    return
  }

  try {
    const response = await candidateCvService.getCvs({ per_page: 50 })
    candidateCvs.value = asArray(response)
  } catch (_) {
    candidateCvs.value = []
  }
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

const formatDate = (value) => {
  if (!value) return 'Chưa cập nhật'
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return 'Chưa cập nhật'
  return new Intl.DateTimeFormat('vi-VN', { dateStyle: 'long' }).format(date)
}

const skillGapList = computed(() => {
  const source = job.value?.danh_sach_ky_nang_thieu || job.value?.ky_nang_yeu_cau || ''
  if (Array.isArray(source)) return source
  return String(source)
    .split(',')
    .map((item) => item.trim())
    .filter(Boolean)
    .slice(0, 6)
})

const requireCandidateAuth = async () => {
  actionMessage.value = ''
  error.value = ''

  if (isCandidateAuthenticated.value) {
    return true
  }

  await router.push('/auth')
  return false
}

const saveCurrentJob = async () => {
  if (!(await requireCandidateAuth()) || !job.value?.id) return

  actionLoading.value = 'save'
  try {
    await savedJobService.toggleSavedJob(job.value.id)
    actionMessage.value = 'Đã cập nhật trạng thái lưu tin.'
  } catch (err) {
    error.value = err.message || 'Không thể lưu tin tuyển dụng'
  } finally {
    actionLoading.value = ''
  }
}

const applyCurrentJob = async () => {
  if (!(await requireCandidateAuth()) || !job.value?.id) return

  if (!defaultCv.value?.id) {
    error.value = 'Bạn cần tạo ít nhất một hồ sơ trước khi ứng tuyển.'
    await router.push('/my-cv')
    return
  }

  actionLoading.value = 'apply'
  try {
    await candidateApplicationService.createApplication({
      ho_so_id: defaultCv.value.id,
      tin_tuyen_dung_id: job.value.id
    })
    actionMessage.value = `Ứng tuyển thành công bằng hồ sơ "${defaultCv.value.tieu_de_ho_so}".`
  } catch (err) {
    error.value = err.message || 'Không thể nộp hồ sơ ứng tuyển'
  } finally {
    actionLoading.value = ''
  }
}

const loadPage = async () => {
  loading.value = true
  error.value = ''

  try {
    const jobResponse = await publicCatalogService.getJobById(route.params.id)
    const fetchedJob = unwrap(jobResponse)
    job.value = fetchedJob
    company.value = fetchedJob?.cong_ty || fetchedJob?.company || null

    const jobsResponse = await publicCatalogService.getJobs({ per_page: 12 })
    relatedJobs.value = asArray(jobsResponse)
      .filter((item) => Number(item.id) !== Number(route.params.id))
      .filter((item) => !company.value?.id || Number(item.cong_ty_id) === Number(company.value.id) || item.chi_tiet_nganh_nghes?.some((industry) => jobIndustries.value.some((current) => Number(current.nganh_nghe_id || current.id) === Number(industry.nganh_nghe_id || industry.id))))
      .slice(0, 4)
  } catch (err) {
    error.value = err.message || 'Không thể tải chi tiết tin tuyển dụng'
  } finally {
    loading.value = false
  }
}

const handleAuthUpdated = async () => {
  loadStoredUser()
  await loadCandidateCvs()
}

onMounted(async () => {
  loadStoredUser()
  await Promise.all([loadPage(), loadCandidateCvs()])
  window.addEventListener('auth-user-updated', handleAuthUpdated)
})

onUnmounted(() => {
  window.removeEventListener('auth-user-updated', handleAuthUpdated)
})
</script>

<template>
  <div class="mx-auto max-w-7xl px-4 py-8">
    <div v-if="actionMessage" class="mb-5 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700 dark:border-green-900/40 dark:bg-green-950/30 dark:text-green-300">
      {{ actionMessage }}
    </div>

    <div v-if="error" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900/40 dark:bg-red-950/30 dark:text-red-300">
      {{ error }}
    </div>

    <div v-if="loading" class="rounded-2xl border border-slate-200 bg-white px-4 py-16 text-center text-sm text-slate-500 shadow-sm dark:border-slate-800 dark:bg-slate-900">
      Đang tải chi tiết tin tuyển dụng...
    </div>

    <div v-else-if="job" class="grid grid-cols-1 gap-8 lg:grid-cols-12">
      <div class="space-y-6 lg:col-span-8">
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900 md:p-8">
          <div class="flex flex-col gap-6 md:flex-row md:items-start">
            <div class="flex h-20 w-20 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-slate-100 dark:bg-slate-800">
              <img v-if="logoUrl" :src="logoUrl" alt="Company logo" class="h-full w-full object-cover" />
              <span v-else class="material-symbols-outlined text-4xl text-slate-400">domain</span>
            </div>
            <div class="min-w-0 flex-1">
              <div class="flex flex-wrap items-center gap-2">
                <span class="rounded-full bg-[#2463eb]/10 px-2.5 py-1 text-xs font-semibold text-[#2463eb]">{{ job.hinh_thuc_lam_viec || 'Dang cap nhat' }}</span>
                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs text-slate-500 dark:bg-slate-800">Han nop {{ formatDate(job.ngay_het_han) }}</span>
              </div>
              <h1 class="mt-3 text-3xl font-black tracking-tight text-slate-900 dark:text-white">{{ job.tieu_de }}</h1>
              <div class="mt-3 flex flex-wrap items-center gap-x-5 gap-y-2 text-sm text-slate-600 dark:text-slate-300">
                <span class="font-semibold text-[#2463eb]">{{ company?.ten_cong_ty || 'Công ty đang cập nhật' }}</span>
                <span class="flex items-center gap-1.5"><span class="material-symbols-outlined text-[18px] text-slate-400">location_on</span>{{ job.dia_diem_lam_viec || 'Đang cập nhật địa điểm' }}</span>
                <span class="flex items-center gap-1.5"><span class="material-symbols-outlined text-[18px] text-slate-400">payments</span>{{ formatCurrency(job.muc_luong) }}</span>
              </div>

              <div class="mt-6 grid grid-cols-2 gap-4 border-y border-slate-100 py-5 dark:border-slate-800 md:grid-cols-4">
                <div>
                  <p class="text-xs font-bold uppercase tracking-wider text-slate-400">So luong</p>
                  <p class="mt-1 font-semibold text-slate-900 dark:text-white">{{ job.so_luong_tuyen || 1 }} vị trí</p>
                </div>
                <div>
                  <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Kinh nghiệm</p>
                  <p class="mt-1 font-semibold text-slate-900 dark:text-white">{{ job.kinh_nghiem_yeu_cau || 'Đang cập nhật' }}</p>
                </div>
                <div>
                  <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Lượt xem</p>
                  <p class="mt-1 font-semibold text-slate-900 dark:text-white">{{ job.luot_xem || 0 }}</p>
                </div>
                <div>
                  <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Ngày đăng</p>
                  <p class="mt-1 font-semibold text-slate-900 dark:text-white">{{ formatDate(job.created_at) }}</p>
                </div>
              </div>
            </div>
          </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900 md:p-8">
          <h2 class="text-xl font-bold text-slate-900 dark:text-white">Mô tả công việc</h2>
          <p class="mt-4 whitespace-pre-line text-sm leading-7 text-slate-600 dark:text-slate-300">{{ job.mo_ta_cong_viec || 'Mô tả công việc đang được cập nhật.' }}</p>

          <h3 class="mt-8 text-lg font-bold text-slate-900 dark:text-white">Thông tin chi tiết</h3>
          <p class="mt-3 whitespace-pre-line text-sm leading-7 text-slate-600 dark:text-slate-300">{{ job.mo_ta_chi_tiet || 'Nội dung chi tiết đang được cập nhật.' }}</p>

          <div class="mt-8">
            <h3 class="text-sm font-bold uppercase tracking-widest text-slate-400">Ngành nghề liên quan</h3>
            <div class="mt-4 flex flex-wrap gap-2">
              <span v-for="industry in jobIndustries" :key="industry.id || industry.nganh_nghe_id" class="rounded-xl border border-[#2463eb]/20 bg-[#2463eb]/10 px-3 py-1.5 text-sm font-medium text-[#2463eb]">
                {{ industry.nganh_nghe?.ten_nganh || industry.ten_nganh || 'Ngành nghề' }}
              </span>
              <span v-if="!jobIndustries.length" class="text-sm text-slate-500">Chưa cập nhật ngành nghề.</span>
            </div>
          </div>
        </section>

        <section v-if="relatedJobs.length" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900 md:p-8">
          <div class="flex items-center justify-between gap-3">
            <h2 class="text-xl font-bold text-slate-900 dark:text-white">Tin tuyển dụng liên quan</h2>
            <RouterLink to="/jobs" class="text-sm font-medium text-[#2463eb] hover:underline">Xem thêm</RouterLink>
          </div>
          <div class="mt-5 grid gap-4 md:grid-cols-2">
            <RouterLink v-for="item in relatedJobs" :key="item.id" :to="`/jobs/${item.id}`" class="rounded-xl border border-slate-200 p-4 transition hover:border-[#2463eb]/40 dark:border-slate-800">
              <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ item.tieu_de }}</p>
              <p class="mt-1 text-sm text-slate-500">{{ item.cong_ty?.ten_cong_ty || 'ông ty đang được cập nhật' }}</p>
              <div class="mt-3 flex flex-wrap gap-2 text-xs text-slate-500">
                <span class="rounded-full bg-slate-100 px-2.5 py-1 dark:bg-slate-800">{{ item.dia_diem_lam_viec || 'Đang được cập nhật' }}</span>
                <span class="rounded-full bg-slate-100 px-2.5 py-1 dark:bg-slate-800">{{ formatCurrency(item.muc_luong) }}</span>
              </div>
            </RouterLink>
          </div>
        </section>
      </div>

      <aside class="space-y-6 lg:col-span-4">
        <section class="overflow-hidden rounded-2xl border-2 border-[#2463eb]/30 bg-white shadow-lg dark:bg-slate-900">
          <div class="bg-[#2463eb] p-6 text-white">
            <h2 class="text-lg font-bold">Thông tin ứng tuyển</h2>
            <p class="mt-1 text-sm text-blue-100">{{ isCandidateAuthenticated ? `Sẵn sàng nộp bằng ${candidateCvs.length} hồ sơ của bạn.` : 'Đăng nhập tài khoản ứng viên để lưu tin và nộp hồ sơ.' }}</p>
          </div>
          <div class="space-y-5 p-6">
            <div class="space-y-3 text-sm text-slate-600 dark:text-slate-300">
              <div class="flex items-start gap-3 rounded-xl bg-slate-50 p-4 dark:bg-slate-800/60">
                <span class="material-symbols-outlined text-[#2463eb]">payments</span>
                <div>
                  <p class="font-semibold text-slate-900 dark:text-white">Mức lương</p>
                  <p>{{ formatCurrency(job.muc_luong) }}</p>
                </div>
              </div>
              <div class="flex items-start gap-3 rounded-xl bg-slate-50 p-4 dark:bg-slate-800/60">
                <span class="material-symbols-outlined text-[#2463eb]">event</span>
                <div>
                  <p class="font-semibold text-slate-900 dark:text-white">Hạn nộp hồ sơ</p>
                  <p>{{ formatDate(job.ngay_het_han) }}</p>
                </div>
              </div>
              <div class="flex items-start gap-3 rounded-xl bg-slate-50 p-4 dark:bg-slate-800/60">
                <span class="material-symbols-outlined text-[#2463eb]">domain</span>
                <div>
                  <p class="font-semibold text-slate-900 dark:text-white">Công ty</p>
                  <p>{{ company?.ten_cong_ty || 'Đang được cập nhật' }}</p>
                  <p class="mt-1 text-xs text-slate-400">{{ company?.website || company?.dia_chi || 'Chưa có thông tin thêm' }}</p>
                </div>
              </div>
            </div>

            <div>
              <h3 class="text-sm font-bold text-slate-900 dark:text-white">Kỹ năng / từ khóa nên có</h3>
              <div class="mt-3 flex flex-wrap gap-2">
                <span v-for="skill in skillGapList" :key="skill" class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-medium text-amber-700 dark:bg-amber-900/20 dark:text-amber-300">{{ skill }}</span>
                <span v-if="!skillGapList.length" class="text-sm text-slate-500">Chưa có danh sách từ khóa bổ sung.</span>
              </div>
            </div>

            <div class="space-y-3">
              <button class="flex w-full items-center justify-center gap-2 rounded-xl bg-[#2463eb] px-4 py-3 text-sm font-bold text-white transition hover:bg-[#1d4fcc]" :disabled="actionLoading === 'apply'" @click="applyCurrentJob">
                Ứng tuyển ngay
                <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
              </button>
              <button class="block w-full rounded-xl border border-slate-200 px-4 py-3 text-center text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800" :disabled="actionLoading === 'save'" @click="saveCurrentJob">
                Lưu tin
              </button>
            </div>
          </div>
        </section>
      </aside>
    </div>
  </div>
</template>
