<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { authService, profileService } from '@/services/api'
import { useNotify } from '@/composables/useNotify'
import { getStoredCandidate, updateStoredCandidate } from '@/utils/authStorage'
import { formatDateVN } from '@/utils/dateTime'
import {
  buildProfileCvPrintHtml,
  cvSkillLevelLabel,
  cvSkillLevelOptions,
  cvTemplateLabel,
  cvTemplateOptions,
  formatCvPeriod,
  getCvTemplateTheme,
  hasBuilderCv as hasBuilderCvUtil,
} from '@/utils/profileCvBuilder'

const notify = useNotify()

const loading = ref(false)
const saving = ref(false)
const deletingId = ref(null)
const togglingId = ref(null)
const parsingId = ref(null)
const profiles = ref([])
const modalOpen = ref(false)
const editingProfileId = ref(null)
const selectedFile = ref(null)
const parseResultModalOpen = ref(false)
const parseResult = ref(null)
const applyingPersonalInfo = ref(false)
const currentCandidate = ref(getStoredCandidate())
const selectedPersonalFieldKeys = ref([])
const detailModalOpen = ref(false)
const selectedProfileDetail = ref(null)

const educationOptions = [
  { value: 'trung_hoc', label: 'Trung học' },
  { value: 'trung_cap', label: 'Trung cấp' },
  { value: 'cao_dang', label: 'Cao đẳng' },
  { value: 'dai_hoc', label: 'Đại học' },
  { value: 'thac_si', label: 'Thạc sĩ' },
  { value: 'tien_si', label: 'Tiến sĩ' },
  { value: 'khac', label: 'Khác' },
]

const cvSourceOptions = [
  { value: 'upload', label: 'Upload file CV' },
  { value: 'builder', label: 'Tạo CV trực tiếp trên hệ thống' },
  { value: 'hybrid', label: 'Kết hợp cả file và CV hệ thống' },
]

const createSkillItem = () => ({ ten: '', muc_do: 'kha' })
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

const totalProfiles = computed(() => profiles.value.length)
const publicProfiles = computed(() => profiles.value.filter((item) => Number(item.trang_thai) === 1).length)
const withFiles = computed(() => profiles.value.filter((item) => item.file_cv).length)
const builderProfiles = computed(() => profiles.value.filter((item) => hasBuilderCv(item)).length)
const parsedProfiles = computed(() =>
  profiles.value.filter((item) => Number(item?.parsing?.parse_status) === 1).length
)

const formatDate = (value) => {
  return formatDateVN(value)
}

const statusMeta = (value) => {
  if (Number(value) === 1) {
    return {
      label: 'Công khai',
      classes: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
      dot: 'bg-green-500',
      action: 'Ẩn',
      actionIcon: 'visibility_off',
      actionClass: 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800',
    }
  }

  return {
    label: 'Đã ẩn',
    classes: 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400',
    dot: 'bg-slate-400',
    action: 'Hiện',
    actionIcon: 'visibility',
    actionClass: 'text-green-600 hover:bg-green-50 dark:hover:bg-green-900/20',
  }
}

const degreeLabel = (value) => {
  return educationOptions.find((option) => option.value === value)?.label || 'Chưa cập nhật'
}

const hasBuilderCv = (profile) => hasBuilderCvUtil(profile)

const normalizeItems = (items, requiredKeys = []) => {
  if (!Array.isArray(items)) return []

  return items
    .map((item) => {
      if (!item || typeof item !== 'object') return null
      const normalized = Object.fromEntries(
        Object.entries(item).map(([key, value]) => [key, String(value ?? '').trim()])
      )

      const hasRequired = requiredKeys.length
        ? requiredKeys.some((key) => normalized[key])
        : Object.values(normalized).some(Boolean)

      return hasRequired ? normalized : null
    })
    .filter(Boolean)
}

const skillLevelLabel = (value) => cvSkillLevelLabel(value)

const previewProfile = computed(() => ({
  tieu_de_ho_so: form.tieu_de_ho_so,
  muc_tieu_nghe_nghiep: form.muc_tieu_nghe_nghiep,
  trinh_do: form.trinh_do,
  kinh_nghiem_nam: form.kinh_nghiem_nam,
  mo_ta_ban_than: form.mo_ta_ban_than,
  nguon_ho_so: form.nguon_ho_so,
  mau_cv: form.mau_cv,
  ky_nang_json: normalizeItems(form.ky_nang_json, ['ten']),
  kinh_nghiem_json: normalizeItems(form.kinh_nghiem_json, ['vi_tri']),
  hoc_van_json: normalizeItems(form.hoc_van_json, ['truong']),
  du_an_json: normalizeItems(form.du_an_json, ['ten']),
  chung_chi_json: normalizeItems(form.chung_chi_json, ['ten']),
}))

const previewTheme = computed(() => getCvTemplateTheme(form.mau_cv))

const exportProfileCv = (profile, owner = currentCandidate.value) => {
  const popup = window.open('', '_blank', 'noopener,noreferrer')
  if (!popup) {
    notify.warning('Trình duyệt đang chặn cửa sổ in. Hãy cho phép popup và thử lại.')
    return
  }

  popup.document.open()
  popup.document.write(buildProfileCvPrintHtml({ profile, owner }))
  popup.document.close()
}

const parseStatusMeta = (profile) => {
  const parsing = profile?.parsing

  if (!profile?.file_cv) {
    return {
      label: 'Chưa có file CV',
      classes: 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400',
      icon: 'upload_file',
    }
  }

  if (!parsing) {
    return {
      label: 'Chưa parse',
      classes: 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300',
      icon: 'psychology_alt',
    }
  }

  if (Number(parsing.parse_status) === 1) {
    return {
      label: 'Đã parse',
      classes: 'bg-violet-100 text-violet-700 dark:bg-violet-900/30 dark:text-violet-300',
      icon: 'check_circle',
    }
  }

  if (Number(parsing.parse_status) === 2) {
    return {
      label: 'Parse lỗi',
      classes: 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300',
      icon: 'error',
    }
  }

  return {
    label: 'Đang parse',
    classes: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',
    icon: 'hourglass_top',
  }
}

const cvFileUrl = (path) => {
  if (!path) return ''
  if (path.startsWith('http')) return path
  return `http://127.0.0.1:8000/storage/${path}`
}

const formatDisplayText = (value) => {
  const text = String(value || '')
    .replace(/[_-]+/g, ' ')
    .replace(/\s+/g, ' ')
    .trim()

  if (!text) return ''

  return text.charAt(0).toUpperCase() + text.slice(1)
}

const normalizedSkillItems = (value) => {
  if (!Array.isArray(value)) return []

  return value
    .map((item) => {
      if (typeof item === 'string') return formatDisplayText(item)
      if (!item || typeof item !== 'object') return ''
      return formatDisplayText(item.skill_name || item.name || item.skill || item.keyword || '')
    })
    .filter(Boolean)
    .filter((item, index, array) => array.indexOf(item) === index)
}

const normalizedSectionItems = (value) => {
  if (!Array.isArray(value)) return []

  return value
    .map((item) => {
      if (typeof item === 'string') return formatDisplayText(item)
      if (!item || typeof item !== 'object') return ''

      const ignoredKeys = ['confidence', 'score', 'type', 'label', 'raw_text']
      const parts = Object.entries(item)
        .filter(([key, raw]) => !ignoredKeys.includes(key) && raw !== null && raw !== undefined && String(raw).trim() !== '')
        .map(([, raw]) => formatDisplayText(raw))
        .filter(Boolean)

      return parts.join(' • ')
    })
    .filter(Boolean)
    .filter((item, index, array) => array.indexOf(item) === index)
}

const parsedSkills = computed(() => normalizedSkillItems(parseResult.value?.parsed_skills_json).slice(0, 12))
const parsedEducation = computed(() => normalizedSectionItems(parseResult.value?.parsed_education_json).slice(0, 8))
const parsedExperience = computed(() => normalizedSectionItems(parseResult.value?.parsed_experience_json).slice(0, 8))
const normalizedParsedPhone = computed(() => {
  const raw = String(parseResult.value?.parsed_phone || '').replace(/\D/g, '')
  if (/^0\d{9}$/.test(raw)) return raw
  if (/^84\d{9}$/.test(raw)) return `0${raw.slice(2)}`
  return ''
})
const candidateSnapshot = computed(() => currentCandidate.value || {})
const availablePersonalFields = computed(() => {
  const fields = []

  if (String(parseResult.value?.parsed_name || '').trim()) {
    fields.push({
      key: 'ho_ten',
      label: 'Họ và tên',
      currentValue: candidateSnapshot.value?.ho_ten || 'Chưa cập nhật',
      parsedValue: String(parseResult.value.parsed_name).trim(),
    })
  }

  if (String(parseResult.value?.parsed_email || '').trim()) {
    fields.push({
      key: 'email',
      label: 'Email',
      currentValue: candidateSnapshot.value?.email || 'Chưa cập nhật',
      parsedValue: String(parseResult.value.parsed_email).trim(),
    })
  }

  if (normalizedParsedPhone.value) {
    fields.push({
      key: 'so_dien_thoai',
      label: 'Số điện thoại',
      currentValue: candidateSnapshot.value?.so_dien_thoai || 'Chưa cập nhật',
      parsedValue: normalizedParsedPhone.value,
    })
  }

  return fields
})

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
  selectedFile.value = null
  editingProfileId.value = null
}

const fillForm = (profile) => {
  form.tieu_de_ho_so = profile?.tieu_de_ho_so || ''
  form.muc_tieu_nghe_nghiep = profile?.muc_tieu_nghe_nghiep || ''
  form.trinh_do = profile?.trinh_do || ''
  form.kinh_nghiem_nam = profile?.kinh_nghiem_nam ?? ''
  form.mo_ta_ban_than = profile?.mo_ta_ban_than || ''
  form.nguon_ho_so = profile?.nguon_ho_so || (hasBuilderCv(profile) ? 'builder' : 'upload')
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
  selectedFile.value = null
}

const fetchProfiles = async () => {
  loading.value = true
  try {
    const response = await profileService.getProfiles({
      per_page: 100,
      sort_by: 'updated_at',
      sort_dir: 'desc',
    })
    const payload = response?.data || {}
    profiles.value = payload.data || []
  } catch (error) {
    profiles.value = []
    notify.apiError(error, 'Không tải được danh sách hồ sơ/CV.')
  } finally {
    loading.value = false
  }
}

const openCreateModal = () => {
  resetForm()
  modalOpen.value = true
}

const openEditModal = (profile) => {
  editingProfileId.value = profile.id
  fillForm(profile)
  modalOpen.value = true
}

const openDetailModal = (profile) => {
  selectedProfileDetail.value = profile
  detailModalOpen.value = true
}

const closeDetailModal = () => {
  detailModalOpen.value = false
  selectedProfileDetail.value = null
}

const closeModal = () => {
  if (saving.value) return
  modalOpen.value = false
  resetForm()
}

const handleFileChange = (event) => {
  const file = event.target.files?.[0]
  if (!file) return
  selectedFile.value = file
}

const buildFormData = () => {
  const payload = new FormData()
  payload.append('tieu_de_ho_so', form.tieu_de_ho_so)
  payload.append('muc_tieu_nghe_nghiep', form.muc_tieu_nghe_nghiep || '')
  payload.append('trinh_do', form.trinh_do || '')
  payload.append('kinh_nghiem_nam', String(form.kinh_nghiem_nam || 0))
  payload.append('mo_ta_ban_than', form.mo_ta_ban_than || '')
  payload.append('nguon_ho_so', form.nguon_ho_so)
  payload.append('mau_cv', form.mau_cv || 'classic')
  payload.append('ky_nang_json', JSON.stringify(normalizeItems(form.ky_nang_json, ['ten'])))
  payload.append('kinh_nghiem_json', JSON.stringify(normalizeItems(form.kinh_nghiem_json, ['vi_tri'])))
  payload.append('hoc_van_json', JSON.stringify(normalizeItems(form.hoc_van_json, ['truong'])))
  payload.append('du_an_json', JSON.stringify(normalizeItems(form.du_an_json, ['ten'])))
  payload.append('chung_chi_json', JSON.stringify(normalizeItems(form.chung_chi_json, ['ten'])))
  payload.append('trang_thai', String(form.trang_thai))
  if (selectedFile.value) {
    payload.append('file_cv', selectedFile.value)
  }
  return payload
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

const submitProfile = async () => {
  saving.value = true
  try {
    const payload = buildFormData()
    if (editingProfileId.value) {
      await profileService.updateProfile(editingProfileId.value, payload)
      notify.success('Cập nhật hồ sơ thành công.')
    } else {
      await profileService.createProfile(payload)
      notify.success('Tạo hồ sơ mới thành công.')
    }
    modalOpen.value = false
    resetForm()
    await fetchProfiles()
  } catch (error) {
    notify.apiError(error, 'Không thể lưu hồ sơ/CV.')
  } finally {
    saving.value = false
  }
}

const toggleProfileStatus = async (profile) => {
  if (togglingId.value) return
  togglingId.value = profile.id
  try {
    await profileService.toggleProfileStatus(profile.id)
    notify.success(`Đã ${Number(profile.trang_thai) === 1 ? 'ẩn' : 'công khai'} hồ sơ.`)
    await fetchProfiles()
  } catch (error) {
    notify.apiError(error, 'Không thể cập nhật trạng thái hồ sơ.')
  } finally {
    togglingId.value = null
  }
}

const deleteProfile = async (profile) => {
  if (deletingId.value) return
  const confirmed = window.confirm(`Bạn có chắc muốn xóa hồ sơ "${profile.tieu_de_ho_so}" không?`)
  if (!confirmed) return

  deletingId.value = profile.id
  try {
    await profileService.deleteProfile(profile.id)
    notify.success('Đã xóa hồ sơ thành công.')
    await fetchProfiles()
  } catch (error) {
    notify.apiError(error, 'Không thể xóa hồ sơ.')
  } finally {
    deletingId.value = null
  }
}

const openParseResultModal = (result) => {
  currentCandidate.value = getStoredCandidate()
  parseResult.value = result
  selectedPersonalFieldKeys.value = [
    String(result?.parsed_name || '').trim() ? 'ho_ten' : null,
    String(result?.parsed_email || '').trim() ? 'email' : null,
    (() => {
      const raw = String(result?.parsed_phone || '').replace(/\D/g, '')
      return /^0\d{9}$/.test(raw) || /^84\d{9}$/.test(raw) ? 'so_dien_thoai' : null
    })(),
  ].filter(Boolean)
  parseResultModalOpen.value = true
}

const closeParseResultModal = () => {
  if (parsingId.value || applyingPersonalInfo.value) return
  parseResultModalOpen.value = false
  parseResult.value = null
  selectedPersonalFieldKeys.value = []
}

const applyPersonalInfoFromCv = async () => {
  if (applyingPersonalInfo.value) return

  const payload = {}

  if (selectedPersonalFieldKeys.value.includes('ho_ten') && String(parseResult.value?.parsed_name || '').trim()) {
    payload.ho_ten = String(parseResult.value.parsed_name).trim()
  }

  if (selectedPersonalFieldKeys.value.includes('email') && String(parseResult.value?.parsed_email || '').trim()) {
    payload.email = String(parseResult.value.parsed_email).trim()
  }

  if (selectedPersonalFieldKeys.value.includes('so_dien_thoai') && normalizedParsedPhone.value) {
    payload.so_dien_thoai = normalizedParsedPhone.value
  }

  if (!Object.keys(payload).length) {
    notify.warning('Hãy chọn ít nhất một trường hợp lệ để áp dụng từ CV.')
    return
  }

  applyingPersonalInfo.value = true
  try {
    const response = await authService.updateProfile(payload)
    const updatedUser = response?.data || null

    if (updatedUser) {
      updateStoredCandidate(updatedUser)
      currentCandidate.value = updatedUser
    }

    const appliedLabels = availablePersonalFields.value
      .filter((item) => selectedPersonalFieldKeys.value.includes(item.key))
      .map((item) => item.label)
      .join(', ')
    notify.success(`Đã áp dụng thông tin cá nhân từ CV: ${appliedLabels}.`)
  } catch (error) {
    notify.apiError(error, 'Không thể áp dụng thông tin cá nhân từ CV.')
  } finally {
    applyingPersonalInfo.value = false
  }
}

const parseProfile = async (profile) => {
  if (parsingId.value) return

  if (!profile.file_cv) {
    notify.warning('Hồ sơ này chưa có file CV để phân tích.')
    return
  }

  parsingId.value = profile.id
  try {
    const response = await profileService.parseProfileCv(profile.id)
    const payload = response?.data || {}
    const syncSummary = response?.sync_summary || null
    openParseResultModal({
      ...payload,
      profileTitle: profile.tieu_de_ho_so,
      syncSummary,
    })
    const updatedFields = Array.isArray(syncSummary?.updated_fields) ? syncSummary.updated_fields : []
    const syncedSkills = Number(syncSummary?.synced_skills || 0)
    const summaryParts = []

    if (updatedFields.length) {
      summaryParts.push(`đã tự điền ${updatedFields.length} trường hồ sơ`)
    }

    if (syncedSkills > 0) {
      summaryParts.push(`đồng bộ ${syncedSkills} kỹ năng`)
    }

    notify.success(
      summaryParts.length
        ? `Đã phân tích CV thành công, ${summaryParts.join(' và ')}.`
        : 'Đã phân tích CV thành công.'
    )
    await fetchProfiles()
  } catch (error) {
    notify.apiError(error, 'Không thể phân tích CV này.')
    await fetchProfiles()
  } finally {
    parsingId.value = null
  }
}

onMounted(fetchProfiles)
</script>

<template>
  <div>
    <div class="flex justify-between items-end mb-8">
      <div>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">CV của tôi</h1>
        <p class="text-slate-500 text-sm mt-1">Quản lý hồ sơ xin việc, chuẩn bị nhiều phiên bản CV cho các vị trí khác nhau.</p>
      </div>
      <button
        class="bg-[#2463eb] text-white px-5 py-2.5 rounded-lg font-bold flex items-center gap-2 shadow-lg shadow-[#2463eb]/20 hover:bg-[#2463eb]/90 transition-all text-sm"
        type="button"
        @click="openCreateModal"
      >
        <span class="material-symbols-outlined text-xl">add</span>
        Tạo CV mới
      </button>
    </div>

    <div class="grid grid-cols-1 gap-4 mb-8 md:grid-cols-2 xl:grid-cols-4">
      <div class="bg-white dark:bg-slate-900 p-5 rounded-xl shadow-sm border border-slate-200 dark:border-slate-800">
        <div class="flex items-center justify-between mb-2">
          <p class="text-slate-500 text-sm font-medium">Tổng hồ sơ</p>
          <div class="p-2 bg-[#2463eb]/10 rounded-lg text-[#2463eb]">
            <span class="material-symbols-outlined">description</span>
          </div>
        </div>
        <h3 class="text-2xl font-bold">{{ totalProfiles }}</h3>
      </div>
      <div class="bg-white dark:bg-slate-900 p-5 rounded-xl shadow-sm border border-slate-200 dark:border-slate-800">
        <div class="flex items-center justify-between mb-2">
          <p class="text-slate-500 text-sm font-medium">Đang công khai</p>
          <div class="p-2 bg-green-100 dark:bg-green-900/20 rounded-lg text-green-600">
            <span class="material-symbols-outlined">visibility</span>
          </div>
        </div>
        <h3 class="text-2xl font-bold">{{ publicProfiles }}</h3>
      </div>
      <div class="bg-white dark:bg-slate-900 p-5 rounded-xl shadow-sm border border-slate-200 dark:border-slate-800">
        <div class="flex items-center justify-between mb-2">
          <p class="text-slate-500 text-sm font-medium">Đã upload file CV</p>
          <div class="p-2 bg-amber-100 dark:bg-amber-900/20 rounded-lg text-amber-600">
            <span class="material-symbols-outlined">upload_file</span>
          </div>
        </div>
        <h3 class="text-2xl font-bold">{{ withFiles }}</h3>
      </div>
      <div class="bg-white dark:bg-slate-900 p-5 rounded-xl shadow-sm border border-slate-200 dark:border-slate-800">
        <div class="flex items-center justify-between mb-2">
          <p class="text-slate-500 text-sm font-medium">CV tạo trên hệ thống</p>
          <div class="p-2 bg-blue-100 dark:bg-blue-900/20 rounded-lg text-blue-600">
            <span class="material-symbols-outlined">edit_note</span>
          </div>
        </div>
        <h3 class="text-2xl font-bold">{{ builderProfiles }}</h3>
      </div>
    </div>

    <div class="mb-8 rounded-2xl border border-blue-100 bg-gradient-to-r from-blue-50 via-white to-sky-50 p-5 shadow-sm dark:border-blue-900/20 dark:from-slate-900 dark:via-slate-900 dark:to-slate-950">
      <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
          <p class="text-xs font-semibold uppercase tracking-[0.24em] text-blue-500">CV Builder</p>
          <h2 class="mt-2 text-xl font-bold text-slate-900 dark:text-white">Tạo CV trực tiếp trên hệ thống</h2>
          <p class="mt-2 max-w-3xl text-sm leading-7 text-slate-600 dark:text-slate-400">
            Bạn có thể dựng CV dạng TopCV ngay trong hệ thống, chọn mẫu hiển thị, điền kỹ năng, kinh nghiệm, học vấn,
            dự án và xuất bản in/PDF cơ bản mà không cần phụ thuộc hoàn toàn vào file upload.
          </p>
        </div>
        <div class="flex flex-wrap gap-3">
          <span class="rounded-full bg-white px-4 py-2 text-xs font-semibold text-slate-700 shadow-sm dark:bg-slate-800 dark:text-slate-200">
            {{ builderProfiles }} CV builder đã tạo
          </span>
          <span class="rounded-full bg-white px-4 py-2 text-xs font-semibold text-slate-700 shadow-sm dark:bg-slate-800 dark:text-slate-200">
            {{ parsedProfiles }} CV đã parse AI
          </span>
        </div>
      </div>
    </div>

    <div v-if="loading" class="space-y-4">
      <div
        v-for="index in 3"
        :key="index"
        class="h-32 animate-pulse rounded-xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900"
      />
    </div>

    <div v-else-if="!profiles.length" class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-14 text-center shadow-sm dark:border-slate-700 dark:bg-slate-900">
      <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-slate-100 dark:bg-slate-800">
        <span class="material-symbols-outlined text-3xl text-slate-500">description</span>
      </div>
      <h2 class="mt-5 text-xl font-bold text-slate-900 dark:text-white">Bạn chưa có hồ sơ nào</h2>
      <p class="mx-auto mt-3 max-w-xl text-sm leading-7 text-slate-500 dark:text-slate-400">
        Tạo hồ sơ đầu tiên để bắt đầu ứng tuyển. Bạn có thể chuẩn bị nhiều phiên bản CV cho các vị trí khác nhau.
      </p>
      <button
        class="mt-6 inline-flex rounded-xl bg-[#2463eb] px-5 py-3 text-sm font-bold text-white transition hover:bg-blue-700"
        type="button"
        @click="openCreateModal"
      >
        Tạo hồ sơ đầu tiên
      </button>
    </div>

    <div v-else class="space-y-4">
      <div
        v-for="profile in profiles"
        :key="profile.id"
        class="bg-white dark:bg-slate-900 rounded-xl p-6 shadow-sm border border-slate-200 dark:border-slate-800 hover:border-[#2463eb]/40 transition-all"
      >
        <div class="flex flex-col md:flex-row md:items-center gap-4 justify-between">
          <div class="flex items-center gap-4">
            <div class="size-14 bg-[#2463eb]/10 rounded-xl flex items-center justify-center shrink-0">
              <span class="material-symbols-outlined text-[#2463eb] text-3xl">description</span>
            </div>
            <div>
              <div class="flex flex-wrap items-center gap-3 mb-1">
                <h3 class="font-bold text-lg text-slate-900 dark:text-white">{{ profile.tieu_de_ho_so }}</h3>
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold" :class="statusMeta(profile.trang_thai).classes">
                  <span class="size-1.5 rounded-full" :class="statusMeta(profile.trang_thai).dot"></span>
                  {{ statusMeta(profile.trang_thai).label }}
                </span>
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold" :class="parseStatusMeta(profile).classes">
                  <span class="material-symbols-outlined text-[12px]">{{ parseStatusMeta(profile).icon }}</span>
                  {{ parseStatusMeta(profile).label }}
                </span>
              </div>
              <p class="text-slate-500 text-sm">
                Cập nhật lần cuối: {{ formatDate(profile.updated_at) }}
                <span v-if="profile.file_cv">• Có file CV</span>
                <span v-else>• Chưa upload file CV</span>
                <span v-if="hasBuilderCv(profile)">• Có CV hệ thống</span>
              </p>
              <div class="flex flex-wrap gap-2 mt-2">
                <span class="text-xs bg-slate-100 dark:bg-slate-800 px-2 py-1 rounded font-medium">
                  {{ degreeLabel(profile.trinh_do) }}
                </span>
                <span class="text-xs bg-slate-100 dark:bg-slate-800 px-2 py-1 rounded font-medium">
                  {{ profile.kinh_nghiem_nam ?? 0 }} năm kinh nghiệm
                </span>
                <span v-if="hasBuilderCv(profile)" class="text-xs bg-blue-50 text-blue-700 dark:bg-blue-900/20 dark:text-blue-300 px-2 py-1 rounded font-medium">
                  {{ cvTemplateLabel(profile.mau_cv) }}
                </span>
              </div>
              <p v-if="profile.muc_tieu_nghe_nghiep" class="mt-3 max-w-3xl text-sm text-slate-500 dark:text-slate-400 line-clamp-2">
                {{ profile.muc_tieu_nghe_nghiep }}
              </p>
            </div>
          </div>

          <div class="flex flex-wrap items-center gap-2 shrink-0">
            <a
              v-if="profile.file_cv"
              :href="cvFileUrl(profile.file_cv)"
              class="flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-medium text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
              target="_blank"
              rel="noopener noreferrer"
            >
              <span class="material-symbols-outlined text-[18px]">download</span> Tải xuống
            </a>
            <button
              v-else-if="hasBuilderCv(profile)"
              class="flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-medium text-blue-600 bg-blue-50 hover:bg-blue-600 hover:text-white transition-colors dark:bg-blue-900/20 dark:text-blue-300 dark:hover:bg-blue-700"
              type="button"
              @click="exportProfileCv(profile)"
            >
              <span class="material-symbols-outlined text-[18px]">print</span> Xuất PDF
            </button>
            <button
              class="flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-medium transition-colors"
              :class="statusMeta(profile.trang_thai).actionClass"
              type="button"
              @click="toggleProfileStatus(profile)"
            >
              <span class="material-symbols-outlined text-[18px]">
                {{ togglingId === profile.id ? 'hourglass_top' : statusMeta(profile.trang_thai).actionIcon }}
              </span>
              {{ statusMeta(profile.trang_thai).action }}
            </button>
            <button
              class="flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-bold text-slate-700 bg-slate-100 hover:bg-slate-900 hover:text-white transition-all dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
              type="button"
              @click="openDetailModal(profile)"
            >
              <span class="material-symbols-outlined text-[18px]">article</span> Xem chi tiết
            </button>
            <button
              class="flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-bold text-violet-600 bg-violet-600/10 hover:bg-violet-600 hover:text-white transition-all disabled:cursor-not-allowed disabled:opacity-60"
              :disabled="parsingId === profile.id"
              type="button"
              @click="parseProfile(profile)"
            >
              <span class="material-symbols-outlined text-[18px]">
                {{ parsingId === profile.id ? 'hourglass_top' : 'auto_awesome' }}
              </span>
              {{ parsingId === profile.id ? 'Đang parse...' : 'Parse CV' }}
            </button>
            <button
              class="flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-bold text-[#2463eb] bg-[#2463eb]/10 hover:bg-[#2463eb] hover:text-white transition-all"
              type="button"
              @click="openEditModal(profile)"
            >
              <span class="material-symbols-outlined text-[18px]">edit</span> Chỉnh sửa
            </button>
            <button
              class="flex items-center justify-center size-9 rounded-lg text-red-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors"
              type="button"
              @click="deleteProfile(profile)"
            >
              <span class="material-symbols-outlined text-[18px]">
                {{ deletingId === profile.id ? 'hourglass_top' : 'delete' }}
              </span>
            </button>
          </div>
        </div>
      </div>
    </div>

    <div class="mt-6">
      <button
        class="w-full flex items-center justify-center gap-3 p-6 border-2 border-dashed border-slate-200 dark:border-slate-800 rounded-xl text-slate-500 hover:border-[#2463eb] hover:text-[#2463eb] transition-all group"
        type="button"
        @click="openCreateModal"
      >
        <span class="material-symbols-outlined text-2xl group-hover:scale-110 transition-transform">note_add</span>
        <span class="font-medium">Tạo thêm hồ sơ để ứng tuyển nhiều vị trí khác nhau</span>
      </button>
    </div>

    <div
      v-if="modalOpen"
      class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/55 px-4 py-6 backdrop-blur-sm"
      @click.self="closeModal"
    >
      <div class="mx-auto w-full max-w-5xl rounded-[28px] border border-slate-200 bg-white shadow-2xl">
        <div class="flex items-start justify-between border-b border-slate-100 px-6 py-5">
          <div>
            <p class="text-xs font-semibold uppercase tracking-[0.28em] text-blue-500">{{ editingProfileId ? 'Chỉnh sửa hồ sơ' : 'Tạo hồ sơ mới' }}</p>
            <h3 class="mt-2 text-2xl font-bold text-slate-900">
              {{ editingProfileId ? 'Cập nhật hồ sơ ứng tuyển' : 'Chuẩn bị hồ sơ ứng tuyển mới' }}
            </h3>
          </div>
          <button
            class="rounded-full p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
            type="button"
            @click="closeModal"
          >
            <span class="material-symbols-outlined">close</span>
          </button>
        </div>

        <div class="max-h-[calc(100vh-8rem)] overflow-y-auto px-6 py-6">
          <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
            <div class="md:col-span-2">
            <label class="mb-2 block text-sm font-semibold text-slate-700">Tiêu đề hồ sơ</label>
            <input
              v-model="form.tieu_de_ho_so"
              class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-700 outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
              placeholder="Ví dụ: CV Backend Developer Laravel"
              type="text"
            />
            </div>

            <div>
            <label class="mb-2 block text-sm font-semibold text-slate-700">Trình độ</label>
            <select
              v-model="form.trinh_do"
              class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-700 outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
            >
              <option value="">Chọn trình độ</option>
              <option v-for="option in educationOptions" :key="option.value" :value="option.value">
                {{ option.label }}
              </option>
            </select>
            </div>

            <div>
            <label class="mb-2 block text-sm font-semibold text-slate-700">Số năm kinh nghiệm</label>
            <input
              v-model="form.kinh_nghiem_nam"
              min="0"
              max="50"
              class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-700 outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
              type="number"
            />
            </div>

            <div class="md:col-span-2">
            <label class="mb-2 block text-sm font-semibold text-slate-700">Mục tiêu nghề nghiệp</label>
            <textarea
              v-model="form.muc_tieu_nghe_nghiep"
              rows="4"
              class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-700 outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
              placeholder="Mô tả ngắn định hướng nghề nghiệp và vị trí bạn muốn ứng tuyển."
            />
            </div>

            <div class="md:col-span-2">
            <label class="mb-2 block text-sm font-semibold text-slate-700">Mô tả bản thân</label>
            <textarea
              v-model="form.mo_ta_ban_than"
              rows="4"
              class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-700 outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
              placeholder="Tóm tắt ngắn về kinh nghiệm, điểm mạnh và định hướng cá nhân."
            />
            </div>

            <div>
            <label class="mb-2 block text-sm font-semibold text-slate-700">Trạng thái hồ sơ</label>
            <select
              v-model="form.trang_thai"
              class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-700 outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
            >
              <option :value="1">Công khai</option>
              <option :value="0">Ẩn</option>
            </select>
            </div>

            <div>
            <label class="mb-2 block text-sm font-semibold text-slate-700">Kiểu hồ sơ</label>
            <select
              v-model="form.nguon_ho_so"
              class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-700 outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
            >
              <option v-for="option in cvSourceOptions" :key="option.value" :value="option.value">
                {{ option.label }}
              </option>
            </select>
          </div>

            <div class="md:col-span-2">
            <label class="mb-2 block text-sm font-semibold text-slate-700">File CV (PDF/DOC/DOCX)</label>
            <label class="flex min-h-[54px] cursor-pointer items-center rounded-2xl border border-dashed border-slate-300 px-4 py-3 text-sm text-slate-500 transition hover:border-blue-400 hover:text-blue-600">
              <input class="hidden" type="file" accept=".pdf,.doc,.docx" @change="handleFileChange" />
              {{ selectedFile ? selectedFile.name : editingProfileId ? 'Chọn file mới nếu muốn thay thế CV hiện tại' : 'Chọn file CV để upload' }}
            </label>
              <p class="mt-2 text-xs text-slate-500">
                Chọn <span class="font-semibold">Upload file</span> nếu bạn đã có CV sẵn, hoặc dùng <span class="font-semibold">CV builder</span> bên dưới để dựng trực tiếp trên hệ thống.
              </p>
            </div>
          </div>

          <div
            v-if="form.nguon_ho_so !== 'upload'"
            class="mt-6 rounded-[28px] border border-blue-100 bg-blue-50/60 p-5"
          >
            <div class="flex flex-col gap-2 md:flex-row md:items-end md:justify-between">
              <div>
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-blue-500">CV builder</p>
                <h4 class="mt-2 text-xl font-bold text-slate-900">Tạo CV trực tiếp trên hệ thống</h4>
                <p class="mt-2 text-sm leading-7 text-slate-600">
                  Điền các section cốt lõi của CV để hệ thống dựng hồ sơ ứng tuyển dạng trực tiếp. Bạn vẫn có thể upload file song song nếu muốn.
                </p>
              </div>
              <div class="w-full md:w-56">
                <label class="mb-2 block text-sm font-semibold text-slate-700">Mẫu hiển thị</label>
                <select
                  v-model="form.mau_cv"
                  class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
                >
                  <option v-for="option in cvTemplateOptions" :key="option.value" :value="option.value">
                    {{ option.label }}
                  </option>
                </select>
              </div>
            </div>

            <div class="mt-6 space-y-5">
              <section class="rounded-3xl border border-white/80 bg-white p-5">
                <div class="mb-4 flex items-center justify-between gap-4">
                  <div>
                    <h5 class="text-base font-bold text-slate-900">Kỹ năng</h5>
                    <p class="text-sm text-slate-500">Liệt kê các kỹ năng nổi bật sẽ hiển thị ngay trong CV builder.</p>
                  </div>
                  <button class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white" type="button" @click="addSectionItem('ky_nang_json')">
                    Thêm kỹ năng
                  </button>
                </div>
                <div class="space-y-3">
                  <div v-for="(item, index) in form.ky_nang_json" :key="`skill-${index}`" class="grid grid-cols-1 gap-3 md:grid-cols-[minmax(0,1fr)_220px_56px]">
                    <input v-model="item.ten" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-700 outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100" placeholder="Ví dụ: Laravel, Vue.js, PostgreSQL" type="text" />
                    <select v-model="item.muc_do" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-700 outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100">
                      <option v-for="level in cvSkillLevelOptions" :key="level.value" :value="level.value">{{ level.label }}</option>
                    </select>
                    <button class="rounded-2xl border border-rose-200 text-rose-500 transition hover:bg-rose-50" type="button" @click="removeSectionItem('ky_nang_json', index)">
                      <span class="material-symbols-outlined">delete</span>
                    </button>
                  </div>
                </div>
              </section>

              <section class="rounded-3xl border border-white/80 bg-white p-5">
                <div class="mb-4 flex items-center justify-between gap-4">
                  <div>
                    <h5 class="text-base font-bold text-slate-900">Kinh nghiệm làm việc</h5>
                    <p class="text-sm text-slate-500">Nhập các mốc kinh nghiệm quan trọng để employer xem nhanh hồ sơ ngay trên hệ thống.</p>
                  </div>
                  <button class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white" type="button" @click="addSectionItem('kinh_nghiem_json')">
                    Thêm kinh nghiệm
                  </button>
                </div>
                <div class="space-y-4">
                  <div v-for="(item, index) in form.kinh_nghiem_json" :key="`exp-${index}`" class="rounded-3xl border border-slate-200 p-4">
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                      <input v-model="item.vi_tri" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100" placeholder="Vị trí" type="text" />
                      <input v-model="item.cong_ty" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100" placeholder="Công ty" type="text" />
                      <input v-model="item.bat_dau" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100" placeholder="Bắt đầu (MM/YYYY)" type="text" />
                      <input v-model="item.ket_thuc" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100" placeholder="Kết thúc / Hiện tại" type="text" />
                      <textarea v-model="item.mo_ta" rows="3" class="md:col-span-2 rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100" placeholder="Mô tả ngắn các đầu việc, thành tựu hoặc tác động nổi bật." />
                    </div>
                    <div class="mt-3 flex justify-end">
                      <button class="rounded-xl border border-rose-200 px-3 py-2 text-sm font-semibold text-rose-500 transition hover:bg-rose-50" type="button" @click="removeSectionItem('kinh_nghiem_json', index)">
                        Xóa kinh nghiệm
                      </button>
                    </div>
                  </div>
                </div>
              </section>

              <section class="rounded-3xl border border-white/80 bg-white p-5">
                <div class="mb-4 flex items-center justify-between gap-4">
                  <div>
                    <h5 class="text-base font-bold text-slate-900">Học vấn</h5>
                    <p class="text-sm text-slate-500">Dùng để dựng phần nền tảng học vấn trong CV.</p>
                  </div>
                  <button class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white" type="button" @click="addSectionItem('hoc_van_json')">
                    Thêm học vấn
                  </button>
                </div>
                <div class="space-y-4">
                  <div v-for="(item, index) in form.hoc_van_json" :key="`edu-${index}`" class="rounded-3xl border border-slate-200 p-4">
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                      <input v-model="item.truong" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100" placeholder="Trường học" type="text" />
                      <input v-model="item.chuyen_nganh" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100" placeholder="Chuyên ngành" type="text" />
                      <input v-model="item.bat_dau" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100" placeholder="Bắt đầu (MM/YYYY)" type="text" />
                      <input v-model="item.ket_thuc" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100" placeholder="Kết thúc" type="text" />
                      <textarea v-model="item.mo_ta" rows="3" class="md:col-span-2 rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100" placeholder="Điểm nổi bật, GPA, hoạt động học thuật..." />
                    </div>
                    <div class="mt-3 flex justify-end">
                      <button class="rounded-xl border border-rose-200 px-3 py-2 text-sm font-semibold text-rose-500 transition hover:bg-rose-50" type="button" @click="removeSectionItem('hoc_van_json', index)">
                        Xóa học vấn
                      </button>
                    </div>
                  </div>
                </div>
              </section>

              <section class="grid grid-cols-1 gap-5 lg:grid-cols-2">
                <div class="rounded-3xl border border-white/80 bg-white p-5">
                  <div class="mb-4 flex items-center justify-between gap-4">
                    <div>
                      <h5 class="text-base font-bold text-slate-900">Dự án</h5>
                      <p class="text-sm text-slate-500">Nêu các dự án nổi bật để làm CV thuyết phục hơn.</p>
                    </div>
                    <button class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white" type="button" @click="addSectionItem('du_an_json')">
                      Thêm dự án
                    </button>
                  </div>
                  <div class="space-y-4">
                    <div v-for="(item, index) in form.du_an_json" :key="`project-${index}`" class="rounded-3xl border border-slate-200 p-4">
                      <div class="grid grid-cols-1 gap-3">
                        <input v-model="item.ten" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100" placeholder="Tên dự án" type="text" />
                        <input v-model="item.vai_tro" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100" placeholder="Vai trò" type="text" />
                        <input v-model="item.cong_nghe" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100" placeholder="Công nghệ" type="text" />
                        <input v-model="item.link" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100" placeholder="Link demo / GitHub" type="text" />
                        <textarea v-model="item.mo_ta" rows="3" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100" placeholder="Mô tả dự án và kết quả nổi bật." />
                      </div>
                      <div class="mt-3 flex justify-end">
                        <button class="rounded-xl border border-rose-200 px-3 py-2 text-sm font-semibold text-rose-500 transition hover:bg-rose-50" type="button" @click="removeSectionItem('du_an_json', index)">
                          Xóa dự án
                        </button>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="rounded-3xl border border-white/80 bg-white p-5">
                  <div class="mb-4 flex items-center justify-between gap-4">
                    <div>
                      <h5 class="text-base font-bold text-slate-900">Chứng chỉ</h5>
                      <p class="text-sm text-slate-500">Thêm chứng chỉ chuyên môn để tăng độ tin cậy cho hồ sơ.</p>
                    </div>
                    <button class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white" type="button" @click="addSectionItem('chung_chi_json')">
                      Thêm chứng chỉ
                    </button>
                  </div>
                  <div class="space-y-4">
                    <div v-for="(item, index) in form.chung_chi_json" :key="`cert-${index}`" class="rounded-3xl border border-slate-200 p-4">
                      <div class="grid grid-cols-1 gap-3">
                        <input v-model="item.ten" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100" placeholder="Tên chứng chỉ" type="text" />
                        <input v-model="item.don_vi" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100" placeholder="Đơn vị cấp" type="text" />
                        <input v-model="item.nam" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100" placeholder="Năm cấp" type="text" />
                      </div>
                      <div class="mt-3 flex justify-end">
                        <button class="rounded-xl border border-rose-200 px-3 py-2 text-sm font-semibold text-rose-500 transition hover:bg-rose-50" type="button" @click="removeSectionItem('chung_chi_json', index)">
                          Xóa chứng chỉ
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
              </section>

              <section class="rounded-3xl border border-slate-200 bg-white p-5">
                <div class="mb-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                  <div>
                    <h5 class="text-base font-bold text-slate-900">Preview live</h5>
                    <p class="text-sm text-slate-500">CV thay đổi ngay theo dữ liệu bạn đang nhập và template đang chọn.</p>
                  </div>
                  <button
                    class="inline-flex items-center justify-center gap-2 rounded-2xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-700"
                    type="button"
                    @click="exportProfileCv(previewProfile)"
                  >
                    <span class="material-symbols-outlined text-[18px]">print</span>
                    In / Xuất PDF preview
                  </button>
                </div>

                <div class="grid grid-cols-1 gap-5 xl:grid-cols-[minmax(0,1.2fr)_360px]">
                  <div
                    class="overflow-hidden rounded-[28px] border border-slate-200 shadow-sm"
                    :style="{ color: previewTheme.text }"
                  >
                    <div class="px-6 py-6 text-white" :style="{ background: previewTheme.hero }">
                      <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
                        <div>
                          <h3 class="text-3xl font-black">
                            {{ currentCandidate?.ho_ten || 'Ứng viên' }}
                          </h3>
                          <p class="mt-2 text-sm font-medium opacity-90">
                            {{ previewProfile.tieu_de_ho_so || 'Hồ sơ ứng tuyển trên hệ thống' }}
                          </p>
                        </div>
                        <div class="flex flex-wrap gap-2 text-xs font-semibold">
                          <span class="rounded-full px-3 py-1.5" :style="{ backgroundColor: 'rgba(255,255,255,0.18)' }">
                            {{ currentCandidate?.email || 'Chưa cập nhật email' }}
                          </span>
                          <span class="rounded-full px-3 py-1.5" :style="{ backgroundColor: 'rgba(255,255,255,0.18)' }">
                            {{ currentCandidate?.so_dien_thoai || 'Chưa cập nhật số điện thoại' }}
                          </span>
                          <span class="rounded-full px-3 py-1.5" :style="{ backgroundColor: 'rgba(255,255,255,0.18)' }">
                            {{ cvTemplateLabel(previewProfile.mau_cv) }}
                          </span>
                        </div>
                      </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 p-5 lg:grid-cols-2" :style="{ backgroundColor: '#fff' }">
                      <div class="rounded-3xl border p-4 lg:col-span-2" :style="{ borderColor: previewTheme.accentSoft, backgroundColor: previewTheme.panel }">
                        <p class="text-xs font-semibold uppercase tracking-[0.24em]" :style="{ color: previewTheme.accent }">Mục tiêu nghề nghiệp</p>
                        <p class="mt-3 whitespace-pre-wrap text-sm leading-7">
                          {{ previewProfile.muc_tieu_nghe_nghiep || 'Điền mục tiêu nghề nghiệp để preview hiển thị rõ hơn.' }}
                        </p>
                      </div>

                      <div class="rounded-3xl border p-4" :style="{ borderColor: previewTheme.accentSoft }">
                        <p class="text-xs font-semibold uppercase tracking-[0.24em]" :style="{ color: previewTheme.accent }">Tóm tắt</p>
                        <p class="mt-3 whitespace-pre-wrap text-sm leading-7">
                          {{ previewProfile.mo_ta_ban_than || 'Thêm mô tả bản thân để giới thiệu ngắn gọn cho nhà tuyển dụng.' }}
                        </p>
                      </div>

                      <div class="rounded-3xl border p-4" :style="{ borderColor: previewTheme.accentSoft }">
                        <p class="text-xs font-semibold uppercase tracking-[0.24em]" :style="{ color: previewTheme.accent }">Thông tin nhanh</p>
                        <div class="mt-3 space-y-2 text-sm">
                          <p>Trình độ: <span class="font-semibold">{{ degreeLabel(previewProfile.trinh_do) }}</span></p>
                          <p>Kinh nghiệm: <span class="font-semibold">{{ previewProfile.kinh_nghiem_nam || 0 }} năm</span></p>
                          <p>Kiểu hồ sơ: <span class="font-semibold">{{ previewProfile.nguon_ho_so }}</span></p>
                        </div>
                      </div>

                      <div class="rounded-3xl border p-4 lg:col-span-2" :style="{ borderColor: previewTheme.accentSoft }">
                        <p class="text-xs font-semibold uppercase tracking-[0.24em]" :style="{ color: previewTheme.accent }">Kỹ năng</p>
                        <div class="mt-3 flex flex-wrap gap-2">
                          <span
                            v-for="(item, index) in previewProfile.ky_nang_json"
                            :key="`preview-skill-${index}`"
                            class="rounded-full px-3 py-1.5 text-xs font-semibold"
                            :style="{ backgroundColor: previewTheme.panel, color: previewTheme.accent }"
                          >
                            {{ item.ten }}<span v-if="item.muc_do"> • {{ skillLevelLabel(item.muc_do) }}</span>
                          </span>
                          <span v-if="!previewProfile.ky_nang_json.length" class="text-sm text-slate-500">Chưa có kỹ năng nào được thêm.</span>
                        </div>
                      </div>

                      <div class="rounded-3xl border p-4 lg:col-span-2" :style="{ borderColor: previewTheme.accentSoft }">
                        <p class="text-xs font-semibold uppercase tracking-[0.24em]" :style="{ color: previewTheme.accent }">Kinh nghiệm nổi bật</p>
                        <div v-if="previewProfile.kinh_nghiem_json.length" class="mt-3 space-y-3">
                          <div
                            v-for="(item, index) in previewProfile.kinh_nghiem_json.slice(0, 3)"
                            :key="`preview-exp-${index}`"
                            class="rounded-2xl p-4"
                            :style="{ backgroundColor: previewTheme.panel }"
                          >
                            <div class="flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
                              <div>
                                <p class="text-sm font-bold">{{ item.vi_tri }}</p>
                                <p class="text-sm text-slate-500">{{ item.cong_ty || 'Chưa cập nhật công ty' }}</p>
                              </div>
                              <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">{{ formatCvPeriod(item.bat_dau, item.ket_thuc) }}</p>
                            </div>
                            <p v-if="item.mo_ta" class="mt-3 text-sm leading-7 text-slate-600">{{ item.mo_ta }}</p>
                          </div>
                        </div>
                        <p v-else class="mt-3 text-sm text-slate-500">Chưa có kinh nghiệm nào được thêm.</p>
                      </div>
                    </div>
                  </div>

                  <div class="space-y-4">
                    <div class="rounded-3xl border border-slate-200 bg-slate-50 p-4">
                      <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-400">Template đang chọn</p>
                      <div class="mt-3 grid grid-cols-1 gap-3">
                        <button
                          v-for="option in cvTemplateOptions"
                          :key="`preview-template-${option.value}`"
                          class="rounded-2xl border px-4 py-3 text-left text-sm font-semibold transition"
                          :class="form.mau_cv === option.value ? 'border-slate-900 bg-slate-900 text-white' : 'border-slate-200 bg-white text-slate-700 hover:border-slate-300'"
                          type="button"
                          @click="form.mau_cv = option.value"
                        >
                          {{ option.label }}
                        </button>
                      </div>
                    </div>

                    <div class="rounded-3xl border border-slate-200 bg-slate-50 p-4">
                      <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-400">Preview checklist</p>
                      <div class="mt-3 space-y-2 text-sm text-slate-600">
                        <p>Tiêu đề hồ sơ: <span class="font-semibold">{{ previewProfile.tieu_de_ho_so ? 'Đã có' : 'Chưa có' }}</span></p>
                        <p>Kỹ năng: <span class="font-semibold">{{ previewProfile.ky_nang_json.length }}</span></p>
                        <p>Kinh nghiệm: <span class="font-semibold">{{ previewProfile.kinh_nghiem_json.length }}</span></p>
                        <p>Học vấn: <span class="font-semibold">{{ previewProfile.hoc_van_json.length }}</span></p>
                        <p>Dự án: <span class="font-semibold">{{ previewProfile.du_an_json.length }}</span></p>
                        <p>Chứng chỉ: <span class="font-semibold">{{ previewProfile.chung_chi_json.length }}</span></p>
                      </div>
                    </div>
                  </div>
                </div>
              </section>
            </div>
          </div>
        </div>

        <div class="flex flex-col gap-3 border-t border-slate-100 px-6 py-5 sm:flex-row sm:justify-end">
          <button
            class="rounded-2xl border border-slate-200 px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
            type="button"
            @click="closeModal"
          >
            Hủy
          </button>
          <button
            class="rounded-2xl bg-[#2463eb] px-5 py-3 text-sm font-bold text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-blue-300"
            :disabled="saving || !form.tieu_de_ho_so"
            type="button"
            @click="submitProfile"
          >
            {{ saving ? 'Đang lưu...' : editingProfileId ? 'Lưu thay đổi' : 'Tạo hồ sơ' }}
          </button>
        </div>
      </div>
    </div>

    <div
      v-if="detailModalOpen && selectedProfileDetail"
      class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/55 px-4 py-6 backdrop-blur-sm"
      @click.self="closeDetailModal"
    >
      <div class="w-full max-w-3xl rounded-[28px] border border-slate-200 bg-white shadow-2xl">
        <div class="flex items-start justify-between border-b border-slate-100 px-6 py-5">
          <div>
            <p class="text-xs font-semibold uppercase tracking-[0.28em] text-slate-500">Chi tiết hồ sơ</p>
            <h3 class="mt-2 text-2xl font-bold text-slate-900">
              {{ selectedProfileDetail.tieu_de_ho_so }}
            </h3>
          </div>
          <button
            class="rounded-full p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
            type="button"
            @click="closeDetailModal"
          >
            <span class="material-symbols-outlined">close</span>
          </button>
        </div>

        <div class="max-h-[calc(100vh-10rem)] overflow-y-auto px-6 py-6">
          <div v-if="hasBuilderCv(selectedProfileDetail)" class="mb-5 rounded-3xl border border-blue-100 bg-blue-50/70 p-5">
            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
              <div>
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-blue-500">CV builder</p>
                <h4 class="mt-2 text-lg font-bold text-slate-900">CV được tạo trực tiếp trên hệ thống</h4>
                <p class="mt-1 text-sm text-slate-600">
                  Mẫu hiển thị hiện tại: <span class="font-semibold text-slate-900">{{ cvTemplateLabel(selectedProfileDetail.mau_cv) }}</span>
                </p>
              </div>
              <button
                class="inline-flex items-center justify-center gap-2 rounded-2xl bg-blue-600 px-5 py-3 text-sm font-bold text-white transition hover:bg-blue-700"
                type="button"
                @click="exportProfileCv(selectedProfileDetail)"
              >
                <span class="material-symbols-outlined text-[18px]">print</span>
                Xuất PDF / In CV
              </button>
            </div>
          </div>

          <div class="flex flex-wrap items-center gap-2">
            <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold" :class="statusMeta(selectedProfileDetail.trang_thai).classes">
              <span class="size-1.5 rounded-full" :class="statusMeta(selectedProfileDetail.trang_thai).dot"></span>
              {{ statusMeta(selectedProfileDetail.trang_thai).label }}
            </span>
            <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold" :class="parseStatusMeta(selectedProfileDetail).classes">
              <span class="material-symbols-outlined text-[14px]">{{ parseStatusMeta(selectedProfileDetail).icon }}</span>
              {{ parseStatusMeta(selectedProfileDetail).label }}
            </span>
          </div>

          <div class="mt-5 grid grid-cols-1 gap-4 md:grid-cols-2">
            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
              <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-400">Trình độ</p>
              <p class="mt-2 text-base font-bold text-slate-900">{{ degreeLabel(selectedProfileDetail.trinh_do) }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
              <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-400">Kinh nghiệm</p>
              <p class="mt-2 text-base font-bold text-slate-900">{{ selectedProfileDetail.kinh_nghiem_nam ?? 0 }} năm</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4 md:col-span-2">
              <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-400">Cập nhật lần cuối</p>
              <p class="mt-2 text-base font-bold text-slate-900">{{ formatDate(selectedProfileDetail.updated_at) }}</p>
            </div>
          </div>

          <div class="mt-5 space-y-4">
            <div class="rounded-2xl border border-slate-200 px-4 py-4">
              <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-400">Mục tiêu nghề nghiệp</p>
              <p class="mt-3 whitespace-pre-wrap text-sm leading-7 text-slate-700">
                {{ selectedProfileDetail.muc_tieu_nghe_nghiep || 'Chưa cập nhật mục tiêu nghề nghiệp.' }}
              </p>
            </div>

            <div class="rounded-2xl border border-slate-200 px-4 py-4">
              <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-400">Mô tả bản thân</p>
              <p class="mt-3 whitespace-pre-wrap text-sm leading-7 text-slate-700">
                {{ selectedProfileDetail.mo_ta_ban_than || 'Chưa cập nhật mô tả bản thân.' }}
              </p>
            </div>

            <div class="rounded-2xl border border-slate-200 px-4 py-4">
              <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-400">File CV</p>
              <div class="mt-3">
                <a
                  v-if="selectedProfileDetail.file_cv"
                  :href="cvFileUrl(selectedProfileDetail.file_cv)"
                  class="inline-flex items-center gap-2 rounded-xl bg-[#2463eb]/10 px-4 py-2.5 text-sm font-semibold text-[#2463eb] transition hover:bg-[#2463eb] hover:text-white"
                  target="_blank"
                  rel="noopener noreferrer"
                >
                  <span class="material-symbols-outlined text-[18px]">download</span>
                  Tải xuống CV
                </a>
                <p v-else class="text-sm text-slate-500">Hồ sơ này chưa có file CV.</p>
              </div>
            </div>

            <div v-if="hasBuilderCv(selectedProfileDetail)" class="grid grid-cols-1 gap-4 lg:grid-cols-2">
              <div class="rounded-2xl border border-slate-200 px-4 py-4 lg:col-span-2">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-400">Kỹ năng</p>
                <div v-if="selectedProfileDetail.ky_nang_json?.length" class="mt-3 flex flex-wrap gap-2">
                  <span
                    v-for="(item, index) in selectedProfileDetail.ky_nang_json"
                    :key="`detail-skill-${index}`"
                    class="rounded-full bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-700"
                  >
                    {{ item.ten }}<span v-if="item.muc_do"> • {{ skillLevelLabel(item.muc_do) }}</span>
                  </span>
                </div>
                <p v-else class="mt-3 text-sm text-slate-500">Chưa cập nhật kỹ năng.</p>
              </div>

              <div class="rounded-2xl border border-slate-200 px-4 py-4 lg:col-span-2">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-400">Kinh nghiệm làm việc</p>
                <div v-if="selectedProfileDetail.kinh_nghiem_json?.length" class="mt-3 space-y-3">
                  <div v-for="(item, index) in selectedProfileDetail.kinh_nghiem_json" :key="`detail-exp-${index}`" class="rounded-2xl bg-slate-50 px-4 py-4">
                    <div class="flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
                      <div>
                        <p class="text-sm font-bold text-slate-900">{{ item.vi_tri || 'Chưa cập nhật vị trí' }}</p>
                        <p class="text-sm text-slate-500">{{ item.cong_ty || 'Chưa cập nhật công ty' }}</p>
                      </div>
                      <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">
                        {{ formatCvPeriod(item.bat_dau, item.ket_thuc) }}
                      </p>
                    </div>
                    <p v-if="item.mo_ta" class="mt-3 whitespace-pre-wrap text-sm leading-7 text-slate-700">{{ item.mo_ta }}</p>
                  </div>
                </div>
                <p v-else class="mt-3 text-sm text-slate-500">Chưa cập nhật kinh nghiệm.</p>
              </div>

              <div class="rounded-2xl border border-slate-200 px-4 py-4">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-400">Học vấn</p>
                <div v-if="selectedProfileDetail.hoc_van_json?.length" class="mt-3 space-y-3">
                  <div v-for="(item, index) in selectedProfileDetail.hoc_van_json" :key="`detail-edu-${index}`" class="rounded-2xl bg-slate-50 px-4 py-4">
                    <p class="text-sm font-bold text-slate-900">{{ item.truong || 'Chưa cập nhật trường học' }}</p>
                    <p class="mt-1 text-sm text-slate-500">{{ item.chuyen_nganh || 'Chưa cập nhật chuyên ngành' }}</p>
                    <p class="mt-2 text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">{{ formatCvPeriod(item.bat_dau, item.ket_thuc) }}</p>
                    <p v-if="item.mo_ta" class="mt-3 whitespace-pre-wrap text-sm leading-7 text-slate-700">{{ item.mo_ta }}</p>
                  </div>
                </div>
                <p v-else class="mt-3 text-sm text-slate-500">Chưa cập nhật học vấn.</p>
              </div>

              <div class="rounded-2xl border border-slate-200 px-4 py-4">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-400">Chứng chỉ</p>
                <div v-if="selectedProfileDetail.chung_chi_json?.length" class="mt-3 space-y-3">
                  <div v-for="(item, index) in selectedProfileDetail.chung_chi_json" :key="`detail-cert-${index}`" class="rounded-2xl bg-slate-50 px-4 py-4">
                    <p class="text-sm font-bold text-slate-900">{{ item.ten || 'Chưa cập nhật chứng chỉ' }}</p>
                    <p class="mt-1 text-sm text-slate-500">{{ item.don_vi || 'Chưa cập nhật đơn vị cấp' }}</p>
                    <p v-if="item.nam" class="mt-2 text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Năm {{ item.nam }}</p>
                  </div>
                </div>
                <p v-else class="mt-3 text-sm text-slate-500">Chưa cập nhật chứng chỉ.</p>
              </div>

              <div class="rounded-2xl border border-slate-200 px-4 py-4 lg:col-span-2">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-400">Dự án</p>
                <div v-if="selectedProfileDetail.du_an_json?.length" class="mt-3 space-y-3">
                  <div v-for="(item, index) in selectedProfileDetail.du_an_json" :key="`detail-project-${index}`" class="rounded-2xl bg-slate-50 px-4 py-4">
                    <div class="flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
                      <div>
                        <p class="text-sm font-bold text-slate-900">{{ item.ten || 'Chưa cập nhật dự án' }}</p>
                        <p class="text-sm text-slate-500">{{ item.vai_tro || 'Chưa cập nhật vai trò' }}</p>
                      </div>
                      <p v-if="item.cong_nghe" class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">{{ item.cong_nghe }}</p>
                    </div>
                    <p v-if="item.mo_ta" class="mt-3 whitespace-pre-wrap text-sm leading-7 text-slate-700">{{ item.mo_ta }}</p>
                    <a v-if="item.link" :href="item.link" class="mt-3 inline-flex text-sm font-semibold text-blue-600 hover:underline" target="_blank" rel="noopener noreferrer">
                      {{ item.link }}
                    </a>
                  </div>
                </div>
                <p v-else class="mt-3 text-sm text-slate-500">Chưa cập nhật dự án.</p>
              </div>
            </div>
          </div>
        </div>

        <div class="flex justify-end border-t border-slate-100 px-6 py-5">
          <button
            class="rounded-2xl bg-slate-900 px-5 py-3 text-sm font-bold text-white transition hover:bg-slate-700"
            type="button"
            @click="closeDetailModal"
          >
            Đóng
          </button>
        </div>
      </div>
    </div>

    <div
      v-if="parseResultModalOpen"
      class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/55 px-4 py-6 backdrop-blur-sm"
      @click.self="closeParseResultModal"
    >
      <div class="mx-auto w-full max-w-4xl rounded-[28px] border border-slate-200 bg-white shadow-2xl">
        <div class="flex items-start justify-between border-b border-slate-100 px-6 py-5">
          <div>
            <p class="text-xs font-semibold uppercase tracking-[0.28em] text-violet-500">Kết quả AI Parse CV</p>
            <h3 class="mt-2 text-2xl font-bold text-slate-900">
              {{ parseResult?.profileTitle || 'Phân tích hồ sơ' }}
            </h3>
          </div>
          <button
            class="rounded-full p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
            type="button"
            @click="closeParseResultModal"
          >
            <span class="material-symbols-outlined">close</span>
          </button>
        </div>

        <div class="max-h-[calc(100vh-10rem)] overflow-y-auto px-6 py-6">
          <div
            v-if="parseResult?.syncSummary"
            class="mb-6 rounded-3xl border border-emerald-200 bg-emerald-50/80 p-5"
          >
            <div class="flex items-start gap-3">
              <div class="rounded-2xl bg-emerald-100 p-2 text-emerald-600">
                <span class="material-symbols-outlined">auto_fix_high</span>
              </div>
              <div class="flex-1">
                <h4 class="text-base font-bold text-emerald-900">Đã tự động đồng bộ thông tin</h4>
                <p class="mt-1 text-sm text-emerald-800">
                  Hệ thống đã lấy kết quả AI parse để giảm thao tác nhập tay cho hồ sơ và kỹ năng cá nhân.
                </p>
                <div class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-2">
                  <div class="min-h-[150px] rounded-2xl bg-white/90 p-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-emerald-500">Trường hồ sơ đã điền</p>
                    <div
                      v-if="Array.isArray(parseResult.syncSummary.updated_fields) && parseResult.syncSummary.updated_fields.length"
                      class="mt-3 flex flex-wrap gap-2"
                    >
                      <span
                        v-for="field in parseResult.syncSummary.updated_fields"
                        :key="field"
                        class="rounded-full bg-emerald-100 px-3 py-1.5 text-xs font-semibold text-emerald-700"
                      >
                        {{ field === 'trinh_do' ? 'Trình độ' : field === 'kinh_nghiem_nam' ? 'Kinh nghiệm' : field }}
                      </span>
                    </div>
                    <p v-else class="mt-3 text-sm text-slate-500">
                      Chưa tự điền thêm trường nào vì hồ sơ đã có dữ liệu sẵn hoặc AI chưa suy luận đủ chắc chắn.
                    </p>
                  </div>
                  <div class="min-h-[150px] rounded-2xl bg-white/90 p-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-emerald-500">Kỹ năng đã đồng bộ</p>
                    <p class="mt-3 text-3xl font-bold text-emerald-700">
                      {{ Number(parseResult.syncSummary.synced_skills || 0) }}
                    </p>
                    <p class="mt-1 text-sm text-slate-500">
                      kỹ năng mới đã được thêm vào mục <span class="font-semibold text-slate-700">Kỹ năng của tôi</span>.
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="mt-4 rounded-3xl border border-blue-200 bg-blue-50/70 p-5">
            <div class="flex flex-col gap-4">
              <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                <div class="max-w-2xl">
                  <h4 class="text-base font-bold text-blue-900">Áp dụng thông tin cá nhân từ CV</h4>
                  <p class="mt-1 text-sm text-blue-800">
                    Dùng nhanh kết quả AI parse để điền vào hồ sơ cá nhân, tránh nhập tay lại từ đầu.
                  </p>
                </div>

                <button
                  class="inline-flex min-w-[250px] items-center justify-center self-start rounded-2xl bg-blue-600 px-5 py-3 text-sm font-bold text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-blue-300"
                  :disabled="applyingPersonalInfo || !selectedPersonalFieldKeys.length"
                  type="button"
                  @click="applyPersonalInfoFromCv"
                >
                  {{ applyingPersonalInfo ? 'Đang áp dụng...' : 'Áp dụng thông tin cá nhân từ CV' }}
                </button>
              </div>

              <div v-if="availablePersonalFields.length" class="grid grid-cols-1 items-stretch gap-3 md:grid-cols-3">
                <label
                  v-for="field in availablePersonalFields"
                  :key="field.key"
                  class="flex h-full rounded-2xl border border-blue-200 bg-white/90 p-4 transition hover:border-blue-300"
                >
                  <div class="flex w-full items-start gap-3">
                    <input
                      v-model="selectedPersonalFieldKeys"
                      :value="field.key"
                      class="mt-1 h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                      type="checkbox"
                    />
                    <div class="flex min-h-[220px] flex-1 flex-col">
                      <p class="text-sm font-bold text-slate-900">{{ field.label }}</p>
                      <div class="mt-2 flex min-w-0 flex-1 flex-col gap-3 text-sm">
                        <div class="flex min-h-[0] min-w-0 flex-1 flex-col justify-center overflow-hidden rounded-xl bg-slate-50 px-4 py-3">
                          <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-400">Hiện tại</p>
                          <p class="mt-1 min-w-0 break-all leading-6 text-slate-700">{{ field.currentValue }}</p>
                        </div>
                        <div class="flex min-h-[0] min-w-0 flex-1 flex-col justify-center overflow-hidden rounded-xl bg-blue-50 px-4 py-3">
                          <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-blue-500">Từ CV</p>
                          <p class="mt-1 min-w-0 break-all leading-6 font-semibold text-blue-800">{{ field.parsedValue }}</p>
                        </div>
                      </div>
                    </div>
                  </div>
                </label>
              </div>
              <p v-else class="text-sm text-slate-500">
                Chưa có đủ dữ liệu cá nhân hợp lệ để tự động áp dụng.
              </p>
            </div>
          </div>

          <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
            <div class="rounded-3xl border border-slate-200 p-5">
              <div class="flex items-center justify-between gap-4">
                <h4 class="text-lg font-bold text-slate-900">Kỹ năng trích xuất</h4>
                <span class="rounded-full bg-violet-100 px-3 py-1 text-xs font-bold text-violet-600">
                  {{ parsedSkills.length }} kỹ năng
                </span>
              </div>
              <div v-if="parsedSkills.length" class="mt-4 flex flex-wrap gap-2">
                <span
                  v-for="skill in parsedSkills"
                  :key="skill"
                  class="rounded-full bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-700"
                >
                  {{ skill }}
                </span>
              </div>
              <p v-else class="mt-4 text-sm text-slate-500">Chưa trích xuất được kỹ năng nổi bật.</p>
            </div>

            <div class="rounded-3xl border border-slate-200 p-5">
              <h4 class="text-lg font-bold text-slate-900">Học vấn</h4>
              <ul v-if="parsedEducation.length" class="mt-4 space-y-3 text-sm leading-7 text-slate-700">
                <li v-for="item in parsedEducation" :key="item" class="rounded-2xl bg-slate-50 px-4 py-3">
                  <div class="flex items-start gap-3">
                    <span class="mt-2 h-2 w-2 flex-shrink-0 rounded-full bg-violet-500"></span>
                    <p class="leading-7">{{ item }}</p>
                  </div>
                </li>
              </ul>
              <p v-else class="mt-4 text-sm text-slate-500">Chưa trích xuất được thông tin học vấn.</p>
            </div>
          </div>

          <div class="mt-6 rounded-3xl border border-slate-200 p-5">
            <h4 class="text-lg font-bold text-slate-900">Kinh nghiệm làm việc</h4>
            <ul v-if="parsedExperience.length" class="mt-4 space-y-3 text-sm leading-7 text-slate-700">
              <li v-for="item in parsedExperience" :key="item" class="rounded-2xl bg-slate-50 px-4 py-3">
                <div class="flex items-start gap-3">
                  <span class="mt-2 h-2 w-2 flex-shrink-0 rounded-full bg-blue-500"></span>
                  <p class="leading-7">{{ item }}</p>
                </div>
              </li>
            </ul>
            <p v-else class="mt-4 text-sm text-slate-500">Chưa trích xuất được phần kinh nghiệm.</p>
          </div>

          <div class="mt-5 rounded-2xl border border-dashed border-slate-200 bg-slate-50/70 px-4 py-3 text-sm text-slate-500">
            Hiện tại AI chỉ áp dụng trực tiếp được các trường cá nhân có độ tin cậy cao nhất:
            <span class="font-semibold text-slate-700">Họ và tên, Email, Số điện thoại</span>.
            Các trường như <span class="font-semibold text-slate-700">Ngày sinh, Giới tính, Địa chỉ</span> chưa được parser trả về ổn định nên chưa tự điền để tránh sai lệch dữ liệu.
          </div>
        </div>

        <div class="flex justify-end border-t border-slate-100 px-6 py-5">
          <button
            class="rounded-2xl bg-violet-600 px-5 py-3 text-sm font-bold text-white transition hover:bg-violet-700"
            type="button"
            @click="closeParseResultModal"
          >
            Đóng kết quả
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
