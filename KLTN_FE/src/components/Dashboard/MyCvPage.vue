<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { candidateCvService } from '@/services/api'

const API_DOMAIN = import.meta.env.VITE_API_DOMAIN || 'http://localhost:8000'

const loading = ref(true)
const submitting = ref(false)
const actionLoadingId = ref(null)
const cvs = ref([])
const pagination = reactive({
  current_page: 1,
  last_page: 1,
  per_page: 10,
  total: 0
})

const error = ref('')
const success = ref('')
const showModal = ref(false)
const editingId = ref(null)
const cvFile = ref(null)

const form = reactive({
  tieu_de_ho_so: '',
  muc_tieu_nghe_nghiep: '',
  trinh_do: '',
  kinh_nghiem_nam: 0,
  mo_ta_ban_than: '',
  trang_thai: 1
})

const isEditing = computed(() => editingId.value !== null)
const publicCount = computed(() => cvs.value.filter((cv) => Number(cv.trang_thai) === 1).length)
const hiddenCount = computed(() => cvs.value.filter((cv) => Number(cv.trang_thai) !== 1).length)

const resetMessages = () => {
  error.value = ''
  success.value = ''
}

const resetForm = () => {
  editingId.value = null
  form.tieu_de_ho_so = ''
  form.muc_tieu_nghe_nghiep = ''
  form.trinh_do = ''
  form.kinh_nghiem_nam = 0
  form.mo_ta_ban_than = ''
  form.trang_thai = 1
  cvFile.value = null
}

const extractCvItems = (response) => {
  if (Array.isArray(response?.data?.data)) {
    pagination.current_page = response.data.current_page || 1
    pagination.last_page = response.data.last_page || 1
    pagination.per_page = response.data.per_page || 10
    pagination.total = response.data.total || response.data.data.length
    return response.data.data
  }

  if (Array.isArray(response?.data)) {
    pagination.current_page = 1
    pagination.last_page = 1
    pagination.per_page = response.data.length || 10
    pagination.total = response.data.length
    return response.data
  }

  if (Array.isArray(response)) {
    pagination.current_page = 1
    pagination.last_page = 1
    pagination.per_page = response.length || 10
    pagination.total = response.length
    return response
  }

  pagination.current_page = 1
  pagination.last_page = 1
  pagination.total = 0
  return []
}

const formatDate = (value) => {
  if (!value) return 'Chưa cập nhật'
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return 'Chưa cập nhật'
  return new Intl.DateTimeFormat('vi-VN').format(date)
}

const getFileName = (path) => {
  if (!path) return 'Chưa có file CV'
  return path.split('/').pop() || path
}

const getFileUrl = (path) => {
  if (!path) return ''
  if (path.startsWith('http://') || path.startsWith('https://')) return path
  const normalizedPath = path.startsWith('/') ? path : `/storage/${path}`
  return `${API_DOMAIN}${normalizedPath}`
}

const loadCvs = async (page = 1, shouldResetMessages = true) => {
  loading.value = true
  if (shouldResetMessages) {
    resetMessages()
  }

  try {
    const response = await candidateCvService.getCvs({
      page,
      per_page: pagination.per_page,
      sort_by: 'updated_at',
      sort_dir: 'desc'
    })
    cvs.value = extractCvItems(response)
  } catch (err) {
    error.value = err.message || 'Không thể tải danh sách CV'
  } finally {
    loading.value = false
  }
}

const openCreateModal = () => {
  resetMessages()
  resetForm()
  showModal.value = true
}

const openEditModal = (cv) => {
  resetMessages()
  editingId.value = cv.id
  form.tieu_de_ho_so = cv.tieu_de_ho_so || ''
  form.muc_tieu_nghe_nghiep = cv.muc_tieu_nghe_nghiep || ''
  form.trinh_do = cv.trinh_do || ''
  form.kinh_nghiem_nam = Number(cv.kinh_nghiem_nam || 0)
  form.mo_ta_ban_than = cv.mo_ta_ban_than || ''
  form.trang_thai = Number(cv.trang_thai) === 1 ? 1 : 0
  cvFile.value = null
  showModal.value = true
}

const closeModal = () => {
  showModal.value = false
  resetForm()
}

const onFileChange = (event) => {
  cvFile.value = event.target.files?.[0] || null
}

const buildFormData = () => {
  const formData = new FormData()
  formData.append('tieu_de_ho_so', form.tieu_de_ho_so)
  formData.append('muc_tieu_nghe_nghiep', form.muc_tieu_nghe_nghiep)
  formData.append('trinh_do', form.trinh_do)
  formData.append('kinh_nghiem_nam', String(form.kinh_nghiem_nam || 0))
  formData.append('mo_ta_ban_than', form.mo_ta_ban_than)
  formData.append('trang_thai', String(form.trang_thai))

  if (cvFile.value) {
    formData.append('file_cv', cvFile.value)
  }

  return formData
}

const submitCv = async () => {
  resetMessages()

  if (!form.tieu_de_ho_so.trim()) {
    error.value = 'Vui lòng nhập tiêu đề hồ sơ'
    return
  }

  submitting.value = true

  try {
    const payload = buildFormData()

    if (isEditing.value) {
      await candidateCvService.updateCv(editingId.value, payload)
      success.value = 'Cập nhật hồ sơ thành công'
    } else {
      await candidateCvService.createCv(payload)
      success.value = 'Tạo hồ sơ thành công'
    }

    closeModal()
    await loadCvs(pagination.current_page, false)
  } catch (err) {
    error.value = err.message || 'Không thể lưu hồ sơ'
  } finally {
    submitting.value = false
  }
}

const toggleStatus = async (cv) => {
  resetMessages()
  actionLoadingId.value = `toggle-${cv.id}`

  try {
    await candidateCvService.toggleCvStatus(cv.id)
    success.value = Number(cv.trang_thai) === 1 ? 'Đã ẩn hồ sơ' : 'Đã công khai hồ sơ'
    await loadCvs(pagination.current_page, false)
  } catch (err) {
    error.value = err.message || 'Không thể đổi trạng thái hồ sơ'
  } finally {
    actionLoadingId.value = null
  }
}

const removeCv = async (cv) => {
  resetMessages()

  if (!window.confirm(`Bạn có chắc muốn xóa hồ sơ "${cv.tieu_de_ho_so}"?`)) {
    return
  }

  actionLoadingId.value = `delete-${cv.id}`

  try {
    await candidateCvService.deleteCv(cv.id)
    success.value = 'Xóa hồ sơ thành công'

    const nextPage = cvs.value.length === 1 && pagination.current_page > 1
      ? pagination.current_page - 1
      : pagination.current_page

    await loadCvs(nextPage, false)
  } catch (err) {
    error.value = err.message || 'Không thể xóa hồ sơ'
  } finally {
    actionLoadingId.value = null
  }
}

const downloadCv = (cv) => {
  const fileUrl = getFileUrl(cv.file_cv || cv.file_cv_url || cv.url)
  if (!fileUrl) {
    error.value = 'Hồ sơ này chưa có file CV để tải xuống'
    return
  }

  window.open(fileUrl, '_blank', 'noopener,noreferrer')
}

onMounted(() => {
  loadCvs()
})
</script>

<template>
  <div class="space-y-6">
    <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
      <div>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">CV của tôi</h1>
        <p class="mt-1 text-sm text-slate-500">Quản lý hồ sơ xin việc và tạo nhiều phiên bản CV cho từng vị trí ứng tuyển.</p>
      </div>
      <button type="button" @click="openCreateModal" class="inline-flex items-center justify-center gap-2 rounded-lg bg-[#2463eb] px-5 py-2.5 text-sm font-bold text-white shadow-lg shadow-[#2463eb]/20 transition-all hover:bg-[#2463eb]/90">
        <span class="material-symbols-outlined text-xl">add</span>
        Tạo CV mới
      </button>
    </div>

    <div v-if="error || success" class="rounded-xl border px-4 py-3 text-sm font-medium" :class="error ? 'border-red-200 bg-red-50 text-red-600' : 'border-emerald-200 bg-emerald-50 text-emerald-700'">
      {{ error || success }}
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
      <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="mb-2 flex items-center justify-between">
          <p class="text-sm font-medium text-slate-500">Tổng hồ sơ</p>
          <div class="rounded-lg bg-[#2463eb]/10 p-2 text-[#2463eb]">
            <span class="material-symbols-outlined">description</span>
          </div>
        </div>
        <h3 class="text-2xl font-bold text-slate-900 dark:text-white">{{ pagination.total || cvs.length }}</h3>
      </div>
      <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="mb-2 flex items-center justify-between">
          <p class="text-sm font-medium text-slate-500">Đang công khai</p>
          <div class="rounded-lg bg-green-100 p-2 text-green-600 dark:bg-green-900/20">
            <span class="material-symbols-outlined">visibility</span>
          </div>
        </div>
        <h3 class="text-2xl font-bold text-slate-900 dark:text-white">{{ publicCount }}</h3>
      </div>
      <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="mb-2 flex items-center justify-between">
          <p class="text-sm font-medium text-slate-500">Đang ẩn</p>
          <div class="rounded-lg bg-slate-100 p-2 text-slate-500 dark:bg-slate-800">
            <span class="material-symbols-outlined">visibility_off</span>
          </div>
        </div>
        <h3 class="text-2xl font-bold text-slate-900 dark:text-white">{{ hiddenCount }}</h3>
      </div>
    </div>

    <div v-if="loading" class="space-y-4">
      <div v-for="item in 3" :key="item" class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="animate-pulse space-y-3">
          <div class="h-5 w-52 rounded bg-slate-100 dark:bg-slate-800"></div>
          <div class="h-4 w-72 rounded bg-slate-100 dark:bg-slate-800"></div>
          <div class="h-10 w-full rounded bg-slate-100 dark:bg-slate-800"></div>
        </div>
      </div>
    </div>

    <div v-else-if="cvs.length" class="space-y-4">
      <div v-for="cv in cvs" :key="cv.id" class="rounded-xl border bg-white p-6 shadow-sm transition-all dark:bg-slate-900" :class="Number(cv.trang_thai) === 1 ? 'border-slate-200 hover:border-[#2463eb]/40 dark:border-slate-800' : 'border-slate-200/80 opacity-80 hover:opacity-100 dark:border-slate-800'">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
          <div class="flex items-start gap-4">
            <div class="flex size-14 shrink-0 items-center justify-center rounded-xl" :class="Number(cv.trang_thai) === 1 ? 'bg-[#2463eb]/10 text-[#2463eb]' : 'bg-slate-100 text-slate-400 dark:bg-slate-800'">
              <span class="material-symbols-outlined text-3xl">description</span>
            </div>
            <div class="space-y-2">
              <div class="flex flex-wrap items-center gap-2">
                <h3 class="text-lg font-bold text-slate-900 dark:text-white">{{ cv.tieu_de_ho_so || `Ho so #${cv.id}` }}</h3>
                <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[12px] font-bold" :class="Number(cv.trang_thai) === 1 ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400'">
                  <span class="size-1.5 rounded-full" :class="Number(cv.trang_thai) === 1 ? 'bg-green-500' : 'bg-slate-400'"></span>
                  {{ Number(cv.trang_thai) === 1 ? 'Công khai' : 'Ẩn' }}
                </span>
              </div>
              <p class="text-sm text-slate-500">
               Cập nhật lần cuối: {{ formatDate(cv.updated_at || cv.created_at) }}
                <span v-if="cv.file_cv || cv.file_cv_url || cv.url">• {{ getFileName(cv.file_cv || cv.file_cv_url || cv.url) }}</span>
              </p>
              <div class="flex flex-wrap gap-2 text-xs">
                <span v-if="cv.trinh_do" class="rounded-full bg-slate-100 px-2.5 py-1 font-medium text-slate-600 dark:bg-slate-800 dark:text-slate-300">{{ cv.trinh_do }}</span>
                <span class="rounded-full bg-[#2463eb]/10 px-2.5 py-1 font-medium text-[#2463eb]">{{ Number(cv.kinh_nghiem_nam || 0) }} năm kinh nghiệm</span>
              </div>
              <p v-if="cv.muc_tieu_nghe_nghiep" class="text-sm leading-6 text-slate-600 dark:text-slate-300">
                <span class="font-semibold">Mục tiêu:</span> {{ cv.muc_tieu_nghe_nghiep }}
              </p>
              <p class="max-w-3xl text-sm leading-6 text-slate-600 dark:text-slate-300">
                {{ cv.mo_ta_ban_than || 'Hồ sơ này chưa có mô tả bản thân. Bạn có thể chỉnh sửa để bổ sung nội dung cho nhà tuyển dụng.' }}
              </p>
            </div>
          </div>

          <div class="flex flex-wrap items-center gap-2 md:justify-end">
            <button type="button" @click="downloadCv(cv)" class="inline-flex items-center gap-1.5 rounded-lg px-3 py-2 text-sm font-medium text-slate-600 transition-colors hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-800">
              <span class="material-symbols-outlined text-[18px]">download</span>
              Tải xuống
            </button>
            <button type="button" @click="toggleStatus(cv)" :disabled="actionLoadingId === `toggle-${cv.id}`" class="inline-flex items-center gap-1.5 rounded-lg px-3 py-2 text-sm font-medium transition-colors disabled:opacity-60" :class="Number(cv.trang_thai) === 1 ? 'text-slate-600 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-800' : 'text-green-600 hover:bg-green-50 dark:hover:bg-green-900/20'">
              <span class="material-symbols-outlined text-[18px]">{{ Number(cv.trang_thai) === 1 ? 'visibility_off' : 'visibility' }}</span>
              {{ actionLoadingId === `toggle-${cv.id}` ? 'Đang xử lý...' : Number(cv.trang_thai) === 1 ? 'Ẩn' : 'Hiển thị' }}
            </button>
            <button type="button" @click="openEditModal(cv)" class="inline-flex items-center gap-1.5 rounded-lg bg-[#2463eb]/10 px-4 py-2 text-sm font-bold text-[#2463eb] transition-all hover:bg-[#2463eb] hover:text-white">
              <span class="material-symbols-outlined text-[18px]">edit</span>
              Chỉnh sửa
            </button>
            <button type="button" @click="removeCv(cv)" :disabled="actionLoadingId === `delete-${cv.id}`" class="inline-flex size-9 items-center justify-center rounded-lg text-red-400 transition-colors hover:bg-red-50 hover:text-red-600 disabled:opacity-60 dark:hover:bg-red-900/20">
              <span class="material-symbols-outlined text-[18px]">{{ actionLoadingId === `delete-${cv.id}` ? 'hourglass_top' : 'delete' }}</span>
            </button>
          </div>
        </div>
      </div>
    </div>

    <div v-else class="rounded-2xl border border-dashed border-slate-200 bg-white p-10 text-center shadow-sm dark:border-slate-800 dark:bg-slate-900">
      <div class="mx-auto mb-4 flex size-16 items-center justify-center rounded-2xl bg-[#2463eb]/10 text-[#2463eb]">
        <span class="material-symbols-outlined text-3xl">note_add</span>
      </div>
      <h3 class="text-lg font-bold text-slate-900 dark:text-white">Bạn chưa có hồ sơ nào</h3>
      <p class="mx-auto mt-2 max-w-xl text-sm leading-6 text-slate-500">
        Tạo hồ sơ đầu tiên để tải file CV, viết mô tả ngắn gọn và quản lý trạng thái công khai cho từng vị trí ứng tuyển.
      </p>
    </div>

    <div class="mt-6">
      <button type="button" @click="openCreateModal" class="group flex w-full items-center justify-center gap-3 rounded-xl border-2 border-dashed border-slate-200 p-6 text-slate-500 transition-all hover:border-[#2463eb] hover:text-[#2463eb] dark:border-slate-800">
        <span class="material-symbols-outlined text-2xl transition-transform group-hover:scale-110">note_add</span>
        <span class="font-medium">Tạo hồ sơ mới để ứng tuyển nhiều vị trí khác nhau</span>
      </button>
    </div>

    <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 p-4">
      <div class="w-full max-w-2xl rounded-2xl bg-white p-6 shadow-2xl dark:bg-slate-900">
        <div class="mb-6 flex items-start justify-between gap-4">
          <div>
            <h3 class="text-xl font-bold text-slate-900 dark:text-white">{{ isEditing ? 'Chỉnh sửa hồ sơ' : 'Tạo hồ sơ mới' }}</h3>
            <p class="mt-1 text-sm text-slate-500">Nhập tiêu đề, mô tả và tải file CV lên nếu bạn đã chuẩn bị sẵn.</p>
          </div>
          <button type="button" @click="closeModal" class="rounded-lg p-2 text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-slate-800">
            <span class="material-symbols-outlined">close</span>
          </button>
        </div>

        <div class="space-y-4">
          <div>
            <label class="mb-1 block text-sm font-semibold text-slate-600 dark:text-slate-300">Tiêu đề hồ sơ</label>
            <input v-model="form.tieu_de_ho_so" type="text" placeholder="Vi du: CV Frontend Developer" class="w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-2.5 transition-all focus:border-[#2463eb] focus:ring-2 focus:ring-[#2463eb]/20 dark:border-slate-800 dark:bg-slate-800/50" />
          </div>

          <div>
            <label class="mb-1 block text-sm font-semibold text-slate-600 dark:text-slate-300">Mục tiêu nghề nghiệp</label>
            <textarea v-model="form.muc_tieu_nghe_nghiep" rows="3" placeholder="Vi du: Tim kiem co hoi Frontend Developer de phat trien san pham web hien dai" class="w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 transition-all focus:border-[#2463eb] focus:ring-2 focus:ring-[#2463eb]/20 dark:border-slate-800 dark:bg-slate-800/50"></textarea>
          </div>

          <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
              <label class="mb-1 block text-sm font-semibold text-slate-600 dark:text-slate-300">Trình độ</label>
              <input v-model="form.trinh_do" type="text" placeholder="Vi du: Dai hoc, Cu nhan CNTT" class="w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-2.5 transition-all focus:border-[#2463eb] focus:ring-2 focus:ring-[#2463eb]/20 dark:border-slate-800 dark:bg-slate-800/50" />
            </div>

            <div>
              <label class="mb-1 block text-sm font-semibold text-slate-600 dark:text-slate-300">Kinh nghiệm (năm)</label>
              <input v-model="form.kinh_nghiem_nam" type="number" min="0" placeholder="0" class="w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-2.5 transition-all focus:border-[#2463eb] focus:ring-2 focus:ring-[#2463eb]/20 dark:border-slate-800 dark:bg-slate-800/50" />
            </div>

            <div>
              <label class="mb-1 block text-sm font-semibold text-slate-600 dark:text-slate-300">Trạng thái</label>
              <select v-model="form.trang_thai" class="w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-2.5 transition-all focus:border-[#2463eb] focus:ring-2 focus:ring-[#2463eb]/20 dark:border-slate-800 dark:bg-slate-800/50">
                <option :value="1">Công khai</option>
                <option :value="0">Ẩn</option>
              </select>
            </div>

            <div>
              <label class="mb-1 block text-sm font-semibold text-slate-600 dark:text-slate-300">File CV</label>
              <input type="file" accept=".pdf,.doc,.docx" class="block w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-2 text-sm text-slate-500 transition-all file:mr-3 file:rounded-md file:border-0 file:bg-[#2463eb]/10 file:px-3 file:py-1.5 file:font-semibold file:text-[#2463eb] dark:border-slate-800 dark:bg-slate-800/50" @change="onFileChange" />
              <p class="mt-2 text-xs text-slate-400">
                <span v-if="cvFile">Đã chọn: {{ cvFile.name }}</span>
                <span v-else-if="isEditing">Có thể bỏ trống nếu không muốn thay đổi file hiện tại.</span>
                <span v-else>Chấp nhận PDF, DOC, DOCX.</span>
              </p>
            </div>
          </div>

          <div>
            <label class="mb-1 block text-sm font-semibold text-slate-600 dark:text-slate-300">Mô tả bản thân</label>
            <textarea v-model="form.mo_ta_ban_than" rows="5" placeholder="óm tắt kinh nghiệm, kỹ năng nổi bật, dự án đã tham gia và điểm mạnh của bạn" class="w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 transition-all focus:border-[#2463eb] focus:ring-2 focus:ring-[#2463eb]/20 dark:border-slate-800 dark:bg-slate-800/50"></textarea>
          </div>
        </div>

        <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
          <button type="button" @click="closeModal" class="rounded-lg border border-slate-200 px-4 py-2.5 font-semibold text-slate-600 transition-colors hover:bg-slate-100 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800">
            Hủy
          </button>
          <button type="button" @click="submitCv" :disabled="submitting" class="rounded-lg bg-[#2463eb] px-5 py-2.5 font-bold text-white transition-all hover:bg-[#2463eb]/90 disabled:opacity-60">
            {{ submitting ? 'Đang lưu...' : isEditing ? 'Lưu thay đổi' : 'Tạo hồ sơ' }}
          </button>
        </div>
      </div>
    </div>
  </div>
  
</template>
