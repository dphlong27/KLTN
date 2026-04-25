import { computed, readonly, ref } from 'vue'
import { employerCompanyService } from '@/services/api'
import { getStoredEmployer } from '@/utils/authStorage'

const company = ref(null)
const loading = ref(false)
const loaded = ref(false)
const loadedForEmployerId = ref(null)
let authResetListenerRegistered = false

const defaultPermissions = {
  co_the_xem: false,
  co_the_quan_ly_cong_ty: false,
  co_the_quan_ly_tin_tuyen_dung: false,
  co_the_xu_ly_ung_tuyen: false,
  co_the_quan_ly_thanh_vien: false,
}

const normalizePermissions = (payload) => ({
  ...defaultPermissions,
  ...(payload || {}),
})

const currentStoredEmployerId = () => Number(getStoredEmployer()?.id || 0) || null

const resetEmployerCompanyPermissions = () => {
  company.value = null
  loaded.value = false
  loading.value = false
  loadedForEmployerId.value = null
}

const ensureAuthResetListener = () => {
  if (authResetListenerRegistered || typeof window === 'undefined') return

  const reset = () => {
    resetEmployerCompanyPermissions()
  }

  window.addEventListener('auth-changed', reset)
  window.addEventListener('auth-invalidated', reset)
  authResetListenerRegistered = true
}

const loadEmployerCompanyPermissions = async ({ force = false } = {}) => {
  ensureAuthResetListener()

  const employerId = currentStoredEmployerId()

  if (loadedForEmployerId.value && loadedForEmployerId.value !== employerId) {
    resetEmployerCompanyPermissions()
  }

  if (loading.value) return company.value
  if (loaded.value && !force) return company.value

  loading.value = true
  try {
    const response = await employerCompanyService.getCompany()
    company.value = response?.data || null
    loadedForEmployerId.value = employerId
  } catch (error) {
    if (error?.status === 404) {
      company.value = null
      loadedForEmployerId.value = employerId
    } else {
      throw error
    }
  } finally {
    loaded.value = true
    loading.value = false
  }

  return company.value
}

export const useEmployerCompanyPermissions = () => {
  ensureAuthResetListener()

  const currentEmployer = computed(() => getStoredEmployer() || null)
  const permissions = computed(() => normalizePermissions(company.value?.quyen_noi_bo))
  const companyMembers = computed(() => Array.isArray(company.value?.thanh_viens) ? company.value.thanh_viens : [])
  const assignableMembers = computed(() =>
    companyMembers.value.map((member) => ({
      id: member.id,
      label: `${member.ho_ten} (${member.ten_vai_tro_noi_bo || 'HR Member'})`,
      role: member.vai_tro_noi_bo || null,
    })),
  )
  const currentInternalRole = computed(() => company.value?.vai_tro_noi_bo_hien_tai || null)
  const currentInternalRoleLabel = computed(() => company.value?.ten_vai_tro_noi_bo_hien_tai || 'HR Member')
  const currentEmployerId = computed(() => Number(currentEmployer.value?.id || 0) || null)
  const hasCompany = computed(() => Boolean(company.value?.id))
  const canViewEmployerData = computed(() => Boolean(permissions.value.co_the_xem))
  const canManageCompanyProfile = computed(() => Boolean(permissions.value.co_the_quan_ly_cong_ty))
  const canManageJobs = computed(() => Boolean(permissions.value.co_the_quan_ly_tin_tuyen_dung))
  const canProcessApplications = computed(() => Boolean(permissions.value.co_the_xu_ly_ung_tuyen))
  const canManageMembers = computed(() => Boolean(permissions.value.co_the_quan_ly_thanh_vien))
  const canManageAllAssignments = computed(() => ['owner', 'admin_hr'].includes(currentInternalRole.value || ''))
  const canViewCompanyAuditLogs = computed(() => Boolean(permissions.value.co_the_xem))

  return {
    company: readonly(company),
    companyMembers,
    assignableMembers,
    permissions,
    hasCompany,
    currentEmployerId,
    currentInternalRole,
    currentInternalRoleLabel,
    canViewEmployerData,
    canManageCompanyProfile,
    canManageJobs,
    canProcessApplications,
    canManageMembers,
    canManageAllAssignments,
    canViewCompanyAuditLogs,
    permissionsLoading: readonly(loading),
    permissionsLoaded: readonly(loaded),
    ensurePermissionsLoaded: loadEmployerCompanyPermissions,
    refreshPermissions: () => loadEmployerCompanyPermissions({ force: true }),
    resetPermissions: resetEmployerCompanyPermissions,
  }
}
