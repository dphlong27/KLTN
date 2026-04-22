<script setup>
import { computed, onMounted, ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { employerApplicationService, employerCompanyService, employerJobService } from '@/services/api'
import { useNotify } from '@/composables/useNotify'

const router = useRouter()
const notify = useNotify()

const loading = ref(false)
const company = ref(null)
const jobs = ref([])
const applications = ref([])

const activeJobs = computed(() => jobs.value.filter((job) => Number(job.trang_thai) === 1))
const pendingApplications = computed(() => applications.value.filter((item) => Number(item.trang_thai) === 0))
const interviewApplications = computed(() => applications.value.filter((item) => [2, 3].includes(Number(item.trang_thai))))
const hiredApplications = computed(() => applications.value.filter((item) => Number(item.trang_thai) === 4))

const companyCompletion = computed(() => {
  if (!company.value) return 0

  const fields = [
    company.value.ten_cong_ty,
    company.value.mo_ta,
    company.value.dia_chi,
    company.value.website,
    company.value.nganh_nghe_id,
    company.value.logo,
  ]

  return Math.round((fields.filter(Boolean).length / fields.length) * 100)
})

const heroStats = computed(() => [
  {
    label: 'Tin đang tuyển',
    value: activeJobs.value.length,
    helper: `${jobs.value.length} tin trong hệ thống`,
    icon: 'work',
  },
  {
    label: 'Hồ sơ mới',
    value: pendingApplications.value.length,
    helper: 'Cần xem và phản hồi sớm',
    icon: 'group_add',
  },
  {
    label: 'Đang phỏng vấn',
    value: interviewApplications.value.length,
    helper: 'Ứng viên trong pipeline',
    icon: 'event_available',
  },
  {
    label: 'Đã tuyển',
    value: hiredApplications.value.length,
    helper: 'Ứng viên trúng tuyển',
    icon: 'workspace_premium',
  },
])

const featureCards = [
  {
    icon: 'rocket_launch',
    title: 'Đăng tuyển nhanh',
    description: 'Tạo tin tuyển dụng, quản lý trạng thái và theo dõi hiệu quả ứng tuyển trong một luồng duy nhất.',
    to: '/employer/jobs',
    cta: 'Quản lý tin tuyển dụng',
  },
  {
    icon: 'psychology',
    title: 'AI lọc ứng viên',
    description: 'So sánh CV, xem điểm phù hợp, giải thích AI và ưu tiên ứng viên có khả năng phù hợp cao.',
    to: '/employer/candidates',
    cta: 'Xem kho ứng viên',
  },
  {
    icon: 'groups',
    title: 'HR team workspace',
    description: 'Phân quyền HR nội bộ, quản lý lời mời, ownership và nhật ký thao tác theo công ty.',
    to: '/employer/hr-management',
    cta: 'Quản lý nhân sự HR',
  },
  {
    icon: 'mark_email_unread',
    title: 'Thông báo realtime',
    description: 'Theo dõi ứng tuyển, lịch phỏng vấn và cập nhật quan trọng qua notification center realtime.',
    to: '/employer/interviews',
    cta: 'Xem lịch phỏng vấn',
  },
]

const actionItems = computed(() => {
  const items = []

  if (!company.value) {
    items.push({
      title: 'Tạo hồ sơ công ty',
      description: 'Hoàn thiện thông tin doanh nghiệp để bắt đầu đăng tuyển và tăng độ tin cậy với ứng viên.',
      to: '/employer/company',
      icon: 'domain_add',
    })
  } else if (companyCompletion.value < 80) {
    items.push({
      title: 'Hoàn thiện hồ sơ công ty',
      description: `Hồ sơ công ty hiện đạt ${companyCompletion.value}%. Bổ sung mô tả, website hoặc logo để tăng uy tín.`,
      to: '/employer/company',
      icon: 'edit_note',
    })
  }

  if (!activeJobs.value.length) {
    items.push({
      title: 'Kích hoạt tin tuyển dụng đầu tiên',
      description: 'Đăng hoặc bật lại tin tuyển dụng để hệ thống bắt đầu thu hút ứng viên phù hợp.',
      to: '/employer/jobs',
      icon: 'post_add',
    })
  }

  if (pendingApplications.value.length) {
    items.push({
      title: 'Xử lý hồ sơ mới',
      description: `Có ${pendingApplications.value.length} hồ sơ đang chờ xem. Nên phản hồi sớm để không bỏ lỡ ứng viên tốt.`,
      to: '/employer/interviews',
      icon: 'priority_high',
    })
  }

  if (!items.length) {
    items.push({
      title: 'Theo dõi dashboard tuyển dụng',
      description: 'Pipeline đang ổn định. Hãy xem dashboard để nắm sức khỏe tuyển dụng theo thời gian thực.',
      to: '/employer',
      icon: 'monitoring',
    })
  }

  return items.slice(0, 3)
})

const loadHome = async () => {
  loading.value = true

  try {
    const [companyRes, jobsRes, applicationsRes] = await Promise.all([
      employerCompanyService.getCompany().catch(() => null),
      employerJobService.getJobs({ per_page: 30 }),
      employerApplicationService.getApplications({ per_page: 30 }),
    ])

    company.value = companyRes?.data || null
    jobs.value = jobsRes?.data?.data || []
    applications.value = applicationsRes?.data?.data || []
  } catch (error) {
    notify.apiError(error, 'Không tải được trang chủ nhà tuyển dụng.')
  } finally {
    loading.value = false
  }
}

const goToCreateJob = () => router.push('/employer/jobs')
const goToDashboard = () => router.push('/employer')

onMounted(loadHome)
</script>

<template>
  <div class="space-y-8">
    <section class="relative overflow-hidden rounded-[34px] border border-slate-200 bg-slate-950 p-7 text-white shadow-2xl shadow-slate-300/50 dark:border-slate-800 dark:shadow-black/30 lg:p-10">
      <div class="absolute inset-0 bg-[radial-gradient(circle_at_12%_18%,rgba(56,189,248,0.32),transparent_28%),radial-gradient(circle_at_82%_12%,rgba(34,197,94,0.2),transparent_28%),linear-gradient(135deg,#07111f_0%,#102449_48%,#0f172a_100%)]"></div>
      <div class="absolute -right-20 top-12 h-64 w-64 rounded-full border border-white/10"></div>
      <div class="absolute -bottom-28 left-1/2 h-72 w-72 rounded-full bg-blue-500/20 blur-3xl"></div>

      <div class="relative z-10 grid gap-10 lg:grid-cols-[minmax(0,1.2fr)_420px] lg:items-center">
        <div>
          <span class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-4 py-2 text-xs font-black uppercase tracking-[0.24em] text-blue-100">
            <span class="material-symbols-outlined text-base">business_center</span>
            Employer Center
          </span>
          <h1 class="mt-6 max-w-3xl text-4xl font-black tracking-tight sm:text-5xl">
            Tuyển dụng thông minh hơn với AI Recruitment Workspace
          </h1>
          <p class="mt-5 max-w-2xl text-base leading-8 text-blue-100/80">
            Trang chủ riêng cho nhà tuyển dụng: đăng tin, quản lý ứng viên, phối hợp HR và theo dõi pipeline tuyển dụng trong một không gian làm việc thống nhất.
          </p>

          <div class="mt-8 flex flex-col gap-3 sm:flex-row">
            <button
              class="inline-flex items-center justify-center gap-2 rounded-2xl bg-white px-5 py-3 text-sm font-black text-slate-950 shadow-xl shadow-black/20 transition hover:-translate-y-0.5 hover:bg-blue-50"
              type="button"
              @click="goToCreateJob"
            >
              <span class="material-symbols-outlined text-[18px]">add_circle</span>
              Đăng tin / quản lý tin
            </button>
            <button
              class="inline-flex items-center justify-center gap-2 rounded-2xl border border-white/15 bg-white/10 px-5 py-3 text-sm font-bold text-white transition hover:bg-white/15"
              type="button"
              @click="goToDashboard"
            >
              <span class="material-symbols-outlined text-[18px]">monitoring</span>
              Xem dashboard vận hành
            </button>
          </div>
        </div>

        <div class="rounded-[28px] border border-white/15 bg-white/10 p-5 backdrop-blur">
          <div class="flex items-center justify-between gap-4">
            <div>
              <p class="text-xs font-bold uppercase tracking-[0.24em] text-blue-100/70">Doanh nghiệp</p>
              <h2 class="mt-2 text-xl font-black">{{ company?.ten_cong_ty || 'Chưa thiết lập công ty' }}</h2>
            </div>
            <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-white text-slate-950">
              <span class="material-symbols-outlined">domain</span>
            </span>
          </div>

          <div class="mt-5 rounded-2xl bg-slate-950/35 p-4">
            <div class="flex items-center justify-between text-sm">
              <span class="font-semibold text-blue-100/80">Mức hoàn thiện hồ sơ</span>
              <span class="font-black">{{ companyCompletion }}%</span>
            </div>
            <div class="mt-3 h-2 overflow-hidden rounded-full bg-white/10">
              <div class="h-full rounded-full bg-emerald-400" :style="{ width: `${companyCompletion}%` }"></div>
            </div>
          </div>

          <div class="mt-5 grid grid-cols-2 gap-3">
            <div
              v-for="stat in heroStats"
              :key="stat.label"
              class="rounded-2xl border border-white/10 bg-white/10 p-4"
            >
              <span class="material-symbols-outlined text-blue-100">{{ stat.icon }}</span>
              <p class="mt-3 text-2xl font-black">{{ loading ? '...' : stat.value }}</p>
              <p class="mt-1 text-xs font-bold text-blue-100/80">{{ stat.label }}</p>
              <p class="mt-1 text-[11px] text-blue-100/55">{{ stat.helper }}</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="grid grid-cols-1 gap-5 lg:grid-cols-4">
      <RouterLink
        v-for="feature in featureCards"
        :key="feature.title"
        :to="feature.to"
        class="group rounded-[26px] border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:border-blue-200 hover:shadow-xl hover:shadow-blue-100/70 dark:border-slate-800 dark:bg-slate-900 dark:hover:border-blue-500/40 dark:hover:shadow-black/30"
      >
        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[#2463eb]/10 text-[#2463eb] transition group-hover:bg-[#2463eb] group-hover:text-white">
          <span class="material-symbols-outlined">{{ feature.icon }}</span>
        </div>
        <h3 class="mt-5 text-lg font-black text-slate-900 dark:text-white">{{ feature.title }}</h3>
        <p class="mt-3 text-sm leading-7 text-slate-500 dark:text-slate-400">{{ feature.description }}</p>
        <span class="mt-5 inline-flex items-center gap-1 text-sm font-black text-[#2463eb]">
          {{ feature.cta }}
          <span class="material-symbols-outlined text-[18px] transition group-hover:translate-x-1">arrow_forward</span>
        </span>
      </RouterLink>
    </section>

    <section class="grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,1fr)_420px]">
      <div class="rounded-[28px] border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
          <div>
            <p class="text-xs font-black uppercase tracking-[0.24em] text-[#2463eb]">Quy trình gợi ý</p>
            <h2 class="mt-2 text-2xl font-black text-slate-900 dark:text-white">Tuyển dụng theo 4 bước</h2>
          </div>
          <RouterLink to="/employer/jobs" class="text-sm font-black text-[#2463eb] hover:underline">Bắt đầu đăng tuyển</RouterLink>
        </div>

        <div class="mt-6 grid grid-cols-1 gap-4 md:grid-cols-4">
          <div
            v-for="(step, index) in ['Hoàn thiện công ty', 'Đăng tin tuyển dụng', 'AI lọc ứng viên', 'Phỏng vấn & offer']"
            :key="step"
            class="rounded-2xl border border-slate-100 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950"
          >
            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-white text-sm font-black text-[#2463eb] shadow-sm dark:bg-slate-900">
              {{ index + 1 }}
            </div>
            <p class="mt-4 font-black text-slate-900 dark:text-white">{{ step }}</p>
            <p class="mt-2 text-sm leading-6 text-slate-500 dark:text-slate-400">
              {{ index === 0 ? 'Tạo niềm tin với ứng viên.' : index === 1 ? 'Mở vị trí và theo dõi hiệu quả.' : index === 2 ? 'Ưu tiên CV phù hợp bằng AI.' : 'Chốt lịch, đánh giá và gửi offer.' }}
            </p>
          </div>
        </div>
      </div>

      <aside class="rounded-[28px] border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <p class="text-xs font-black uppercase tracking-[0.24em] text-amber-500">Việc nên làm</p>
        <h2 class="mt-2 text-2xl font-black text-slate-900 dark:text-white">Ưu tiên hôm nay</h2>
        <div class="mt-5 space-y-3">
          <RouterLink
            v-for="item in actionItems"
            :key="item.title"
            :to="item.to"
            class="flex gap-3 rounded-2xl border border-slate-100 bg-slate-50 p-4 transition hover:border-blue-200 hover:bg-blue-50 dark:border-slate-800 dark:bg-slate-950 dark:hover:border-blue-500/40 dark:hover:bg-blue-500/10"
          >
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white text-[#2463eb] dark:bg-slate-900">
              <span class="material-symbols-outlined text-[20px]">{{ item.icon }}</span>
            </span>
            <span>
              <span class="block font-black text-slate-900 dark:text-white">{{ item.title }}</span>
              <span class="mt-1 block text-sm leading-6 text-slate-500 dark:text-slate-400">{{ item.description }}</span>
            </span>
          </RouterLink>
        </div>
      </aside>
    </section>
  </div>
</template>
