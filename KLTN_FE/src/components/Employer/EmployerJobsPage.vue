<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { employerJobService, publicCatalogService } from '@/services/api'

const loading = ref(true)
const error = ref('')
const success = ref('')
const submitting = ref(false)
const jobs = ref([])
const industries = ref([])
const showModal = ref(false)
const editingId = ref(null)
const filter = ref('tat_ca')
const selectedIndustryIds = ref([])

const jobTypeOptions = [
  { value: 'full_time', label: 'Full-time' },
  { value: 'part_time', label: 'Part-time' },
  { value: 'internship', label: 'Internship' },
  { value: 'freelance', label: 'Freelance' },
  { value: 'remote', label: 'Remote' },
  { value: 'hybrid', label: 'Hybrid' }
]

const form = reactive({
  tieu_de: '',
  mo_ta_cong_viec: '',
  dia_diem_lam_viec: '',
  hinh_thuc_lam_viec: '',
  mo_ta_chi_tiet: '',
  so_luong_tuyen: 1,
  muc_luong: '',
  kinh_nghiem_yeu_cau: '',
  ngay_het_han: '',
  trang_thai: 1
})

const filteredJobs = computed(() => {
  if (filter.value === 'tat_ca') return jobs.value
  if (filter.value === 'dang_hoat_dong') return jobs.value.filter((item) => Number(item.trang_thai) === 1 || String(item.trang_thai).toLowerCase() === 'active')
  return jobs.value.filter((item) => !(Number(item.trang_thai) === 1 || String(item.trang_thai).toLowerCase() === 'active'))
})

const activeCount = computed(() => jobs.value.filter((item) => Number(item.trang_thai) === 1 || String(item.trang_thai).toLowerCase() === 'active').length)
const applicantCount = computed(() => jobs.value.reduce((sum, item) => sum + Number(item.so_luong_ung_tuyen || item.ung_tuyens_count || 0), 0))

const unwrap = (payload) => payload?.data ?? payload ?? []
const asArray = (payload) => {
  const data = unwrap(payload)
  if (Array.isArray(data?.data)) return data.data
  if (Array.isArray(data)) return data
  return []
}

const resetForm = () => {
  editingId.value = null
  form.tieu_de = ''
  form.mo_ta_cong_viec = ''
  form.dia_diem_lam_viec = ''
  form.hinh_thuc_lam_viec = ''
  form.mo_ta_chi_tiet = ''
  form.so_luong_tuyen = 1
  form.muc_luong = ''
  form.kinh_nghiem_yeu_cau = ''
  form.ngay_het_han = ''
  form.trang_thai = 1
  selectedIndustryIds.value = []
}

const formatDate = (value) => {
  if (!value) return 'Chua cap nhat'
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return 'Chua cap nhat'
  return new Intl.DateTimeFormat('vi-VN').format(date)
}

const loadJobs = async () => {
  loading.value = true
  error.value = ''
  try {
    const [jobsResponse, industriesResponse] = await Promise.all([
      employerJobService.getJobs({ per_page: 50 }),
      publicCatalogService.getIndustries()
    ])
    jobs.value = asArray(jobsResponse)
    industries.value = asArray(industriesResponse)
  } catch (err) {
    error.value = err.message || 'Khong the tai tin tuyen dung'
  } finally {
    loading.value = false
  }
}

const openCreate = () => {
  success.value = ''
  error.value = ''
  resetForm()
  showModal.value = true
}

const openEdit = (job) => {
  success.value = ''
  error.value = ''
  editingId.value = job.id
  form.tieu_de = job.tieu_de || ''
  form.mo_ta_cong_viec = job.mo_ta_cong_viec || ''
  form.dia_diem_lam_viec = job.dia_diem_lam_viec || ''
  form.hinh_thuc_lam_viec = job.hinh_thuc_lam_viec || ''
  form.mo_ta_chi_tiet = job.mo_ta_chi_tiet || ''
  form.so_luong_tuyen = Number(job.so_luong_tuyen || 1)
  form.muc_luong = job.muc_luong || ''
  form.kinh_nghiem_yeu_cau = job.kinh_nghiem_yeu_cau || ''
  form.ngay_het_han = String(job.ngay_het_han || '').slice(0, 10)
  form.trang_thai = Number(job.trang_thai) === 1 ? 1 : 0
  selectedIndustryIds.value = (job.chi_tiet_nganh_nghes || job.nganh_nghes || [])
    .map((item) => Number(item.nganh_nghe_id || item.id || item.nganh_nghe?.id))
    .filter((value) => Number.isFinite(value))
  showModal.value = true
}

const closeModal = () => {
  showModal.value = false
  resetForm()
}

const submitJob = async () => {
  if (!form.tieu_de.trim()) {
    error.value = 'Vui long nhap tieu de tin tuyen dung'
    return
  }

  if (!form.hinh_thuc_lam_viec) {
    error.value = 'Vui long chon hinh thuc lam viec'
    return
  }

  if (!selectedIndustryIds.value.length) {
    error.value = 'Vui long chon it nhat 1 nganh nghe'
    return
  }

  submitting.value = true
  error.value = ''
  success.value = ''

  const payload = {
    tieu_de: form.tieu_de.trim(),
    mo_ta_cong_viec: form.mo_ta_cong_viec.trim(),
    dia_diem_lam_viec: form.dia_diem_lam_viec.trim(),
    hinh_thuc_lam_viec: form.hinh_thuc_lam_viec,
    mo_ta_chi_tiet: form.mo_ta_chi_tiet.trim(),
    so_luong_tuyen: Number(form.so_luong_tuyen || 1),
    muc_luong: form.muc_luong === '' ? null : Number(form.muc_luong),
    kinh_nghiem_yeu_cau: form.kinh_nghiem_yeu_cau.trim(),
    ngay_het_han: form.ngay_het_han || null,
    trang_thai: Number(form.trang_thai),
    nganh_nghe_ids: selectedIndustryIds.value,
    nganh_nghes: selectedIndustryIds.value,
    danh_sach_nganh_nghe: selectedIndustryIds.value,
    chi_tiet_nganh_nghes: selectedIndustryIds.value.map((id) => ({ nganh_nghe_id: id }))
  }

  try {
    if (editingId.value) {
      await employerJobService.updateJob(editingId.value, payload)
      success.value = 'Cap nhat tin tuyen dung thanh cong'
    } else {
      await employerJobService.createJob(payload)
      success.value = 'Tao tin tuyen dung thanh cong'
    }
    closeModal()
    await loadJobs()
  } catch (err) {
    error.value = err.message || 'Khong the luu tin tuyen dung'
  } finally {
    submitting.value = false
  }
}

const toggleStatus = async (job) => {
  error.value = ''
  success.value = ''
  try {
    await employerJobService.toggleJobStatus(job.id)
    success.value = 'Da doi trang thai tin tuyen dung'
    await loadJobs()
  } catch (err) {
    error.value = err.message || 'Khong the doi trang thai tin'
  }
}

const removeJob = async (job) => {
  if (!window.confirm(`Xoa tin tuyen dung "${job.tieu_de}"?`)) return

  error.value = ''
  success.value = ''
  try {
    await employerJobService.deleteJob(job.id)
    success.value = 'Da xoa tin tuyen dung'
    await loadJobs()
  } catch (err) {
    error.value = err.message || 'Khong the xoa tin tuyen dung'
  }
}

onMounted(loadJobs)
</script>

<template>
  <div class="space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
      <div>
        <h1 class="text-3xl font-black tracking-tight text-slate-900 dark:text-white">Job Listings</h1>
        <p class="mt-1 text-sm text-slate-500">Tao, cap nhat va quan ly cac tin tuyen dung cua doanh nghiep.</p>
      </div>
      <button class="inline-flex items-center gap-2 rounded-xl bg-[#2463eb] px-5 py-3 text-sm font-bold text-white shadow-lg shadow-[#2463eb]/20 transition hover:bg-[#1d4fcc]" @click="openCreate">
        <span class="material-symbols-outlined text-[20px]">add</span>
        Dang tin moi
      </button>
    </div>

    <div v-if="error" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900/40 dark:bg-red-950/30 dark:text-red-300">{{ error }}</div>
    <div v-if="success" class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700 dark:border-green-900/40 dark:bg-green-950/30 dark:text-green-300">{{ success }}</div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
      <div class="rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900">
        <p class="text-sm text-slate-500">Tong tin da dang</p>
        <p class="mt-2 text-3xl font-bold text-slate-900 dark:text-white">{{ jobs.length }}</p>
      </div>
      <div class="rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900">
        <p class="text-sm text-slate-500">Tin dang hoat dong</p>
        <p class="mt-2 text-3xl font-bold text-green-600">{{ activeCount }}</p>
      </div>
      <div class="rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900">
        <p class="text-sm text-slate-500">Luong ung vien</p>
        <p class="mt-2 text-3xl font-bold text-[#2463eb]">{{ applicantCount }}</p>
      </div>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
      <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-4 py-4 dark:border-slate-800">
        <div class="flex flex-wrap gap-2">
          <button class="rounded-full px-3 py-1.5 text-sm transition" :class="filter === 'tat_ca' ? 'bg-[#2463eb] text-white' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300'" @click="filter = 'tat_ca'">Tat ca</button>
          <button class="rounded-full px-3 py-1.5 text-sm transition" :class="filter === 'dang_hoat_dong' ? 'bg-[#2463eb] text-white' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300'" @click="filter = 'dang_hoat_dong'">Dang hoat dong</button>
          <button class="rounded-full px-3 py-1.5 text-sm transition" :class="filter === 'tam_an' ? 'bg-[#2463eb] text-white' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300'" @click="filter = 'tam_an'">Tam an</button>
        </div>
      </div>

      <div v-if="loading" class="px-4 py-12 text-center text-sm text-slate-500">Dang tai tin tuyen dung...</div>
      <div v-else-if="!filteredJobs.length" class="px-4 py-12 text-center text-sm text-slate-500">Khong co tin tuyen dung nao.</div>
      <div v-else class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
          <thead class="bg-slate-50 dark:bg-slate-800/50">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Tin tuyen dung</th>
              <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Dia diem</th>
              <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Han nop</th>
              <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Trang thai</th>
              <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Thao tac</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
            <tr v-for="item in filteredJobs" :key="item.id">
              <td class="px-4 py-4">
                <p class="font-semibold text-slate-900 dark:text-white">{{ item.tieu_de }}</p>
                <p class="mt-1 text-xs text-slate-500">{{ item.hinh_thuc_lam_viec || 'Dang cap nhat hinh thuc' }} · {{ item.kinh_nghiem_yeu_cau || 'Kinh nghiem dang cap nhat' }}</p>
              </td>
              <td class="px-4 py-4 text-sm text-slate-600 dark:text-slate-300">{{ item.dia_diem_lam_viec || 'Dang cap nhat' }}</td>
              <td class="px-4 py-4 text-sm text-slate-600 dark:text-slate-300">{{ formatDate(item.ngay_het_han) }}</td>
              <td class="px-4 py-4">
                <span class="rounded-full px-3 py-1 text-xs font-semibold" :class="Number(item.trang_thai) === 1 || String(item.trang_thai).toLowerCase() === 'active' ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300'">
                  {{ Number(item.trang_thai) === 1 || String(item.trang_thai).toLowerCase() === 'active' ? 'Dang hoat dong' : 'Tam an' }}
                </span>
              </td>
              <td class="px-4 py-4">
                <div class="flex justify-end gap-2">
                  <button class="rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-600 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800" @click="openEdit(item)">Sua</button>
                  <button class="rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-600 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800" @click="toggleStatus(item)">Doi trang thai</button>
                  <button class="rounded-lg border border-red-200 px-3 py-2 text-sm text-red-600 transition hover:bg-red-50 dark:border-red-900/40" @click="removeJob(item)">Xoa</button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 px-4 py-8">
      <div class="max-h-[90vh] w-full max-w-3xl overflow-auto rounded-2xl bg-white p-6 shadow-2xl dark:bg-slate-900">
        <div class="flex items-center justify-between gap-3">
          <h2 class="text-xl font-bold text-slate-900 dark:text-white">{{ editingId ? 'Cap nhat tin tuyen dung' : 'Tao tin tuyen dung moi' }}</h2>
          <button class="rounded-lg bg-slate-100 p-2 text-slate-500 dark:bg-slate-800" @click="closeModal">
            <span class="material-symbols-outlined text-[20px]">close</span>
          </button>
        </div>

        <div class="mt-6 grid grid-cols-1 gap-4 md:grid-cols-2">
          <label class="space-y-1 md:col-span-2">
            <span class="text-sm font-medium text-slate-600 dark:text-slate-300">Tieu de</span>
            <input v-model="form.tieu_de" class="w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-2.5 dark:border-slate-700 dark:bg-slate-800/60" type="text" />
          </label>
          <label class="space-y-1">
            <span class="text-sm font-medium text-slate-600 dark:text-slate-300">Dia diem lam viec</span>
            <input v-model="form.dia_diem_lam_viec" class="w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-2.5 dark:border-slate-700 dark:bg-slate-800/60" type="text" />
          </label>
          <label class="space-y-1">
            <span class="text-sm font-medium text-slate-600 dark:text-slate-300">Hinh thuc lam viec</span>
            <select v-model="form.hinh_thuc_lam_viec" class="w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-2.5 dark:border-slate-700 dark:bg-slate-800/60">
              <option value="">Chon hinh thuc</option>
              <option v-for="option in jobTypeOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
            </select>
          </label>
          <label class="space-y-1">
            <span class="text-sm font-medium text-slate-600 dark:text-slate-300">So luong tuyen</span>
            <input v-model="form.so_luong_tuyen" class="w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-2.5 dark:border-slate-700 dark:bg-slate-800/60" type="number" min="1" />
          </label>
          <label class="space-y-1">
            <span class="text-sm font-medium text-slate-600 dark:text-slate-300">Muc luong</span>
            <input v-model="form.muc_luong" class="w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-2.5 dark:border-slate-700 dark:bg-slate-800/60" type="number" min="0" />
          </label>
          <label class="space-y-1">
            <span class="text-sm font-medium text-slate-600 dark:text-slate-300">Kinh nghiem yeu cau</span>
            <input v-model="form.kinh_nghiem_yeu_cau" class="w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-2.5 dark:border-slate-700 dark:bg-slate-800/60" type="text" />
          </label>
          <label class="space-y-1">
            <span class="text-sm font-medium text-slate-600 dark:text-slate-300">Ngay het han</span>
            <input v-model="form.ngay_het_han" class="w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-2.5 dark:border-slate-700 dark:bg-slate-800/60" type="date" />
          </label>
          <label class="space-y-1">
            <span class="text-sm font-medium text-slate-600 dark:text-slate-300">Trang thai</span>
            <select v-model="form.trang_thai" class="w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-2.5 dark:border-slate-700 dark:bg-slate-800/60">
              <option :value="1">Dang hoat dong</option>
              <option :value="0">Tam an</option>
            </select>
          </label>
          <div class="space-y-2 md:col-span-2">
            <span class="text-sm font-medium text-slate-600 dark:text-slate-300">Nganh nghe</span>
            <div class="grid grid-cols-1 gap-2 rounded-lg border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-800/60 md:grid-cols-2">
              <label v-for="industry in industries" :key="industry.id" class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-200">
                <input v-model="selectedIndustryIds" :value="industry.id" type="checkbox" class="rounded border-slate-300 text-[#2463eb] focus:ring-[#2463eb]" />
                <span>{{ industry.ten_nganh }}</span>
              </label>
              <p v-if="!industries.length" class="text-sm text-slate-500">Chua tai duoc danh sach nganh nghe.</p>
            </div>
          </div>
          <label class="space-y-1 md:col-span-2">
            <span class="text-sm font-medium text-slate-600 dark:text-slate-300">Mo ta cong viec</span>
            <textarea v-model="form.mo_ta_cong_viec" rows="4" class="w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-2.5 dark:border-slate-700 dark:bg-slate-800/60"></textarea>
          </label>
          <label class="space-y-1 md:col-span-2">
            <span class="text-sm font-medium text-slate-600 dark:text-slate-300">Mo ta chi tiet</span>
            <textarea v-model="form.mo_ta_chi_tiet" rows="4" class="w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-2.5 dark:border-slate-700 dark:bg-slate-800/60"></textarea>
          </label>
        </div>

        <div class="mt-6 flex justify-end gap-3">
          <button class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-600 dark:border-slate-700 dark:text-slate-300" @click="closeModal">Huy</button>
          <button class="rounded-lg bg-[#2463eb] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#1d4fcc]" :disabled="submitting" @click="submitJob">
            {{ submitting ? 'Dang luu...' : editingId ? 'Luu thay doi' : 'Tao tin' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
