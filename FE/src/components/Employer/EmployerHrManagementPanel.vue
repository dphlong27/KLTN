<script setup>
import { computed, onMounted, ref } from 'vue'
import { employerCompanyService } from '@/services/api'
import { useNotify } from '@/composables/useNotify'

const notify = useNotify()

const loading = ref(false)
const memberSubmitting = ref(false)
const invitationSubmitting = ref(false)
const roleUpdatingIds = ref([])
const removingMemberIds = ref([])
const cancelingInvitationIds = ref([])
const respondingInvitationIds = ref([])
const company = ref(null)
const memberEmail = ref('')
const memberRole = ref('recruiter')
const inviteEmail = ref('')
const inviteRole = ref('recruiter')
const receivedInvitations = ref([])
const hrAuditLogs = ref([])
const hrAuditPagination = ref(null)

const hasCompany = computed(() => Boolean(company.value?.id))
const companyMembers = computed(() => Array.isArray(company.value?.thanh_viens) ? company.value.thanh_viens : [])
const pendingInvitations = computed(() => Array.isArray(company.value?.loi_moi_dang_cho) ? company.value.loi_moi_dang_cho : [])
const canManageMembers = computed(() => Boolean(company.value?.quyen_noi_bo?.co_the_quan_ly_thanh_vien))
const currentInternalRoleLabel = computed(() => company.value?.ten_vai_tro_noi_bo_hien_tai || 'HR Member')
const internalRoleOptions = computed(() => {
  const defaultOptions = {
    recruiter: 'Recruiter',
    interviewer: 'Interviewer',
    viewer: 'Viewer',
    admin_hr: 'Admin HR',
  }

  return Object.entries(company.value?.vai_tro_noi_bo_options || defaultOptions)
    .filter(([role]) => role !== 'owner')
})
const ownerSummary = computed(() => {
  if (!hasCompany.value) return 'Bạn cần tạo công ty trước khi có thể quản lý nhân sự HR nội bộ.'
  if (canManageMembers.value) return 'Bạn đang là owner. Tại đây có thể thêm, gỡ và đổi vai trò nội bộ cho HR.'
  return `Bạn đang đăng nhập với vai trò ${currentInternalRoleLabel.value}. Chỉ owner mới có thể thay đổi thành viên nội bộ.`
})

const invitationGuidance = 'Bạn có thể mời cả email chưa đăng ký. Khi người đó tạo tài khoản nhà tuyển dụng bằng đúng email này, lời mời sẽ tự hiện để họ chấp nhận.'

const fetchCompany = async () => {
  loading.value = true
  try {
    const response = await employerCompanyService.getCompany()
    company.value = response?.data || null
  } catch (error) {
    if (error?.status === 404) {
      company.value = null
    } else {
      notify.apiError(error, 'Không tải được dữ liệu quản lý HR.')
    }
  } finally {
    loading.value = false
  }
}

const fetchReceivedInvitations = async () => {
  try {
    const response = await employerCompanyService.getReceivedInvitations()
    receivedInvitations.value = Array.isArray(response?.data) ? response.data : []
  } catch (error) {
    receivedInvitations.value = []
    notify.apiError(error, 'Không tải được lời mời tham gia công ty.')
  }
}

const fetchHrAuditLogs = async (page = 1) => {
  try {
    const response = await employerCompanyService.getHrAuditLogs({ page, per_page: 8 })
    const payload = response?.data || {}
    hrAuditLogs.value = Array.isArray(payload.data) ? payload.data : []
    hrAuditPagination.value = payload
  } catch (error) {
    hrAuditLogs.value = []
    hrAuditPagination.value = null
    notify.apiError(error, 'Không tải được lịch sử thao tác HR.')
  }
}

const addHrMember = async () => {
  if (!hasCompany.value) {
    notify.warning('Hãy tạo công ty trước khi thêm HR.')
    return
  }

  if (!canManageMembers.value) {
    notify.warning('Chỉ owner mới có thể thêm HR vào công ty.')
    return
  }

  const email = String(memberEmail.value || '').trim()
  if (!email) {
    notify.warning('Vui lòng nhập email tài khoản nhà tuyển dụng.')
    return
  }

  memberSubmitting.value = true
  try {
    const response = await employerCompanyService.addMember(email, memberRole.value)
    company.value = response?.data?.cong_ty || response?.data?.data?.cong_ty || company.value
    if (!company.value) {
      await fetchCompany()
    }
    await fetchHrAuditLogs()
    memberEmail.value = ''
    memberRole.value = 'recruiter'
    notify.success('Đã thêm HR vào công ty.')
  } catch (error) {
    notify.apiError(error, 'Không thể thêm HR vào công ty.')
  } finally {
    memberSubmitting.value = false
  }
}

const sendInvitation = async () => {
  if (!hasCompany.value) {
    notify.warning('Hãy tạo công ty trước khi gửi lời mời HR.')
    return
  }

  if (!canManageMembers.value) {
    notify.warning('Chỉ owner mới có thể gửi lời mời HR.')
    return
  }

  const email = String(inviteEmail.value || '').trim()
  if (!email) {
    notify.warning('Vui lòng nhập email muốn mời.')
    return
  }

  invitationSubmitting.value = true
  try {
    const response = await employerCompanyService.sendInvitation(email, inviteRole.value)
    company.value = response?.data?.cong_ty || response?.data?.data?.cong_ty || company.value
    if (!company.value) {
      await fetchCompany()
    }
    await fetchHrAuditLogs()
    inviteEmail.value = ''
    inviteRole.value = 'recruiter'
    notify.success('Đã gửi lời mời tham gia công ty.')
  } catch (error) {
    notify.apiError(error, 'Không thể gửi lời mời HR.')
  } finally {
    invitationSubmitting.value = false
  }
}

const cancelInvitation = async (inviteId) => {
  const normalizedId = Number(inviteId || 0)
  if (!normalizedId) return

  cancelingInvitationIds.value = [...cancelingInvitationIds.value, normalizedId]
  try {
    const response = await employerCompanyService.cancelInvitation(normalizedId)
    company.value = response?.data?.cong_ty || response?.data?.data?.cong_ty || company.value
    if (!company.value) {
      await fetchCompany()
    }
    await fetchHrAuditLogs(hrAuditPagination.value?.current_page || 1)
    notify.success('Đã hủy lời mời.')
  } catch (error) {
    notify.apiError(error, 'Không thể hủy lời mời.')
  } finally {
    cancelingInvitationIds.value = cancelingInvitationIds.value.filter((id) => id !== normalizedId)
  }
}

const respondToInvitation = async (inviteId, action) => {
  const normalizedId = Number(inviteId || 0)
  if (!normalizedId) return

  respondingInvitationIds.value = [...respondingInvitationIds.value, normalizedId]
  try {
    await employerCompanyService.respondToInvitation(normalizedId, action)
    await Promise.all([fetchCompany(), fetchReceivedInvitations(), fetchHrAuditLogs(hrAuditPagination.value?.current_page || 1)])
    notify.success(action === 'accept' ? 'Đã chấp nhận lời mời tham gia công ty.' : 'Đã từ chối lời mời.')
  } catch (error) {
    notify.apiError(error, 'Không thể phản hồi lời mời.')
  } finally {
    respondingInvitationIds.value = respondingInvitationIds.value.filter((id) => id !== normalizedId)
  }
}

const updateHrMemberRole = async (member, nextRole) => {
  const memberId = Number(member?.id || 0)
  const normalizedRole = String(nextRole || '').trim()

  if (!memberId || !normalizedRole || member?.la_chu_so_huu) return
  if (normalizedRole === member?.vai_tro_noi_bo) return

  if (!canManageMembers.value) {
    notify.warning('Chỉ owner mới có thể cập nhật vai trò HR.')
    return
  }

  roleUpdatingIds.value = [...roleUpdatingIds.value, memberId]
  try {
    const response = await employerCompanyService.updateMemberRole(memberId, normalizedRole)
    company.value = response?.data?.cong_ty || response?.data?.data?.cong_ty || company.value
    if (!company.value) {
      await fetchCompany()
    }
    await fetchHrAuditLogs(hrAuditPagination.value?.current_page || 1)
    notify.success('Đã cập nhật vai trò nội bộ.')
  } catch (error) {
    notify.apiError(error, 'Không thể cập nhật vai trò nội bộ.')
  } finally {
    roleUpdatingIds.value = roleUpdatingIds.value.filter((id) => id !== memberId)
  }
}

const removeHrMember = async (member) => {
  const memberId = Number(member?.id || 0)
  if (!memberId) return

  if (!canManageMembers.value) {
    notify.warning('Chỉ owner mới có thể gỡ HR khỏi công ty.')
    return
  }

  removingMemberIds.value = [...removingMemberIds.value, memberId]
  try {
    const response = await employerCompanyService.removeMember(memberId)
    company.value = response?.data?.cong_ty || response?.data?.data?.cong_ty || company.value
    if (!company.value) {
      await fetchCompany()
    }
    await fetchHrAuditLogs(hrAuditPagination.value?.current_page || 1)
    notify.success('Đã gỡ HR khỏi công ty.')
  } catch (error) {
    notify.apiError(error, 'Không thể gỡ HR khỏi công ty.')
  } finally {
    removingMemberIds.value = removingMemberIds.value.filter((id) => id !== memberId)
  }
}

onMounted(async () => {
  await Promise.all([fetchCompany(), fetchReceivedInvitations(), fetchHrAuditLogs()])
})
</script>

<template>
  <div class="space-y-6">
    <section class="rounded-xl border border-slate-200 bg-white p-8 shadow-sm dark:border-slate-800 dark:bg-slate-900">
      <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
          <h2 class="text-xl font-bold text-slate-900 dark:text-white">Quản lý nhân sự HR nội bộ</h2>
          <p class="mt-2 text-sm leading-7 text-slate-500 dark:text-slate-400">{{ ownerSummary }}</p>
        </div>
        <div class="flex flex-wrap gap-3">
          <span class="inline-flex items-center gap-2 rounded-full bg-[#2463eb]/10 px-3 py-1 text-xs font-bold text-[#2463eb]">
            <span class="size-2 rounded-full bg-[#2463eb]" />
            {{ companyMembers.length }} thành viên
          </span>
          <button
            class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold transition hover:bg-slate-100 dark:border-slate-700 dark:hover:bg-slate-800"
            type="button"
            @click="fetchCompany"
          >
            Tải lại
          </button>
        </div>
      </div>
    </section>

    <section v-if="loading" class="rounded-xl border border-slate-200 bg-white p-8 shadow-sm dark:border-slate-800 dark:bg-slate-900">
      <div class="h-48 animate-pulse rounded-2xl bg-slate-100 dark:bg-slate-800" />
    </section>

    <template v-else>
      <section
        v-if="hasCompany && canManageMembers"
        class="rounded-xl border border-slate-200 bg-white p-8 shadow-sm dark:border-slate-800 dark:bg-slate-900"
      >
        <h3 class="text-lg font-bold text-slate-900 dark:text-white">Thêm HR mới</h3>
        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
          Nhập email của tài khoản nhà tuyển dụng đã tồn tại trong hệ thống, sau đó chọn vai trò nội bộ phù hợp.
        </p>
        <div class="mt-5 grid grid-cols-1 gap-3 md:grid-cols-[minmax(0,1fr)_220px_auto]">
          <input
            v-model="memberEmail"
            class="w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 transition-all focus:border-transparent focus:ring-2 focus:ring-[#2463eb] dark:border-slate-700 dark:bg-slate-950"
            type="email"
            placeholder="hr@company.com"
            @keydown.enter.prevent="addHrMember"
          >
          <select
            v-model="memberRole"
            class="w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 transition-all focus:border-transparent focus:ring-2 focus:ring-[#2463eb] dark:border-slate-700 dark:bg-slate-950"
          >
            <option v-for="[role, label] in internalRoleOptions" :key="role" :value="role">
              {{ label }}
            </option>
          </select>
          <button
            class="inline-flex items-center justify-center gap-2 rounded-lg bg-[#2463eb] px-5 py-3 text-sm font-bold text-white transition hover:bg-[#2463eb]/90 disabled:cursor-not-allowed disabled:opacity-60"
            :disabled="memberSubmitting"
            type="button"
            @click="addHrMember"
          >
            <span class="material-symbols-outlined text-[18px]">person_add</span>
            {{ memberSubmitting ? 'Đang thêm...' : 'Thêm HR' }}
          </button>
        </div>
      </section>

      <section
        v-if="hasCompany && canManageMembers"
        class="rounded-xl border border-slate-200 bg-white p-8 shadow-sm dark:border-slate-800 dark:bg-slate-900"
      >
        <h3 class="text-lg font-bold text-slate-900 dark:text-white">Gửi lời mời tham gia công ty</h3>
        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
          Dùng khi bạn muốn người được mời tự xác nhận tham gia thay vì thêm trực tiếp vào công ty.
        </p>
        <p class="mt-2 rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-700 dark:border-blue-500/20 dark:bg-blue-500/10 dark:text-blue-200">
          {{ invitationGuidance }}
        </p>
        <div class="mt-5 grid grid-cols-1 gap-3 md:grid-cols-[minmax(0,1fr)_220px_auto]">
          <input
            v-model="inviteEmail"
            class="w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 transition-all focus:border-transparent focus:ring-2 focus:ring-[#2463eb] dark:border-slate-700 dark:bg-slate-950"
            type="email"
            placeholder="invite-hr@company.com"
            @keydown.enter.prevent="sendInvitation"
          >
          <select
            v-model="inviteRole"
            class="w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 transition-all focus:border-transparent focus:ring-2 focus:ring-[#2463eb] dark:border-slate-700 dark:bg-slate-950"
          >
            <option v-for="[role, label] in internalRoleOptions" :key="`invite-${role}`" :value="role">
              {{ label }}
            </option>
          </select>
          <button
            class="inline-flex items-center justify-center gap-2 rounded-lg border border-[#2463eb] px-5 py-3 text-sm font-bold text-[#2463eb] transition hover:bg-[#2463eb]/5 disabled:cursor-not-allowed disabled:opacity-60"
            :disabled="invitationSubmitting"
            type="button"
            @click="sendInvitation"
          >
            <span class="material-symbols-outlined text-[18px]">mark_email_unread</span>
            {{ invitationSubmitting ? 'Đang gửi...' : 'Gửi lời mời' }}
          </button>
        </div>
      </section>

      <section
        v-if="hasCompany && canManageMembers"
        class="rounded-xl border border-slate-200 bg-white p-8 shadow-sm dark:border-slate-800 dark:bg-slate-900"
      >
        <div class="mb-6 flex items-center justify-between gap-4">
          <div>
            <h3 class="text-lg font-bold text-slate-900 dark:text-white">Lời mời đang chờ</h3>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
              Theo dõi các HR đã được mời nhưng chưa phản hồi.
            </p>
          </div>
          <span class="inline-flex items-center gap-2 rounded-full bg-amber-100 px-3 py-1 text-xs font-bold text-amber-700 dark:bg-amber-900/30 dark:text-amber-300">
            <span class="size-2 rounded-full bg-amber-500" />
            {{ pendingInvitations.length }} lời mời
          </span>
        </div>

        <div v-if="pendingInvitations.length" class="space-y-4">
          <div
            v-for="invite in pendingInvitations"
            :key="invite.id"
            class="flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-950/30 lg:flex-row lg:items-center lg:justify-between"
          >
            <div>
              <p class="font-bold text-slate-900 dark:text-white">{{ invite.email }}</p>
              <p class="mt-1 text-sm text-slate-500">
                Vai trò mời: {{ invite.ten_vai_tro_noi_bo }}
              </p>
              <p class="mt-1 text-xs" :class="invite.da_co_tai_khoan ? 'text-emerald-600 dark:text-emerald-300' : 'text-amber-600 dark:text-amber-300'">
                {{ invite.da_co_tai_khoan ? 'Đã có tài khoản nhà tuyển dụng, có thể chấp nhận lời mời ngay.' : 'Chưa có tài khoản nhà tuyển dụng. Lời mời sẽ chờ cho đến khi người này đăng ký đúng email.' }}
              </p>
              <p class="mt-1 text-xs text-slate-400">
                Gửi bởi {{ invite.nguoi_moi?.ho_ten || 'Owner' }} lúc {{ new Date(invite.created_at).toLocaleString('vi-VN') }}
              </p>
            </div>
            <button
              class="inline-flex items-center gap-2 rounded-lg border border-rose-200 px-3 py-2 text-sm font-semibold text-rose-600 transition hover:bg-rose-50 disabled:cursor-not-allowed disabled:opacity-60 dark:border-rose-900/40 dark:text-rose-300 dark:hover:bg-rose-900/10"
              :disabled="cancelingInvitationIds.includes(invite.id)"
              type="button"
              @click="cancelInvitation(invite.id)"
            >
              <span class="material-symbols-outlined text-[18px]">cancel</span>
              {{ cancelingInvitationIds.includes(invite.id) ? 'Đang hủy...' : 'Hủy lời mời' }}
            </button>
          </div>
        </div>

        <div v-else class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-5 py-6 text-sm text-slate-500 dark:border-slate-700 dark:bg-slate-800/40 dark:text-slate-400">
          Chưa có lời mời nào đang chờ phản hồi.
        </div>
      </section>

      <section class="rounded-xl border border-slate-200 bg-white p-8 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="mb-6 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
          <div>
            <h3 class="text-lg font-bold text-slate-900 dark:text-white">Danh sách HR nội bộ</h3>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
              Theo dõi vai trò và trạng thái phân quyền của từng thành viên trong công ty.
            </p>
          </div>
          <span class="inline-flex w-fit items-center gap-2 rounded-full px-3 py-1 text-xs font-bold"
            :class="canManageMembers ? 'bg-[#2463eb]/10 text-[#2463eb]' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300'">
            <span class="size-2 rounded-full" :class="canManageMembers ? 'bg-[#2463eb]' : 'bg-slate-400'" />
            {{ canManageMembers ? 'Có quyền quản lý' : `Vai trò hiện tại: ${currentInternalRoleLabel}` }}
          </span>
        </div>

        <div v-if="!hasCompany" class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-5 py-6 text-sm text-slate-500 dark:border-slate-700 dark:bg-slate-800/40 dark:text-slate-400">
          Bạn cần tạo công ty trước khi sử dụng module quản lý HR.
        </div>

        <div v-else-if="companyMembers.length" class="space-y-4">
          <div
            v-for="member in companyMembers"
            :key="member.id"
            class="flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-950/30 lg:flex-row lg:items-center lg:justify-between"
          >
            <div class="flex items-center gap-4">
              <div class="flex h-14 w-14 items-center justify-center overflow-hidden rounded-2xl border border-slate-200 bg-slate-100 text-lg font-bold text-[#2463eb] dark:border-slate-700 dark:bg-slate-800">
                <img v-if="member.avatar_url" :src="member.avatar_url" alt="avatar HR" class="h-full w-full object-cover">
                <span v-else>{{ String(member.ho_ten || 'H').trim().charAt(0).toUpperCase() }}</span>
              </div>
              <div>
                <p class="font-bold text-slate-900 dark:text-white">{{ member.ho_ten }}</p>
                <p class="text-sm text-slate-500">{{ member.email }}</p>
                <p class="text-sm text-slate-500">{{ member.so_dien_thoai || 'Chưa cập nhật số điện thoại' }}</p>
                <p class="mt-1 text-xs font-medium text-slate-400">Vai trò nội bộ: {{ member.ten_vai_tro_noi_bo || 'HR Member' }}</p>
              </div>
            </div>

            <div class="flex flex-wrap items-center gap-2 lg:justify-end">
              <span
                class="inline-flex items-center gap-1 rounded-full px-3 py-1 text-xs font-bold"
                :class="member.la_chu_so_huu ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300' : 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300'"
              >
                <span class="material-symbols-outlined text-sm">{{ member.la_chu_so_huu ? 'workspace_premium' : 'badge' }}</span>
                {{ member.la_chu_so_huu ? 'Owner' : (member.ten_vai_tro_noi_bo || 'HR Member') }}
              </span>
              <select
                v-if="canManageMembers && !member.la_chu_so_huu"
                :value="member.vai_tro_noi_bo || 'recruiter'"
                class="min-w-[180px] rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium transition-all focus:border-transparent focus:ring-2 focus:ring-[#2463eb] dark:border-slate-700 dark:bg-slate-900"
                :disabled="roleUpdatingIds.includes(member.id)"
                @change="updateHrMemberRole(member, $event.target.value)"
              >
                <option v-for="[role, label] in internalRoleOptions" :key="role" :value="role">
                  {{ label }}
                </option>
              </select>
              <button
                v-if="canManageMembers && !member.la_chu_so_huu"
                class="inline-flex items-center gap-2 rounded-lg border border-rose-200 px-3 py-2 text-sm font-semibold text-rose-600 transition hover:bg-rose-50 disabled:cursor-not-allowed disabled:opacity-60 dark:border-rose-900/40 dark:text-rose-300 dark:hover:bg-rose-900/10"
                :disabled="removingMemberIds.includes(member.id) || roleUpdatingIds.includes(member.id)"
                type="button"
                @click="removeHrMember(member)"
              >
                <span class="material-symbols-outlined text-[18px]">person_remove</span>
                {{ removingMemberIds.includes(member.id) ? 'Đang gỡ...' : 'Gỡ HR' }}
              </button>
            </div>
          </div>
        </div>

        <div v-else class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-5 py-6 text-sm text-slate-500 dark:border-slate-700 dark:bg-slate-800/40 dark:text-slate-400">
          Công ty hiện chưa có thêm HR nội bộ nào ngoài owner.
        </div>
      </section>

      <section class="rounded-xl border border-slate-200 bg-white p-8 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="mb-6 flex items-center justify-between gap-4">
          <div>
            <h3 class="text-lg font-bold text-slate-900 dark:text-white">Lời mời dành cho bạn</h3>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
              Nếu bạn được một công ty mời tham gia, bạn có thể phản hồi trực tiếp tại đây.
            </p>
          </div>
          <span class="inline-flex items-center gap-2 rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-700 dark:bg-slate-800 dark:text-slate-300">
            <span class="size-2 rounded-full bg-slate-400" />
            {{ receivedInvitations.length }} lời mời
          </span>
        </div>

        <div v-if="receivedInvitations.length" class="space-y-4">
          <div
            v-for="invite in receivedInvitations"
            :key="`received-${invite.id}`"
            class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-950/30"
          >
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
              <div>
                <p class="text-lg font-bold text-slate-900 dark:text-white">{{ invite.ten_cong_ty }}</p>
                <p class="mt-1 text-sm text-slate-500">Vai trò được mời: {{ invite.ten_vai_tro_noi_bo }}</p>
                <p class="mt-1 text-sm text-slate-500">Người mời: {{ invite.nguoi_moi?.ho_ten || invite.nguoi_moi?.email || 'Công ty' }}</p>
                <p class="mt-1 text-xs text-slate-400">Nhận lúc {{ new Date(invite.created_at).toLocaleString('vi-VN') }}</p>
              </div>
              <div class="flex flex-wrap gap-2">
                <button
                  class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold transition hover:bg-slate-100 disabled:cursor-not-allowed disabled:opacity-60 dark:border-slate-700 dark:hover:bg-slate-800"
                  :disabled="respondingInvitationIds.includes(invite.id)"
                  type="button"
                  @click="respondToInvitation(invite.id, 'reject')"
                >
                  {{ respondingInvitationIds.includes(invite.id) ? 'Đang xử lý...' : 'Từ chối' }}
                </button>
                <button
                  class="rounded-lg bg-[#2463eb] px-4 py-2 text-sm font-bold text-white transition hover:bg-[#2463eb]/90 disabled:cursor-not-allowed disabled:opacity-60"
                  :disabled="respondingInvitationIds.includes(invite.id)"
                  type="button"
                  @click="respondToInvitation(invite.id, 'accept')"
                >
                  {{ respondingInvitationIds.includes(invite.id) ? 'Đang xử lý...' : 'Chấp nhận' }}
                </button>
              </div>
            </div>
          </div>
        </div>

        <div v-else class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-5 py-6 text-sm text-slate-500 dark:border-slate-700 dark:bg-slate-800/40 dark:text-slate-400">
          Hiện không có lời mời tham gia công ty nào dành cho bạn.
        </div>
      </section>

      <section class="rounded-xl border border-slate-200 bg-white p-8 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="mb-6 flex items-center justify-between gap-4">
          <div>
            <h3 class="text-lg font-bold text-slate-900 dark:text-white">Lịch sử thao tác HR nội bộ</h3>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
              Theo dõi các thay đổi quan trọng trong module nhân sự như thêm HR, đổi vai trò và lời mời tham gia công ty.
            </p>
          </div>
          <button
            class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold transition hover:bg-slate-100 dark:border-slate-700 dark:hover:bg-slate-800"
            type="button"
            @click="fetchHrAuditLogs(hrAuditPagination?.current_page || 1)"
          >
            Tải lại log
          </button>
        </div>

        <div v-if="hrAuditLogs.length" class="space-y-4">
          <div
            v-for="log in hrAuditLogs"
            :key="`audit-${log.id}`"
            class="rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-950/30"
          >
            <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
              <div>
                <p class="font-semibold text-slate-900 dark:text-white">{{ log.mo_ta }}</p>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                  Thực hiện bởi {{ log.nguoi_thuc_hien?.ho_ten || log.nguoi_thuc_hien?.email || 'Hệ thống' }}
                  <span v-if="log.nguoi_bi_tac_dong"> • Tác động tới {{ log.nguoi_bi_tac_dong.ho_ten || log.nguoi_bi_tac_dong.email }}</span>
                </p>
              </div>
              <span class="text-xs text-slate-400">
                {{ new Date(log.created_at).toLocaleString('vi-VN') }}
              </span>
            </div>
          </div>
        </div>

        <div v-else class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-5 py-6 text-sm text-slate-500 dark:border-slate-700 dark:bg-slate-800/40 dark:text-slate-400">
          Chưa có lịch sử thao tác HR nội bộ nào để hiển thị.
        </div>

        <div v-if="hrAuditPagination" class="mt-5 flex items-center justify-end gap-2">
          <button
            class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold transition hover:bg-slate-100 disabled:cursor-not-allowed disabled:opacity-50 dark:border-slate-700 dark:hover:bg-slate-800"
            :disabled="!hrAuditPagination.prev_page_url"
            type="button"
            @click="fetchHrAuditLogs((hrAuditPagination.current_page || 1) - 1)"
          >
            Trước
          </button>
          <button
            class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold transition hover:bg-slate-100 disabled:cursor-not-allowed disabled:opacity-50 dark:border-slate-700 dark:hover:bg-slate-800"
            :disabled="!hrAuditPagination.next_page_url"
            type="button"
            @click="fetchHrAuditLogs((hrAuditPagination.current_page || 1) + 1)"
          >
            Sau
          </button>
        </div>
      </section>
    </template>
  </div>
</template>
