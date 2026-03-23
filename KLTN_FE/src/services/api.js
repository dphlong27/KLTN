import {
  ADMIN_LOGIN_ENDPOINT,
  API_BASE_URL,
  CANDIDATE_LOGIN_ENDPOINT,
  CANDIDATE_REGISTER_ENDPOINT,
  CHANGE_PASSWORD_ENDPOINT,
  LOGOUT_ENDPOINT,
  PROFILE_ENDPOINT,
  UPDATE_PROFILE_ENDPOINT
} from '@/config/api'

const getAuthToken = () => localStorage.getItem('access_token') || localStorage.getItem('token')

const buildQueryString = (params = {}) => {
  const searchParams = new URLSearchParams()

  Object.entries(params).forEach(([key, value]) => {
    if (value === undefined || value === null || value === '') return
    searchParams.append(key, String(value))
  })

  const query = searchParams.toString()
  return query ? `?${query}` : ''
}

const apiCall = async (endpoint, options = {}) => {
  const url = `${API_BASE_URL}${endpoint}`
  const isFormData = options.body instanceof FormData
  const headers = {
    ...options.headers
  }

  if (!isFormData && !headers['Content-Type']) {
    headers['Content-Type'] = 'application/json'
  }

  const token = getAuthToken()
  if (token) {
    headers.Authorization = `Bearer ${token}`
  }

  try {
    const response = await fetch(url, {
      ...options,
      headers
    })

    const contentType = response.headers.get('content-type') || ''
    let data = null

    if (contentType.includes('application/json')) {
      data = await response.json()
    } else {
      const text = await response.text()
      if (!response.ok) {
        throw {
          status: response.status,
          message: `API Error: ${response.status} ${response.statusText}`,
          details: text.substring(0, 300)
        }
      }
      return { data: text }
    }

    if (!response.ok) {
      const validationMessage = data?.errors
        ? Object.values(data.errors).flat().join('\n')
        : null

      throw {
        status: response.status,
        message: validationMessage || data?.message || `Loi ${response.status}: ${response.statusText}`,
        data
      }
    }

    return data
  } catch (error) {
    if (error instanceof TypeError) {
      throw {
        status: 0,
        message: `Khong the ket noi den API tai ${API_BASE_URL}`,
        error: error.message
      }
    }

    throw error
  }
}

const createMultipartPut = (endpoint, formData) => {
  formData.set('_method', 'PUT')
  return apiCall(endpoint, {
    method: 'POST',
    body: formData
  })
}

const createMultipartPatch = (endpoint, formData) => {
  formData.set('_method', 'PATCH')
  return apiCall(endpoint, {
    method: 'POST',
    body: formData
  })
}

export const authService = {
  registerCandidate: (fullName, email, phone, password, confirmPassword) =>
    apiCall(CANDIDATE_REGISTER_ENDPOINT, {
      method: 'POST',
      body: JSON.stringify({
        ho_ten: fullName,
        email,
        so_dien_thoai: phone,
        mat_khau: password,
        mat_khau_confirmation: confirmPassword,
        vai_tro: 0,
        role: 'ung_vien',
        fullName,
        phone,
        password,
        password_confirmation: confirmPassword
      })
    }),

  login: (email, password) =>
    apiCall(CANDIDATE_LOGIN_ENDPOINT, {
      method: 'POST',
      body: JSON.stringify({
        email,
        mat_khau: password,
        password,
        vai_tro: 0,
        role: 'ung_vien'
      })
    }),

  registerEmployer: (companyName, contactPerson, email, phone, password) =>
    apiCall(CANDIDATE_REGISTER_ENDPOINT, {
      method: 'POST',
      body: JSON.stringify({
        ho_ten: contactPerson,
        ten_cong_ty: companyName,
        company_name: companyName,
        email,
        so_dien_thoai: phone,
        mat_khau: password,
        mat_khau_confirmation: password,
        vai_tro: 1,
        role: 'nha_tuyen_dung',
        phone,
        password,
        password_confirmation: password
      })
    }),

  loginEmployer: (email, password) =>
    apiCall(CANDIDATE_LOGIN_ENDPOINT, {
      method: 'POST',
      body: JSON.stringify({
        email,
        mat_khau: password,
        password,
        vai_tro: 1,
        role: 'nha_tuyen_dung'
      })
    }),

  loginAdmin: (email, password) =>
    apiCall(ADMIN_LOGIN_ENDPOINT, {
      method: 'POST',
      body: JSON.stringify({
        email,
        mat_khau: password,
        password,
        vai_tro: 2,
        role: 'admin'
      })
    }),

  logout: () => apiCall(LOGOUT_ENDPOINT, { method: 'POST' }),
  getProfile: () => apiCall(PROFILE_ENDPOINT, { method: 'GET' }),

  updateProfile: (data) => {
    if (data instanceof FormData) {
      return createMultipartPut(UPDATE_PROFILE_ENDPOINT, data)
    }

    return apiCall(UPDATE_PROFILE_ENDPOINT, {
      method: 'PUT',
      body: JSON.stringify(data)
    })
  },

  changePassword: (oldPassword, newPassword, confirmPassword) =>
    apiCall(CHANGE_PASSWORD_ENDPOINT, {
      method: 'POST',
      body: JSON.stringify({
        mat_khau_cu: oldPassword,
        mat_khau_hien_tai: oldPassword,
        mat_khau_moi: newPassword,
        mat_khau_moi_confirmation: confirmPassword,
        password_confirmation: confirmPassword,
        oldPassword,
        newPassword,
        confirmPassword
      })
    })
}

export const publicCatalogService = {
  getJobs: (params = {}) => apiCall(`/tin-tuyen-dungs${buildQueryString(params)}`, { method: 'GET' }),
  getJobById: (id) => apiCall(`/tin-tuyen-dungs/${id}`, { method: 'GET' }),
  getCompanies: (params = {}) => apiCall(`/cong-tys${buildQueryString(params)}`, { method: 'GET' }),
  getCompanyById: (id) => apiCall(`/cong-tys/${id}`, { method: 'GET' }),
  getIndustries: (params = {}) => apiCall(`/nganh-nghes${buildQueryString(params)}`, { method: 'GET' }),
  getIndustryTree: () => apiCall('/nganh-nghes/cay', { method: 'GET' }),
  getSkills: (params = {}) => apiCall(`/ky-nangs${buildQueryString(params)}`, { method: 'GET' })
}

export const candidateCvService = {
  getCvs: (params = {}) => apiCall(`/ung-vien/ho-sos${buildQueryString(params)}`, { method: 'GET' }),
  getCvById: (id) => apiCall(`/ung-vien/ho-sos/${id}`, { method: 'GET' }),
  createCv: (data) => apiCall('/ung-vien/ho-sos', {
    method: 'POST',
    body: data instanceof FormData ? data : JSON.stringify(data)
  }),
  updateCv: (id, data) => {
    if (data instanceof FormData) {
      return createMultipartPut(`/ung-vien/ho-sos/${id}`, data)
    }

    return apiCall(`/ung-vien/ho-sos/${id}`, {
      method: 'PUT',
      body: JSON.stringify(data)
    })
  },
  deleteCv: (id) => apiCall(`/ung-vien/ho-sos/${id}`, { method: 'DELETE' }),
  toggleCvStatus: (id) => apiCall(`/ung-vien/ho-sos/${id}/trang-thai`, { method: 'PATCH' })
}

export const candidateSkillService = {
  getSkills: () => apiCall('/ung-vien/ky-nangs', { method: 'GET' }),
  createSkill: (data) => apiCall('/ung-vien/ky-nangs', {
    method: 'POST',
    body: data instanceof FormData ? data : JSON.stringify(data)
  }),
  updateSkill: (id, data) => {
    if (data instanceof FormData) {
      return createMultipartPut(`/ung-vien/ky-nangs/${id}`, data)
    }

    return apiCall(`/ung-vien/ky-nangs/${id}`, {
      method: 'PUT',
      body: JSON.stringify(data)
    })
  },
  deleteSkill: (id) => apiCall(`/ung-vien/ky-nangs/${id}`, { method: 'DELETE' })
}

export const savedJobService = {
  getSavedJobs: () => apiCall('/ung-vien/tin-da-luu', { method: 'GET' }),
  toggleSavedJob: (jobId) => apiCall(`/ung-vien/tin-da-luu/${jobId}/toggle`, { method: 'POST' })
}

export const candidateApplicationService = {
  getApplications: () => apiCall('/ung-vien/ung-tuyens', { method: 'GET' }),
  createApplication: (data) => apiCall('/ung-vien/ung-tuyens', {
    method: 'POST',
    body: JSON.stringify(data)
  })
}

export const matchingService = {
  getMatchings: () => apiCall('/ung-vien/ket-qua-matchings', { method: 'GET' })
}

export const careerAdviceService = {
  getAdviceList: () => apiCall('/ung-vien/tu-van-nghe-nghieps', { method: 'GET' })
}

export const employerCompanyService = {
  getMyCompany: () => apiCall('/nha-tuyen-dung/cong-ty', { method: 'GET' }),
  createCompany: (data) => apiCall('/nha-tuyen-dung/cong-ty', {
    method: 'POST',
    body: data instanceof FormData ? data : JSON.stringify(data)
  }),
  updateCompany: (data) => {
    if (data instanceof FormData) {
      return createMultipartPut('/nha-tuyen-dung/cong-ty', data)
    }

    return apiCall('/nha-tuyen-dung/cong-ty', {
      method: 'PUT',
      body: JSON.stringify(data)
    })
  }
}

export const employerJobService = {
  getJobs: (params = {}) => apiCall(`/nha-tuyen-dung/tin-tuyen-dungs${buildQueryString(params)}`, { method: 'GET' }),
  getJobById: (id) => apiCall(`/nha-tuyen-dung/tin-tuyen-dungs/${id}`, { method: 'GET' }),
  createJob: (data) => apiCall('/nha-tuyen-dung/tin-tuyen-dungs', {
    method: 'POST',
    body: JSON.stringify(data)
  }),
  updateJob: (id, data) => apiCall(`/nha-tuyen-dung/tin-tuyen-dungs/${id}`, {
    method: 'PUT',
    body: JSON.stringify(data)
  }),
  toggleJobStatus: (id, data = null) => {
    if (data instanceof FormData) {
      return createMultipartPatch(`/nha-tuyen-dung/tin-tuyen-dungs/${id}/trang-thai`, data)
    }

    return apiCall(`/nha-tuyen-dung/tin-tuyen-dungs/${id}/trang-thai`, {
      method: 'PATCH',
      body: data ? JSON.stringify(data) : undefined
    })
  },
  deleteJob: (id) => apiCall(`/nha-tuyen-dung/tin-tuyen-dungs/${id}`, { method: 'DELETE' })
}

export const employerApplicationService = {
  getApplications: (params = {}) => apiCall(`/nha-tuyen-dung/ung-tuyens${buildQueryString(params)}`, { method: 'GET' }),
  updateStatus: (id, data) => apiCall(`/nha-tuyen-dung/ung-tuyens/${id}/trang-thai`, {
    method: 'PATCH',
    body: JSON.stringify(data)
  })
}

export const employerCandidateProfileService = {
  getProfiles: (params = {}) => apiCall(`/nha-tuyen-dung/ho-sos${buildQueryString(params)}`, { method: 'GET' }),
  getProfileById: (id) => apiCall(`/nha-tuyen-dung/ho-sos/${id}`, { method: 'GET' })
}

export const userService = {
  getUsers: (options = {}) => apiCall(`/admin/nguoi-dungs${buildQueryString(options)}`, { method: 'GET' }),
  getUserStats: () => apiCall('/admin/nguoi-dungs/thong-ke', { method: 'GET' }),
  getUserById: (id) => apiCall(`/admin/nguoi-dungs/${id}`, { method: 'GET' }),
  createUser: (data) => apiCall('/admin/nguoi-dungs', { method: 'POST', body: JSON.stringify(data) }),
  updateUser: (id, data) => apiCall(`/admin/nguoi-dungs/${id}`, { method: 'PUT', body: JSON.stringify(data) }),
  resetPassword: (id, mat_khau) => apiCall(`/admin/nguoi-dungs/${id}`, { method: 'PUT', body: JSON.stringify({ mat_khau }) }),
  toggleLock: (id) => apiCall(`/admin/nguoi-dungs/${id}/khoa`, { method: 'PATCH' }),
  deleteUser: (id) => apiCall(`/admin/nguoi-dungs/${id}`, { method: 'DELETE' })
}

export const companyService = {
  getCompanies: (options = {}) => apiCall(`/admin/cong-tys${buildQueryString(options)}`, { method: 'GET' }),
  getCompanyStats: () => apiCall('/admin/cong-tys/thong-ke', { method: 'GET' }),
  getCompanyById: (id) => apiCall(`/admin/cong-tys/${id}`, { method: 'GET' }),
  createCompany: (data) => apiCall('/admin/cong-tys', { method: 'POST', body: JSON.stringify(data) }),
  updateCompany: (id, data) => apiCall(`/admin/cong-tys/${id}`, { method: 'PUT', body: JSON.stringify(data) }),
  toggleCompanyStatus: (id) => apiCall(`/admin/cong-tys/${id}/trang-thai`, { method: 'PATCH' }),
  deleteCompany: (id) => apiCall(`/admin/cong-tys/${id}`, { method: 'DELETE' })
}

export default {
  authService,
  publicCatalogService,
  candidateCvService,
  candidateSkillService,
  savedJobService,
  candidateApplicationService,
  matchingService,
  careerAdviceService,
  employerCompanyService,
  employerJobService,
  employerApplicationService,
  employerCandidateProfileService,
  userService,
  companyService
}
