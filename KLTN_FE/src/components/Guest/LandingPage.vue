<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { publicCatalogService } from '@/services/api'
import { API_DOMAIN } from '@/config/api'

const router = useRouter()
const loading = ref(true)
const error = ref('')
const jobs = ref([])
const companies = ref([])
const industries = ref([])
const searchForm = reactive({
  keyword: '',
  location: ''
})

const unwrap = (payload) => payload?.data ?? payload ?? []
const asArray = (payload) => {
  const data = unwrap(payload)
  if (Array.isArray(data?.data)) return data.data
  if (Array.isArray(data)) return data
  return []
}

const featuredJobs = computed(() => [...jobs.value].sort((a, b) => Number(b.luot_xem || 0) - Number(a.luot_xem || 0)).slice(0, 3))
const latestJobs = computed(() => [...jobs.value].sort((a, b) => new Date(b.created_at || 0) - new Date(a.created_at || 0)).slice(0, 6))
const highlightedCompanies = computed(() => companies.value.slice(0, 4))
const highlightedIndustries = computed(() => industries.value.slice(0, 8))
const totalOpenJobs = computed(() => jobs.value.length)
const totalCompanies = computed(() => companies.value.length)

const normalizeIndustries = (job) => job.nganh_nghes || job.chi_tiet_nganh_nghes || []
const getCompany = (job) => job.cong_ty || job.company || {}

const companyLogoUrl = (company) => {
  const path = company?.logo
  if (!path) return ''
  if (String(path).startsWith('http')) return path
  return `${API_DOMAIN}${String(path).startsWith('/') ? path : `/storage/${path}`}`
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

const formatCompactCurrency = (value) => {
  if (value === null || value === undefined || value === '') return 'Linh hoat'
  const amount = Number(value)
  if (Number.isNaN(amount)) return String(value)
  return new Intl.NumberFormat('vi-VN', {
    notation: 'compact',
    maximumFractionDigits: 1
  }).format(amount)
}

const formatDate = (value) => {
  if (!value) return 'Dang mo don'
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return 'Dang mo don'
  return new Intl.DateTimeFormat('vi-VN').format(date)
}

const submitSearch = () => {
  router.push({
    path: '/jobs',
    query: {
      search: searchForm.keyword || undefined,
      dia_diem: searchForm.location || undefined
    }
  })
}

const loadLandingData = async () => {
  loading.value = true
  error.value = ''

  try {
    const [jobsResponse, companiesResponse, industriesResponse] = await Promise.all([
      publicCatalogService.getJobs({ per_page: 24 }),
      publicCatalogService.getCompanies({ per_page: 12 }),
      publicCatalogService.getIndustries()
    ])

    jobs.value = asArray(jobsResponse)
    companies.value = asArray(companiesResponse)
    industries.value = asArray(industriesResponse)
  } catch (err) {
    error.value = err.message || 'Khong the tai du lieu trang chu'
  } finally {
    loading.value = false
  }
}

onMounted(loadLandingData)
</script>

<template>
  <div class="bg-[radial-gradient(circle_at_top_left,_rgba(36,99,235,0.14),_transparent_26%),radial-gradient(circle_at_80%_20%,_rgba(14,165,233,0.14),_transparent_24%),linear-gradient(180deg,_#f8fbff_0%,_#ffffff_42%,_#f8fafc_100%)] dark:bg-slate-950">
    <section class="relative overflow-hidden border-b border-slate-200/70 py-16 dark:border-slate-800 lg:py-24">
      <div class="mx-auto grid max-w-7xl items-center gap-12 px-6 lg:grid-cols-[1.15fr,0.85fr]">
        <div class="relative z-10">
          <div class="inline-flex items-center gap-2 rounded-full border border-[#2463eb]/15 bg-white/80 px-4 py-2 text-sm font-semibold text-[#2463eb] shadow-sm backdrop-blur">
            <span class="material-symbols-outlined text-[18px]">auto_awesome</span>
            Nền tảng kết nối dữ liệu tuyển dụng thực tế và công nghệ AI thông minh
          </div>
          <h1 class="mt-6 max-w-4xl text-4xl font-black tracking-tight text-slate-900 sm:text-5xl lg:text-6xl">
            Tìm việc làm, chọn đúng người với <span class="text-[#2463eb]">dữ liệu thật</span> và gợi ý thông minh
          </h1>
          <p class="mt-5 max-w-2xl text-lg leading-8 text-slate-600">
            Từ hồ sơ ứng viên, tin tuyển dụng, công ty, kỹ năng đến AI matching, mỗi thành phần trong hệ thống của bạn đều có thể trở thành trải nghiệm tìm việc và tuyển dụng mãnh liệt, đẹp và dễ dùng hơn.
          </p>

          <div class="mt-8 grid gap-3 rounded-3xl border border-slate-200 bg-white/90 p-3 shadow-2xl shadow-slate-200/60 backdrop-blur dark:border-slate-800 dark:bg-slate-900/90 md:grid-cols-[1.4fr,1fr,auto]">
            <input v-model="searchForm.keyword" class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4 text-sm text-slate-900 outline-none transition focus:border-[#2463eb] dark:border-slate-700 dark:bg-slate-800 dark:text-white" type="text" placeholder="Vị trí, công ty, kỹ năng" />
            <input v-model="searchForm.location" class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4 text-sm text-slate-900 outline-none transition focus:border-[#2463eb] dark:border-slate-700 dark:bg-slate-800 dark:text-white" type="text" placeholder="Địa điểm làm việc" />
            <button class="rounded-2xl bg-[#2463eb] px-6 py-4 text-sm font-bold text-white transition hover:bg-[#1d4fcc]" @click="submitSearch">
              Tìm kiếm
            </button>
          </div>

          <div class="mt-6 flex flex-wrap gap-3">
            <RouterLink to="/jobs" class="rounded-xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800 dark:bg-white dark:text-slate-900">
              Xem tất cả việc làm
            </RouterLink>
            <RouterLink to="/auth" class="rounded-xl border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-white dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-900">
              Tạo tài khoản ngay
            </RouterLink>
          </div>

          <div class="mt-10 grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div class="rounded-2xl border border-slate-200 bg-white/90 p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
              <p class="text-sm text-slate-500">Tin đăng mở</p>
              <p class="mt-2 text-3xl font-black text-slate-900 dark:text-white">{{ totalOpenJobs }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white/90 p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
              <p class="text-sm text-slate-500">Doanh nghiệp</p>
              <p class="mt-2 text-3xl font-black text-slate-900 dark:text-white">{{ totalCompanies }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white/90 p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
              <p class="text-sm text-slate-500">Ngành nghề</p>
              <p class="mt-2 text-3xl font-black text-slate-900 dark:text-white">{{ industries.length }}</p>
            </div>
          </div>
        </div>

        <div class="relative">
          <div class="absolute -left-8 top-8 h-28 w-28 rounded-full bg-sky-200/60 blur-3xl"></div>
          <div class="absolute -right-8 bottom-8 h-32 w-32 rounded-full bg-blue-300/50 blur-3xl"></div>
          <div class="relative rounded-[32px] border border-slate-200 bg-white/90 p-5 shadow-2xl shadow-slate-300/40 backdrop-blur dark:border-slate-800 dark:bg-slate-900/90">
            <div class="rounded-[28px] bg-slate-950 p-6 text-white">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-xs uppercase tracking-[0.24em] text-sky-200">AI Recruitment Snapshot</p>
                  <h2 class="mt-2 text-2xl font-bold">Thị trường đang cần gì?</h2>
                </div>
                <span class="material-symbols-outlined text-4xl text-sky-300">insights</span>
              </div>
              <div class="mt-6 grid gap-3 sm:grid-cols-2">
                <div class="rounded-2xl bg-white/10 p-4">
                  <p class="text-xs uppercase tracking-widest text-slate-300">Mức lương nổi bật</p>
                  <p class="mt-2 text-2xl font-black">{{ featuredJobs[0] ? formatCompactCurrency(featuredJobs[0].muc_luong) : '30Tr+' }}</p>
                </div>
                <div class="rounded-2xl bg-white/10 p-4">
                  <p class="text-xs uppercase tracking-widest text-slate-300">Công ty nổi bật</p>
                  <p class="mt-2 text-lg font-bold">{{ highlightedCompanies[0]?.ten_cong_ty || 'TechViet Solutions' }}</p>
                </div>
              </div>
            </div>

            <div class="mt-4 space-y-3">
              <div v-for="job in featuredJobs" :key="job.id" class="rounded-2xl border border-slate-200 bg-white p-4 transition hover:border-[#2463eb]/40 dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-start justify-between gap-3">
                  <div class="min-w-0">
                    <p class="text-sm font-bold text-slate-900 dark:text-white">{{ job.tieu_de }}</p>
                    <p class="mt-1 text-sm text-slate-500">{{ getCompany(job).ten_cong_ty || 'Công ty đang cập nhật' }}</p>
                  </div>
                  <span class="rounded-full bg-[#2463eb]/10 px-2.5 py-1 text-xs font-semibold text-[#2463eb]">{{ job.hinh_thuc_lam_viec || 'Đang mở' }}</span>
                </div>
                <div class="mt-3 flex flex-wrap gap-2 text-xs text-slate-500">
                  <span class="rounded-full bg-slate-100 px-2.5 py-1 dark:bg-slate-800">{{ job.dia_diem_lam_viec || 'Địa điểm đang cập nhật' }}</span>
                  <span class="rounded-full bg-slate-100 px-2.5 py-1 dark:bg-slate-800">{{ formatCurrency(job.muc_luong) }}</span>
                </div>
              </div>
              <p v-if="!featuredJobs.length" class="rounded-2xl border border-dashed border-slate-300 px-4 py-8 text-center text-sm text-slate-500 dark:border-slate-700">
                Đang tải dữ liệu việc làm nổi bật...
              </p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="py-20">
      <div class="mx-auto max-w-7xl px-6">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
          <div>
            <p class="text-sm font-semibold uppercase tracking-[0.22em] text-[#2463eb]">Năng lực của hệ thống</p>
            <h2 class="mt-2 text-3xl font-black tracking-tight text-slate-900 dark:text-white">Trang chủ phản ánh dung hệ dữ liệu bạn đang có</h2>
          </div>
          <RouterLink to="/ai-career" class="text-sm font-semibold text-[#2463eb] hover:underline">Khám phá AI career advisor</RouterLink>
        </div>

        <div class="mt-10 grid gap-6 md:grid-cols-3">
          <div class="rounded-3xl border border-slate-200 bg-white p-7 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-[#2463eb]/10 text-[#2463eb]">
              <span class="material-symbols-outlined text-3xl">auto_awesome</span>
            </div>
            <h3 class="mt-6 text-xl font-bold text-slate-900 dark:text-white">AI Matching</h3>
            <p class="mt-3 text-sm leading-7 text-slate-600 dark:text-slate-300">Hỗ trợ so khớp CV và vị trí, giúp ứng viên nhìn rõ độ phù hợp, doanh nghiệp dễ ưu tiên hồ sơ tốt hơn.</p>
          </div>
          <div class="rounded-3xl border border-slate-200 bg-white p-7 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-sky-100 text-sky-600">
              <span class="material-symbols-outlined text-3xl">apartment</span>
            </div>
            <h3 class="mt-6 text-xl font-bold text-slate-900 dark:text-white">Hệ thống doanh nghiệp</h3>
            <p class="mt-3 text-sm leading-7 text-slate-600 dark:text-slate-300">Từ thông tin công ty, tin tuyển dụng đến ứng viên ứng tuyển, mỗi lượng dữ liệu đều có thể hiển thị rõ ràng và nhất quán.</p>
          </div>
          <div class="rounded-3xl border border-slate-200 bg-white p-7 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-600">
              <span class="material-symbols-outlined text-3xl">category</span>
            </div>
            <h3 class="mt-6 text-xl font-bold text-slate-900 dark:text-white">Ngành nghề và kỹ năng</h3>
            <p class="mt-3 text-sm leading-7 text-slate-600 dark:text-slate-300">Danh mục ngành nghề, công ty và các job mở giúp trang chủ không chỉ đẹp mà còn có giá trị thông tin thực.</p>
          </div>
        </div>
      </div>
    </section>

    <section class="border-y border-slate-200/80 bg-white/80 py-20 backdrop-blur dark:border-slate-800 dark:bg-slate-950/70">
      <div class="mx-auto max-w-7xl px-6">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
          <div>
            <p class="text-sm font-semibold uppercase tracking-[0.22em] text-[#2463eb]">Ngành nghề nổi bật</p>
            <h2 class="mt-2 text-3xl font-black tracking-tight text-slate-900 dark:text-white">Khám phá nhu cầu từ các nhóm ngành đang mở rộng</h2>
          </div>
          <RouterLink to="/jobs" class="text-sm font-semibold text-[#2463eb] hover:underline">Xem tất cả việc làm</RouterLink>
        </div>

        <div class="mt-10 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
          <RouterLink v-for="industry in highlightedIndustries" :key="industry.id" :to="{ path: '/jobs', query: { nganh_nghe_id: industry.id } }" class="group rounded-3xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-1 hover:border-[#2463eb]/35 hover:shadow-lg dark:border-slate-800 dark:bg-slate-900">
            <div class="flex items-center justify-between">
              <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[#2463eb]/10 text-[#2463eb]">
                <span class="material-symbols-outlined text-2xl">workspaces</span>
              </div>
              <span class="material-symbols-outlined text-slate-300 transition group-hover:text-[#2463eb]">arrow_forward</span>
            </div>
            <h3 class="mt-6 text-lg font-bold text-slate-900 dark:text-white">{{ industry.ten_nganh }}</h3>
            <p class="mt-2 text-sm text-slate-500">Danh mục công việc, công ty và xu hướng tuyển dụng đang được hệ thống ghi nhận.</p>
          </RouterLink>
          <div v-if="!highlightedIndustries.length" class="rounded-3xl border border-dashed border-slate-300 px-4 py-10 text-center text-sm text-slate-500 dark:border-slate-700 sm:col-span-2 xl:col-span-4">
            Đang tải danh mục ngành nghề...
          </div>
        </div>
      </div>
    </section>

    <section class="py-20">
      <div class="mx-auto max-w-7xl px-6">
        <div class="grid gap-12 lg:grid-cols-[0.9fr,1.1fr]">
          <div>
            <p class="text-sm font-semibold uppercase tracking-[0.22em] text-[#2463eb]">Doanh nghiệp nổi bật</p>
            <h2 class="mt-2 text-3xl font-black tracking-tight text-slate-900 dark:text-white">Những đơn vị đang xây dựng đội ngũ trong hệ thống của bạn</h2>
            <p class="mt-4 text-sm leading-7 text-slate-600 dark:text-slate-300">Phần này giúp trang chủ liên kết được dữ liệu công ty, thông tin doanh nghiệp và tin tuyển dụng đang mở một cách thuyết phục hơn.</p>
          </div>
          <div class="grid gap-4 md:grid-cols-2">
            <article v-for="company in highlightedCompanies" :key="company.id" class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
              <div class="flex items-start gap-4">
                <div class="flex h-14 w-14 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-slate-100 dark:bg-slate-800">
                  <img v-if="companyLogoUrl(company)" :src="companyLogoUrl(company)" alt="Company logo" class="h-full w-full object-cover" />
                  <span v-else class="material-symbols-outlined text-2xl text-slate-400">domain</span>
                </div>
                <div class="min-w-0">
                  <h3 class="text-lg font-bold text-slate-900 dark:text-white">{{ company.ten_cong_ty }}</h3>
                  <p class="mt-1 text-sm text-slate-500">{{ company.dia_chi || 'Đang cập nhật địa chỉ' }}</p>
                </div>
              </div>
              <p class="mt-4 line-clamp-3 text-sm leading-7 text-slate-600 dark:text-slate-300">{{ company.mo_ta || 'Doanh nghiep dang mo rong doi ngu va xay dung thuong hieu tuyen dung tren nen tang.' }}</p>
            </article>
            <div v-if="!highlightedCompanies.length" class="rounded-3xl border border-dashed border-slate-300 px-4 py-10 text-center text-sm text-slate-500 dark:border-slate-700 md:col-span-2">
              Đang tải danh sách doanh nghiệp nổi bật...
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="pb-20">
      <div class="mx-auto max-w-7xl px-6">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
          <div>
            <p class="text-sm font-semibold uppercase tracking-[0.22em] text-[#2463eb]">Tin tuyển dụng mới nhất</p>
            <h2 class="mt-2 text-3xl font-black tracking-tight text-slate-900 dark:text-white">Dữ liệu việc làm được đưa thẳng lên trang chủ</h2>
          </div>
          <RouterLink to="/jobs" class="text-sm font-semibold text-[#2463eb] hover:underline">Mở trang tìm việc</RouterLink>
        </div>

        <div v-if="error" class="mt-8 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900/40 dark:bg-red-950/30 dark:text-red-300">
          {{ error }}
        </div>

        <div class="mt-10 grid gap-5 lg:grid-cols-2 xl:grid-cols-3">
          <article v-for="job in latestJobs" :key="job.id" class="group rounded-[28px] border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:border-[#2463eb]/40 hover:shadow-xl dark:border-slate-800 dark:bg-slate-900">
            <div class="flex items-start justify-between gap-3">
              <div>
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[#2463eb]">{{ job.hinh_thuc_lam_viec || 'Đang mở' }}</p>
                <RouterLink :to="`/jobs/${job.id}`" class="mt-3 block text-xl font-bold leading-snug text-slate-900 transition group-hover:text-[#2463eb] dark:text-white">
                  {{ job.tieu_de }}
                </RouterLink>
                <p class="mt-2 text-sm text-slate-500">{{ getCompany(job).ten_cong_ty || 'Công ty đang cập nhật' }}</p>
              </div>
              <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs text-slate-500 dark:bg-slate-800">{{ formatDate(job.ngay_het_han) }}</span>
            </div>

            <div class="mt-4 flex flex-wrap gap-2 text-xs text-slate-500">
              <span class="rounded-full bg-slate-100 px-2.5 py-1 dark:bg-slate-800">{{ job.dia_diem_lam_viec || 'Địa điểm đang cập nhật' }}</span>
              <span class="rounded-full bg-slate-100 px-2.5 py-1 dark:bg-slate-800">{{ formatCurrency(job.muc_luong) }}</span>
              <span class="rounded-full bg-slate-100 px-2.5 py-1 dark:bg-slate-800">{{ job.kinh_nghiem_yeu_cau || 'Kinh nghiệm linh hoạt' }}</span>
            </div>

            <div class="mt-4 flex flex-wrap gap-2">
              <span v-for="industry in normalizeIndustries(job).slice(0, 3)" :key="industry.id || industry.nganh_nghe_id" class="rounded-full bg-[#2463eb]/10 px-2.5 py-1 text-xs font-medium text-[#2463eb]">
                {{ industry.ten_nganh || industry.nganh_nghe?.ten_nganh || 'Ngành nghề' }}
              </span>
            </div>

            <p class="mt-4 line-clamp-3 text-sm leading-7 text-slate-600 dark:text-slate-300">{{ job.mo_ta_cong_viec || 'Mô tả công việc đang được cập nhật.' }}</p>

            <div class="mt-6 flex items-center justify-between border-t border-slate-100 pt-4 dark:border-slate-800">
              <span class="text-sm font-semibold text-slate-500">{{ Number(job.luot_xem || 0) }} lượt xem</span>
              <RouterLink :to="`/jobs/${job.id}`" class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#2463eb] dark:bg-white dark:text-slate-900 dark:hover:text-white">
                Xem chi tiết
              </RouterLink>
            </div>
          </article>
          <div v-if="loading && !latestJobs.length" class="rounded-3xl border border-dashed border-slate-300 px-4 py-14 text-center text-sm text-slate-500 dark:border-slate-700 lg:col-span-2 xl:col-span-3">
            Đang tải tin tuyển dụng mới nhất...
          </div>
        </div>
      </div>
    </section>
  </div>
</template>
