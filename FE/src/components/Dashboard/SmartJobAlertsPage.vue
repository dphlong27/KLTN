<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { smartJobAlertService } from '@/services/api'
import { useNotify } from '@/composables/useNotify'

const notify = useNotify()

const loading = ref(false)
const actionId = ref(null)
const alerts = ref([])
const stats = ref(null)
const filters = reactive({
  match_level: '',
  trang_thai: '',
  min_score: '',
})
const pagination = reactive({
  current_page: 1,
  last_page: 1,
  per_page: 12,
  total: 0,
})

const levelMeta = {
  excellent: {
    label: 'Rất phù hợp',
    classes: 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300',
    ring: 'ring-emerald-200 dark:ring-emerald-500/30',
  },
  strong: {
    label: 'Phù hợp cao',
    classes: 'bg-blue-50 text-blue-700 dark:bg-blue-500/10 dark:text-blue-300',
    ring: 'ring-blue-200 dark:ring-blue-500/30',
  },
  good: {
    label: 'Nên xem',
    classes: 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300',
    ring: 'ring-amber-200 dark:ring-amber-500/30',
  },
  medium: {
    label: 'Có tín hiệu',
    classes: 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300',
    ring: 'ring-slate-200 dark:ring-slate-700',
  },
}

const statCards = computed(() => [
  {
    label: 'Smart alert',
    value: stats.value?.total || 0,
    helper: 'Tổng job đang được gợi ý',
    icon: 'notifications_active',
    tone: 'bg-blue-50 text-blue-700 dark:bg-blue-500/10 dark:text-blue-300',
  },
  {
    label: 'Alert mới',
    value: stats.value?.new || 0,
    helper: 'Chưa đọc',
    icon: 'fiber_new',
    tone: 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300',
  },
  {
    label: 'Match mạnh',
    value: stats.value?.strong || 0,
    helper: 'Từ 65% trở lên',
    icon: 'verified',
    tone: 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300',
  },
  {
    label: 'Điểm trung bình',
    value: `${stats.value?.average_score || 0}%`,
    helper: 'Theo các alert hiện có',
    icon: 'query_stats',
    tone: 'bg-cyan-50 text-cyan-700 dark:bg-cyan-500/10 dark:text-cyan-300',
  },
])

const formatCurrency = (value) => {
  if (value === null || value === undefined || value === '') return 'Thỏa thuận'
  return `${new Intl.NumberFormat('vi-VN').format(Number(value))} đ`
}

const formatSalary = (job) => {
  if (!job) return 'Thỏa thuận'
  if (job.muc_luong_tu && job.muc_luong_den) {
    return `${formatCurrency(job.muc_luong_tu)} - ${formatCurrency(job.muc_luong_den)}`
  }
  if (job.muc_luong) return formatCurrency(job.muc_luong)
  return 'Thỏa thuận'
}

const formatDate = (value) => {
  if (!value) return 'Vừa cập nhật'
  return new Intl.DateTimeFormat('vi-VN', { dateStyle: 'medium' }).format(new Date(value))
}

const metaForLevel = (level) => levelMeta[level] || levelMeta.medium

const progressColor = (score) => {
  if (score >= 80) return 'bg-emerald-500'
  if (score >= 65) return 'bg-blue-500'
  if (score >= 50) return 'bg-amber-500'
  return 'bg-slate-400'
}

const fetchStats = async () => {
  try {
    const response = await smartJobAlertService.getStats()
    stats.value = response?.data || null
  } catch (error) {
    notify.apiError(error, 'Không tải được thống kê Smart Job Alert.')
  }
}

const fetchAlerts = async (page = 1) => {
  loading.value = true
  try {
    const response = await smartJobAlertService.getAlerts({
      ...filters,
      page,
      per_page: pagination.per_page,
    })
    const payload = response?.data || {}
    alerts.value = payload.data || []
    pagination.current_page = payload.current_page || 1
    pagination.last_page = payload.last_page || 1
    pagination.total = payload.total || 0
  } catch (error) {
    alerts.value = []
    notify.apiError(error, 'Không tải được Smart Job Alert.')
  } finally {
    loading.value = false
  }
}

const applyFilters = async () => {
  await fetchAlerts(1)
}

const resetFilters = async () => {
  filters.match_level = ''
  filters.trang_thai = ''
  filters.min_score = ''
  await fetchAlerts(1)
}

const markAsRead = async (alert) => {
  if (!alert?.id || actionId.value) return
  actionId.value = alert.id
  try {
    const response = await smartJobAlertService.markAsRead(alert.id)
    const updated = response?.data
    alerts.value = alerts.value.map((item) => Number(item.id) === Number(alert.id) ? updated : item)
    await fetchStats()
  } catch (error) {
    notify.apiError(error, 'Không đánh dấu được alert.')
  } finally {
    actionId.value = null
  }
}

const dismissAlert = async (alert) => {
  if (!alert?.id || actionId.value) return
  actionId.value = alert.id
  try {
    await smartJobAlertService.dismiss(alert.id)
    alerts.value = alerts.value.filter((item) => Number(item.id) !== Number(alert.id))
    pagination.total = Math.max(0, pagination.total - 1)
    await fetchStats()
    notify.info('Đã bỏ qua alert này.')
  } catch (error) {
    notify.apiError(error, 'Không bỏ qua được alert.')
  } finally {
    actionId.value = null
  }
}

const changePage = async (page) => {
  if (page < 1 || page > pagination.last_page || page === pagination.current_page) return
  await fetchAlerts(page)
}

onMounted(async () => {
  await Promise.all([fetchStats(), fetchAlerts(1)])
})
</script>

<template>
  <div class="space-y-8">
    <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
      <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
        <div>
          <p class="text-xs font-bold uppercase tracking-[0.24em] text-[#2463eb]">Smart Job Alert</p>
          <h1 class="mt-2 text-3xl font-black tracking-tight text-slate-950 dark:text-white">Việc làm phù hợp vừa xuất hiện</h1>
          <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-500 dark:text-slate-400">
            Hệ thống tự chấm nhanh các job mới từ công ty bạn theo dõi dựa trên CV, kỹ năng, ngành mục tiêu và kinh nghiệm.
          </p>
        </div>
        <RouterLink to="/followed-companies" class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 px-4 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">
          <span class="material-symbols-outlined text-[18px]">apartment</span>
          Công ty đã follow
        </RouterLink>
      </div>
    </section>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
      <article v-for="card in statCards" :key="card.label" class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="flex size-11 items-center justify-center rounded-xl" :class="card.tone">
          <span class="material-symbols-outlined">{{ card.icon }}</span>
        </div>
        <p class="mt-4 text-sm font-semibold text-slate-500 dark:text-slate-400">{{ card.label }}</p>
        <p class="mt-1 text-2xl font-black text-slate-950 dark:text-white">{{ card.value }}</p>
        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ card.helper }}</p>
      </article>
    </div>

    <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
      <div class="grid grid-cols-1 gap-3 md:grid-cols-5">
        <select v-model="filters.match_level" class="rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-[#2463eb] dark:border-slate-700 dark:bg-slate-950 dark:text-white">
          <option value="">Tất cả mức phù hợp</option>
          <option value="excellent">Rất phù hợp</option>
          <option value="strong">Phù hợp cao</option>
          <option value="good">Nên xem</option>
        </select>
        <select v-model="filters.trang_thai" class="rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-[#2463eb] dark:border-slate-700 dark:bg-slate-950 dark:text-white">
          <option value="">Đang hiển thị</option>
          <option value="new">Chưa đọc</option>
          <option value="read">Đã đọc</option>
          <option value="dismissed">Đã bỏ qua</option>
        </select>
        <input v-model="filters.min_score" type="number" min="0" max="100" class="rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-[#2463eb] dark:border-slate-700 dark:bg-slate-950 dark:text-white" placeholder="Điểm tối thiểu" />
        <button class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#2463eb] px-4 py-3 text-sm font-bold text-white transition hover:bg-blue-700 disabled:opacity-60" type="button" :disabled="loading" @click="applyFilters">
          <span class="material-symbols-outlined text-[18px]">filter_alt</span>
          Lọc
        </button>
        <button class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 px-4 py-3 text-sm font-bold text-slate-600 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800" type="button" @click="resetFilters">
          <span class="material-symbols-outlined text-[18px]">restart_alt</span>
          Xóa lọc
        </button>
      </div>
    </section>

    <section class="grid grid-cols-1 gap-5 xl:grid-cols-2">
      <article
        v-for="alert in alerts"
        :key="alert.id"
        class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm ring-1 transition hover:-translate-y-0.5 hover:shadow-md dark:border-slate-800 dark:bg-slate-900"
        :class="metaForLevel(alert.match_level).ring"
      >
        <div class="flex items-start justify-between gap-4">
          <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2">
              <span class="rounded-full px-2.5 py-1 text-xs font-bold" :class="metaForLevel(alert.match_level).classes">
                {{ metaForLevel(alert.match_level).label }}
              </span>
              <span v-if="alert.trang_thai === 'new'" class="rounded-full bg-rose-50 px-2.5 py-1 text-xs font-bold text-rose-600 dark:bg-rose-500/10 dark:text-rose-300">Mới</span>
            </div>
            <h2 class="mt-3 line-clamp-2 text-xl font-black text-slate-950 dark:text-white">{{ alert.job?.tieu_de || 'Tin tuyển dụng' }}</h2>
            <p class="mt-1 text-sm font-semibold text-slate-600 dark:text-slate-300">{{ alert.company?.ten_cong_ty || 'Công ty' }}</p>
          </div>
          <div class="shrink-0 text-right">
            <p class="text-3xl font-black text-[#2463eb]">{{ Math.round(alert.match_score || 0) }}%</p>
            <p class="text-xs font-bold uppercase text-slate-400">match</p>
          </div>
        </div>

        <div class="mt-4 h-2 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
          <div class="h-full rounded-full" :class="progressColor(alert.match_score)" :style="{ width: `${Math.min(100, Math.max(5, alert.match_score || 0))}%` }"></div>
        </div>

        <div class="mt-4 grid grid-cols-1 gap-3 text-sm text-slate-600 dark:text-slate-300 sm:grid-cols-3">
          <div class="rounded-xl bg-slate-50 p-3 dark:bg-slate-950">
            <p class="text-xs font-bold uppercase text-slate-400">Địa điểm</p>
            <p class="mt-1 font-semibold">{{ alert.job?.dia_diem_lam_viec || 'Linh hoạt' }}</p>
          </div>
          <div class="rounded-xl bg-slate-50 p-3 dark:bg-slate-950">
            <p class="text-xs font-bold uppercase text-slate-400">Hình thức</p>
            <p class="mt-1 font-semibold">{{ alert.job?.hinh_thuc_lam_viec || 'Chưa rõ' }}</p>
          </div>
          <div class="rounded-xl bg-slate-50 p-3 dark:bg-slate-950">
            <p class="text-xs font-bold uppercase text-slate-400">Lương</p>
            <p class="mt-1 font-semibold">{{ formatSalary(alert.job) }}</p>
          </div>
        </div>

        <div v-if="alert.reasons?.length" class="mt-4 space-y-2">
          <div v-for="reason in alert.reasons" :key="reason" class="flex gap-2 text-sm leading-6 text-slate-600 dark:text-slate-300">
            <span class="material-symbols-outlined mt-0.5 text-[17px] text-[#2463eb]">check_circle</span>
            <span>{{ reason }}</span>
          </div>
        </div>

        <div class="mt-4 flex flex-wrap gap-2">
          <span v-for="skill in alert.matched_skills?.slice(0, 6)" :key="skill" class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300">
            {{ skill }}
          </span>
          <span v-for="skill in alert.missing_skills?.slice(0, 3)" :key="`missing-${skill}`" class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-500 dark:bg-slate-800 dark:text-slate-300">
            + {{ skill }}
          </span>
        </div>

        <div class="mt-5 flex flex-col gap-3 border-t border-slate-100 pt-4 dark:border-slate-800 sm:flex-row sm:items-center sm:justify-between">
          <p class="text-xs text-slate-500 dark:text-slate-400">Gợi ý ngày {{ formatDate(alert.notified_at || alert.created_at) }}</p>
          <div class="flex flex-wrap gap-2">
            <button v-if="alert.trang_thai === 'new'" class="inline-flex items-center justify-center gap-1 rounded-xl border border-slate-200 px-3 py-2 text-sm font-bold text-slate-600 transition hover:bg-slate-50 disabled:opacity-60 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800" type="button" :disabled="actionId === alert.id" @click="markAsRead(alert)">
              <span class="material-symbols-outlined text-[17px]">done</span>
              Đã đọc
            </button>
            <button class="inline-flex items-center justify-center gap-1 rounded-xl border border-slate-200 px-3 py-2 text-sm font-bold text-slate-600 transition hover:bg-slate-50 disabled:opacity-60 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800" type="button" :disabled="actionId === alert.id" @click="dismissAlert(alert)">
              <span class="material-symbols-outlined text-[17px]">visibility_off</span>
              Bỏ qua
            </button>
            <RouterLink :to="`/jobs/${alert.job?.id}`" class="inline-flex items-center justify-center gap-1 rounded-xl bg-[#2463eb] px-3 py-2 text-sm font-bold text-white transition hover:bg-blue-700" @click="markAsRead(alert)">
              Xem job
              <span class="material-symbols-outlined text-[17px]">arrow_forward</span>
            </RouterLink>
          </div>
        </div>
      </article>

      <div v-if="!alerts.length && !loading" class="col-span-full rounded-xl border border-dashed border-slate-300 bg-white p-10 text-center dark:border-slate-700 dark:bg-slate-900">
        <span class="material-symbols-outlined text-5xl text-slate-300">notifications_active</span>
        <h2 class="mt-3 text-xl font-black text-slate-900 dark:text-white">Chưa có smart alert phù hợp</h2>
        <p class="mx-auto mt-2 max-w-xl text-sm leading-6 text-slate-500 dark:text-slate-400">
          Hãy theo dõi thêm công ty, cập nhật kỹ năng/CV và hệ thống sẽ tự báo khi có job mới đủ khớp.
        </p>
      </div>

      <div v-if="loading" class="col-span-full rounded-xl border border-slate-200 bg-white p-10 text-center text-sm text-slate-500 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-400">
        Đang tải Smart Job Alert...
      </div>
    </section>

    <div v-if="pagination.last_page > 1" class="flex items-center justify-center gap-2">
      <button class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-bold text-slate-600 disabled:opacity-40 dark:border-slate-700 dark:text-slate-300" type="button" :disabled="pagination.current_page <= 1" @click="changePage(pagination.current_page - 1)">Trước</button>
      <span class="text-sm font-semibold text-slate-500 dark:text-slate-400">Trang {{ pagination.current_page }} / {{ pagination.last_page }}</span>
      <button class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-bold text-slate-600 disabled:opacity-40 dark:border-slate-700 dark:text-slate-300" type="button" :disabled="pagination.current_page >= pagination.last_page" @click="changePage(pagination.current_page + 1)">Sau</button>
    </div>
  </div>
</template>
