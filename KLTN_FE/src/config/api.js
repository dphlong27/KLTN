const API_DOMAIN = import.meta.env.VITE_API_DOMAIN || 'http://localhost:8000'
const API_PREFIX = import.meta.env.VITE_API_PREFIX || '/api/v1'

export const API_BASE_URL = `${API_DOMAIN}${API_PREFIX}`
export const CANDIDATE_REGISTER_ENDPOINT = '/dang-ky'
export const CANDIDATE_LOGIN_ENDPOINT = '/dang-nhap'
export const LOGOUT_ENDPOINT = '/dang-xuat'
export const ADMIN_LOGIN_ENDPOINT = '/dang-nhap'
export const PROFILE_ENDPOINT = '/ho-so'
export const UPDATE_PROFILE_ENDPOINT = '/cap-nhat-ho-so'
export const CHANGE_PASSWORD_ENDPOINT = '/doi-mat-khau'

export { API_DOMAIN, API_PREFIX }
