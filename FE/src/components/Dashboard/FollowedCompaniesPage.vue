<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { RouterLink } from 'vue-router'
import { followCompanyService } from '@/services/api'
import { useNotify } from '@/composables/useNotify'

const notify = useNotify()

const loading = ref(false)
const removingCompanyId = ref(null)
const companies = ref([])
const search = ref('')
const sortOption = ref('followed_desc')
const pagination = reactive({
  current_page: 1,
  last_page: 1,
  per_page: 12,
  total: 0,
  from: 0,
  to: 0,
})

const normalizeDate = (value) => {
  if (!value) return 0
  const timestamp = new Date(value).getTime()
  return Number.isNaN(timestamp) ? 0 : timestamp
}

const formatCurrency = (value) => {
  if (value === null || value === undefined || value === '') return 'Thoa thuan'
  return `${new Intl.NumberFormat('vi-VN').format(Number(value))} đ`
}

const formatSalary = (job) => {
  if (!job) return 'Thoa thuan'
  if (job.muc_luong_tu && job.muc_luong_den) {
    return `${formatCurrency(job.muc_luong_tu)} - ${formatCurrency(job.muc_luong_den)}`
  }
  if (job.muc_luong) return formatCurrency(job.muc_luong)
  return 'Thoa thuan'
}

const formatFollowedDate = (value) => {
  if (!value) return 'Theo dõi gần đây'
  return `Theo dõi ${new Date(value).toLocaleDateString('vi-VN')}`
}

const formatJobDate = (value) => {
  if (!value) return 'Mới đăng'
  const diff = Date.now() - normalizeDate(value)
  const diffHours = Math.floor(diff / (1000 * 60 * 60))
  if (diffHours < 24) return `Đăng ${Math.max(diffHours, 1)} giờ trước`
  return `Đăng ${Math.max(Math.floor(diffHours / 24), 1)} ngày trước`
}

const fetchFollowedCompanies = async (page = 1) => {
  loading.value = true
  try {
    const response = await followCompanyService.getFollowedCompanies({
      page,
      per_page: pagination.per_page,
      recent_jobs_limit: 3,
    })
    const payload = response?.data || {}
    companies.value = payload.data || []
    pagination.current_page = payload.current_page || 1
    pagination.last_page = payload.last_page || 1
    pagination.total = payload.total || 0
    pagination.from = payload.from || 0
    pagination.to = payload.to || 0
  } catch (error) {
    companies.value = []
    pagination.current_page = 1
    pagination.last_page = 1
    pagination.total = 0
    pagination.from = 0
    pagination.to = 0
    notify.apiError(error, 'Khong tai duoc danh sach cong ty da follow.')
  } finally {
    loading.value = false
  }
}

const filteredCompanies = computed(() => {
  const keyword = search.value.trim().toLowerCase()

  const items = companies.value.filter((company) => {
    if (!keyword) return true
    const haystacks = [
      company.ten_cong_ty,
      company.dia_chi,
      company.email,
      company.nganh_nghe?.ten_nganh,
      ...(company.tin_tuyen_dungs || []).map((job) => job.tieu_de),
    ]
      .filter(Boolean)
      .join(' ')
      .toLowerCase()

    return haystacks.includes(keyword)
  })

  switch (sortOption.value) {
    case 'jobs_desc':
      return [...items].sort((a, b) => Number(b.so_tin_dang_hoat_dong || 0) - Number(a.so_tin_dang_hoat_dong || 0))
    case 'name_asc':
      return [...items].sort((a, b) => String(a.ten_cong_ty || '').localeCompare(String(b.ten_cong_ty || ''), 'vi'))
    case 'followed_asc':
      return [...items].sort((a, b) => normalizeDate(a.theo_doi_luc) - normalizeDate(b.theo_doi_luc))
    case 'followed_desc':
    default:
      return [...items].sort((a, b) => normalizeDate(b.theo_doi_luc) - normalizeDate(a.theo_doi_luc))
  }
})

const stats = computed(() => {
  const totalOpenJobs = companies.value.reduce((sum, company) => sum + Number(company.so_tin_dang_hoat_dong || 0), 0)
  const totalFollowers = companies.value.reduce((sum, company) => sum + Number(company.so_nguoi_theo_doi || 0), 0)
  const companiesWithFreshJobs = companies.value.filter((company) =>
    (company.tin_tuyen_dungs || []).some((job) => {
      const diff = Date.now() - normalizeDate(job.created_at)
      return diff >= 0 && diff <= 7 * 24 * 60 * 60 * 1000
    }),
  ).length

  return {
    totalCompanies: pagination.total || companies.value.length,
    totalOpenJobs,
    totalFollowers,
    companiesWithFreshJobs,
  }
})

const unfollowCompany = async (companyId) => {
  if (removingCompanyId.value) return
  removingCompanyId.value = companyId
  try {
    await followCompanyService.toggleFollowCompany(companyId)
    companies.value = companies.value.filter((company) => Number(company.id) !== Number(companyId))
    pagination.total = Math.max(0, pagination.total - 1)
    notify.info('Da bo follow cong ty.')
  } catch (error) {
    notify.apiError(error, 'Khong the cap nhat trang thai follow cong ty.')
  } finally {
    removingCompanyId.value = null
  }
}

const changePage = async (page) => {
  if (page < 1 || page > pagination.last_page || page === pagination.current_page) return
  await fetchFollowedCompanies(page)
}

onMounted(() => {
  fetchFollowedCompanies()
})

watch(search, () => {
  // local filtering only; keep current page data intact
})
</script>

<template>
  <div class="space-y-8">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
      <div>
        <p class="text-xs font-semibold uppercase tracking-[0.28em] text-blue-600/70">Mạng lưới doanh nghiệp</p>
        <h1 class="mt-2 text-3xl font-bold text-slate-900 dark:text-white">Công ty đã follow</h1>
        <p class="mt-2 text-slate-600 dark:text-slate-400">
          Quản lý những doanh nghiệp bạn đang theo dõi và xem nhanh các job mới nhất từ họ.
        </p>
      </div>
      <RouterLink
        :to="{ name: 'CompanyList' }"
        class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-500 px-5 py-3 font-semibold text-white shadow-lg shadow-blue-500/20 transition-all hover:from-blue-500 hover:to-indigo-400"
      >
        <span class="material-symbols-outlined text-xl">travel_explore</span>
        Khám phá thêm công ty
      </RouterLink>
    </div>

    <div class="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-4">
      <div class="rounded-2xl border border-slate-200 bg-white/90 p-6 shadow-sm shadow-slate-950/5 dark:border-slate-800 dark:bg-slate-900/80">
        <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Công ty đang follow</p>
        <h3 class="mt-2 text-3xl font-bold text-slate-900 dark:text-white">{{ stats.totalCompanies }}</h3>
      </div>
      <div class="rounded-2xl border border-slate-200 bg-white/90 p-6 shadow-sm shadow-slate-950/5 dark:border-slate-800 dark:bg-slate-900/80">
        <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Tin đang mở</p>
        <h3 class="mt-2 text-3xl font-bold text-slate-900 dark:text-white">{{ stats.totalOpenJobs }}</h3>
      </div>
      <div class="rounded-2xl border border-slate-200 bg-white/90 p-6 shadow-sm shadow-slate-950/5 dark:border-slate-800 dark:bg-slate-900/80">
        <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Công ty có job mới</p>
        <h3 class="mt-2 text-3xl font-bold text-slate-900 dark:text-white">{{ stats.companiesWithFreshJobs }}</h3>
      </div>
      <div class="rounded-2xl border border-slate-200 bg-white/90 p-6 shadow-sm shadow-slate-950/5 dark:border-slate-800 dark:bg-slate-900/80">
        <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Tổng follower</p>
        <h3 class="mt-2 text-3xl font-bold text-slate-900 dark:text-white">{{ stats.totalFollowers }}</h3>
      </div>
    </div>

    <section class="rounded-2xl border border-slate-200 bg-white/95 p-6 shadow-sm shadow-slate-950/5 dark:border-slate-800 dark:bg-slate-900/85">
      <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div class="flex-1">
          <label class="mb-2 block text-sm font-semibold text-slate-700 dark:text-slate-200">Tìm công ty đã follow</label>
          <input
            v-model="search"
            type="text"
            placeholder="Tên công ty, địa điểm, ngành nghề, tên job..."
            class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-700 outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100"
          >
        </div>
        <div class="w-full lg:w-64">
          <label class="mb-2 block text-sm font-semibold text-slate-700 dark:text-slate-200">Sắp xếp</label>
          <select
            v-model="sortOption"
            class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-700 outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100"
          >
            <option value="followed_desc">Mới follow trước</option>
            <option value="followed_asc">Follow lâu nhất</option>
            <option value="jobs_desc">Nhiều job nhất</option>
            <option value="name_asc">Tên A-Z</option>
          </select>
        </div>
      </div>
    </section>

    <div v-if="loading" class="grid gap-5 xl:grid-cols-2">
      <div
        v-for="index in 4"
        :key="index"
        class="h-72 animate-pulse rounded-3xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900"
      />
    </div>

    <div
      v-else-if="!filteredCompanies.length"
      class="rounded-3xl border border-dashed border-slate-300 bg-white px-6 py-14 text-center shadow-sm dark:border-slate-700 dark:bg-slate-900"
    >
      <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-slate-100 dark:bg-slate-800">
        <span class="material-symbols-outlined text-3xl text-slate-500">apartment</span>
      </div>
      <h2 class="mt-5 text-2xl font-bold text-slate-900 dark:text-white">
        {{ search ? 'Không tìm thấy công ty phù hợp' : 'Bạn chưa follow công ty nào' }}
      </h2>
      <p class="mx-auto mt-3 max-w-2xl text-sm leading-7 text-slate-500 dark:text-slate-400">
        {{ search
          ? 'Thử đổi từ khóa tìm kiếm hoặc bỏ bộ lọc để xem lại danh sách công ty đã follow.'
          : 'Khi follow công ty, bạn có thể quay lại đây để theo dõi job mới và quản lý danh sách doanh nghiệp mình quan tâm.' }}
      </p>
      <RouterLink
        :to="{ name: 'CompanyList' }"
        class="mt-6 inline-flex rounded-xl bg-[#2463eb] px-5 py-3 text-sm font-bold text-white"
      >
        Xem danh sach cong ty
      </RouterLink>
    </div>

    <div v-else class="grid gap-5 xl:grid-cols-2">
      <article
        v-for="company in filteredCompanies"
        :key="company.id"
        class="flex h-full flex-col rounded-3xl border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-0.5 hover:border-blue-200 hover:shadow-lg dark:border-slate-800 dark:bg-slate-900"
      >
        <div class="flex h-full flex-col gap-5">
          <div class="flex items-start gap-4">
            <div class="flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-[#2463eb]/10 text-2xl font-black text-[#2463eb]">
              <img v-if="company.logo_url" :src="company.logo_url" :alt="company.ten_cong_ty" class="h-full w-full object-cover">
              <span v-else>{{ company.ten_cong_ty?.slice(0, 1) || 'C' }}</span>
            </div>
            <div class="min-w-0 flex-1">
              <div class="flex flex-wrap items-center gap-2">
                <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-bold text-blue-700 dark:bg-blue-900/30 dark:text-blue-300">
                  {{ company.nganh_nghe?.ten_nganh || 'Đang cập nhật ngành nghề' }}
                </span>
                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                  {{ formatFollowedDate(company.theo_doi_luc) }}
                </span>
              </div>
              <RouterLink
                :to="{ name: 'CompanyDetail', params: { id: company.id } }"
                class="mt-3 block text-2xl font-bold text-slate-900 transition hover:text-blue-600 dark:text-white dark:hover:text-blue-300"
              >
                {{ company.ten_cong_ty }}
              </RouterLink>
              <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                {{ company.dia_chi || 'Đang cập nhật địa chỉ' }}
              </p>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
            <div class="rounded-2xl bg-slate-50 px-4 py-4 dark:bg-slate-800/70">
              <p class="text-xs uppercase tracking-wide text-slate-400">Quy mô</p>
              <p class="mt-2 text-sm font-semibold text-slate-800 dark:text-slate-100">{{ company.quy_mo || 'Đang cập nhật' }}</p>
            </div>
            <div class="rounded-2xl bg-slate-50 px-4 py-4 dark:bg-slate-800/70">
              <p class="text-xs uppercase tracking-wide text-slate-400">Tin đang mở</p>
              <p class="mt-2 text-sm font-semibold text-slate-800 dark:text-slate-100">{{ company.so_tin_dang_hoat_dong || 0 }}</p>
            </div>
            <div class="rounded-2xl bg-slate-50 px-4 py-4 dark:bg-slate-800/70">
              <p class="text-xs uppercase tracking-wide text-slate-400">Follower</p>
              <p class="mt-2 text-sm font-semibold text-slate-800 dark:text-slate-100">{{ company.so_nguoi_theo_doi || 0 }}</p>
            </div>
            <div class="rounded-2xl bg-slate-50 px-4 py-4 dark:bg-slate-800/70">
              <p class="text-xs uppercase tracking-wide text-slate-400">Liên hệ</p>
              <p class="mt-2 truncate text-sm font-semibold text-slate-800 dark:text-slate-100">{{ company.email || 'Đang cập nhật' }}</p>
            </div>
          </div>

          <p class="line-clamp-3 text-sm leading-7 text-slate-600 dark:text-slate-400">
            {{ company.mo_ta || 'Công ty đang cập nhật thêm thông tin giới thiệu.' }}
          </p>

          <div class="flex-1 rounded-2xl border border-slate-200 bg-slate-50/80 p-4 dark:border-slate-800 dark:bg-slate-950/50">
            <div class="flex items-center justify-between gap-3">
              <h3 class="text-sm font-bold text-slate-900 dark:text-white">Job mới nhất</h3>
              <RouterLink
                :to="{ path: '/jobs', query: { search: company.ten_cong_ty } }"
                class="text-xs font-bold text-[#2463eb] hover:underline"
              >
                Xem thêm
              </RouterLink>
            </div>

            <div v-if="company.tin_tuyen_dungs?.length" class="mt-4 space-y-3">
              <div
                v-for="job in company.tin_tuyen_dungs"
                :key="job.id"
                class="rounded-2xl bg-white px-4 py-4 shadow-sm dark:bg-slate-900"
              >
                <div class="flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
                  <div>
                    <RouterLink
                      :to="{ name: 'JobDetail', params: { id: job.id } }"
                      class="font-bold text-slate-900 hover:text-blue-600 dark:text-white dark:hover:text-blue-300"
                    >
                      {{ job.tieu_de }}
                    </RouterLink>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                      {{ job.dia_diem_lam_viec || 'Linh hoat' }} • {{ job.hinh_thuc_lam_viec || 'Dang cap nhat' }}
                    </p>
                  </div>
                  <div class="text-left md:text-right">
                    <p class="text-sm font-bold text-slate-900 dark:text-white">{{ formatSalary(job) }}</p>
                    <p class="mt-1 text-xs text-slate-400">{{ formatJobDate(job.created_at) }}</p>
                  </div>
                </div>
              </div>
            </div>

            <div v-else class="mt-4 rounded-2xl border border-dashed border-slate-300 px-4 py-6 text-sm text-slate-500 dark:border-slate-700 dark:text-slate-400">
              Cong ty nay hien chua co job dang mo.
            </div>
          </div>

          <div class="mt-auto flex flex-wrap gap-3 pt-1">
            <RouterLink
              :to="{ name: 'CompanyDetail', params: { id: company.id } }"
              class="rounded-2xl bg-[#2463eb] px-5 py-3 text-sm font-bold text-white transition hover:bg-blue-700"
            >
              Xem chi tiết công ty
            </RouterLink>
            <button
              class="rounded-2xl border border-rose-200 px-5 py-3 text-sm font-bold text-rose-600 transition hover:bg-rose-50 disabled:cursor-not-allowed disabled:opacity-60 dark:border-rose-900/40 dark:hover:bg-rose-900/10"
              :disabled="removingCompanyId === company.id"
              type="button"
              @click="unfollowCompany(company.id)"
            >
              {{ removingCompanyId === company.id ? 'Đang cập nhật...' : 'Bỏ theo dõi' }}
            </button>
          </div>
        </div>
      </article>
    </div>

    <div v-if="pagination.last_page > 1" class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-white px-5 py-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
      <p class="text-sm text-slate-500 dark:text-slate-400">
        Hien thi {{ pagination.from }}-{{ pagination.to }} tren {{ pagination.total }} cong ty da follow
      </p>
      <div class="flex items-center gap-2">
        <button
          class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800"
          :disabled="pagination.current_page === 1"
          type="button"
          @click="changePage(pagination.current_page - 1)"
        >
          Truoc
        </button>
        <span class="text-sm font-semibold text-slate-700 dark:text-slate-200">
          {{ pagination.current_page }}/{{ pagination.last_page }}
        </span>
        <button
          class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800"
          :disabled="pagination.current_page === pagination.last_page"
          type="button"
          @click="changePage(pagination.current_page + 1)"
        >
          Sau
        </button>
      </div>
    </div>
  </div>
</template>
