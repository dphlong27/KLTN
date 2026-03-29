<script setup>
import { ref, reactive, onMounted, computed } from 'vue'
import { userService } from '@/services/api'
import { useNotify } from '@/composables/useNotify'

const notify = useNotify()

// State
const users = ref([])
const loading = ref(false)
const error = ref(null)
const currentPage = ref(1)
const totalUsers = ref(0)
const perPage = ref(5)

// Filters
const searchQuery = ref('')
const selectedRole = ref('')
const selectedStatus = ref('')

// Stats
const stats = reactive({
  total: 0,
  jobSeekers: 0,
  employers: 0,
  pendingApprovals: 0
})

// Modals
const showModal = ref(false)
const showDeleteModal = ref(false)
const editingUser = ref(null)
const deletingUser = ref(null)

// Form data
const formData = reactive({
  ho_ten: '',
  email: '',
  mat_khau: '',
  so_dien_thoai: '',
  ngay_sinh: '',
  gioi_tinh: '',
  dia_chi: '',
  vai_tro: 0,
  trang_thai: 1,
})

// Map vai_tro to display text
const roleMap = {
  0: 'Ứng viên',
  1: 'Nhà tuyển dụng',
  2: 'Admin'
}

const roleColors = {
  0: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
  1: 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
  2: 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300'
}

const loadStats = async () => {
  try {
    const response = await userService.getUserStats()
    const payload = response?.data || {}

    stats.total = payload.tong || 0
    stats.jobSeekers = payload.ung_vien || 0
    stats.employers = payload.nha_tuyen_dung || 0
    stats.pendingApprovals = payload.bi_khoa || 0
  } catch (err) {
    console.error('Không thể tải thống kê người dùng', err)
  }
}

// Load users
const loadUsers = async () => {
  loading.value = true
  error.value = null
  try {
    const response = await userService.getUsers({
      page: currentPage.value,
      per_page: perPage.value,
      vai_tro: selectedRole.value ? parseInt(selectedRole.value) : undefined,
      trang_thai: selectedStatus.value ? (selectedStatus.value === 'active' ? 1 : 0) : undefined,
      search: searchQuery.value || undefined
    })

    // Xử lý response từ API Laravel
    let users_list = []
    let total = 0

    if (response.data) {
      // Kiểm tra nếu response.data là array (format đơn giản)
      if (Array.isArray(response.data)) {
        users_list = response.data
        total = response.total || response.data.length
      } 
      // Kiểm tra nếu response.data có property 'data' (paginated format)
      else if (response.data.data && Array.isArray(response.data.data)) {
        users_list = response.data.data
        total = response.data.meta?.total || response.data.total || 0
      }
      // Kiểm tra nếu response có property 'nguoi_dungs' (custom format)
      else if (response.nguoi_dungs) {
        users_list = response.nguoi_dungs
        total = response.total || response.nguoi_dungs.length
      }
    }

    users.value = users_list
    totalUsers.value = total
  } catch (err) {
    error.value = err.message || 'Không thể tải danh sách người dùng'
  } finally {
    loading.value = false
  }
}

// Open modal for editing user
const openEditUserModal = (user) => {
  editingUser.value = user.id
  formData.ho_ten = user.ho_ten
  formData.email = user.email
  formData.mat_khau = ''
  formData.so_dien_thoai = user.so_dien_thoai || ''
  formData.ngay_sinh = user.ngay_sinh || ''
  formData.gioi_tinh = user.gioi_tinh || ''
  formData.dia_chi = user.dia_chi || ''
  formData.vai_tro = user.vai_tro
  formData.trang_thai = user.trang_thai ?? 1
  showModal.value = true
}

// Submit form
const submitForm = async () => {
  try {
    const payload = {
      ho_ten: formData.ho_ten,
      email: formData.email,
      so_dien_thoai: formData.so_dien_thoai || null,
      ngay_sinh: formData.ngay_sinh || null,
      gioi_tinh: formData.gioi_tinh || null,
      dia_chi: formData.dia_chi || null,
      vai_tro: formData.vai_tro,
      trang_thai: formData.trang_thai,
    }

    if (editingUser.value) {
      if (formData.mat_khau) {
        payload.mat_khau = formData.mat_khau
      }
      await userService.updateUser(editingUser.value, payload)
      notify.success('Đã cập nhật người dùng')
    }
    showModal.value = false
    await loadUsers()
    await loadStats()
  } catch (err) {
    error.value = err.message || 'Lỗi lưu người dùng'
  }
}

// Delete confirmation modal
const confirmDelete = (user) => {
  deletingUser.value = user
  showDeleteModal.value = true
}

// Delete user
const deleteUser = async () => {
  try {
    await userService.deleteUser(deletingUser.value.id)
    showDeleteModal.value = false
    await loadUsers()
    await loadStats()
    notify.success('Đã xóa người dùng')
  } catch (err) {
    error.value = err.message || 'Lỗi xóa người dùng'
  }
}

// Toggle lock
const toggleLock = async (userId) => {
  try {
    await userService.toggleLock(userId)
    await loadUsers()
    await loadStats()
    notify.success('Đã cập nhật trạng thái tài khoản')
  } catch (err) {
    error.value = err.message || 'Lỗi khoá/mở khoá tài khoản'
  }
}

// Computed properties
const totalPages = computed(() => Math.ceil(totalUsers.value / perPage.value))

// Watchers
const onSearch = () => {
  currentPage.value = 1
  loadUsers()
}

const onFilterChange = () => {
  currentPage.value = 1
  loadUsers()
}

// Lifecycle
onMounted(() => {
  loadUsers()
  loadStats()
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

  <!-- Stats Grid -->
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="bg-white dark:bg-slate-900 p-6 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
      <div class="flex items-center justify-between mb-2">
        <span class="text-slate-500 text-sm font-medium">Tổng người dùng</span>
        <span class="material-symbols-outlined text-[#2463eb]/40">groups</span>
      </div>
      <div class="text-2xl font-bold">{{ stats.total }}</div>
      <div class="mt-2 text-xs text-emerald-600 font-medium flex items-center">
        <span class="material-symbols-outlined text-xs mr-1">check_circle</span> Tất cả chủ động quản lý
      </div>
    </div>
    <div class="bg-white dark:bg-slate-900 p-6 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
      <div class="flex items-center justify-between mb-2">
        <span class="text-slate-500 text-sm font-medium">Ứng viên</span>
        <span class="material-symbols-outlined text-[#2463eb]/40">person_search</span>
      </div>
      <div class="text-2xl font-bold">{{ stats.jobSeekers }}</div>
      <div class="mt-2 text-xs text-emerald-600 font-medium flex items-center">
        <span class="material-symbols-outlined text-xs mr-1">trending_up</span> Hoạt động
      </div>
    </div>
    <div class="bg-white dark:bg-slate-900 p-6 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
      <div class="flex items-center justify-between mb-2">
        <span class="text-slate-500 text-sm font-medium">Nhà tuyển dụng</span>
        <span class="material-symbols-outlined text-[#2463eb]/40">business</span>
      </div>
      <div class="text-2xl font-bold">{{ stats.employers }}</div>
      <div class="mt-2 text-xs text-emerald-600 font-medium flex items-center">
        <span class="material-symbols-outlined text-xs mr-1">trending_up</span> Hoạt động
      </div>
    </div>
    <div class="bg-white dark:bg-slate-900 p-6 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
      <div class="flex items-center justify-between mb-2">
        <span class="text-slate-500 text-sm font-medium">Đợi phê duyệt</span>
        <span class="material-symbols-outlined text-amber-500/40">pending_actions</span>
      </div>
      <div class="text-2xl font-bold text-amber-500">{{ stats.pendingApprovals }}</div>
      <div class="mt-2 text-xs text-slate-400 font-medium">Cần xử lý</div>
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
        placeholder="Tìm theo tên, email hoặc công ty..." 
        type="text" 
      />
    </div>
    <div class="flex items-center gap-3">
      <select 
        v-model="selectedRole"
        @change="onFilterChange"
        class="bg-slate-50 dark:bg-slate-800 border-none rounded-lg text-sm px-4 py-2 focus:ring-2 focus:ring-[#2463eb]"
      >
        <option value="">Tất cả vai trò</option>
        <option value="0">Ứng viên</option>
        <option value="1">Nhà tuyển dụng</option>
        <option value="2">Admin</option>
      </select>
      <select 
        v-model="selectedStatus"
        @change="onFilterChange"
        class="bg-slate-50 dark:bg-slate-800 border-none rounded-lg text-sm px-4 py-2 focus:ring-2 focus:ring-[#2463eb]"
      >
        <option value="">Tất cả trạng thái</option>
        <option value="active">Hoạt động</option>
        <option value="inactive">Khóa</option>
      </select>
    </div>
  </div>

  <!-- User Table -->
  <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-left border-collapse">
        <thead>
          <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/50">
            <th class="px-6 py-4 font-semibold text-xs uppercase tracking-wider text-slate-500">Thông tin người dùng</th>
            <th class="px-6 py-4 font-semibold text-xs uppercase tracking-wider text-slate-500">Vai trò</th>
            <th class="px-6 py-4 font-semibold text-xs uppercase tracking-wider text-slate-500">Ngày tham gia</th>
            <th class="px-6 py-4 font-semibold text-xs uppercase tracking-wider text-slate-500">Trạng thái</th>
            <th class="px-6 py-4 font-semibold text-xs uppercase tracking-wider text-slate-500 text-center">Hành động</th>
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
          <template v-else-if="users.length === 0">
            <tr>
              <td colspan="5" class="px-6 py-8 text-center text-slate-500">
                <span class="material-symbols-outlined text-3xl mb-2">inbox</span>
                <div>Không tìm thấy người dùng</div>
              </td>
            </tr>
          </template>
          <template v-else>
            <tr v-for="user in users" :key="user.id" class="hover:bg-slate-50/50 dark:hover:bg-slate-800/50 transition-colors">
              <td class="px-6 py-4">
                <div class="flex items-center gap-3">
                  <div class="w-10 h-10 rounded-full bg-[#2463eb]/10 flex items-center justify-center text-[#2463eb] font-bold">
                    {{ user.ho_ten?.charAt(0).toUpperCase() || 'U' }}
                  </div>
                  <div>
                    <div class="font-semibold text-sm">{{ user.ho_ten }}</div>
                    <div class="text-xs text-slate-500">{{ user.email }}</div>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4">
                <span :class="['px-2 py-1 rounded text-xs font-medium', roleColors[user.vai_tro]]">
                  {{ roleMap[user.vai_tro] }}
                </span>
              </td>
              <td class="px-6 py-4 text-sm text-slate-600 dark:text-slate-400">
                {{ user.created_at ? new Date(user.created_at).toLocaleDateString('vi-VN') : 'N/A' }}
              </td>
              <td class="px-6 py-4">
                <div class="flex items-center gap-2">
                  <div :class="['w-2 h-2 rounded-full', user.trang_thai === 1 ? 'bg-emerald-500' : 'bg-red-500']"></div>
                  <span :class="['text-sm font-medium', user.trang_thai === 1 ? 'text-emerald-600' : 'text-red-600']">
                    {{ user.trang_thai === 1 ? 'Hoạt động' : 'Đã khóa' }}
                  </span>
                </div>
              </td>
              <td class="px-6 py-4 text-right">
                <div class="flex items-center justify-center gap-2">
                  <button 
                    @click="openEditUserModal(user)"
                    class="p-2 text-slate-400 hover:text-[#2463eb] transition-colors rounded-lg"
                    title="Chỉnh sửa"
                  >
                    <span class="material-symbols-outlined text-xl">edit</span>
                  </button>
                  <button 
                    @click="toggleLock(user.id)"
                    :class="['p-2 transition-colors rounded-lg', user.trang_thai === 1 ? 'text-slate-400 hover:text-amber-600' : 'text-slate-400 hover:text-green-600']"
                    :title="user.trang_thai === 1 ? 'Khóa' : 'Mở khóa'"
                  >
                    <span class="material-symbols-outlined text-xl">{{ user.trang_thai === 1 ? 'block' : 'lock_open' }}</span>
                  </button>
                  <button 
                    @click="confirmDelete(user)"
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
    <div v-if="!loading && users.length > 0" class="bg-slate-50/50 dark:bg-slate-800/50 border-t border-slate-200 dark:border-slate-800 px-6 py-4 flex items-center justify-between">
      <div class="text-sm text-slate-500">
        Hiển thị <span class="font-medium text-slate-900 dark:text-slate-100">{{ (currentPage - 1) * perPage + 1 }}</span> 
        đến <span class="font-medium text-slate-900 dark:text-slate-100">{{ Math.min(currentPage * perPage, totalUsers) }}</span> 
        trên <span class="font-medium text-slate-900 dark:text-slate-100">{{ totalUsers }}</span> người dùng
      </div>
      <div class="flex items-center gap-2">
        <button 
          @click="currentPage > 1 && (currentPage--, loadUsers())"
          :disabled="currentPage === 1"
          class="p-2 rounded-lg border border-slate-200 dark:border-slate-700 disabled:opacity-50 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors"
        >
          <span class="material-symbols-outlined">chevron_left</span>
        </button>
        <button 
          v-for="page in totalPages"
          :key="page"
          @click="currentPage = page, loadUsers()"
          :class="['w-8 h-8 rounded-lg text-sm font-medium transition-colors', currentPage === page ? 'bg-[#2463eb] text-white' : 'hover:bg-slate-200 dark:hover:bg-slate-700']"
        >
          {{ page }}
        </button>
        <button 
          @click="currentPage < totalPages && (currentPage++, loadUsers())"
          :disabled="currentPage === totalPages"
          class="p-2 rounded-lg border border-slate-200 dark:border-slate-700 disabled:opacity-50 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors"
        >
          <span class="material-symbols-outlined">chevron_right</span>
        </button>
      </div>
    </div>
  </div>

  <!-- Modal: Tạo/Sửa người dùng -->
  <div v-if="showModal" class="fixed inset-0 bg-black/50 dark:bg-black/70 flex items-center justify-center z-50">
    <div class="bg-white dark:bg-slate-900 rounded-xl shadow-xl max-w-md w-full mx-4">
      <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 dark:border-slate-800">
        <h3 class="text-lg font-semibold">Chỉnh sửa người dùng</h3>
        <button @click="showModal = false" class="text-slate-400 hover:text-slate-600">
          <span class="material-symbols-outlined">close</span>
        </button>
      </div>
      <form @submit.prevent="submitForm" class="p-6 space-y-4">
        <div>
          <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Họ tên</label>
          <input v-model="formData.ho_ten" type="text" required class="w-full px-3 py-2 border border-slate-300 dark:border-slate-700 rounded-lg dark:bg-slate-800 focus:ring-2 focus:ring-[#2463eb] outline-none" />
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Email</label>
          <input v-model="formData.email" type="email" required class="w-full px-3 py-2 border border-slate-300 dark:border-slate-700 rounded-lg dark:bg-slate-800 focus:ring-2 focus:ring-[#2463eb] outline-none" />
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
            Mật khẩu mới (không bắt buộc)
          </label>
          <input
            v-model="formData.mat_khau"
            type="password"
            minlength="6"
            class="w-full px-3 py-2 border border-slate-300 dark:border-slate-700 rounded-lg dark:bg-slate-800 focus:ring-2 focus:ring-[#2463eb] outline-none"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Điện thoại</label>
          <input v-model="formData.so_dien_thoai" type="tel" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-700 rounded-lg dark:bg-slate-800 focus:ring-2 focus:ring-[#2463eb] outline-none" />
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Ngày sinh</label>
          <input v-model="formData.ngay_sinh" type="date" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-700 rounded-lg dark:bg-slate-800 focus:ring-2 focus:ring-[#2463eb] outline-none" />
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Giới tính</label>
          <select v-model="formData.gioi_tinh" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-700 rounded-lg dark:bg-slate-800 focus:ring-2 focus:ring-[#2463eb] outline-none">
            <option value="">-- Chọn --</option>
            <option value="nam">Nam</option>
            <option value="nu">Nữ</option>
            <option value="khac">Khác</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Địa chỉ</label>
          <input v-model="formData.dia_chi" type="text" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-700 rounded-lg dark:bg-slate-800 focus:ring-2 focus:ring-[#2463eb] outline-none" />
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Vai trò</label>
          <select v-model.number="formData.vai_tro" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-700 rounded-lg dark:bg-slate-800 focus:ring-2 focus:ring-[#2463eb] outline-none">
            <option value="0">Ứng viên</option>
            <option value="1">Nhà tuyển dụng</option>
            <option value="2">Admin</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Trạng thái</label>
          <select v-model.number="formData.trang_thai" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-700 rounded-lg dark:bg-slate-800 focus:ring-2 focus:ring-[#2463eb] outline-none">
            <option :value="1">Hoạt động</option>
            <option :value="0">Khóa</option>
          </select>
        </div>
        <div class="flex gap-3 pt-4">
          <button type="button" @click="showModal = false" class="flex-1 px-4 py-2 border border-slate-300 dark:border-slate-700 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
            Hủy
          </button>
          <button type="submit" class="flex-1 px-4 py-2 bg-[#2463eb] text-white rounded-lg hover:bg-[#2463eb]/90 transition-colors font-medium">
            Cập nhật
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
          <h3 class="text-lg font-semibold">Xóa người dùng</h3>
        </div>
        <p class="text-slate-600 dark:text-slate-400 mb-6">
          Bạn có chắc muốn xóa <strong>{{ deletingUser?.ho_ten }}</strong>? Hành động này không thể hoàn tác.
        </p>
        <div class="flex gap-3">
          <button @click="showDeleteModal = false" class="flex-1 px-4 py-2 border border-slate-300 dark:border-slate-700 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
            Hủy
          </button>
          <button @click="deleteUser" class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors font-medium">
            Xóa
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
