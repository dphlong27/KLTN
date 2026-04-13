<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { jobService, profileService } from '@/services/api'
import { useNotify } from '@/composables/useNotify'
import { getStoredCandidate } from '@/utils/authStorage'
import ProfileCvPreview from '@/components/Dashboard/ProfileCvPreview.vue'
import {
  buildCvIndustryPreset,
  buildProfileCvPrintHtml,
  cvSkillLevelOptions,
  cvStylePreferenceOptions,
  cvTemplateLabel,
  cvTemplateOptions,
  suggestCvTemplate,
} from '@/utils/profileCvBuilder'

const route = useRoute()
const router = useRouter()
const notify = useNotify()

const loading = ref(false)
const saving = ref(false)
const loadingIndustries = ref(false)
const previewModalOpen = ref(false)
const currentCandidate = ref(getStoredCandidate())
const industryOptions = ref([])
const selectedIndustryId = ref('')
const stylePreference = ref('balanced')

const educationOptions = [
  { value: 'trung_hoc', label: 'Trung học' },
  { value: 'trung_cap', label: 'Trung cấp' },
  { value: 'cao_dang', label: 'Cao đẳng' },
  { value: 'dai_hoc', label: 'Đại học' },
  { value: 'thac_si', label: 'Thạc sĩ' },
  { value: 'tien_si', label: 'Tiến sĩ' },
  { value: 'khac', label: 'Khác' },
]

const createSkillItem = (ten = '', muc_do = 'kha') => ({ ten, muc_do })
const createExperienceItem = () => ({ vi_tri: '', cong_ty: '', bat_dau: '', ket_thuc: '', mo_ta: '' })
const createEducationItem = () => ({ truong: '', chuyen_nganh: '', bat_dau: '', ket_thuc: '', mo_ta: '' })
const createProjectItem = () => ({ ten: '', vai_tro: '', cong_nghe: '', mo_ta: '', link: '' })
const createCertificateItem = () => ({ ten: '', don_vi: '', nam: '' })

const form = reactive({
  tieu_de_ho_so: '',
  muc_tieu_nghe_nghiep: '',
  trinh_do: '',
  kinh_nghiem_nam: '',
  mo_ta_ban_than: '',
  nguon_ho_so: 'builder',
  mau_cv: 'classic',
  ky_nang_json: [createSkillItem()],
  kinh_nghiem_json: [createExperienceItem()],
  hoc_van_json: [createEducationItem()],
  du_an_json: [],
  chung_chi_json: [],
  trang_thai: 1,
})

const extractList = (response) => {
  const payload = response?.data
  if (Array.isArray(payload?.data)) return payload.data
  if (Array.isArray(payload)) return payload
  return []
}

const normalizeItems = (items, requiredKeys = []) => {
  if (!Array.isArray(items)) return []

  return items
    .map((item) => {
      if (!item || typeof item !== 'object') return null
      const normalized = Object.fromEntries(
        Object.entries(item).map(([key, value]) => [key, String(value ?? '').trim()]),
      )

      const hasRequired = requiredKeys.length
        ? requiredKeys.some((key) => normalized[key])
        : Object.values(normalized).some(Boolean)

      return hasRequired ? normalized : null
    })
    .filter(Boolean)
}

const editingProfileId = computed(() => {
  const raw = String(route.query.id || '').trim()
  return raw ? Number(raw) || null : null
})

const pageTitle = computed(() => (editingProfileId.value ? 'Chỉnh sửa CV hệ thống' : 'Tạo CV trên hệ thống'))
const pageDescription = computed(() =>
  editingProfileId.value
    ? 'Cập nhật CV builder đang có, thay template hoặc hoàn thiện thêm các section.'
    : 'Dựng CV trực tiếp trên hệ thống, chọn template theo ngành nghề hoặc gu hiển thị của bạn.',
)

const selectedIndustry = computed(() =>
  industryOptions.value.find((item) => String(item.id) === String(selectedIndustryId.value)) || null,
)

const selectedIndustryName = computed(
  () => selectedIndustry.value?.ten_nganh || selectedIndustry.value?.ten_nganh_nghe || '',
)

const industryPreset = computed(() => buildCvIndustryPreset(selectedIndustryName.value, stylePreference.value))
const recommendedTemplate = computed(() => suggestCvTemplate(selectedIndustryName.value, stylePreference.value))
const previewProfile = computed(() => ({
  ...form,
  ky_nang_json: normalizeItems(form.ky_nang_json, ['ten']),
  kinh_nghiem_json: normalizeItems(form.kinh_nghiem_json, ['vi_tri']),
  hoc_van_json: normalizeItems(form.hoc_van_json, ['truong']),
  du_an_json: normalizeItems(form.du_an_json, ['ten']),
  chung_chi_json: normalizeItems(form.chung_chi_json, ['ten']),
}))

const resetForm = () => {
  form.tieu_de_ho_so = ''
  form.muc_tieu_nghe_nghiep = ''
  form.trinh_do = ''
  form.kinh_nghiem_nam = ''
  form.mo_ta_ban_than = ''
  form.nguon_ho_so = 'builder'
  form.mau_cv = 'classic'
  form.ky_nang_json = [createSkillItem()]
  form.kinh_nghiem_json = [createExperienceItem()]
  form.hoc_van_json = [createEducationItem()]
  form.du_an_json = []
  form.chung_chi_json = []
  form.trang_thai = 1
  selectedIndustryId.value = ''
  stylePreference.value = 'balanced'
}

const fillForm = (profile) => {
  form.tieu_de_ho_so = profile?.tieu_de_ho_so || ''
  form.muc_tieu_nghe_nghiep = profile?.muc_tieu_nghe_nghiep || ''
  form.trinh_do = profile?.trinh_do || ''
  form.kinh_nghiem_nam = profile?.kinh_nghiem_nam ?? ''
  form.mo_ta_ban_than = profile?.mo_ta_ban_than || ''
  form.nguon_ho_so = 'builder'
  form.mau_cv = profile?.mau_cv || 'classic'
  form.ky_nang_json = Array.isArray(profile?.ky_nang_json) && profile.ky_nang_json.length
    ? profile.ky_nang_json.map((item) => ({ ten: item?.ten || '', muc_do: item?.muc_do || 'kha' }))
    : [createSkillItem()]
  form.kinh_nghiem_json = Array.isArray(profile?.kinh_nghiem_json) && profile.kinh_nghiem_json.length
    ? profile.kinh_nghiem_json.map((item) => ({ vi_tri: item?.vi_tri || '', cong_ty: item?.cong_ty || '', bat_dau: item?.bat_dau || '', ket_thuc: item?.ket_thuc || '', mo_ta: item?.mo_ta || '' }))
    : [createExperienceItem()]
  form.hoc_van_json = Array.isArray(profile?.hoc_van_json) && profile.hoc_van_json.length
    ? profile.hoc_van_json.map((item) => ({ truong: item?.truong || '', chuyen_nganh: item?.chuyen_nganh || '', bat_dau: item?.bat_dau || '', ket_thuc: item?.ket_thuc || '', mo_ta: item?.mo_ta || '' }))
    : [createEducationItem()]
  form.du_an_json = Array.isArray(profile?.du_an_json)
    ? profile.du_an_json.map((item) => ({ ten: item?.ten || '', vai_tro: item?.vai_tro || '', cong_nghe: item?.cong_nghe || '', mo_ta: item?.mo_ta || '', link: item?.link || '' }))
    : []
  form.chung_chi_json = Array.isArray(profile?.chung_chi_json)
    ? profile.chung_chi_json.map((item) => ({ ten: item?.ten || '', don_vi: item?.don_vi || '', nam: item?.nam || '' }))
    : []
  form.trang_thai = Number(profile?.trang_thai ?? 1)
}

const loadIndustries = async () => {
  loadingIndustries.value = true
  try {
    const response = await jobService.getIndustries({ per_page: 200 })
    industryOptions.value = extractList(response)
  } catch (error) {
    industryOptions.value = []
    notify.apiError(error, 'Không thể tải danh sách ngành nghề cho CV builder.')
  } finally {
    loadingIndustries.value = false
  }
}

const loadEditingProfile = async () => {
  if (!editingProfileId.value) {
    resetForm()
    return
  }

  loading.value = true
  try {
    const response = await profileService.getProfileById(editingProfileId.value)
    const profile = response?.data || null

    if (!profile) {
      notify.warning('Không tìm thấy hồ sơ cần chỉnh sửa.')
      router.replace('/my-cv')
      return
    }

    fillForm(profile)
  } catch (error) {
    notify.apiError(error, 'Không thể tải CV hệ thống cần chỉnh sửa.')
    router.replace('/my-cv')
  } finally {
    loading.value = false
  }
}

const addSectionItem = (field) => {
  if (field === 'ky_nang_json') form.ky_nang_json.push(createSkillItem())
  if (field === 'kinh_nghiem_json') form.kinh_nghiem_json.push(createExperienceItem())
  if (field === 'hoc_van_json') form.hoc_van_json.push(createEducationItem())
  if (field === 'du_an_json') form.du_an_json.push(createProjectItem())
  if (field === 'chung_chi_json') form.chung_chi_json.push(createCertificateItem())
}

const removeSectionItem = (field, index) => {
  if (!Array.isArray(form[field])) return
  form[field].splice(index, 1)
  if (!form[field].length && ['ky_nang_json', 'kinh_nghiem_json', 'hoc_van_json'].includes(field)) {
    addSectionItem(field)
  }
}

const applyIndustryPreset = () => {
  if (!selectedIndustryName.value) {
    notify.warning('Hãy chọn ngành nghề trước khi áp preset.')
    return
  }

  const preset = buildCvIndustryPreset(selectedIndustryName.value, stylePreference.value)
  form.mau_cv = preset.template

  if (!String(form.tieu_de_ho_so).trim()) {
    form.tieu_de_ho_so = preset.suggestedTitle
  }

  if (!String(form.muc_tieu_nghe_nghiep).trim()) {
    form.muc_tieu_nghe_nghiep = preset.suggestedObjective
  }

  const existingSkills = normalizeItems(form.ky_nang_json, ['ten']).map((item) => item.ten.toLowerCase())
  const missingSkills = preset.suggestedSkills.filter((item) => !existingSkills.includes(item.toLowerCase()))
  if (missingSkills.length) {
    form.ky_nang_json = [...normalizeItems(form.ky_nang_json, ['ten']), ...missingSkills.map((item) => createSkillItem(item, 'kha'))]
  }

  notify.success(`Đã áp dụng preset CV cho ngành ${selectedIndustryName.value}.`)
}

const exportPreview = () => {
  const popup = window.open('', '_blank', 'noopener,noreferrer')
  if (!popup) {
    notify.warning('Trình duyệt đang chặn cửa sổ in. Hãy cho phép popup và thử lại.')
    return
  }

  popup.document.open()
  popup.document.write(buildProfileCvPrintHtml({ profile: previewProfile.value, owner: currentCandidate.value }))
  popup.document.close()
}

const openPreviewModal = () => {
  previewModalOpen.value = true
}

const closePreviewModal = () => {
  previewModalOpen.value = false
}

const buildFormData = () => {
  const payload = new FormData()
  payload.append('tieu_de_ho_so', form.tieu_de_ho_so)
  payload.append('muc_tieu_nghe_nghiep', form.muc_tieu_nghe_nghiep || '')
  payload.append('trinh_do', form.trinh_do || '')
  payload.append('kinh_nghiem_nam', String(form.kinh_nghiem_nam || 0))
  payload.append('mo_ta_ban_than', form.mo_ta_ban_than || '')
  payload.append('nguon_ho_so', 'builder')
  payload.append('mau_cv', form.mau_cv || 'classic')
  payload.append('ky_nang_json', JSON.stringify(normalizeItems(form.ky_nang_json, ['ten'])))
  payload.append('kinh_nghiem_json', JSON.stringify(normalizeItems(form.kinh_nghiem_json, ['vi_tri'])))
  payload.append('hoc_van_json', JSON.stringify(normalizeItems(form.hoc_van_json, ['truong'])))
  payload.append('du_an_json', JSON.stringify(normalizeItems(form.du_an_json, ['ten'])))
  payload.append('chung_chi_json', JSON.stringify(normalizeItems(form.chung_chi_json, ['ten'])))
  payload.append('trang_thai', String(form.trang_thai))
  return payload
}

const submitProfile = async () => {
  saving.value = true
  try {
    const payload = buildFormData()
    if (editingProfileId.value) {
      await profileService.updateProfile(editingProfileId.value, payload)
      notify.success('Đã cập nhật CV hệ thống.')
    } else {
      await profileService.createProfile(payload)
      notify.success('Đã tạo CV hệ thống mới.')
    }

    router.push('/my-cv')
  } catch (error) {
    notify.apiError(error, 'Không thể lưu CV hệ thống.')
  } finally {
    saving.value = false
  }
}

watch(
  () => route.query.id,
  async () => {
    await loadEditingProfile()
  },
  { immediate: true },
)

onMounted(() => {
  loadIndustries()
})
</script>

<template>
  <div class="space-y-8">
    <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
      <div>
        <p class="text-xs font-semibold uppercase tracking-[0.28em] text-blue-500">CV Builder</p>
        <h1 class="mt-2 text-3xl font-black text-slate-900 dark:text-white">{{ pageTitle }}</h1>
        <p class="mt-3 max-w-3xl text-sm leading-7 text-slate-600 dark:text-slate-400">
          {{ pageDescription }}
        </p>
      </div>

      <div class="flex flex-wrap gap-3">
        <button
          class="inline-flex items-center gap-2 rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200"
          type="button"
          @click="router.push('/my-cv')"
        >
          <span class="material-symbols-outlined text-[18px]">arrow_back</span>
          Quay lại CV của tôi
        </button>
        <button
          class="inline-flex items-center gap-2 rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-700"
          type="button"
          @click="exportPreview"
        >
          <span class="material-symbols-outlined text-[18px]">print</span>
          In / Xuất PDF
        </button>
      </div>
    </div>

    <div class="space-y-6">
        <section class="rounded-[28px] border border-blue-100 bg-gradient-to-r from-blue-50 via-white to-sky-50 p-6 shadow-sm dark:border-blue-900/20 dark:from-slate-900 dark:via-slate-900 dark:to-slate-950">
          <div class="grid grid-cols-1 gap-4 lg:grid-cols-[minmax(0,1fr)_220px_220px_auto] lg:items-end">
            <div>
              <label class="mb-2 block text-sm font-semibold text-slate-700 dark:text-slate-200">Ngành nghề mục tiêu</label>
              <select
                v-model="selectedIndustryId"
                class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
              >
                <option value="">Chọn ngành nghề để gợi ý template</option>
                <option
                  v-for="industry in industryOptions"
                  :key="industry.id"
                  :value="String(industry.id)"
                >
                  {{ industry.ten_nganh || industry.ten_nganh_nghe }}
                </option>
              </select>
            </div>

            <div>
              <label class="mb-2 block text-sm font-semibold text-slate-700 dark:text-slate-200">Phong cách hiển thị</label>
              <select
                v-model="stylePreference"
                class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
              >
                <option v-for="option in cvStylePreferenceOptions" :key="option.value" :value="option.value">
                  {{ option.label }}
                </option>
              </select>
            </div>

            <div class="rounded-2xl border border-white/80 bg-white/90 px-4 py-3 shadow-sm dark:border-slate-700 dark:bg-slate-900/80">
              <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Template đề xuất</p>
              <p class="mt-2 text-lg font-bold text-slate-900 dark:text-white">{{ cvTemplateLabel(recommendedTemplate) }}</p>
            </div>

            <button
              class="inline-flex h-[50px] items-center justify-center gap-2 rounded-2xl bg-[#2463eb] px-5 text-sm font-bold text-white transition hover:bg-blue-700"
              :disabled="loadingIndustries || !selectedIndustryId"
              type="button"
              @click="applyIndustryPreset"
            >
              <span class="material-symbols-outlined text-[18px]">auto_fix_high</span>
              Áp preset
            </button>
          </div>

          <div class="mt-4 rounded-2xl border border-blue-100 bg-white/90 p-4 text-sm leading-7 text-slate-600 shadow-sm dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-300">
            <p>
              Chọn ngành nghề để hệ thống gợi ý template, tiêu đề CV, mục tiêu nghề nghiệp và một số kỹ năng nền phù hợp.
              Bạn vẫn có thể đổi template thủ công theo sở thích ở phần preview.
            </p>
          </div>
        </section>

        <section v-if="loading" class="rounded-[28px] border border-slate-200 bg-white p-10 shadow-sm dark:border-slate-800 dark:bg-slate-900">
          <div class="h-40 animate-pulse rounded-3xl bg-slate-100 dark:bg-slate-800" />
        </section>

        <template v-else>
          <section class="rounded-[28px] border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
              <div class="md:col-span-2">
                <label class="mb-2 block text-sm font-semibold text-slate-700 dark:text-slate-200">Tiêu đề hồ sơ</label>
                <input
                  v-model="form.tieu_de_ho_so"
                  class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-700 outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100"
                  placeholder="Ví dụ: CV Backend Developer Laravel"
                  type="text"
                />
              </div>

              <div>
                <label class="mb-2 block text-sm font-semibold text-slate-700 dark:text-slate-200">Trình độ</label>
                <select
                  v-model="form.trinh_do"
                  class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-700 outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100"
                >
                  <option value="">Chọn trình độ</option>
                  <option v-for="option in educationOptions" :key="option.value" :value="option.value">
                    {{ option.label }}
                  </option>
                </select>
              </div>

              <div>
                <label class="mb-2 block text-sm font-semibold text-slate-700 dark:text-slate-200">Số năm kinh nghiệm</label>
                <input
                  v-model="form.kinh_nghiem_nam"
                  min="0"
                  max="50"
                  class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-700 outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100"
                  type="number"
                />
              </div>

              <div>
                <label class="mb-2 block text-sm font-semibold text-slate-700 dark:text-slate-200">Trạng thái hồ sơ</label>
                <select
                  v-model="form.trang_thai"
                  class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-700 outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100"
                >
                  <option :value="1">Công khai</option>
                  <option :value="0">Ẩn</option>
                </select>
              </div>

              <div>
                <label class="mb-2 block text-sm font-semibold text-slate-700 dark:text-slate-200">Template đang dùng</label>
                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-700 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                  {{ cvTemplateLabel(form.mau_cv) }}
                </div>
              </div>

              <div class="md:col-span-2">
                <label class="mb-2 block text-sm font-semibold text-slate-700 dark:text-slate-200">Mục tiêu nghề nghiệp</label>
                <textarea
                  v-model="form.muc_tieu_nghe_nghiep"
                  rows="4"
                  class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-700 outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100"
                  placeholder="Mô tả ngắn định hướng nghề nghiệp và vị trí bạn muốn ứng tuyển."
                />
              </div>

              <div class="md:col-span-2">
                <label class="mb-2 block text-sm font-semibold text-slate-700 dark:text-slate-200">Mô tả bản thân</label>
                <textarea
                  v-model="form.mo_ta_ban_than"
                  rows="4"
                  class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-700 outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100"
                  placeholder="Tóm tắt ngắn về kinh nghiệm, điểm mạnh và định hướng cá nhân."
                />
              </div>
            </div>
          </section>

          <section class="rounded-[28px] border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="mb-4 flex items-center justify-between gap-4">
              <div>
                <h2 class="text-lg font-bold text-slate-900 dark:text-white">Kỹ năng</h2>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Liệt kê những kỹ năng nổi bật bạn muốn employer nhìn thấy ngay.</p>
              </div>
              <button class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white" type="button" @click="addSectionItem('ky_nang_json')">
                Thêm kỹ năng
              </button>
            </div>

            <div class="space-y-3">
              <div v-for="(item, index) in form.ky_nang_json" :key="`skill-${index}`" class="grid grid-cols-1 gap-3 md:grid-cols-[minmax(0,1fr)_220px_56px]">
                <input v-model="item.ten" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100" placeholder="Ví dụ: Laravel, Vue.js, PostgreSQL" type="text" />
                <select v-model="item.muc_do" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                  <option v-for="level in cvSkillLevelOptions" :key="level.value" :value="level.value">{{ level.label }}</option>
                </select>
                <button class="rounded-2xl border border-rose-200 text-rose-500 transition hover:bg-rose-50 dark:border-rose-900/40 dark:hover:bg-rose-900/10" type="button" @click="removeSectionItem('ky_nang_json', index)">
                  <span class="material-symbols-outlined">delete</span>
                </button>
              </div>
            </div>
          </section>

          <section class="rounded-[28px] border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="mb-4 flex items-center justify-between gap-4">
              <div>
                <h2 class="text-lg font-bold text-slate-900 dark:text-white">Kinh nghiệm làm việc</h2>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Tập trung vào vị trí, công ty, thời gian và tác động nổi bật.</p>
              </div>
              <button class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white" type="button" @click="addSectionItem('kinh_nghiem_json')">
                Thêm kinh nghiệm
              </button>
            </div>

            <div class="space-y-4">
              <div v-for="(item, index) in form.kinh_nghiem_json" :key="`exp-${index}`" class="rounded-3xl border border-slate-200 p-4 dark:border-slate-700">
                <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                  <input v-model="item.vi_tri" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100" placeholder="Vị trí" type="text" />
                  <input v-model="item.cong_ty" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100" placeholder="Công ty" type="text" />
                  <input v-model="item.bat_dau" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100" placeholder="Bắt đầu (MM/YYYY)" type="text" />
                  <input v-model="item.ket_thuc" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100" placeholder="Kết thúc / Hiện tại" type="text" />
                  <textarea v-model="item.mo_ta" rows="3" class="md:col-span-2 rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100" placeholder="Mô tả ngắn các đầu việc, thành tựu hoặc tác động nổi bật." />
                </div>
                <div class="mt-3 flex justify-end">
                  <button class="rounded-xl border border-rose-200 px-3 py-2 text-sm font-semibold text-rose-500 transition hover:bg-rose-50 dark:border-rose-900/40 dark:hover:bg-rose-900/10" type="button" @click="removeSectionItem('kinh_nghiem_json', index)">
                    Xóa kinh nghiệm
                  </button>
                </div>
              </div>
            </div>
          </section>

          <section class="rounded-[28px] border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="mb-4 flex items-center justify-between gap-4">
              <div>
                <h2 class="text-lg font-bold text-slate-900 dark:text-white">Học vấn</h2>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Dùng để employer nắm nhanh nền tảng học thuật của bạn.</p>
              </div>
              <button class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white" type="button" @click="addSectionItem('hoc_van_json')">
                Thêm học vấn
              </button>
            </div>

            <div class="space-y-4">
              <div v-for="(item, index) in form.hoc_van_json" :key="`edu-${index}`" class="rounded-3xl border border-slate-200 p-4 dark:border-slate-700">
                <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                  <input v-model="item.truong" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100" placeholder="Trường học" type="text" />
                  <input v-model="item.chuyen_nganh" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100" placeholder="Chuyên ngành" type="text" />
                  <input v-model="item.bat_dau" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100" placeholder="Bắt đầu (MM/YYYY)" type="text" />
                  <input v-model="item.ket_thuc" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100" placeholder="Kết thúc" type="text" />
                  <textarea v-model="item.mo_ta" rows="3" class="md:col-span-2 rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100" placeholder="Điểm nổi bật, GPA, hoạt động học thuật..." />
                </div>
                <div class="mt-3 flex justify-end">
                  <button class="rounded-xl border border-rose-200 px-3 py-2 text-sm font-semibold text-rose-500 transition hover:bg-rose-50 dark:border-rose-900/40 dark:hover:bg-rose-900/10" type="button" @click="removeSectionItem('hoc_van_json', index)">
                    Xóa học vấn
                  </button>
                </div>
              </div>
            </div>
          </section>

          <section class="grid grid-cols-1 gap-6 xl:grid-cols-2">
            <div class="rounded-[28px] border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
              <div class="mb-4 flex items-center justify-between gap-4">
                <div>
                  <h2 class="text-lg font-bold text-slate-900 dark:text-white">Dự án</h2>
                  <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Nêu các dự án nổi bật để CV có chiều sâu hơn.</p>
                </div>
                <button class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white" type="button" @click="addSectionItem('du_an_json')">
                  Thêm dự án
                </button>
              </div>

              <div class="space-y-4">
                <div v-for="(item, index) in form.du_an_json" :key="`project-${index}`" class="rounded-3xl border border-slate-200 p-4 dark:border-slate-700">
                  <div class="grid grid-cols-1 gap-3">
                    <input v-model="item.ten" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100" placeholder="Tên dự án" type="text" />
                    <input v-model="item.vai_tro" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100" placeholder="Vai trò" type="text" />
                    <input v-model="item.cong_nghe" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100" placeholder="Công nghệ" type="text" />
                    <input v-model="item.link" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100" placeholder="Link demo / GitHub" type="text" />
                    <textarea v-model="item.mo_ta" rows="3" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100" placeholder="Mô tả dự án và kết quả nổi bật." />
                  </div>
                  <div class="mt-3 flex justify-end">
                    <button class="rounded-xl border border-rose-200 px-3 py-2 text-sm font-semibold text-rose-500 transition hover:bg-rose-50 dark:border-rose-900/40 dark:hover:bg-rose-900/10" type="button" @click="removeSectionItem('du_an_json', index)">
                      Xóa dự án
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <div class="rounded-[28px] border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
              <div class="mb-4 flex items-center justify-between gap-4">
                <div>
                  <h2 class="text-lg font-bold text-slate-900 dark:text-white">Chứng chỉ</h2>
                  <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Bổ sung các chứng chỉ hoặc khóa học đáng chú ý.</p>
                </div>
                <button class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white" type="button" @click="addSectionItem('chung_chi_json')">
                  Thêm chứng chỉ
                </button>
              </div>

              <div class="space-y-4">
                <div v-for="(item, index) in form.chung_chi_json" :key="`cert-${index}`" class="rounded-3xl border border-slate-200 p-4 dark:border-slate-700">
                  <div class="grid grid-cols-1 gap-3">
                    <input v-model="item.ten" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100" placeholder="Tên chứng chỉ" type="text" />
                    <input v-model="item.don_vi" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100" placeholder="Đơn vị cấp" type="text" />
                    <input v-model="item.nam" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100" placeholder="Năm cấp" type="text" />
                  </div>
                  <div class="mt-3 flex justify-end">
                    <button class="rounded-xl border border-rose-200 px-3 py-2 text-sm font-semibold text-rose-500 transition hover:bg-rose-50 dark:border-rose-900/40 dark:hover:bg-rose-900/10" type="button" @click="removeSectionItem('chung_chi_json', index)">
                      Xóa chứng chỉ
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </section>

          <section class="rounded-[28px] border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
              <div>
                <h2 class="text-lg font-bold text-slate-900 dark:text-white">Template theo sở thích</h2>
                <p class="mt-2 max-w-2xl text-sm leading-7 text-slate-500 dark:text-slate-400">
                  Nếu không muốn theo template đề xuất của ngành, bạn có thể đổi thủ công tại đây. Preview sẽ mở riêng bằng nút để page nhập liệu rộng hơn.
                </p>
              </div>
              <div class="flex flex-wrap gap-3">
                <button
                  class="inline-flex items-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-200 dark:hover:bg-slate-800"
                  type="button"
                  @click="openPreviewModal"
                >
                  <span class="material-symbols-outlined text-[18px]">visibility</span>
                  Xem preview
                </button>
                <button
                  class="inline-flex items-center gap-2 rounded-2xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-700"
                  type="button"
                  @click="exportPreview"
                >
                  <span class="material-symbols-outlined text-[18px]">print</span>
                  In / Xuất PDF
                </button>
              </div>
            </div>

            <div class="mt-5 grid grid-cols-1 gap-3 lg:grid-cols-2 2xl:grid-cols-3">
              <button
                v-for="option in cvTemplateOptions"
                :key="`template-${option.value}`"
                class="rounded-2xl border px-4 py-4 text-left transition"
                :class="form.mau_cv === option.value
                  ? 'border-slate-900 bg-slate-900 text-white dark:border-slate-200 dark:bg-slate-100 dark:text-slate-900'
                  : 'border-slate-200 bg-white text-slate-700 hover:border-slate-300 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-200 dark:hover:border-slate-500'"
                type="button"
                @click="form.mau_cv = option.value"
              >
                <p class="text-sm font-bold">{{ option.label }}</p>
                <p
                  class="mt-1 text-xs leading-6"
                  :class="form.mau_cv === option.value ? 'text-white/80 dark:text-slate-700' : 'text-slate-500 dark:text-slate-400'"
                >
                  {{ option.description }}
                </p>
              </button>
            </div>
          </section>

          <div class="flex flex-col gap-3 sm:flex-row sm:justify-end">
            <button
              class="rounded-2xl border border-slate-200 px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800"
              type="button"
              @click="router.push('/my-cv')"
            >
              Hủy
            </button>
            <button
              class="rounded-2xl bg-[#2463eb] px-5 py-3 text-sm font-bold text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-blue-300"
              :disabled="saving || !form.tieu_de_ho_so"
              type="button"
              @click="submitProfile"
            >
              {{ saving ? 'Đang lưu...' : editingProfileId ? 'Lưu thay đổi' : 'Tạo CV hệ thống' }}
            </button>
          </div>
        </template>
    </div>

    <div
      v-if="previewModalOpen"
      class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/55 px-4 py-6 backdrop-blur-sm"
      @click.self="closePreviewModal"
    >
      <div class="mx-auto w-full max-w-5xl rounded-[28px] border border-slate-200 bg-white shadow-2xl dark:border-slate-800 dark:bg-slate-900">
        <div class="flex items-start justify-between border-b border-slate-100 px-6 py-5 dark:border-slate-800">
          <div>
            <p class="text-xs font-semibold uppercase tracking-[0.28em] text-blue-500">Preview CV</p>
            <h3 class="mt-2 text-2xl font-bold text-slate-900 dark:text-white">{{ form.tieu_de_ho_so || 'CV hệ thống' }}</h3>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
              Xem thử CV với dữ liệu bạn đang nhập trước khi lưu chính thức.
            </p>
          </div>
          <button
            class="rounded-full p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700 dark:hover:bg-slate-800"
            type="button"
            @click="closePreviewModal"
          >
            <span class="material-symbols-outlined">close</span>
          </button>
        </div>

        <div class="max-h-[calc(100vh-8rem)] overflow-y-auto px-6 py-6">
          <ProfileCvPreview :profile="previewProfile" :owner="currentCandidate" />
        </div>

        <div class="flex flex-col gap-3 border-t border-slate-100 px-6 py-5 sm:flex-row sm:justify-end dark:border-slate-800">
          <button
            class="rounded-2xl border border-slate-200 px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800"
            type="button"
            @click="closePreviewModal"
          >
            Đóng preview
          </button>
          <button
            class="rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-700"
            type="button"
            @click="exportPreview"
          >
            In / Xuất PDF
          </button>
        </div>
      </div>
    </div>

    <div class="pointer-events-none fixed inset-x-0 bottom-5 z-40 flex justify-center px-4">
      <div class="pointer-events-auto flex items-center gap-3 rounded-full border border-slate-200 bg-white/95 px-3 py-3 shadow-2xl backdrop-blur dark:border-slate-700 dark:bg-slate-900/95">
        <button
          class="inline-flex items-center gap-2 rounded-full bg-[#2463eb] px-4 py-2.5 text-sm font-bold text-white transition hover:bg-blue-700"
          type="button"
          @click="openPreviewModal"
        >
          <span class="material-symbols-outlined text-[18px]">visibility</span>
          Xem preview
        </button>
        <button
          class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-950 dark:text-slate-200 dark:hover:bg-slate-800"
          type="button"
          @click="exportPreview"
        >
          <span class="material-symbols-outlined text-[18px]">print</span>
          In PDF
        </button>
      </div>
    </div>
  </div>
</template>
