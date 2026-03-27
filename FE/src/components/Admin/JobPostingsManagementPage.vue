<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref } from 'vue'
import { adminJobPostingService, companyService } from '@/services/api'
import { useNotify } from '@/composables/useNotify'

const notify = useNotify()

const jobs = ref([])
const companies = ref([])
const loading = ref(false)
const error = ref('')
const currentPage = ref(1)
const totalJobs = ref(0)
const perPage = ref(10)

const searchQuery = ref('')
const selectedStatus = ref('')
const selectedCompany = ref('')

const stats = reactive({
  total: 0,
  active: 0,
  paused: 0,
})

const showEditModal = ref(false)
const showDeleteModal = ref(false)
const editingJob = ref(null)
const deletingJob = ref(null)
let searchDebounceTimer = null

const formData = reactive({
  tieu_de: '',
  dia_diem_lam_viec: '',
  hinh_thuc_lam_viec: '',
  cap_bac: '',
  so_luong_tuyen: 1,
  muc_luong: '',
  kinh_nghiem_yeu_cau: '',
  ngay_het_han: '',
  mo_ta_cong_viec: '',
  trang_thai: 1,
})

const workModeOptions = [
  { value: '', label: 'Chưa thiết lập' },
  { value: 'full_time', label: 'Toàn thời gian' },
  { value: 'part_time', label: 'Bán thời gian' },
  { value: 'remote', label: 'Remote' },
  { value: 'hybrid', label: 'Hybrid' },
  { value: 'internship', label: 'Thực tập' },
]

const statusMap = {
  1: 'Đang hoạt động',
  0: 'Tạm ngưng',
}

const statusColors = {
  1: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
  0: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
}

const totalPages = computed(() => Math.max(1, Math.ceil(totalJobs.value / perPage.value)))

const formatCurrency = (value) => {
  if (value === null || value === undefined || value === '') return 'Thỏa thuận'
  return `${new Intl.NumberFormat('vi-VN').format(Number(value))}\u00A0đ`
}

const parseDateTime = (value) => {
  if (!value) return null

  const normalized = String(value).includes(' ')
    ? String(value).replace(' ', 'T')
    : String(value)

  const parsed = new Date(normalized)
  return Number.isNaN(parsed.getTime()) ? null : parsed
}

const formatDate = (value) => {
  const date = parseDateTime(value)
  if (!date) return 'N/A'

  return new Intl.DateTimeFormat('vi-VN', {
    dateStyle: 'medium',
    timeStyle: 'short',
  }).format(date)
}

const formatDateTimeInput = (value) => {
  const date = parseDateTime(value)
  if (!date) return ''

  const pad = (part) => String(part).padStart(2, '0')
  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`
}

const truncate = (text, max = 140) => {
  if (!text) return 'Chưa có mô tả'
  return text.length > max ? `${text.slice(0, max)}...` : text
}

const normalizeJobs = (response) => {
  const payload = response?.data

  if (Array.isArray(payload)) {
    jobs.value = payload
    totalJobs.value = response?.total || payload.length
    return
  }

  if (payload?.data && Array.isArray(payload.data)) {
    jobs.value = payload.data
    totalJobs.value = payload.total || payload.meta?.total || payload.data.length
    return
  }

  jobs.value = []
  totalJobs.value = 0
}

const loadStats = async () => {
  try {
    const response = await adminJobPostingService.getStats()
    const payload = response?.data || {}
    stats.total = payload.tong_tin || 0
    stats.active = payload.hoat_dong || 0
    stats.paused = payload.tam_ngung || 0
  } catch (err) {
    console.error('Không thể tải thống kê tin tuyển dụng', err)
  }
}

const loadCompanies = async () => {
  try {
    const response = await companyService.getCompanies({ per_page: 100 })
    const payload = response?.data

    if (Array.isArray(payload)) {
      companies.value = payload
      return
    }

    if (payload?.data && Array.isArray(payload.data)) {
      companies.value = payload.data
      return
    }

    companies.value = []
  } catch (err) {
    console.error('Không thể tải danh sách công ty', err)
  }
}

const loadJobs = async () => {
  loading.value = true
  error.value = ''

  try {
    const response = await adminJobPostingService.getJobs({
      page: currentPage.value,
      per_page: perPage.value,
      search: searchQuery.value || undefined,
      trang_thai: selectedStatus.value,
      cong_ty_id: selectedCompany.value || undefined,
    })

    normalizeJobs(response)
  } catch (err) {
    error.value = err.message || 'Không thể tải danh sách tin tuyển dụng'
  } finally {
    loading.value = false
  }
}

const refreshAll = async () => {
  await Promise.all([loadJobs(), loadStats()])
}

const openEditModal = (job) => {
  editingJob.value = job
  formData.tieu_de = job.tieu_de || ''
  formData.dia_diem_lam_viec = job.dia_diem_lam_viec || ''
  formData.hinh_thuc_lam_viec = job.hinh_thuc_lam_viec || ''
  formData.cap_bac = job.cap_bac || ''
  formData.so_luong_tuyen = job.so_luong_tuyen || 1
  formData.muc_luong = job.muc_luong ?? ''
  formData.kinh_nghiem_yeu_cau = job.kinh_nghiem_yeu_cau || ''
  formData.ngay_het_han = formatDateTimeInput(job.ngay_het_han)
  formData.mo_ta_cong_viec = job.mo_ta_cong_viec || ''
  formData.trang_thai = job.trang_thai ?? 1
  showEditModal.value = true
}

const submitEdit = async () => {
  if (!editingJob.value) return

  try {
    await adminJobPostingService.updateJob(editingJob.value.id, {
      ...formData,
      muc_luong: formData.muc_luong === '' ? null : Number(formData.muc_luong),
      so_luong_tuyen: Number(formData.so_luong_tuyen) || 1,
      ngay_het_han: formData.ngay_het_han || null,
      kinh_nghiem_yeu_cau: formData.kinh_nghiem_yeu_cau || null,
      cap_bac: formData.cap_bac || null,
      hinh_thuc_lam_viec: formData.hinh_thuc_lam_viec || null,
    })

    showEditModal.value = false
    notify.success('Đã cập nhật tin tuyển dụng')
    await refreshAll()
  } catch (err) {
    error.value = err.message || 'Không thể cập nhật tin tuyển dụng'
  }
}

const toggleStatus = async (job) => {
  try {
    await adminJobPostingService.toggleStatus(job.id)
    notify.success('Đã cập nhật trạng thái tin')
    await refreshAll()
  } catch (err) {
    error.value = err.message || 'Không thể cập nhật trạng thái'
  }
}

const confirmDelete = (job) => {
  deletingJob.value = job
  showDeleteModal.value = true
}

const deleteJob = async () => {
  if (!deletingJob.value) return

  try {
    await adminJobPostingService.deleteJob(deletingJob.value.id)
    showDeleteModal.value = false
    deletingJob.value = null
    notify.success('Đã xóa tin tuyển dụng')
    await refreshAll()
  } catch (err) {
    error.value = err.message || 'Không thể xóa tin tuyển dụng'
  }
}

const onFilterChange = async () => {
  currentPage.value = 1
  await loadJobs()
}

const onSearch = () => {
  currentPage.value = 1

  if (searchDebounceTimer) {
    clearTimeout(searchDebounceTimer)
  }

  searchDebounceTimer = window.setTimeout(() => {
    loadJobs()
  }, 250)
}

const resetFilters = async () => {
  searchQuery.value = ''
  selectedStatus.value = ''
  selectedCompany.value = ''
  perPage.value = 10
  currentPage.value = 1
  await loadJobs()
}

const goToPage = async (page) => {
  currentPage.value = page
  await loadJobs()
}

const goToPreviousPage = async () => {
  if (currentPage.value === 1) return
  currentPage.value -= 1
  await loadJobs()
}

const goToNextPage = async () => {
  if (currentPage.value === totalPages.value) return
  currentPage.value += 1
  await loadJobs()
}

onMounted(async () => {
  await Promise.all([loadCompanies(), refreshAll()])
})

onBeforeUnmount(() => {
  if (searchDebounceTimer) {
    clearTimeout(searchDebounceTimer)
  }
})
</script>

<template>
  <div v-if="error" class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700 dark:border-red-900 dark:bg-red-900/20 dark:text-red-300">
    {{ error }}
  </div>

  <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
    <div class="space-y-1">
      <h1 class="text-3xl font-black tracking-tight">Quản lý tin tuyển dụng</h1>
      <p class="text-slate-500 dark:text-slate-400">Rà soát, cập nhật và điều phối tất cả tin tuyển dụng trong hệ thống.</p>
    </div>
  </div>

  <div class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
    <div class="rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900">
      <div class="mb-2 flex items-center justify-between">
        <span class="text-sm font-medium text-slate-500">Tổng tin</span>
        <span class="material-symbols-outlined text-[#2463eb]/40">work</span>
      </div>
      <div class="text-2xl font-black">{{ stats.total }}</div>
      <div class="mt-2 text-xs font-medium text-slate-500">Toàn bộ tin trên hệ thống</div>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900">
      <div class="mb-2 flex items-center justify-between">
        <span class="text-sm font-medium text-slate-500">Đang hoạt động</span>
        <span class="material-symbols-outlined text-emerald-500/40">check_circle</span>
      </div>
      <div class="text-2xl font-black text-emerald-500">{{ stats.active }}</div>
      <div class="mt-2 text-xs font-medium text-slate-500">Đang hiển thị với ứng viên</div>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900">
      <div class="mb-2 flex items-center justify-between">
        <span class="text-sm font-medium text-slate-500">Tạm ngưng</span>
        <span class="material-symbols-outlined text-amber-500/40">pause_circle</span>
      </div>
      <div class="text-2xl font-black text-amber-500">{{ stats.paused }}</div>
      <div class="mt-2 text-xs font-medium text-slate-500">Cần kiểm tra lại trước khi mở</div>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900">
      <div class="mb-2 flex items-center justify-between">
        <span class="text-sm font-medium text-slate-500">Tỷ lệ hoạt động</span>
        <span class="material-symbols-outlined text-[#2463eb]/40">monitoring</span>
      </div>
      <div class="text-2xl font-black">{{ stats.total ? Math.round((stats.active / stats.total) * 100) : 0 }}%</div>
      <div class="mt-2 text-xs font-medium text-slate-500">Tỷ lệ tin đang mở trên tổng số</div>
    </div>
  </div>

  <div class="mb-6 flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
    <div class="flex flex-col gap-3 lg:flex-row">
      <div class="relative flex-1">
        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">search</span>
        <input
          v-model="searchQuery"
          @input="onSearch"
          type="text"
          class="w-full rounded-xl border-none bg-slate-50 py-3 pl-10 pr-4 text-sm focus:ring-2 focus:ring-[#2463eb] dark:bg-slate-800"
          placeholder="Tìm theo tiêu đề tin hoặc tên công ty..."
        />
      </div>

      <select
        v-model="selectedCompany"
        @change="onFilterChange"
        class="rounded-xl border-none bg-slate-50 px-4 py-3 text-sm focus:ring-2 focus:ring-[#2463eb] dark:bg-slate-800 lg:min-w-[240px]"
      >
        <option value="">Tất cả công ty</option>
        <option v-for="company in companies" :key="company.id" :value="company.id">
          {{ company.ten_cong_ty }}
        </option>
      </select>

      <select
        v-model="selectedStatus"
        @change="onFilterChange"
        class="rounded-xl border-none bg-slate-50 px-4 py-3 text-sm focus:ring-2 focus:ring-[#2463eb] dark:bg-slate-800 lg:min-w-[180px]"
      >
        <option value="">Tất cả trạng thái</option>
        <option :value="1">Đang hoạt động</option>
        <option :value="0">Tạm ngưng</option>
      </select>

      <select
        v-model="perPage"
        @change="onFilterChange"
        class="rounded-xl border-none bg-slate-50 px-4 py-3 text-sm focus:ring-2 focus:ring-[#2463eb] dark:bg-slate-800 lg:min-w-[150px]"
      >
        <option :value="10">10 / trang</option>
        <option :value="20">20 / trang</option>
        <option :value="50">50 / trang</option>
      </select>

      <button
        @click="resetFilters"
        class="rounded-xl border border-slate-200 px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800"
      >
        Reset bộ lọc
      </button>
    </div>
  </div>

  <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
    <div class="overflow-x-auto">
      <table class="w-full table-fixed text-left">
        <thead class="border-b border-slate-200 bg-slate-50/80 dark:border-slate-800 dark:bg-slate-800/50">
          <tr>
            <th class="w-[38%] px-6 py-4 text-xs font-bold uppercase tracking-wider text-slate-500">Tin tuyển dụng</th>
            <th class="w-[19%] px-5 py-4 text-xs font-bold uppercase tracking-wider text-slate-500">Công ty</th>
            <th class="w-[11%] px-4 py-4 text-center text-xs font-bold uppercase tracking-wider text-slate-500">Lương</th>
            <th class="w-[10%] px-4 py-4 text-center text-xs font-bold uppercase tracking-wider text-slate-500">Ngày hết hạn</th>
            <th class="w-[12%] px-4 py-4 text-center text-xs font-bold uppercase tracking-wider text-slate-500">Trạng thái</th>
            <th class="w-[10%] px-4 py-4 text-center text-xs font-bold uppercase tracking-wider text-slate-500">Hành động</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
          <tr v-if="loading">
            <td colspan="6" class="px-6 py-10 text-center text-slate-500">
              <span class="material-symbols-outlined animate-spin">hourglass_empty</span>
              <div class="mt-2">Đang tải danh sách tin tuyển dụng...</div>
            </td>
          </tr>

          <tr v-else-if="jobs.length === 0">
            <td colspan="6" class="px-6 py-10 text-center text-slate-500">
              <span class="material-symbols-outlined text-3xl">work_off</span>
              <div class="mt-2">Không tìm thấy tin tuyển dụng phù hợp.</div>
            </td>
          </tr>

          <tr v-for="job in jobs" :key="job.id" class="align-top hover:bg-slate-50/60 dark:hover:bg-slate-800/30">
            <td class="px-6 py-5 pr-4">
              <div class="space-y-2">
                <div class="text-sm font-bold text-slate-900 dark:text-slate-100">{{ job.tieu_de }}</div>
                <div class="text-xs text-slate-500">
                  {{ job.dia_diem_lam_viec || 'Chưa có địa điểm' }}
                  <span v-if="job.cap_bac">• {{ job.cap_bac }}</span>
                  <span v-if="job.hinh_thuc_lam_viec">• {{ job.hinh_thuc_lam_viec }}</span>
                </div>
                <div class="text-xs leading-5 text-slate-500">{{ truncate(job.mo_ta_cong_viec) }}</div>
              </div>
            </td>
            <td class="px-5 py-5 pr-3">
              <div class="text-sm font-semibold">{{ job.cong_ty?.ten_cong_ty || 'N/A' }}</div>
              <div class="mt-1 text-xs text-slate-500">
                {{ job.nganh_nghes?.map((item) => item.ten_nganh).join(', ') || 'Chưa gắn ngành nghề' }}
              </div>
            </td>
            <td class="whitespace-nowrap px-4 py-5 text-center text-sm font-semibold">
              {{ formatCurrency(job.muc_luong) }}
            </td>
            <td class="px-4 py-5 text-center text-sm text-slate-600 dark:text-slate-400">
              {{ formatDate(job.ngay_het_han) }}
            </td>
            <td class="px-4 py-5 text-center">
              <span :class="['inline-flex whitespace-nowrap rounded-full px-3 py-1 text-xs font-bold', statusColors[job.trang_thai]]">
                {{ statusMap[job.trang_thai] }}
              </span>
            </td>
            <td class="px-4 py-5">
              <div class="flex justify-center gap-1.5">
                <button
                  @click="openEditModal(job)"
                  class="rounded-lg p-2 text-slate-400 transition-colors hover:text-[#2463eb]"
                  title="Chỉnh sửa"
                >
                  <span class="material-symbols-outlined text-xl">edit</span>
                </button>
                <button
                  @click="toggleStatus(job)"
                  class="rounded-lg p-2 text-slate-400 transition-colors hover:text-amber-600"
                  title="Đổi trạng thái"
                >
                  <span class="material-symbols-outlined text-xl">{{ job.trang_thai === 1 ? 'pause_circle' : 'play_circle' }}</span>
                </button>
                <button
                  @click="confirmDelete(job)"
                  class="rounded-lg p-2 text-slate-400 transition-colors hover:text-red-600"
                  title="Xóa"
                >
                  <span class="material-symbols-outlined text-xl">delete</span>
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="!loading && jobs.length > 0" class="flex items-center justify-between border-t border-slate-200 bg-slate-50/70 px-6 py-4 dark:border-slate-800 dark:bg-slate-800/40">
      <div class="text-sm text-slate-500">
        Hiển thị
        <span class="font-medium text-slate-900 dark:text-slate-100">{{ (currentPage - 1) * perPage + 1 }}</span>
        đến
        <span class="font-medium text-slate-900 dark:text-slate-100">{{ Math.min(currentPage * perPage, totalJobs) }}</span>
        trên
        <span class="font-medium text-slate-900 dark:text-slate-100">{{ totalJobs }}</span>
        tin tuyển dụng
      </div>
      <div class="flex items-center gap-2">
        <button
          @click="goToPreviousPage"
          :disabled="currentPage === 1"
          class="rounded-lg border border-slate-200 p-2 transition-colors hover:bg-slate-100 disabled:opacity-50 dark:border-slate-700 dark:hover:bg-slate-700"
        >
          <span class="material-symbols-outlined">chevron_left</span>
        </button>
        <button
          v-for="page in totalPages"
          :key="page"
          @click="goToPage(page)"
          :class="['h-8 w-8 rounded-lg text-sm font-medium transition-colors', currentPage === page ? 'bg-[#2463eb] text-white' : 'hover:bg-slate-100 dark:hover:bg-slate-700']"
        >
          {{ page }}
        </button>
        <button
          @click="goToNextPage"
          :disabled="currentPage === totalPages"
          class="rounded-lg border border-slate-200 p-2 transition-colors hover:bg-slate-100 disabled:opacity-50 dark:border-slate-700 dark:hover:bg-slate-700"
        >
          <span class="material-symbols-outlined">chevron_right</span>
        </button>
      </div>
    </div>
  </div>

  <div v-if="showEditModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4">
    <div class="max-h-[90vh] w-full max-w-3xl overflow-y-auto rounded-2xl bg-white shadow-xl dark:bg-slate-900">
      <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4 dark:border-slate-800">
        <h3 class="text-lg font-semibold">Chỉnh sửa tin tuyển dụng</h3>
        <button @click="showEditModal = false" class="text-slate-400 hover:text-slate-600">
          <span class="material-symbols-outlined">close</span>
        </button>
      </div>

      <form @submit.prevent="submitEdit" class="space-y-4 p-6">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
          <div class="md:col-span-2">
            <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Tiêu đề</label>
            <input v-model="formData.tieu_de" type="text" required class="w-full rounded-lg border border-slate-300 px-3 py-2 outline-none focus:ring-2 focus:ring-[#2463eb] dark:border-slate-700 dark:bg-slate-800" />
          </div>

          <div>
            <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Địa điểm</label>
            <input v-model="formData.dia_diem_lam_viec" type="text" class="w-full rounded-lg border border-slate-300 px-3 py-2 outline-none focus:ring-2 focus:ring-[#2463eb] dark:border-slate-700 dark:bg-slate-800" />
          </div>

          <div>
            <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Hình thức</label>
            <select v-model="formData.hinh_thuc_lam_viec" class="w-full rounded-lg border border-slate-300 px-3 py-2 outline-none focus:ring-2 focus:ring-[#2463eb] dark:border-slate-700 dark:bg-slate-800">
              <option v-for="option in workModeOptions" :key="option.value" :value="option.value">
                {{ option.label }}
              </option>
            </select>
          </div>

          <div>
            <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Cấp bậc</label>
            <input v-model="formData.cap_bac" type="text" class="w-full rounded-lg border border-slate-300 px-3 py-2 outline-none focus:ring-2 focus:ring-[#2463eb] dark:border-slate-700 dark:bg-slate-800" />
          </div>

          <div>
            <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Số lượng tuyển</label>
            <input v-model="formData.so_luong_tuyen" type="number" min="1" class="w-full rounded-lg border border-slate-300 px-3 py-2 outline-none focus:ring-2 focus:ring-[#2463eb] dark:border-slate-700 dark:bg-slate-800" />
          </div>

          <div>
            <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Mức lương</label>
            <input v-model="formData.muc_luong" type="number" min="0" class="w-full rounded-lg border border-slate-300 px-3 py-2 outline-none focus:ring-2 focus:ring-[#2463eb] dark:border-slate-700 dark:bg-slate-800" />
          </div>

          <div>
            <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Kinh nghiệm yêu cầu</label>
            <input v-model="formData.kinh_nghiem_yeu_cau" type="text" class="w-full rounded-lg border border-slate-300 px-3 py-2 outline-none focus:ring-2 focus:ring-[#2463eb] dark:border-slate-700 dark:bg-slate-800" />
          </div>

          <div>
            <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Ngày giờ hết hạn</label>
            <input v-model="formData.ngay_het_han" type="datetime-local" class="w-full rounded-lg border border-slate-300 px-3 py-2 outline-none focus:ring-2 focus:ring-[#2463eb] dark:border-slate-700 dark:bg-slate-800" />
          </div>

          <div>
            <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Trạng thái</label>
            <select v-model.number="formData.trang_thai" class="w-full rounded-lg border border-slate-300 px-3 py-2 outline-none focus:ring-2 focus:ring-[#2463eb] dark:border-slate-700 dark:bg-slate-800">
              <option :value="1">Đang hoạt động</option>
              <option :value="0">Tạm ngưng</option>
            </select>
          </div>

          <div class="md:col-span-2">
            <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Mô tả công việc</label>
            <textarea v-model="formData.mo_ta_cong_viec" rows="6" class="w-full rounded-lg border border-slate-300 px-3 py-2 outline-none focus:ring-2 focus:ring-[#2463eb] dark:border-slate-700 dark:bg-slate-800"></textarea>
          </div>
        </div>

        <div class="flex justify-end gap-3 pt-4">
          <button type="button" @click="showEditModal = false" class="rounded-lg border border-slate-300 px-4 py-2 transition-colors hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-800">
            Hủy
          </button>
          <button type="submit" class="rounded-lg bg-[#2463eb] px-5 py-2 text-white transition-colors hover:bg-[#2463eb]/90">
            Lưu thay đổi
          </button>
        </div>
      </form>
    </div>
  </div>

  <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4">
    <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl dark:bg-slate-900">
      <div class="mb-3 flex items-center gap-3">
        <span class="material-symbols-outlined text-2xl text-red-500">warning</span>
        <h3 class="text-lg font-semibold">Xóa tin tuyển dụng</h3>
      </div>
      <p class="text-sm leading-6 text-slate-500">
        Bạn có chắc muốn xóa tin <strong>{{ deletingJob?.tieu_de }}</strong>? Hành động này không thể hoàn tác.
      </p>
      <div class="mt-6 flex justify-end gap-3">
        <button @click="showDeleteModal = false" class="rounded-lg border border-slate-300 px-4 py-2 transition-colors hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-800">
          Hủy
        </button>
        <button @click="deleteJob" class="rounded-lg bg-red-600 px-4 py-2 text-white transition-colors hover:bg-red-700">
          Xóa tin
        </button>
      </div>
    </div>
  </div>
</template>
