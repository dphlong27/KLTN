import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { authService } from '@/services/api'

const emitAuthUserUpdated = (payload = null) => {
  if (typeof window === 'undefined') return
  window.dispatchEvent(new CustomEvent('auth-user-updated', { detail: payload }))
}

export const useAuth = () => {
  const router = useRouter()
  const user = ref(null)
  const isAuthenticated = computed(() => !!user.value)
  const isLoading = ref(false)
  const error = ref('')

  // Load user from localStorage on app initialization
  const loadUser = () => {
    const storedUser =
      localStorage.getItem('user') ||
      localStorage.getItem('employer') ||
      localStorage.getItem('admin')
    if (storedUser) {
      try {
        user.value = JSON.parse(storedUser)
      } catch (e) {
        console.error('Error loading user from localStorage:', e)
      }
    }
  }

  // Guest/Candidate login
  const loginCandidate = async (email, password) => {
    isLoading.value = true
    error.value = ''
    try {
      const response = await authService.login(email, password)
      const token =
        response.token ||
        response.access_token ||
        response?.data?.token ||
        response?.data?.access_token ||
        response?.data?.accessToken ||
        response?.authorization?.token ||
        response?.authorization?.access_token ||
        null

      const candidate =
        response.user ||
        response.candidate ||
        response?.data?.user ||
        response?.data?.candidate ||
        response?.data?.nguoi_dung ||
        response?.nguoi_dung ||
        null

      if (token) {
        localStorage.setItem('token', token)
        localStorage.setItem('access_token', token)
      }

      if (candidate) {
        syncStoredUser(candidate, 'user')
      }

      if (!token) {
        throw {
          message: 'Dang nhap ung vien thanh cong nhung khong tim thay token trong response'
        }
      }

      await router.push('/dashboard')
      return response
    } catch (err) {
      error.value = err.message || 'Login failed'
      throw err
    } finally {
      isLoading.value = false
    }
  }

  // Guest/Candidate registration
  const registerCandidate = async (fullName, email, phone, password, confirmPassword) => {
    isLoading.value = true
    error.value = ''
    try {
      const response = await authService.registerCandidate(fullName, email, phone, password, confirmPassword)
      return response
    } catch (err) {
      error.value = err.message || 'Registration failed'
      throw err
    } finally {
      isLoading.value = false
    }
  }

  // Employer login
  const loginEmployer = async (email, password) => {
    isLoading.value = true
    error.value = ''
    try {
      const response = await authService.loginEmployer(email, password)
      const token =
        response.token ||
        response.access_token ||
        response?.data?.token ||
        response?.data?.access_token ||
        null

      const employer =
        response.employer ||
        response.user ||
        response?.data?.employer ||
        response?.data?.user ||
        response?.data?.nguoi_dung ||
        response?.nguoi_dung ||
        null

      if (!token) {
        throw {
          message: 'Dang nhap nha tuyen dung thanh cong nhung khong tim thay token trong response'
        }
      }

      localStorage.setItem('token', token)
      localStorage.setItem('access_token', token)

      if (employer) {
        localStorage.setItem('employer', JSON.stringify(employer))
        user.value = employer
        emitAuthUserUpdated({ key: 'employer', user: employer })
      }

      await router.push('/employer')
      return response
    } catch (err) {
      error.value = err.message || 'Login failed'
      throw err
    } finally {
      isLoading.value = false
    }
  }

  const syncStoredUser = (storedUser, key = 'user') => {
    if (!storedUser) return
    localStorage.setItem(key, JSON.stringify(storedUser))
    user.value = storedUser
    emitAuthUserUpdated({ key, user: storedUser })
  }

  // Admin login
  const loginAdmin = async (email, password) => {
    isLoading.value = true
    error.value = ''
    try {
      const response = await authService.loginAdmin(email, password)
      const token =
        response.token ||
        response.access_token ||
        response?.data?.token ||
        response?.data?.access_token ||
        response?.data?.accessToken ||
        response?.authorization?.token ||
        response?.authorization?.access_token ||
        null

      const admin =
        response.admin ||
        response.user ||
        response?.data?.admin ||
        response?.data?.user ||
        response?.data?.nguoi_dung ||
        response?.nguoi_dung ||
        null

      const adminRole = Number(
        admin?.vai_tro ??
        admin?.role_id ??
        admin?.role ??
        response?.vai_tro ??
        response?.role_id ??
        response?.data?.vai_tro ??
        response?.data?.role_id ??
        -1
      )

      if (token) {
        localStorage.setItem('token', token)
        localStorage.setItem('access_token', token)
      }

      if (admin) {
        syncStoredUser(admin, 'admin')
      }

      if (!token) {
        throw {
          message: 'Dang nhap admin thanh cong nhung khong tim thay token trong response'
        }
      }

      if (adminRole !== 2) {
        localStorage.removeItem('token')
        localStorage.removeItem('access_token')
        localStorage.removeItem('admin')
        user.value = null
        throw {
          message: 'Tai khoan nay khong co quyen truy cap khu vuc admin'
        }
      }

      await router.push('/admin')
      return response
    } catch (err) {
      error.value = err.message || 'Admin login failed'
      throw err
    } finally {
      isLoading.value = false
    }
  }

  // Employer registration
  const registerEmployer = async (companyName, contactPerson, email, phone, password) => {
    isLoading.value = true
    error.value = ''
    try {
      const response = await authService.registerEmployer(companyName, contactPerson, email, phone, password)
      return response
    } catch (err) {
      error.value = err.message || 'Registration failed'
      throw err
    } finally {
      isLoading.value = false
    }
  }

  // Logout
  const logout = async () => {
    isLoading.value = true
    try {
      await authService.logout()
    } catch (err) {
      console.error('Logout error:', err)
    } finally {
      localStorage.removeItem('token')
      localStorage.removeItem('access_token')
      localStorage.removeItem('user')
      localStorage.removeItem('employer')
      localStorage.removeItem('admin')
      user.value = null
      emitAuthUserUpdated(null)
      isLoading.value = false
      await router.push('/')
    }
  }

  // Change password
  const changePassword = async (oldPassword, newPassword, confirmPassword) => {
    isLoading.value = true
    error.value = ''
    try {
      const response = await authService.changePassword(oldPassword, newPassword, confirmPassword)
      return response
    } catch (err) {
      error.value = err.message || 'Password change failed'
      throw err
    } finally {
      isLoading.value = false
    }
  }

  // Forgot password
  const forgotPassword = async (email) => {
    isLoading.value = true
    error.value = ''
    try {
      const response = await authService.forgotPassword(email)
      return response
    } catch (err) {
      error.value = err.message || 'Failed to send reset email'
      throw err
    } finally {
      isLoading.value = false
    }
  }

  // Reset password
  const resetPassword = async (token, newPassword, confirmPassword) => {
    isLoading.value = true
    error.value = ''
    try {
      const response = await authService.resetPassword(token, newPassword, confirmPassword)
      return response
    } catch (err) {
      error.value = err.message || 'Password reset failed'
      throw err
    } finally {
      isLoading.value = false
    }
  }

  return {
    user,
    isAuthenticated,
    isLoading,
    error,
    loadUser,
    loginCandidate,
    registerCandidate,
    loginEmployer,
    loginAdmin,
    registerEmployer,
    logout,
    changePassword,
    forgotPassword,
    resetPassword,
    syncStoredUser
  }
}
