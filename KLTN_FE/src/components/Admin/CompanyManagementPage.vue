<script setup>
import { ref, reactive, onMounted, computed } from 'vue'
import { companyService } from '@/services/api'

// State
const companies = ref([])
const loading = ref(false)
const error = ref(null)
const currentPage = ref(1)
const totalCompanies = ref(0)
const perPage = ref(5)

// Filters
const searchQuery = ref('')
const selectedStatus = ref('')

// Stats
const stats = reactive({
  total: 0,
  active: 0,
  pending: 0,
  rejected: 0
})

// Modals
const showModal = ref(false)
const showDeleteModal = ref(false)
const editingCompany = ref(null)
const deletingCompany = ref(null)

// Form data
const formData = reactive({
  ten_cong_ty: '',
  dia_chi: '',
  quy_mo: '',
  mo_ta: '',
  website: ''
})

// Quy mô options
const scaleOptions = [
  { value: '1-10', label: '1-10 nhân viên' },
  { value: '11-50', label: '11-50 nhân viên' },
  { value: '51-200', label: '51-200 nhân viên' },
  { value: '201-500', label: '201-500 nhân viên' },
  { value: '501-1000', label: '501-1000 nhân viên' },
  { value: '>1000', label: 'Trên 1000 nhân viên' }
]

const statusMap = {
  pending: 'Đang duyệt',
  approved: 'Đã duyệt',
  rejected: 'Từ chối'
}

const statusColors = {
  pending: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
  approved: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
  rejected: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'
}

// Calculate stats from companies data
const calculateStats = () => {
  stats.total = companies.value.length
  stats.active = companies.value.filter(c => c.trang_thai === 'approved').length
  stats.pending = companies.value.filter(c => c.trang_thai === 'pending').length
  stats.rejected = companies.value.filter(c => c.trang_thai === 'rejected').length
}

// Load companies
const loadCompanies = async () => {
  loading.value = true
  error.value = null
  try {
    const response = await companyService.getCompanies({
      page: currentPage.value,
      per_page: perPage.value,
      search: searchQuery.value || undefined,
      trang_thai: selectedStatus.value || undefined
    })

    // Xử lý response từ API Laravel
    // Format 1: {data: [...], meta: {...}} hoặc {data: [...], total: 100}
    // Format 2: {success: true, data: {data: [...], meta: {...}}}
    let companies_list = []
    let total = 0

    if (response.data) {
      // Kiểm tra nếu response.data là array (format đơn giản)
      if (Array.isArray(response.data)) {
        companies_list = response.data
        total = response.total || response.data.length
      } 
      // Kiểm tra nếu response.data có property 'data' (paginated format)
      else if (response.data.data && Array.isArray(response.data.data)) {
        companies_list = response.data.data
        total = response.data.meta?.total || response.data.total || 0
      }
      // Kiểm tra nếu response có property 'cong_tys' (custom format)
      else if (response.cong_tys) {
        companies_list = response.cong_tys
        total = response.total || response.cong_tys.length
      }
    }

    companies.value = companies_list
    totalCompanies.value = total
    calculateStats()
  } catch (err) {
    error.value = err.message || 'Không thể tải danh sách công ty'
  } finally {
    loading.value = false
  }
}

// Open modal for new company
const openNewCompanyModal = () => {
  editingCompany.value = null
  formData.ten_cong_ty = ''
  formData.dia_chi = ''
  formData.quy_mo = ''
  formData.mo_ta = ''
  formData.website = ''
  showModal.value = true
}

// Open modal for editing company
const openEditCompanyModal = (company) => {
  editingCompany.value = company.id
  formData.ten_cong_ty = company.ten_cong_ty
  formData.dia_chi = company.dia_chi
  formData.quy_mo = company.quy_mo
  formData.mo_ta = company.mo_ta || ''
  formData.website = company.website || ''
  showModal.value = true
}

// Submit form
const submitForm = async () => {
  try {
    if (editingCompany.value) {
      await companyService.updateCompany(editingCompany.value, formData)
    } else {
      await companyService.createCompany(formData)
    }
    showModal.value = false
    await loadCompanies()
  } catch (err) {
    error.value = err.message || 'Lỗi lưu công ty'
  }
}

// Delete confirmation modal
const confirmDelete = (company) => {
  deletingCompany.value = company
  showDeleteModal.value = true
}

// Delete company
const deleteCompany = async () => {
  try {
    await companyService.deleteCompany(deletingCompany.value.id)
    showDeleteModal.value = false
    await loadCompanies()
  } catch (err) {
    error.value = err.message || 'Lỗi xóa công ty'
  }
}

// Toggle status
const toggleStatus = async (companyId) => {
  try {
    await companyService.toggleCompanyStatus(companyId)
    await loadCompanies()
  } catch (err) {
    error.value = err.message || 'Lỗi cập nhật trạng thái'
  }
}

// Computed properties
const totalPages = computed(() => Math.ceil(totalCompanies.value / perPage.value))

// Watchers
const onSearch = () => {
  currentPage.value = 1
  loadCompanies()
}

const onFilterChange = () => {
  currentPage.value = 1
  loadCompanies()
}

// Lifecycle
onMounted(() => {
  loadCompanies()
})
</script>

<template>
  <!-- Error Alert -->
  <div v-if="error" class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-900 rounded-lg flex items-start gap-3">
    <span class="material-symbols-outlined text-red-600 mt-1 flex-shrink-0">error</span>
    <div class="flex-1 text-sm text-red-700 dark:text-red-400 whitespace-pre-wrap break-words">{{ error }}</div>
    <button @click="error = null" class="text-red-600 hover:text-red-700 flex-shrink-0 mt-1">
      <span class="material-symbols-outlined">close</span>
    </button>
  </div>

  <!-- Page Header -->
  <div class="flex flex-wrap items-center justify-between gap-4 mb-8">
    <div class="flex flex-col gap-1">
      <h1 class="text-3xl font-black leading-tight tracking-tight">Quản Lý Công Ty</h1>
      <p class="text-slate-500 dark:text-slate-400 text-base">Quản lý danh sách công ty và tình trạng xác minh.</p>
    </div>
    <button 
      @click="openNewCompanyModal"
      class="flex items-center gap-2 px-5 h-11 bg-[#2463eb] text-white rounded-xl text-sm font-bold hover:bg-[#2463eb]/90 transition-all shadow-md shadow-[#2463eb]/20"
    >
      <span class="material-symbols-outlined text-lg">add</span> Thêm Công Ty
    </button>
  </div>

  <!-- Stats Grid -->
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="bg-white dark:bg-slate-900 p-6 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
      <div class="flex items-center justify-between mb-2">
        <span class="text-slate-500 text-sm font-medium">Tổng công ty</span>
        <span class="material-symbols-outlined text-[#2463eb]/40">business</span>
      </div>
      <div class="text-2xl font-bold">{{ stats.total }}</div>
      <div class="mt-2 text-xs text-emerald-600 font-medium flex items-center">
        <span class="material-symbols-outlined text-xs mr-1">check_circle</span> Tất cả chủ động quản lý
      </div>
    </div>
    <div class="bg-white dark:bg-slate-900 p-6 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
      <div class="flex items-center justify-between mb-2">
        <span class="text-slate-500 text-sm font-medium">Đã duyệt</span>
        <span class="material-symbols-outlined text-[#2463eb]/40">verified</span>
      </div>
      <div class="text-2xl font-bold">{{ stats.active }}</div>
      <div class="mt-2 text-xs text-emerald-600 font-medium flex items-center">
        <span class="material-symbols-outlined text-xs mr-1">trending_up</span> Hoạt động
      </div>
    </div>
    <div class="bg-white dark:bg-slate-900 p-6 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
      <div class="flex items-center justify-between mb-2">
        <span class="text-slate-500 text-sm font-medium">Đang duyệt</span>
        <span class="material-symbols-outlined text-amber-500/40">pending_actions</span>
      </div>
      <div class="text-2xl font-bold text-amber-500">{{ stats.pending }}</div>
      <div class="mt-2 text-xs text-amber-600 font-medium flex items-center">
        <span class="material-symbols-outlined text-xs mr-1">schedule</span> Cần xử lý
      </div>
    </div>
    <div class="bg-white dark:bg-slate-900 p-6 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
      <div class="flex items-center justify-between mb-2">
        <span class="text-slate-500 text-sm font-medium">Từ chối</span>
        <span class="material-symbols-outlined text-red-500/40">cancel</span>
      </div>
      <div class="text-2xl font-bold text-red-500">{{ stats.rejected }}</div>
      <div class="mt-2 text-xs text-red-600 font-medium flex items-center">
        <span class="material-symbols-outlined text-xs mr-1">close</span> Từ chối
      </div>
    </div>
  </div>

  <!-- Filters -->
  <div class="bg-white dark:bg-slate-900 p-4 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm mb-6 flex flex-wrap items-center gap-4">
    <div class="flex-1 min-w-[300px] relative">
      <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">search</span>
      <input 
        v-model="searchQuery"
        @input="onSearch"
        class="w-full pl-10 pr-4 py-2 bg-slate-50 dark:bg-slate-800 border-none rounded-lg focus:ring-2 focus:ring-[#2463eb] text-sm" 
        placeholder="Tìm theo tên công ty hoặc địa chỉ..." 
        type="text" 
      />
    </div>
    <select 
      v-model="selectedStatus"
      @change="onFilterChange"
      class="bg-slate-50 dark:bg-slate-800 border-none rounded-lg text-sm px-4 py-2 focus:ring-2 focus:ring-[#2463eb]"
    >
      <option value="">Tất cả trạng thái</option>
      <option value="approved">Đã duyệt</option>
      <option value="pending">Đang duyệt</option>
      <option value="rejected">Từ chối</option>
    </select>
  </div>

  <!-- Company Table -->
  <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-left border-collapse">
        <thead>
          <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/50">
            <th class="px-6 py-4 font-semibold text-xs uppercase tracking-wider text-slate-500">Tên công ty</th>
            <th class="px-6 py-4 font-semibold text-xs uppercase tracking-wider text-slate-500">Quy mô</th>
            <th class="px-6 py-4 font-semibold text-xs uppercase tracking-wider text-slate-500">Địa chỉ</th>
            <th class="px-6 py-4 font-semibold text-xs uppercase tracking-wider text-slate-500">Trạng thái</th>
            <th class="px-6 py-4 font-semibold text-xs uppercase tracking-wider text-slate-500 text-right">Hành động</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
          <template v-if="loading">
            <tr>
              <td colspan="5" class="px-6 py-8 text-center text-slate-500">
                <span class="material-symbols-outlined animate-spin">hourglass_empty</span>
                <div>Đang tải...</div>
              </td>
            </tr>
          </template>
          <template v-else-if="companies.length === 0">
            <tr>
              <td colspan="5" class="px-6 py-8 text-center text-slate-500">
                <span class="material-symbols-outlined text-3xl mb-2">inbox</span>
                <div>Không tìm thấy công ty</div>
              </td>
            </tr>
          </template>
          <template v-else>
            <tr v-for="company in companies" :key="company.id" class="hover:bg-slate-50/50 dark:hover:bg-slate-800/50 transition-colors">
              <td class="px-6 py-4">
                <div class="flex items-center gap-3">
                  <div class="w-10 h-10 rounded-full bg-[#2463eb]/10 flex items-center justify-center text-[#2463eb] font-bold">
                    {{ company.ten_cong_ty?.charAt(0).toUpperCase() || 'C' }}
                  </div>
                  <div>
                    <div class="font-semibold text-sm">{{ company.ten_cong_ty }}</div>
                    <div class="text-xs text-slate-500">{{ company.website || 'N/A' }}</div>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4 text-sm">
                {{ company.quy_mo }}
              </td>
              <td class="px-6 py-4 text-sm text-slate-600 dark:text-slate-400">
                {{ company.dia_chi }}
              </td>
              <td class="px-6 py-4">
                <span :class="['px-2 py-1 rounded text-xs font-medium', statusColors[company.trang_thai]]">
                  {{ statusMap[company.trang_thai] || 'N/A' }}
                </span>
              </td>
              <td class="px-6 py-4 text-right">
                <div class="flex items-center justify-end gap-2">
                  <button 
                    @click="openEditCompanyModal(company)"
                    class="p-2 text-slate-400 hover:text-[#2463eb] transition-colors rounded-lg"
                    title="Chỉnh sửa"
                  >
                    <span class="material-symbols-outlined text-xl">edit</span>
                  </button>
                  <button 
                    @click="toggleStatus(company.id)"
                    class="p-2 text-slate-400 hover:text-amber-600 transition-colors rounded-lg"
                    title="Đổi trạng thái"
                  >
                    <span class="material-symbols-outlined text-xl">toggle_on</span>
                  </button>
                  <button 
                    @click="confirmDelete(company)"
                    class="p-2 text-slate-400 hover:text-red-600 transition-colors rounded-lg"
                    title="Xóa"
                  >
                    <span class="material-symbols-outlined text-xl">delete</span>
                  </button>
                </div>
              </td>
            </tr>
          </template>
        </tbody>
      </table>
    </div>
    <!-- Pagination -->
    <div v-if="!loading && companies.length > 0" class="bg-slate-50/50 dark:bg-slate-800/50 border-t border-slate-200 dark:border-slate-800 px-6 py-4 flex items-center justify-between">
      <div class="text-sm text-slate-500">
        Hiển thị <span class="font-medium text-slate-900 dark:text-slate-100">{{ (currentPage - 1) * perPage + 1 }}</span> 
        đến <span class="font-medium text-slate-900 dark:text-slate-100">{{ Math.min(currentPage * perPage, totalCompanies) }}</span> 
        trên <span class="font-medium text-slate-900 dark:text-slate-100">{{ totalCompanies }}</span> công ty
      </div>
      <div class="flex items-center gap-2">
        <button 
          @click="currentPage > 1 && (currentPage--, loadCompanies())"
          :disabled="currentPage === 1"
          class="p-2 rounded-lg border border-slate-200 dark:border-slate-700 disabled:opacity-50 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors"
        >
          <span class="material-symbols-outlined">chevron_left</span>
        </button>
        <button 
          v-for="page in totalPages"
          :key="page"
          @click="currentPage = page, loadCompanies()"
          :class="['w-8 h-8 rounded-lg text-sm font-medium transition-colors', currentPage === page ? 'bg-[#2463eb] text-white' : 'hover:bg-slate-200 dark:hover:bg-slate-700']"
        >
          {{ page }}
        </button>
        <button 
          @click="currentPage < totalPages && (currentPage++, loadCompanies())"
          :disabled="currentPage === totalPages"
          class="p-2 rounded-lg border border-slate-200 dark:border-slate-700 disabled:opacity-50 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors"
        >
          <span class="material-symbols-outlined">chevron_right</span>
        </button>
      </div>
    </div>
  </div>

  <!-- Modal: Tạo/Sửa công ty -->
  <div v-if="showModal" class="fixed inset-0 bg-black/50 dark:bg-black/70 flex items-center justify-center z-50">
    <div class="bg-white dark:bg-slate-900 rounded-xl shadow-xl max-w-md w-full mx-4">
      <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 dark:border-slate-800">
        <h3 class="text-lg font-semibold">{{ editingCompany ? 'Chỉnh sửa công ty' : 'Tạo công ty mới' }}</h3>
        <button @click="showModal = false" class="text-slate-400 hover:text-slate-600">
          <span class="material-symbols-outlined">close</span>
        </button>
      </div>
      <form @submit.prevent="submitForm" class="p-6 space-y-4">
        <div>
          <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Tên công ty</label>
          <input v-model="formData.ten_cong_ty" type="text" required class="w-full px-3 py-2 border border-slate-300 dark:border-slate-700 rounded-lg dark:bg-slate-800 focus:ring-2 focus:ring-[#2463eb] outline-none" />
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Địa chỉ</label>
          <input v-model="formData.dia_chi" type="text" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-700 rounded-lg dark:bg-slate-800 focus:ring-2 focus:ring-[#2463eb] outline-none" />
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Quy mô</label>
          <select v-model="formData.quy_mo" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-700 rounded-lg dark:bg-slate-800 focus:ring-2 focus:ring-[#2463eb] outline-none">
            <option value="">-- Chọn quy mô --</option>
            <option v-for="option in scaleOptions" :key="option.value" :value="option.value">
              {{ option.label }}
            </option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Website</label>
          <input v-model="formData.website" type="text" placeholder="https://..." class="w-full px-3 py-2 border border-slate-300 dark:border-slate-700 rounded-lg dark:bg-slate-800 focus:ring-2 focus:ring-[#2463eb] outline-none" />
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Mô tả</label>
          <textarea v-model="formData.mo_ta" rows="3" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-700 rounded-lg dark:bg-slate-800 focus:ring-2 focus:ring-[#2463eb] outline-none"></textarea>
        </div>
        <div class="flex gap-3 pt-4">
          <button type="button" @click="showModal = false" class="flex-1 px-4 py-2 border border-slate-300 dark:border-slate-700 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
            Hủy
          </button>
          <button type="submit" class="flex-1 px-4 py-2 bg-[#2463eb] text-white rounded-lg hover:bg-[#2463eb]/90 transition-colors font-medium">
            {{ editingCompany ? 'Cập nhật' : 'Tạo' }}
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- Modal: Xác nhận xóa -->
  <div v-if="showDeleteModal" class="fixed inset-0 bg-black/50 dark:bg-black/70 flex items-center justify-center z-50">
    <div class="bg-white dark:bg-slate-900 rounded-xl shadow-xl max-w-sm w-full mx-4">
      <div class="p-6">
        <div class="flex items-center gap-3 mb-4">
          <span class="material-symbols-outlined text-2xl text-red-600">warning</span>
          <h3 class="text-lg font-semibold">Xóa công ty</h3>
        </div>
        <p class="text-slate-600 dark:text-slate-400 mb-6">
          Bạn có chắc muốn xóa <strong>{{ deletingCompany?.ten_cong_ty }}</strong>? Hành động này không thể hoàn tác.
        </p>
        <div class="flex gap-3">
          <button @click="showDeleteModal = false" class="flex-1 px-4 py-2 border border-slate-300 dark:border-slate-700 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
            Hủy
          </button>
          <button @click="deleteCompany" class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors font-medium">
            Xóa
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
