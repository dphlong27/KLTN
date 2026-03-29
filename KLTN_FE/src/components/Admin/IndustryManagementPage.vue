<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { adminIndustryService } from '@/services/api'
import { useNotify } from '@/composables/useNotify'

const notify = useNotify()

const industries = ref([])
const loading = ref(false)
const error = ref('')
const currentPage = ref(1)
const totalIndustries = ref(0)
const perPage = ref(10)

const searchQuery = ref('')
const selectedStatus = ref('')
const selectedParent = ref('')

const stats = reactive({
  total: 0,
  visible: 0,
  hidden: 0,
  root: 0,
  child: 0,
})

const showModal = ref(false)
const showDeleteModal = ref(false)
const editingIndustry = ref(null)
const deletingIndustry = ref(null)

const formData = reactive({
  ten_nganh: '',
  mo_ta: '',
  danh_muc_cha_id: '',
  icon: '',
  trang_thai: 1,
})

const statusMap = {
  1: 'Hiển thị',
  0: 'Ẩn',
}

const statusColors = {
  1: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
  0: 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300',
}

const totalPages = computed(() => Math.max(1, Math.ceil(totalIndustries.value / perPage.value)))
const parentOptions = computed(() => industries.value.filter((item) => !item.danh_muc_cha_id))

const normalizeIndustries = (response) => {
  const payload = response?.data

  if (Array.isArray(payload)) {
    industries.value = payload
    totalIndustries.value = response?.total || payload.length
    return
  }

  if (payload?.data && Array.isArray(payload.data)) {
    industries.value = payload.data
    totalIndustries.value = payload.total || payload.meta?.total || payload.data.length
    return
  }

  industries.value = []
  totalIndustries.value = 0
}

const loadStats = async () => {
  try {
    const response = await adminIndustryService.getStats()
    const payload = response?.data || {}
    stats.total = payload.tong || 0
    stats.visible = payload.hien_thi || 0
    stats.hidden = payload.an || 0
    stats.root = payload.nganh_goc || 0
    stats.child = payload.nganh_con || 0
  } catch (err) {
    console.error('Không thể tải thống kê ngành nghề', err)
  }
}

const loadIndustries = async () => {
  loading.value = true
  error.value = ''

  try {
    const response = await adminIndustryService.getIndustries({
      page: currentPage.value,
      per_page: perPage.value,
      search: searchQuery.value || undefined,
      trang_thai: selectedStatus.value,
      danh_muc_cha_id: selectedParent.value,
    })

    normalizeIndustries(response)
  } catch (err) {
    error.value = err.message || 'Không thể tải danh sách ngành nghề'
  } finally {
    loading.value = false
  }
}

const refreshAll = async () => {
  await Promise.all([loadIndustries(), loadStats()])
}

const resetForm = () => {
  formData.ten_nganh = ''
  formData.mo_ta = ''
  formData.danh_muc_cha_id = ''
  formData.icon = ''
  formData.trang_thai = 1
}

const openCreateModal = () => {
  editingIndustry.value = null
  resetForm()
  showModal.value = true
}

const openEditModal = (industry) => {
  editingIndustry.value = industry
  formData.ten_nganh = industry.ten_nganh || ''
  formData.mo_ta = industry.mo_ta || ''
  formData.danh_muc_cha_id = industry.danh_muc_cha_id ?? ''
  formData.icon = industry.icon || ''
  formData.trang_thai = industry.trang_thai ?? 1
  showModal.value = true
}

const submitForm = async () => {
  try {
    const payload = {
      ten_nganh: formData.ten_nganh,
      mo_ta: formData.mo_ta || null,
      danh_muc_cha_id: formData.danh_muc_cha_id === '' ? null : Number(formData.danh_muc_cha_id),
      icon: formData.icon || null,
      trang_thai: Number(formData.trang_thai),
    }

    if (editingIndustry.value) {
      await adminIndustryService.updateIndustry(editingIndustry.value.id, payload)
      notify.success('Đã cập nhật ngành nghề')
    } else {
      await adminIndustryService.createIndustry(payload)
      notify.success('Đã tạo ngành nghề')
    }

    showModal.value = false
    await refreshAll()
  } catch (err) {
    error.value = err.message || 'Không thể lưu ngành nghề'
  }
}

const toggleStatus = async (industry) => {
  try {
    await adminIndustryService.toggleStatus(industry.id)
    notify.success('Đã cập nhật trạng thái ngành nghề')
    await refreshAll()
  } catch (err) {
    error.value = err.message || 'Không thể cập nhật trạng thái'
  }
}

const confirmDelete = (industry) => {
  deletingIndustry.value = industry
  showDeleteModal.value = true
}

const deleteIndustry = async () => {
  try {
    await adminIndustryService.deleteIndustry(deletingIndustry.value.id)
    showDeleteModal.value = false
    deletingIndustry.value = null
    notify.success('Đã xóa ngành nghề')
    await refreshAll()
  } catch (err) {
    error.value = err.message || 'Không thể xóa ngành nghề'
  }
}

const onFilterChange = async () => {
  currentPage.value = 1
  await loadIndustries()
}

onMounted(async () => {
  await refreshAll()
})
</script>

<template>
  <div v-if="error" class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700 dark:border-red-900 dark:bg-red-900/20 dark:text-red-300">
    {{ error }}
  </div>

  <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
    <div class="flex flex-col gap-2">
      <h1 class="text-3xl font-extrabold tracking-tight">Quản lý ngành nghề</h1>
      <p class="max-w-2xl text-base text-slate-500 dark:text-slate-400">Tạo, phân loại và kiểm soát danh mục ngành nghề dùng cho toàn hệ thống tuyển dụng.</p>
    </div>
    <button @click="openCreateModal" class="flex h-11 items-center gap-2 rounded-xl bg-[#2463eb] px-6 text-sm font-bold text-white shadow-lg shadow-[#2463eb]/20 transition-all hover:bg-[#2463eb]/90">
      <span class="material-symbols-outlined text-sm">add</span> Thêm ngành nghề
    </button>
  </div>

  <div class="mb-8 grid grid-cols-1 gap-6 md:grid-cols-3 lg:grid-cols-5">
    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
      <div class="mb-2 flex items-center justify-between">
        <p class="text-sm font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Tổng ngành</p>
        <span class="material-symbols-outlined rounded-lg bg-[#2463eb]/10 p-2 text-[#2463eb]">category</span>
      </div>
      <p class="text-3xl font-bold">{{ stats.total }}</p>
      <div class="mt-2 text-xs text-slate-400">Toàn bộ danh mục hiện có</div>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
      <div class="mb-2 flex items-center justify-between">
        <p class="text-sm font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Hiển thị</p>
        <span class="material-symbols-outlined rounded-lg bg-emerald-500/10 p-2 text-emerald-500">check_circle</span>
      </div>
      <p class="text-3xl font-bold text-emerald-500">{{ stats.visible }}</p>
      <div class="mt-2 text-xs text-slate-400">Đang được dùng trên hệ thống</div>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
      <div class="mb-2 flex items-center justify-between">
        <p class="text-sm font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Đang ẩn</p>
        <span class="material-symbols-outlined rounded-lg bg-slate-500/10 p-2 text-slate-500">visibility_off</span>
      </div>
      <p class="text-3xl font-bold">{{ stats.hidden }}</p>
      <div class="mt-2 text-xs text-slate-400">Tạm thời không hiển thị</div>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
      <div class="mb-2 flex items-center justify-between">
        <p class="text-sm font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Ngành gốc</p>
        <span class="material-symbols-outlined rounded-lg bg-indigo-500/10 p-2 text-indigo-500">account_tree</span>
      </div>
      <p class="text-3xl font-bold">{{ stats.root }}</p>
      <div class="mt-2 text-xs text-slate-400">Danh mục cha cấp cao nhất</div>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
      <div class="mb-2 flex items-center justify-between">
        <p class="text-sm font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Ngành con</p>
        <span class="material-symbols-outlined rounded-lg bg-amber-500/10 p-2 text-amber-500">subdirectory_arrow_right</span>
      </div>
      <p class="text-3xl font-bold">{{ stats.child }}</p>
      <div class="mt-2 text-xs text-slate-400">Danh mục phân nhánh</div>
    </div>
  </div>

  <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
    <div class="flex flex-wrap items-center gap-3 border-b border-slate-200 px-6 py-4 dark:border-slate-800">
      <div class="relative min-w-[260px] flex-1">
        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">search</span>
        <input
          v-model="searchQuery"
          @input="onFilterChange"
          type="text"
          class="w-full rounded-xl border-none bg-slate-50 py-2.5 pl-10 pr-4 text-sm focus:ring-2 focus:ring-[#2463eb] dark:bg-slate-800"
          placeholder="Tìm theo tên ngành, slug hoặc mô tả..."
        />
      </div>

      <select
        v-model="selectedStatus"
        @change="onFilterChange"
        class="rounded-xl border-none bg-slate-50 px-4 py-2.5 text-sm focus:ring-2 focus:ring-[#2463eb] dark:bg-slate-800"
      >
        <option value="">Tất cả trạng thái</option>
        <option :value="1">Hiển thị</option>
        <option :value="0">Ẩn</option>
      </select>

      <select
        v-model="selectedParent"
        @change="onFilterChange"
        class="rounded-xl border-none bg-slate-50 px-4 py-2.5 text-sm focus:ring-2 focus:ring-[#2463eb] dark:bg-slate-800"
      >
        <option value="">Tất cả cấp cha</option>
        <option value="0">Chỉ ngành gốc</option>
        <option v-for="industry in parentOptions" :key="industry.id" :value="industry.id">
          {{ industry.ten_nganh }}
        </option>
      </select>
    </div>

    <div class="overflow-x-auto">
      <table class="w-full table-fixed text-left">
        <thead>
          <tr class="bg-slate-50 dark:bg-slate-800/50">
            <th class="w-[38%] px-6 py-4 text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300">Ngành nghề</th>
            <th class="w-[20%] px-6 py-4 text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300">Slug</th>
            <th class="w-[20%] px-6 py-4 text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300">Danh mục cha</th>
            <th class="w-[12%] px-6 py-4 text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300 text-center">Hiển thị</th>
            <th class="w-[10%] px-6 py-4 text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300 text-right">Hành động</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
          <tr v-if="loading">
            <td colspan="5" class="px-6 py-10 text-center text-slate-500">
              <span class="material-symbols-outlined animate-spin">hourglass_empty</span>
              <div class="mt-2">Đang tải danh sách ngành nghề...</div>
            </td>
          </tr>

          <tr v-else-if="industries.length === 0">
            <td colspan="5" class="px-6 py-10 text-center text-slate-500">
              <span class="material-symbols-outlined text-3xl">category_search</span>
              <div class="mt-2">Không tìm thấy ngành nghề phù hợp.</div>
            </td>
          </tr>

          <tr v-for="industry in industries" :key="industry.id" class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors">
            <td class="px-6 py-5">
              <div class="flex items-center gap-3">
                <div class="flex size-10 items-center justify-center rounded-lg bg-[#2463eb]/10 text-[#2463eb]">
                  <span class="material-symbols-outlined text-[20px]">{{ industry.icon || 'category' }}</span>
                </div>
                <div>
                  <p class="font-semibold">{{ industry.ten_nganh }}</p>
                  <p class="text-xs text-slate-500 dark:text-slate-400">{{ industry.mo_ta || 'Chưa có mô tả' }}</p>
                </div>
              </div>
            </td>
            <td class="px-6 py-5 text-sm text-slate-600 dark:text-slate-400">
              {{ industry.slug }}
            </td>
            <td class="px-6 py-5 text-sm text-slate-600 dark:text-slate-400">
              {{ industry.danh_muc_cha?.ten_nganh || 'Ngành gốc' }}
            </td>
            <td class="px-6 py-5 text-center">
              <span :class="['inline-flex whitespace-nowrap rounded-full px-2.5 py-1 text-xs font-bold', statusColors[industry.trang_thai]]">
                {{ statusMap[industry.trang_thai] }}
              </span>
            </td>
            <td class="px-6 py-5 text-right">
              <div class="flex justify-end gap-2">
                <button @click="openEditModal(industry)" class="rounded-lg p-2 text-slate-400 transition-colors hover:text-[#2463eb]">
                  <span class="material-symbols-outlined text-[20px]">edit</span>
                </button>
                <button @click="toggleStatus(industry)" class="rounded-lg p-2 text-slate-400 transition-colors hover:text-amber-600">
                  <span class="material-symbols-outlined text-[20px]">{{ industry.trang_thai === 1 ? 'visibility_off' : 'visibility' }}</span>
                </button>
                <button @click="confirmDelete(industry)" class="rounded-lg p-2 text-slate-400 transition-colors hover:text-rose-500">
                  <span class="material-symbols-outlined text-[20px]">delete</span>
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="!loading && industries.length > 0" class="flex items-center justify-between border-t border-slate-100 px-6 py-4 dark:border-slate-800">
      <p class="text-sm text-slate-500 dark:text-slate-400">
        Hiển thị {{ (currentPage - 1) * perPage + 1 }} đến {{ Math.min(currentPage * perPage, totalIndustries) }} trên {{ totalIndustries }} ngành nghề
      </p>
      <div class="flex items-center gap-2">
        <button @click="currentPage > 1 && (currentPage--, loadIndustries())" :disabled="currentPage === 1" class="rounded-lg border border-slate-200 p-2 disabled:opacity-50 dark:border-slate-700">
          <span class="material-symbols-outlined text-[18px]">chevron_left</span>
        </button>
        <button
          v-for="page in totalPages"
          :key="page"
          @click="currentPage = page; loadIndustries()"
          :class="['rounded-lg px-3 py-1 text-sm font-medium', currentPage === page ? 'bg-[#2463eb] text-white' : 'text-slate-600 hover:bg-slate-50 dark:text-slate-400 dark:hover:bg-slate-800']"
        >
          {{ page }}
        </button>
        <button @click="currentPage < totalPages && (currentPage++, loadIndustries())" :disabled="currentPage === totalPages" class="rounded-lg border border-slate-200 p-2 disabled:opacity-50 dark:border-slate-700">
          <span class="material-symbols-outlined text-[18px]">chevron_right</span>
        </button>
      </div>
    </div>
  </div>

  <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4">
    <div class="w-full max-w-2xl rounded-2xl bg-white shadow-xl dark:bg-slate-900">
      <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4 dark:border-slate-800">
        <h3 class="text-lg font-semibold">{{ editingIndustry ? 'Chỉnh sửa ngành nghề' : 'Tạo ngành nghề mới' }}</h3>
        <button @click="showModal = false" class="text-slate-400 hover:text-slate-600">
          <span class="material-symbols-outlined">close</span>
        </button>
      </div>
      <form @submit.prevent="submitForm" class="space-y-4 p-6">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
          <div class="md:col-span-2">
            <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Tên ngành nghề</label>
            <input v-model="formData.ten_nganh" type="text" required class="w-full rounded-lg border border-slate-300 px-3 py-2 outline-none focus:ring-2 focus:ring-[#2463eb] dark:border-slate-700 dark:bg-slate-800" />
          </div>
          <div>
            <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Danh mục cha</label>
            <select v-model="formData.danh_muc_cha_id" class="w-full rounded-lg border border-slate-300 px-3 py-2 outline-none focus:ring-2 focus:ring-[#2463eb] dark:border-slate-700 dark:bg-slate-800">
              <option value="">Ngành gốc</option>
              <option v-for="industry in parentOptions.filter((item) => !editingIndustry || item.id !== editingIndustry.id)" :key="industry.id" :value="industry.id">
                {{ industry.ten_nganh }}
              </option>
            </select>
          </div>
          <div>
            <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Icon</label>
            <input v-model="formData.icon" type="text" placeholder="category" class="w-full rounded-lg border border-slate-300 px-3 py-2 outline-none focus:ring-2 focus:ring-[#2463eb] dark:border-slate-700 dark:bg-slate-800" />
          </div>
          <div class="md:col-span-2">
            <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Mô tả</label>
            <textarea v-model="formData.mo_ta" rows="4" class="w-full rounded-lg border border-slate-300 px-3 py-2 outline-none focus:ring-2 focus:ring-[#2463eb] dark:border-slate-700 dark:bg-slate-800"></textarea>
          </div>
          <div>
            <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Trạng thái</label>
            <select v-model.number="formData.trang_thai" class="w-full rounded-lg border border-slate-300 px-3 py-2 outline-none focus:ring-2 focus:ring-[#2463eb] dark:border-slate-700 dark:bg-slate-800">
              <option :value="1">Hiển thị</option>
              <option :value="0">Ẩn</option>
            </select>
          </div>
        </div>
        <div class="flex justify-end gap-3 pt-2">
          <button type="button" @click="showModal = false" class="rounded-lg border border-slate-300 px-4 py-2 transition-colors hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-800">Hủy</button>
          <button type="submit" class="rounded-lg bg-[#2463eb] px-5 py-2 text-white transition-colors hover:bg-[#2463eb]/90">
            {{ editingIndustry ? 'Lưu thay đổi' : 'Tạo ngành nghề' }}
          </button>
        </div>
      </form>
    </div>
  </div>

  <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4">
    <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl dark:bg-slate-900">
      <div class="mb-3 flex items-center gap-3">
        <span class="material-symbols-outlined text-2xl text-red-500">warning</span>
        <h3 class="text-lg font-semibold">Xóa ngành nghề</h3>
      </div>
      <p class="text-sm leading-6 text-slate-500">
        Bạn có chắc muốn xóa ngành nghề <strong>{{ deletingIndustry?.ten_nganh }}</strong>? Nếu ngành nghề đang có danh mục con, hệ thống sẽ từ chối thao tác này.
      </p>
      <div class="mt-6 flex justify-end gap-3">
        <button @click="showDeleteModal = false" class="rounded-lg border border-slate-300 px-4 py-2 transition-colors hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-800">
          Hủy
        </button>
        <button @click="deleteIndustry" class="rounded-lg bg-red-600 px-4 py-2 text-white transition-colors hover:bg-red-700">
          Xóa ngành
        </button>
      </div>
    </div>
  </div>
</template>
