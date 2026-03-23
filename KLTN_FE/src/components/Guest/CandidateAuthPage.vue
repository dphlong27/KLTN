<script setup>
import { computed, reactive, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { useAuth } from '@/composables/useAuth'
import {
  API_BASE_URL,
  API_DOMAIN,
  CANDIDATE_LOGIN_ENDPOINT,
  CANDIDATE_REGISTER_ENDPOINT
} from '@/config/api'

const EMPLOYER_REGISTER_ENDPOINT = '/auth/register-employer'
const EMPLOYER_LOGIN_ENDPOINT = '/employer/auth/login'

const {
  registerCandidate,
  loginCandidate,
  registerEmployer,
  loginEmployer,
  isLoading,
  error
} = useAuth()

const activeAudience = ref('candidate')
const activeTab = ref('login')
const successMessage = ref('')

const showCandidateLoginPassword = ref(false)
const showCandidateRegisterPassword = ref(false)
const showCandidateRegisterConfirmPassword = ref(false)
const showEmployerLoginPassword = ref(false)
const showEmployerRegisterPassword = ref(false)

const candidateRegisterForm = reactive({
  fullName: '',
  email: '',
  phone: '',
  password: '',
  confirmPassword: ''
})

const candidateLoginForm = reactive({
  email: '',
  password: '',
  rememberMe: true
})

const employerRegisterForm = reactive({
  companyName: '',
  contactPerson: '',
  email: '',
  phone: '',
  password: ''
})

const employerLoginForm = reactive({
  email: '',
  password: '',
  rememberMe: true
})

const candidateRegisterErrors = reactive({
  fullName: '',
  email: '',
  phone: '',
  password: '',
  confirmPassword: ''
})

const candidateLoginErrors = reactive({
  email: '',
  password: ''
})

const employerRegisterErrors = reactive({
  companyName: '',
  contactPerson: '',
  email: '',
  phone: '',
  password: ''
})

const employerLoginErrors = reactive({
  email: '',
  password: ''
})

const endpointPreview = computed(() => {
  if (activeAudience.value === 'candidate') {
    return {
      register: `${API_BASE_URL}${CANDIDATE_REGISTER_ENDPOINT}`,
      login: `${API_BASE_URL}${CANDIDATE_LOGIN_ENDPOINT}`
    }
  }

  return {
    register: `${API_BASE_URL}${EMPLOYER_REGISTER_ENDPOINT}`,
    login: `${API_BASE_URL}${EMPLOYER_LOGIN_ENDPOINT}`
  }
})

const clearMessages = () => {
  successMessage.value = ''
}

const resetCandidateErrors = () => {
  candidateRegisterErrors.fullName = ''
  candidateRegisterErrors.email = ''
  candidateRegisterErrors.phone = ''
  candidateRegisterErrors.password = ''
  candidateRegisterErrors.confirmPassword = ''
  candidateLoginErrors.email = ''
  candidateLoginErrors.password = ''
}

const resetEmployerErrors = () => {
  employerRegisterErrors.companyName = ''
  employerRegisterErrors.contactPerson = ''
  employerRegisterErrors.email = ''
  employerRegisterErrors.phone = ''
  employerRegisterErrors.password = ''
  employerLoginErrors.email = ''
  employerLoginErrors.password = ''
}

const switchAudience = (audience) => {
  activeAudience.value = audience
  activeTab.value = 'login'
  clearMessages()
}

const switchTab = (tab) => {
  activeTab.value = tab
  clearMessages()
}

const isValidEmail = (value) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)
const isValidPhone = (value) => /^0\d{9}$/.test(value.replace(/\s+/g, ''))

const validateCandidateRegister = () => {
  resetCandidateErrors()

  if (!candidateRegisterForm.fullName.trim()) {
    candidateRegisterErrors.fullName = 'Vui lòng nhập họ tên'
  }

  if (!candidateRegisterForm.email.trim()) {
    candidateRegisterErrors.email = 'Vui lòng nhập email'
  } else if (!isValidEmail(candidateRegisterForm.email)) {
    candidateRegisterErrors.email = 'Email không hợp lệ'
  }

  if (!candidateRegisterForm.phone.trim()) {
    candidateRegisterErrors.phone = 'Vui lòng nhập số điện thoại'
  } else if (!isValidPhone(candidateRegisterForm.phone)) {
    candidateRegisterErrors.phone = 'Số điện thoại không hợp lệ'
  }

  if (!candidateRegisterForm.password) {
    candidateRegisterErrors.password = 'Vui lòng nhập mật khẩu'
  } else if (candidateRegisterForm.password.length < 6) {
    candidateRegisterErrors.password = 'Mật khẩu phải có ít nhất 6 ký tự'
  }

  if (!candidateRegisterForm.confirmPassword) {
    candidateRegisterErrors.confirmPassword = 'Vui lòng xác nhận mật khẩu'
  } else if (candidateRegisterForm.confirmPassword !== candidateRegisterForm.password) {
    candidateRegisterErrors.confirmPassword = 'Mật khẩu xác nhận không khớp'
  }

  return Object.values(candidateRegisterErrors).every((value) => !value)
}

const validateCandidateLogin = () => {
  candidateLoginErrors.email = ''
  candidateLoginErrors.password = ''

  if (!candidateLoginForm.email.trim()) {
    candidateLoginErrors.email = 'Vui lòng nhập email'
  } else if (!isValidEmail(candidateLoginForm.email)) {
    candidateLoginErrors.email = 'Email không hợp lệ'
  }

  if (!candidateLoginForm.password) {
    candidateLoginErrors.password = 'Vui lòng nhập mật khẩu'
  }

  return Object.values(candidateLoginErrors).every((value) => !value)
}

const validateEmployerRegister = () => {
  resetEmployerErrors()

  if (!employerRegisterForm.companyName.trim()) {
    employerRegisterErrors.companyName = 'Vui lòng nhập tên công ty'
  }

  if (!employerRegisterForm.contactPerson.trim()) {
    employerRegisterErrors.contactPerson = 'Vui lòng nhập người liên hệ'
  }

  if (!employerRegisterForm.email.trim()) {
    employerRegisterErrors.email = 'Vui lòng nhập email'
  } else if (!isValidEmail(employerRegisterForm.email)) {
    employerRegisterErrors.email = 'Email không hợp lệ'
  }

  if (!employerRegisterForm.phone.trim()) {
    employerRegisterErrors.phone = 'Vui lòng nhập số điện thoại'
  } else if (!isValidPhone(employerRegisterForm.phone)) {
    employerRegisterErrors.phone = 'Số điện thoại không hợp lệ'
  }

  if (!employerRegisterForm.password) {
    employerRegisterErrors.password = 'Vui lòng nhập mật khẩu'
  } else if (employerRegisterForm.password.length < 6) {
    employerRegisterErrors.password = 'Mật khẩu phải có ít nhất 6 ký tự'
  }

  return Object.values(employerRegisterErrors).every((value) => !value)
}

const validateEmployerLogin = () => {
  employerLoginErrors.email = ''
  employerLoginErrors.password = ''

  if (!employerLoginForm.email.trim()) {
    employerLoginErrors.email = 'Vui lòng nhập email'
  } else if (!isValidEmail(employerLoginForm.email)) {
    employerLoginErrors.email = 'Email không hợp lệ'
  }

  if (!employerLoginForm.password) {
    employerLoginErrors.password = 'Vui lòng nhập mật khẩu'
  }

  return Object.values(employerLoginErrors).every((value) => !value)
}

const handleCandidateRegister = async () => {
  clearMessages()

  if (!validateCandidateRegister()) return

  const savedEmail = candidateRegisterForm.email

  try {
    await registerCandidate(
      candidateRegisterForm.fullName,
      candidateRegisterForm.email,
      candidateRegisterForm.phone,
      candidateRegisterForm.password,
      candidateRegisterForm.confirmPassword
    )

    successMessage.value = 'Đăng ký ứng viên thành công. Vui lòng đăng nhập để tiếp tục.'
    Object.assign(candidateRegisterForm, {
      fullName: '',
      email: '',
      phone: '',
      password: '',
      confirmPassword: ''
    })
    activeTab.value = 'login'
    candidateLoginForm.email = savedEmail
  } catch (_) {
    // error state from useAuth
  }
}

const handleCandidateLogin = async () => {
  clearMessages()

  if (!validateCandidateLogin()) return

  try {
    await loginCandidate(candidateLoginForm.email, candidateLoginForm.password)
  } catch (_) {
    // error state from useAuth
  }
}

const handleEmployerRegister = async () => {
  clearMessages()

  if (!validateEmployerRegister()) return

  const savedEmail = employerRegisterForm.email

  try {
    await registerEmployer(
      employerRegisterForm.companyName,
      employerRegisterForm.contactPerson,
      employerRegisterForm.email,
      employerRegisterForm.phone,
      employerRegisterForm.password
    )

    successMessage.value = 'Đăng ký nhà tuyển dụng thành công. Vui lòng đăng nhập để tiếp tục.'
    Object.assign(employerRegisterForm, {
      companyName: '',
      contactPerson: '',
      email: '',
      phone: '',
      password: ''
    })
    activeTab.value = 'login'
    employerLoginForm.email = savedEmail
  } catch (_) {
    // error state from useAuth
  }
}

const handleEmployerLogin = async () => {
  clearMessages()

  if (!validateEmployerLogin()) return

  try {
    await loginEmployer(employerLoginForm.email, employerLoginForm.password)
  } catch (_) {
    // error state from useAuth
  }
}
</script>

<template>
  <section class="relative overflow-hidden bg-[radial-gradient(circle_at_top_left,_rgba(36,99,235,0.18),_transparent_36%),linear-gradient(135deg,#f8fbff_0%,#eef5ff_42%,#f8fafc_100%)]">
    <div class="absolute inset-0 opacity-70">
      <div class="absolute -top-10 left-10 h-72 w-72 rounded-full bg-[#2463eb]/10 blur-3xl"></div>
      <div class="absolute bottom-0 right-0 h-80 w-80 rounded-full bg-cyan-300/15 blur-3xl"></div>
    </div>

    <div class="relative mx-auto flex min-h-screen max-w-7xl flex-col px-6 py-10 lg:flex-row lg:items-center lg:gap-10 lg:px-10">
      <div class="w-full lg:w-[52%]">
        <div class="inline-flex items-center gap-3 rounded-full border border-white/60 bg-white/70 px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm backdrop-blur">
          <span class="material-symbols-outlined text-[#2463eb]">rocket_launch</span>
          {{ activeAudience === 'candidate' ? 'Candidate Access' : 'Employer Access' }}
        </div>

        <div class="mt-8 max-w-2xl">
          <p class="text-sm font-semibold uppercase tracking-[0.3em] text-[#2463eb]">AI Recruitment Platform</p>
          <h1 class="mt-4 text-4xl font-black tracking-tight text-slate-900 sm:text-5xl lg:text-6xl">
            {{
              activeAudience === 'candidate'
                ? 'Đăng nhập hoặc đăng ký để khám phá cơ hội nghề nghiệp phù hợp với bạn'
                : 'Đăng nhập hoặc đăng ký doanh nghiệp để kết nối với ứng viên và quản lý tuyển dụng hiệu quả'
            }}
          </h1>
          <p class="mt-6 max-w-xl text-lg leading-8 text-slate-600">
            {{
              activeAudience === 'candidate'
                ? 'Một giao diện duy nhất cho cả đăng nhập và đăng ký giúp ứng viên dễ dàng truy cập và khám phá các cơ hội nghề nghiệp phù hợp.'
                : 'Nhà tuyển dụng có thể đăng nhập và tạo tài khoản ngay trên cùng một trang, không cần tách riêng màn hình như trước.'
            }}
          </p>
        </div>

        <div class="mt-10 grid gap-4 sm:grid-cols-3">
          <div class="rounded-2xl border border-white/70 bg-white/80 p-5 shadow-sm backdrop-blur">
            <p class="text-3xl font-black text-slate-900">Nhanh</p>
            <p class="mt-2 text-sm text-slate-500">chuyển nhanh giữa 2 đối tượng và 2 chế độ login/register.</p>
          </div>
          <div class="rounded-2xl border border-white/70 bg-white/80 p-5 shadow-sm backdrop-blur">
            <p class="text-3xl font-black text-slate-900">Rõ ràng</p>
            <p class="mt-2 text-sm text-slate-500">Mỗi loại tài khoản có form và validation riêng, tránh nhầm lẫn khi đăng nhập.</p>
          </div>
          <div class="rounded-2xl border border-white/70 bg-white/80 p-5 shadow-sm backdrop-blur">
            <p class="text-3xl font-black text-slate-900">Linh hoạt</p>
            <p class="mt-2 text-sm text-slate-500">Endpoint được preview trực tiếp theo loại tài khoản để debug nhanh hơn.</p>
          </div>
        </div>

        <div class="mt-8 rounded-3xl border border-slate-200/70 bg-slate-900 px-6 py-5 text-white shadow-2xl shadow-slate-900/10">
          <p class="text-sm font-semibold text-slate-200">Cấu hình API hiện tại</p>
          <p class="mt-2 break-all text-sm text-slate-400">Domain: {{ API_DOMAIN }}</p>
          <p class="mt-1 break-all text-sm text-slate-400">Đăng ký: {{ endpointPreview.register }}</p>
          <p class="mt-1 break-all text-sm text-slate-400">Đăng nhập: {{ endpointPreview.login }}</p>
        </div>
      </div>

      <div class="mt-10 w-full lg:mt-0 lg:w-[48%]">
        <div class="rounded-[2rem] border border-white/70 bg-white/85 p-6 shadow-2xl shadow-[#2463eb]/10 backdrop-blur-xl sm:p-8">
          <div class="mb-4 flex items-center justify-between rounded-2xl bg-slate-100 p-1">
            <button
              type="button"
              @click="switchAudience('candidate')"
              :class="[
                'flex-1 rounded-xl px-4 py-3 text-sm font-bold transition',
                activeAudience === 'candidate' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500'
              ]"
            >
              Ứng viên
            </button>
            <button
              type="button"
              @click="switchAudience('employer')"
              :class="[
                'flex-1 rounded-xl px-4 py-3 text-sm font-bold transition',
                activeAudience === 'employer' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500'
              ]"
            >
              Nhà tuyển dụng
            </button>
          </div>

          <div class="flex items-center justify-between rounded-2xl bg-slate-100 p-1">
            <button
              type="button"
              @click="switchTab('login')"
              :class="[
                'flex-1 rounded-xl px-4 py-3 text-sm font-bold transition',
                activeTab === 'login' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500'
              ]"
            >
              Đăng nhập
            </button>
            <button
              type="button"
              @click="switchTab('register')"
              :class="[
                'flex-1 rounded-xl px-4 py-3 text-sm font-bold transition',
                activeTab === 'register' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500'
              ]"
            >
              Đăng ký
            </button>
          </div>

          <div
            v-if="error || successMessage"
            class="mt-6 rounded-2xl px-4 py-3 text-sm font-medium"
            :class="error ? 'border border-red-200 bg-red-50 text-red-600' : 'border border-emerald-200 bg-emerald-50 text-emerald-700'"
          >
            {{ error || successMessage }}
          </div>

          <form
            v-if="activeAudience === 'candidate' && activeTab === 'login'"
            class="mt-6 space-y-5"
            @submit.prevent="handleCandidateLogin"
          >
            <div class="space-y-2">
              <label class="block text-sm font-semibold text-slate-700">Email</label>
              <input
                v-model="candidateLoginForm.email"
                type="email"
                autocomplete="email"
                placeholder="ban@example.com"
                class="w-full rounded-2xl border bg-slate-50 px-4 py-3.5 text-slate-900 outline-none transition focus:border-[#2463eb] focus:bg-white focus:ring-4 focus:ring-[#2463eb]/10"
                :class="candidateLoginErrors.email ? 'border-red-400' : 'border-slate-200'"
                :disabled="isLoading"
              />
              <p v-if="candidateLoginErrors.email" class="text-xs font-medium text-red-500">{{ candidateLoginErrors.email }}</p>
            </div>

            <div class="space-y-2">
              <label class="block text-sm font-semibold text-slate-700">Mật khẩu</label>
              <div class="relative">
                <input
                  v-model="candidateLoginForm.password"
                  :type="showCandidateLoginPassword ? 'text' : 'password'"
                  autocomplete="current-password"
                  placeholder="Nhập mật khẩu"
                  class="w-full rounded-2xl border bg-slate-50 px-4 py-3.5 pr-12 text-slate-900 outline-none transition focus:border-[#2463eb] focus:bg-white focus:ring-4 focus:ring-[#2463eb]/10"
                  :class="candidateLoginErrors.password ? 'border-red-400' : 'border-slate-200'"
                  :disabled="isLoading"
                />
                <button type="button" @click="showCandidateLoginPassword = !showCandidateLoginPassword" class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 transition hover:text-slate-600">
                  <span class="material-symbols-outlined text-[20px]">{{ showCandidateLoginPassword ? 'visibility_off' : 'visibility' }}</span>
                </button>
              </div>
              <p v-if="candidateLoginErrors.password" class="text-xs font-medium text-red-500">{{ candidateLoginErrors.password }}</p>
            </div>

            <label class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
              <input v-model="candidateLoginForm.rememberMe" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-[#2463eb] focus:ring-[#2463eb]/20" />
              Ghi nhớ đăng nhập
            </label>

            <button type="submit" :disabled="isLoading" class="flex w-full items-center justify-center gap-2 rounded-2xl bg-slate-900 px-4 py-4 text-base font-bold text-white shadow-lg shadow-slate-900/15 transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-70">
              <span class="material-symbols-outlined text-[20px]">{{ isLoading ? 'progress_activity' : 'login' }}</span>
              {{ isLoading ? 'Đang đăng nhập...' : 'Đăng nhập Ứng viên' }}
            </button>
          </form>

          <form
            v-else-if="activeAudience === 'candidate' && activeTab === 'register'"
            class="mt-6 space-y-5"
            @submit.prevent="handleCandidateRegister"
          >
            <div class="space-y-2">
              <label class="block text-sm font-semibold text-slate-700">Họ tên</label>
              <input v-model="candidateRegisterForm.fullName" type="text" placeholder="Nguyen Van A" class="w-full rounded-2xl border bg-slate-50 px-4 py-3.5 text-slate-900 outline-none transition focus:border-[#2463eb] focus:bg-white focus:ring-4 focus:ring-[#2463eb]/10" :class="candidateRegisterErrors.fullName ? 'border-red-400' : 'border-slate-200'" :disabled="isLoading" />
              <p v-if="candidateRegisterErrors.fullName" class="text-xs font-medium text-red-500">{{ candidateRegisterErrors.fullName }}</p>
            </div>

            <div class="space-y-2">
              <label class="block text-sm font-semibold text-slate-700">Email</label>
              <input v-model="candidateRegisterForm.email" type="email" placeholder="ban@example.com" class="w-full rounded-2xl border bg-slate-50 px-4 py-3.5 text-slate-900 outline-none transition focus:border-[#2463eb] focus:bg-white focus:ring-4 focus:ring-[#2463eb]/10" :class="candidateRegisterErrors.email ? 'border-red-400' : 'border-slate-200'" :disabled="isLoading" />
              <p v-if="candidateRegisterErrors.email" class="text-xs font-medium text-red-500">{{ candidateRegisterErrors.email }}</p>
            </div>

            <div class="space-y-2">
              <label class="block text-sm font-semibold text-slate-700">Số điện thoại</label>
              <input v-model="candidateRegisterForm.phone" type="tel" placeholder="0901234567" class="w-full rounded-2xl border bg-slate-50 px-4 py-3.5 text-slate-900 outline-none transition focus:border-[#2463eb] focus:bg-white focus:ring-4 focus:ring-[#2463eb]/10" :class="candidateRegisterErrors.phone ? 'border-red-400' : 'border-slate-200'" :disabled="isLoading" />
              <p v-if="candidateRegisterErrors.phone" class="text-xs font-medium text-red-500">{{ candidateRegisterErrors.phone }}</p>
            </div>

            <div class="space-y-2">
              <label class="block text-sm font-semibold text-slate-700">Mật khẩu</label>
              <div class="relative">
                <input v-model="candidateRegisterForm.password" :type="showCandidateRegisterPassword ? 'text' : 'password'" placeholder="Nhập mật khẩu" class="w-full rounded-2xl border bg-slate-50 px-4 py-3.5 pr-12 text-slate-900 outline-none transition focus:border-[#2463eb] focus:bg-white focus:ring-4 focus:ring-[#2463eb]/10" :class="candidateRegisterErrors.password ? 'border-red-400' : 'border-slate-200'" :disabled="isLoading" />
                <button type="button" @click="showCandidateRegisterPassword = !showCandidateRegisterPassword" class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 transition hover:text-slate-600">
                  <span class="material-symbols-outlined text-[20px]">{{ showCandidateRegisterPassword ? 'visibility_off' : 'visibility' }}</span>
                </button>
              </div>
              <p v-if="candidateRegisterErrors.password" class="text-xs font-medium text-red-500">{{ candidateRegisterErrors.password }}</p>
            </div>

            <div class="space-y-2">
              <label class="block text-sm font-semibold text-slate-700">Xác nhận mật khẩu</label>
              <div class="relative">
                <input v-model="candidateRegisterForm.confirmPassword" :type="showCandidateRegisterConfirmPassword ? 'text' : 'password'" placeholder="Nhập lại mật khẩu" class="w-full rounded-2xl border bg-slate-50 px-4 py-3.5 pr-12 text-slate-900 outline-none transition focus:border-[#2463eb] focus:bg-white focus:ring-4 focus:ring-[#2463eb]/10" :class="candidateRegisterErrors.confirmPassword ? 'border-red-400' : 'border-slate-200'" :disabled="isLoading" />
                <button type="button" @click="showCandidateRegisterConfirmPassword = !showCandidateRegisterConfirmPassword" class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 transition hover:text-slate-600">
                  <span class="material-symbols-outlined text-[20px]">{{ showCandidateRegisterConfirmPassword ? 'visibility_off' : 'visibility' }}</span>
                </button>
              </div>
              <p v-if="candidateRegisterErrors.confirmPassword" class="text-xs font-medium text-red-500">{{ candidateRegisterErrors.confirmPassword }}</p>
            </div>

            <button type="submit" :disabled="isLoading" class="flex w-full items-center justify-center gap-2 rounded-2xl bg-[#2463eb] px-4 py-4 text-base font-bold text-white shadow-lg shadow-[#2463eb]/20 transition hover:bg-[#1f57cf] disabled:cursor-not-allowed disabled:opacity-70">
              <span class="material-symbols-outlined text-[20px]">{{ isLoading ? 'progress_activity' : 'person_add' }}</span>
              {{ isLoading ? 'Đang đăng ký' : 'Tạo tài khoản ứng viên' }}
            </button>
          </form>

          <form
            v-else-if="activeAudience === 'employer' && activeTab === 'login'"
            class="mt-6 space-y-5"
            @submit.prevent="handleEmployerLogin"
          >
            <div class="space-y-2">
              <label class="block text-sm font-semibold text-slate-700">Email công ty</label>
              <input v-model="employerLoginForm.email" type="email" placeholder="company@example.com" class="w-full rounded-2xl border bg-slate-50 px-4 py-3.5 text-slate-900 outline-none transition focus:border-[#2463eb] focus:bg-white focus:ring-4 focus:ring-[#2463eb]/10" :class="employerLoginErrors.email ? 'border-red-400' : 'border-slate-200'" :disabled="isLoading" />
              <p v-if="employerLoginErrors.email" class="text-xs font-medium text-red-500">{{ employerLoginErrors.email }}</p>
            </div>

            <div class="space-y-2">
              <label class="block text-sm font-semibold text-slate-700">Mật khẩu</label>
              <div class="relative">
                <input v-model="employerLoginForm.password" :type="showEmployerLoginPassword ? 'text' : 'password'" placeholder="Nhập mật khẩu" class="w-full rounded-2xl border bg-slate-50 px-4 py-3.5 pr-12 text-slate-900 outline-none transition focus:border-[#2463eb] focus:bg-white focus:ring-4 focus:ring-[#2463eb]/10" :class="employerLoginErrors.password ? 'border-red-400' : 'border-slate-200'" :disabled="isLoading" />
                <button type="button" @click="showEmployerLoginPassword = !showEmployerLoginPassword" class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 transition hover:text-slate-600">
                  <span class="material-symbols-outlined text-[20px]">{{ showEmployerLoginPassword ? 'visibility_off' : 'visibility' }}</span>
                </button>
              </div>
              <p v-if="employerLoginErrors.password" class="text-xs font-medium text-red-500">{{ employerLoginErrors.password }}</p>
            </div>

            <label class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
              <input v-model="employerLoginForm.rememberMe" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-[#2463eb] focus:ring-[#2463eb]/20" />
              Ghi nhớ đăng nhập
            </label>

            <button type="submit" :disabled="isLoading" class="flex w-full items-center justify-center gap-2 rounded-2xl bg-slate-900 px-4 py-4 text-base font-bold text-white shadow-lg shadow-slate-900/15 transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-70">
              <span class="material-symbols-outlined text-[20px]">{{ isLoading ? 'progress_activity' : 'business_center' }}</span>
              {{ isLoading ? 'Đang đăng nhập...' : 'Đăng nhập nhà tuyển dụng' }}
            </button>
          </form>

          <form
            v-else
            class="mt-6 space-y-5"
            @submit.prevent="handleEmployerRegister"
          >
            <div class="space-y-2">
              <label class="block text-sm font-semibold text-slate-700">Tên công ty</label>
              <input v-model="employerRegisterForm.companyName" type="text" placeholder="Tên công ty ABC" class="w-full rounded-2xl border bg-slate-50 px-4 py-3.5 text-slate-900 outline-none transition focus:border-[#2463eb] focus:bg-white focus:ring-4 focus:ring-[#2463eb]/10" :class="employerRegisterErrors.companyName ? 'border-red-400' : 'border-slate-200'" :disabled="isLoading" />
              <p v-if="employerRegisterErrors.companyName" class="text-xs font-medium text-red-500">{{ employerRegisterErrors.companyName }}</p>
            </div>

            <div class="space-y-2">
              <label class="block text-sm font-semibold text-slate-700">Người liên hệ</label>
              <input v-model="employerRegisterForm.contactPerson" type="text" placeholder="Nguyễn Văn B" class="w-full rounded-2xl border bg-slate-50 px-4 py-3.5 text-slate-900 outline-none transition focus:border-[#2463eb] focus:bg-white focus:ring-4 focus:ring-[#2463eb]/10" :class="employerRegisterErrors.contactPerson ? 'border-red-400' : 'border-slate-200'" :disabled="isLoading" />
              <p v-if="employerRegisterErrors.contactPerson" class="text-xs font-medium text-red-500">{{ employerRegisterErrors.contactPerson }}</p>
            </div>

            <div class="space-y-2">
              <label class="block text-sm font-semibold text-slate-700">Email công ty</label>
              <input v-model="employerRegisterForm.email" type="email" placeholder="company@example.com" class="w-full rounded-2xl border bg-slate-50 px-4 py-3.5 text-slate-900 outline-none transition focus:border-[#2463eb] focus:bg-white focus:ring-4 focus:ring-[#2463eb]/10" :class="employerRegisterErrors.email ? 'border-red-400' : 'border-slate-200'" :disabled="isLoading" />
              <p v-if="employerRegisterErrors.email" class="text-xs font-medium text-red-500">{{ employerRegisterErrors.email }}</p>
            </div>

            <div class="space-y-2">
              <label class="block text-sm font-semibold text-slate-700">Số điện thoại</label>
              <input v-model="employerRegisterForm.phone" type="tel" placeholder="0901234567" class="w-full rounded-2xl border bg-slate-50 px-4 py-3.5 text-slate-900 outline-none transition focus:border-[#2463eb] focus:bg-white focus:ring-4 focus:ring-[#2463eb]/10" :class="employerRegisterErrors.phone ? 'border-red-400' : 'border-slate-200'" :disabled="isLoading" />
              <p v-if="employerRegisterErrors.phone" class="text-xs font-medium text-red-500">{{ employerRegisterErrors.phone }}</p>
            </div>

            <div class="space-y-2">
              <label class="block text-sm font-semibold text-slate-700">Mật khẩu</label>
              <div class="relative">
                <input v-model="employerRegisterForm.password" :type="showEmployerRegisterPassword ? 'text' : 'password'" placeholder="Nhập mật khẩu" class="w-full rounded-2xl border bg-slate-50 px-4 py-3.5 pr-12 text-slate-900 outline-none transition focus:border-[#2463eb] focus:bg-white focus:ring-4 focus:ring-[#2463eb]/10" :class="employerRegisterErrors.password ? 'border-red-400' : 'border-slate-200'" :disabled="isLoading" />
                <button type="button" @click="showEmployerRegisterPassword = !showEmployerRegisterPassword" class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 transition hover:text-slate-600">
                  <span class="material-symbols-outlined text-[20px]">{{ showEmployerRegisterPassword ? 'visibility_off' : 'visibility' }}</span>
                </button>
              </div>
              <p v-if="employerRegisterErrors.password" class="text-xs font-medium text-red-500">{{ employerRegisterErrors.password }}</p>
            </div>

            <button type="submit" :disabled="isLoading" class="flex w-full items-center justify-center gap-2 rounded-2xl bg-[#2463eb] px-4 py-4 text-base font-bold text-white shadow-lg shadow-[#2463eb]/20 transition hover:bg-[#1f57cf] disabled:cursor-not-allowed disabled:opacity-70">
              <span class="material-symbols-outlined text-[20px]">{{ isLoading ? 'progress_activity' : 'domain_add' }}</span>
              {{ isLoading ? 'Dang dang ky...' : 'Tao tai khoan nha tuyen dung' }}
            </button>
          </form>

          <div class="mt-8 rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-4 text-sm text-slate-500">
           Bạn vẫn có thể truy cập trang đăng nhập/đăng ký cũ nếu muốn:
            <div class="mt-2 flex flex-wrap gap-3">
              <RouterLink to="/auth" class="font-semibold text-[#2463eb] hover:underline">Ứng viên</RouterLink>
              <RouterLink to="/employer/auth" class="font-semibold text-[#2463eb] hover:underline">Nhà tuyển dụng</RouterLink>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>
