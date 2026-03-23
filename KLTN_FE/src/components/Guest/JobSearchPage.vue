<script setup>
import { computed, onMounted, onUnmounted, reactive, ref, watch } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import {
  candidateApplicationService,
  candidateCvService,
  publicCatalogService,
  savedJobService
} from '@/services/api'

const router = useRouter()
const route = useRoute()
const loading = ref(true)
const error = ref('')
const jobs = ref([])
const industries = ref([])
const currentUser = ref(null)
const candidateCvs = ref([])
const actionMessage = ref('')
const actionLoadingId = ref('')
const filters = reactive({
  keyword: '',
  location: '',
  industryId: '',
  experience: '',
  sortBy: 'newest'
})

const syncFiltersFromRoute = () => {
  filters.keyword = String(route.query.search || '')
  filters.location = String(route.query.dia_diem || '')
  filters.industryId = String(route.query.nganh_nghe_id || '')
}

const unwrap = (payload) => payload?.data ?? payload ?? []
const asArray = (payload) => {
  const data = unwrap(payload)
  if (Array.isArray(data?.data)) return data.data
  if (Array.isArray(data)) return data
  return []
}

const jobCompany = (job) => job.cong_ty || job.company || {}
const jobIndustries = (job) => job.chi_tiet_nganh_nghes || job.nganh_nghes || []
const isCandidateAuthenticated = computed(() => Boolean(currentUser.value))
const defaultCv = computed(() => candidateCvs.value.find((item) => Number(item.trang_thai) === 1) || candidateCvs.value[0] || null)

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
  if (!value) return 'Chua cap nhat'
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return 'Chua cap nhat'
  return new Intl.DateTimeFormat('vi-VN').format(date)
}

const normalizeText = (value) => String(value || '').toLowerCase().trim()

const filteredJobs = computed(() => {
  const keyword = normalizeText(filters.keyword)
  const location = normalizeText(filters.location)

  let items = jobs.value.filter((job) => {
    const matchesKeyword = !keyword || [
      job.tieu_de,
      job.mo_ta_cong_viec,
      job.mo_ta_chi_tiet,
      jobCompany(job).ten_cong_ty
    ].some((value) => normalizeText(value).includes(keyword))

    const matchesLocation = !location || normalizeText(job.dia_diem_lam_viec).includes(location)

    const matchesIndustry = !filters.industryId || jobIndustries(job).some((item) => {
      const industryId = item.nganh_nghe_id || item.id || item.nganh_nghe?.id
      return Number(industryId) === Number(filters.industryId)
    })

    const matchesExperience = !filters.experience || normalizeText(job.kinh_nghiem_yeu_cau).includes(normalizeText(filters.experience))

    return matchesKeyword && matchesLocation && matchesIndustry && matchesExperience
  })

  if (filters.sortBy === 'salary') {
    items = items.sort((a, b) => Number(b.muc_luong || 0) - Number(a.muc_luong || 0))
  } else if (filters.sortBy === 'deadline') {
    items = items.sort((a, b) => new Date(a.ngay_het_han || 0) - new Date(b.ngay_het_han || 0))
  } else {
    items = items.sort((a, b) => new Date(b.created_at || 0) - new Date(a.created_at || 0))
  }

  return items
})

const resetFilters = () => {
  filters.keyword = ''
  filters.location = ''
  filters.industryId = ''
  filters.experience = ''
  filters.sortBy = 'newest'
}

const requireCandidateAuth = async () => {
  actionMessage.value = ''
  error.value = ''

  if (isCandidateAuthenticated.value) {
    return true
  }

  await router.push('/auth')
  return false
}

const saveJob = async (jobId) => {
  if (!(await requireCandidateAuth())) return

  actionLoadingId.value = `save-${jobId}`
  try {
    await savedJobService.toggleSavedJob(jobId)
    actionMessage.value = 'Da cap nhat trang thai luu tin.'
  } catch (err) {
    error.value = err.message || 'Khong the luu tin tuyen dung'
  } finally {
    actionLoadingId.value = ''
  }
}

const applyJob = async (jobId) => {
  if (!(await requireCandidateAuth())) return

  if (!defaultCv.value?.id) {
    error.value = 'Ban can tao it nhat mot ho so truoc khi ung tuyen.'
    await router.push('/my-cv')
    return
  }

  actionLoadingId.value = `apply-${jobId}`
  try {
    await candidateApplicationService.createApplication({
      ho_so_id: defaultCv.value.id,
      tin_tuyen_dung_id: jobId
    })
    actionMessage.value = `Ung tuyen thanh cong bang ho so "${defaultCv.value.tieu_de_ho_so}".`
  } catch (err) {
    error.value = err.message || 'Khong the nop ho so ung tuyen'
  } finally {
    actionLoadingId.value = ''
  }
}

const loadPageData = async () => {
  loading.value = true
  error.value = ''
  
  try {
    const [jobsResponse, industriesResponse] = await Promise.all([
      publicCatalogService.getJobs({
        per_page: 100,
        search: filters.keyword || undefined,
        nganh_nghe_id: filters.industryId || undefined,
        dia_diem: filters.location || undefined
      }),
      publicCatalogService.getIndustries()
    ])

    jobs.value = asArray(jobsResponse)
    industries.value = asArray(industriesResponse)
  } catch (err) {
    error.value = err.message || 'Khong the tai danh sach tin tuyen dung'
  } finally {
    loading.value = false
  }
}

const handleAuthUpdated = async () => {
  loadStoredUser()
  await loadCandidateCvs()
}

onMounted(async () => {
  syncFiltersFromRoute()
  loadStoredUser()
  await Promise.all([loadPageData(), loadCandidateCvs()])
  window.addEventListener('auth-user-updated', handleAuthUpdated)
})

onUnmounted(() => {
  window.removeEventListener('auth-user-updated', handleAuthUpdated)
})

watch(
  () => route.query,
  async () => {
    syncFiltersFromRoute()
    await loadPageData()
  }
)
</script>

<template>
  <div class="mx-auto max-w-7xl px-4 py-8">
    <div v-if="actionMessage" class="mb-5 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700 dark:border-green-900/40 dark:bg-green-950/30 dark:text-green-300">
      {{ actionMessage }}
    </div>

    <div class="flex flex-col gap-8 lg:flex-row">
      <aside class="w-full shrink-0 lg:w-72">
        <div class="sticky top-24 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
          <div class="mb-5 flex items-center justify-between">
            <h2 class="text-lg font-bold text-slate-900 dark:text-white">Bộ lọc</h2>
            <button class="text-sm font-medium text-[#2463eb] hover:underline" @click="resetFilters">Xóa bộ lọc</button>
          </div>

          <div class="space-y-5">
            <label class="block space-y-2">
              <span class="text-sm font-medium text-slate-700 dark:text-slate-200">Ngành nghề</span>
              <select v-model="filters.industryId" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-800/60">
                <option value="">Tất cả ngành nghề</option>
                <option v-for="industry in industries" :key="industry.id" :value="industry.id">{{ industry.ten_nganh }}</option>
              </select>
            </label>

            <label class="block space-y-2">
              <span class="text-sm font-medium text-slate-700 dark:text-slate-200">Kinh nghiệm</span>
              <input v-model="filters.experience" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-800/60" type="text" placeholder="Ví dụ: 1 năm, 3 năm..." />
            </label>

            <label class="block space-y-2">
              <span class="text-sm font-medium text-slate-700 dark:text-slate-200">Sắp xếp</span>
              <select v-model="filters.sortBy" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-800/60">
                <option value="newest">Mới nhất</option>
                <option value="salary">Lương cao nhất</option>
                <option value="deadline">Hạn nộp gần nhất</option>
              </select>
            </label>

            <div class="rounded-xl bg-slate-50 p-4 text-sm text-slate-600 dark:bg-slate-800/60 dark:text-slate-300">
              <p class="font-semibold text-slate-900 dark:text-white">{{ filteredJobs.length }} kết quả</p>
              <p class="mt-1">{{ isCandidateAuthenticated ? `Đang sẵn sàng ứng tuyển với ${candidateCvs.length} hồ sơ.` : 'Đăng nhập ứng viên để lưu tin và ứng tuyển nhanh.' }}</p>
            </div>
          </div>
        </div>
      </aside>

      <section class="min-w-0 flex-1 space-y-5">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
          <div>
            <h2 class="text-2xl font-bold text-slate-900 dark:text-white">Danh sách tin tuyển dụng</h2>
            <p class="text-sm text-slate-500">{{ filteredJobs.length }} vị trí đang mở tuyển</p>
          </div>
        </div>

        <div v-if="error" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900/40 dark:bg-red-950/30 dark:text-red-300">
          {{ error }}
        </div>

        <div v-if="loading" class="rounded-2xl border border-slate-200 bg-white px-4 py-14 text-center text-sm text-slate-500 shadow-sm dark:border-slate-800 dark:bg-slate-900">
          Đang tải danh sách tin tuyển dụng...
        </div>

        <div v-else-if="!filteredJobs.length" class="rounded-2xl border border-dashed border-slate-300 bg-white px-4 py-14 text-center text-sm text-slate-500 shadow-sm dark:border-slate-700 dark:bg-slate-900">
          Không tìm thấy tin tuyển dụng phù hợp với bộ lọc hiện tại.
        </div>

        <div v-else class="space-y-4">
          <article v-for="job in filteredJobs" :key="job.id" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-[#2463eb]/40 hover:shadow-md dark:border-slate-800 dark:bg-slate-900">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
              <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                  <span class="rounded-full bg-[#2463eb]/10 px-2.5 py-1 text-xs font-semibold text-[#2463eb]">{{ job.hinh_thuc_lam_viec || 'Đang cập nhật' }}</span>
                  <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs text-slate-500 dark:bg-slate-800">Hạn nộp {{ formatDate(job.ngay_het_han) }}</span>
                </div>
                <RouterLink :to="`/jobs/${job.id}`" class="mt-3 block text-xl font-bold text-slate-900 transition hover:text-[#2463eb] dark:text-white">
                  {{ job.tieu_de }}
                </RouterLink>
                <p class="mt-1 text-sm font-medium text-[#2463eb]">{{ jobCompany(job).ten_cong_ty || 'Công ty đang cập nhật' }}</p>

                <div class="mt-4 flex flex-wrap gap-x-5 gap-y-2 text-sm text-slate-600 dark:text-slate-300">
                  <span class="flex items-center gap-1.5"><span class="material-symbols-outlined text-[18px] text-slate-400">location_on</span>{{ job.dia_diem_lam_viec || 'Địa điểm đang cập nhật' }}</span>
                  <span class="flex items-center gap-1.5"><span class="material-symbols-outlined text-[18px] text-slate-400">payments</span>{{ formatCurrency(job.muc_luong) }}</span>
                  <span class="flex items-center gap-1.5"><span class="material-symbols-outlined text-[18px] text-slate-400">groups</span>{{ job.so_luong_tuyen || 1 }} vị trí</span>
                  <span class="flex items-center gap-1.5"><span class="material-symbols-outlined text-[18px] text-slate-400">history</span>{{ job.kinh_nghiem_yeu_cau || 'Kinh nghiệm đang cập nhật' }}</span>
                </div>

                <div class="mt-4 flex flex-wrap gap-2">
                  <span v-for="industry in jobIndustries(job).slice(0, 4)" :key="industry.id || industry.nganh_nghe_id" class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                    {{ industry.nganh_nghe?.ten_nganh || industry.ten_nganh || 'Ngành nghề' }}
                  </span>
                </div>

                <p class="mt-4 line-clamp-2 text-sm text-slate-600 dark:text-slate-300">{{ job.mo_ta_cong_viec || job.mo_ta_chi_tiet || 'Mô tả công việc đang được cập nhật.' }}</p>
              </div>

              <div class="flex shrink-0 items-center gap-2 lg:ml-4">
                <RouterLink :to="`/jobs/${job.id}`" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">
                  Xem chi tiết
                </RouterLink>
                <button class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800" :disabled="actionLoadingId === `save-${job.id}`" @click="saveJob(job.id)">
                  Lưu tin
                </button>
                <button class="rounded-xl bg-[#2463eb] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#1d4fcc]" :disabled="actionLoadingId === `apply-${job.id}`" @click="applyJob(job.id)">
                  Ứng tuyển
                </button>
              </div>
            </div>
          </article>
        </div>
      </section>
    </div>
  </div>
</template>

