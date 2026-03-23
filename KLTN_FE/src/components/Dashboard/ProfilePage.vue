<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { authService } from '@/services/api'
import { useAuth } from '@/composables/useAuth'

const { syncStoredUser } = useAuth()

const loading = ref(true)
const savingProfile = ref(false)
const changingPassword = ref(false)
const error = ref('')
const success = ref('')
const avatarFile = ref(null)
const avatarPreview = ref('')

const profileForm = reactive({
  ho_ten: '',
  email: '',
  so_dien_thoai: '',
  ngay_sinh: '',
  gioi_tinh: '',
  dia_chi: ''
})

const passwordForm = reactive({
  mat_khau_cu: '',
  mat_khau_moi: '',
  mat_khau_moi_confirmation: ''
})

const genderFromApi = (value) => {
  if (value === 1 || value === '1' || value === 'nam') return 'nam'
  if (value === 0 || value === '0' || value === 'nu') return 'nu'
  if (value === 2 || value === '2' || value === 'khac') return 'khac'
  return ''
}

const normalizeDateForInput = (value) => {
  if (!value) return ''
  const stringValue = String(value).trim()
  if (/^\d{4}-\d{2}-\d{2}$/.test(stringValue)) return stringValue
  if (/^\d{4}-\d{2}-\d{2}T/.test(stringValue)) return stringValue.slice(0, 10)

  const parsedDate = new Date(stringValue)
  if (Number.isNaN(parsedDate.getTime())) return ''

  const year = parsedDate.getFullYear()
  const month = String(parsedDate.getMonth() + 1).padStart(2, '0')
  const day = String(parsedDate.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}

const strength = computed(() => {
  const fields = [
    profileForm.ho_ten,
    profileForm.email,
    profileForm.so_dien_thoai,
    profileForm.ngay_sinh,
    profileForm.gioi_tinh,
    profileForm.dia_chi
  ]

  const completed = fields.filter((value) => String(value || '').trim()).length
  return Math.round((completed / fields.length) * 100)
})

const initials = computed(() => {
  const words = profileForm.ho_ten.trim().split(/\s+/).filter(Boolean)
  return words.slice(0, 2).map((word) => word[0]?.toUpperCase()).join('') || 'UV'
})

const loadProfile = async () => {
  loading.value = true
  error.value = ''

  try {
    const response = await authService.getProfile()
    const profile = response?.data || response?.user || response?.nguoi_dung || response || {}

    profileForm.ho_ten = profile.ho_ten || profile.fullName || ''
    profileForm.email = profile.email || ''
    profileForm.so_dien_thoai = profile.so_dien_thoai || profile.phone || ''
    profileForm.ngay_sinh = normalizeDateForInput(profile.ngay_sinh)
    profileForm.gioi_tinh = genderFromApi(profile.gioi_tinh)
    profileForm.dia_chi = profile.dia_chi || ''
    avatarPreview.value = profile.anh_dai_dien_url || profile.avatar_url || profile.anh_dai_dien || ''

    syncStoredUser(profile, 'user')
  } catch (err) {
    error.value = err.message || 'Khong the tai thong tin ho so ca nhan'
  } finally {
    loading.value = false
  }
}

const onAvatarChange = (event) => {
  const file = event.target.files?.[0]
  if (!file) return

  avatarFile.value = file
  avatarPreview.value = URL.createObjectURL(file)
}

const saveProfile = async () => {
  savingProfile.value = true
  error.value = ''
  success.value = ''

  try {
    const payload = {
      ho_ten: profileForm.ho_ten.trim(),
      email: profileForm.email.trim()
    }

    if (profileForm.so_dien_thoai.trim()) {
      payload.so_dien_thoai = profileForm.so_dien_thoai.trim()
    }

    if (profileForm.ngay_sinh) {
      payload.ngay_sinh = normalizeDateForInput(profileForm.ngay_sinh)
    }

    if (profileForm.gioi_tinh) {
      payload.gioi_tinh = profileForm.gioi_tinh
    }

    if (profileForm.dia_chi.trim()) {
      payload.dia_chi = profileForm.dia_chi.trim()
    }

    let requestData = payload

    if (avatarFile.value) {
      const formData = new FormData()

      Object.entries(payload).forEach(([key, value]) => {
        formData.append(key, String(value))
      })

      formData.append('anh_dai_dien', avatarFile.value)
      requestData = formData
    }

    const response = await authService.updateProfile(requestData)
    const updatedProfile = response?.data || response?.user || response?.nguoi_dung || response || {}

    syncStoredUser(updatedProfile, 'user')
    avatarFile.value = null
    success.value = 'Cap nhat ho so thanh cong'
  } catch (err) {
    error.value = err.message || 'Cap nhat ho so that bai'
  } finally {
    savingProfile.value = false
  }
}

const submitPasswordChange = async () => {
  error.value = ''
  success.value = ''

  if (!passwordForm.mat_khau_cu || !passwordForm.mat_khau_moi || !passwordForm.mat_khau_moi_confirmation) {
    error.value = 'Vui long nhap day du thong tin doi mat khau'
    return
  }

  if (passwordForm.mat_khau_moi !== passwordForm.mat_khau_moi_confirmation) {
    error.value = 'Mat khau moi va xac nhan mat khau khong khop'
    return
  }

  changingPassword.value = true

  try {
    await authService.changePassword(
      passwordForm.mat_khau_cu,
      passwordForm.mat_khau_moi,
      passwordForm.mat_khau_moi_confirmation
    )

    passwordForm.mat_khau_cu = ''
    passwordForm.mat_khau_moi = ''
    passwordForm.mat_khau_moi_confirmation = ''
    success.value = 'Doi mat khau thanh cong. Neu backend thu hoi token, vui long dang nhap lai.'
  } catch (err) {
    error.value = err.message || 'Doi mat khau that bai'
  } finally {
    changingPassword.value = false
  }
}

onMounted(() => {
  loadProfile()
})
</script>

<template>
  <div class="mx-auto grid max-w-6xl grid-cols-1 gap-8 lg:grid-cols-12">
    <aside class="flex flex-col gap-6 lg:col-span-4">
      <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div v-if="loading" class="space-y-4">
          <div class="mx-auto h-32 w-32 animate-pulse rounded-full bg-slate-100 dark:bg-slate-800"></div>
          <div class="mx-auto h-4 w-40 animate-pulse rounded bg-slate-100 dark:bg-slate-800"></div>
          <div class="mx-auto h-3 w-24 animate-pulse rounded bg-slate-100 dark:bg-slate-800"></div>
        </div>

        <div v-else class="flex flex-col items-center text-center">
          <div class="relative group">
            <div class="mb-4 flex h-32 w-32 items-center justify-center overflow-hidden rounded-full border-4 border-[#2463eb]/20 bg-slate-50 text-3xl font-black text-[#2463eb] dark:bg-slate-800">
              <img v-if="avatarPreview" :src="avatarPreview" alt="Avatar" class="h-full w-full object-cover" />
              <span v-else>{{ initials }}</span>
            </div>
            <label class="absolute bottom-4 right-0 cursor-pointer rounded-full bg-[#2463eb] p-2 text-white shadow-lg transition-transform hover:bg-[#2463eb]/90 active:scale-95">
              <span class="material-symbols-outlined text-sm">photo_camera</span>
              <input class="hidden" type="file" accept="image/*" @change="onAvatarChange" />
            </label>
          </div>

          <h3 class="text-xl font-bold text-slate-900 dark:text-slate-100">{{ profileForm.ho_ten || 'Ung vien' }}</h3>
          <p class="mb-4 text-sm text-slate-500 dark:text-slate-400">{{ profileForm.email || 'Cap nhat thong tin tai khoan cua ban' }}</p>

          <div class="mb-2 h-2 w-full overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
            <div class="h-full rounded-full bg-[#2463eb]" :style="{ width: `${strength}%` }"></div>
          </div>
          <p class="mb-6 text-xs font-medium text-slate-400">Do hoan thien ho so: {{ strength }}%</p>

          <button
            type="button"
            @click="saveProfile"
            :disabled="savingProfile"
            class="w-full rounded-lg bg-[#2463eb] py-2.5 font-bold text-white transition-all hover:shadow-lg hover:shadow-[#2463eb]/30 disabled:opacity-60"
          >
            {{ savingProfile ? 'Dang luu...' : 'Luu ho so' }}
          </button>
        </div>
      </div>

      <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <h4 class="mb-4 flex items-center gap-2 font-bold">
          <span class="material-symbols-outlined text-[#2463eb]">vpn_key</span>
          Doi mat khau
        </h4>

        <div class="space-y-3">
          <input v-model="passwordForm.mat_khau_cu" type="password" placeholder="Mat khau hien tai" class="w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-2.5 transition-all focus:border-[#2463eb] focus:ring-2 focus:ring-[#2463eb]/20 dark:border-slate-800 dark:bg-slate-800/50" />
          <input v-model="passwordForm.mat_khau_moi" type="password" placeholder="Mat khau moi" class="w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-2.5 transition-all focus:border-[#2463eb] focus:ring-2 focus:ring-[#2463eb]/20 dark:border-slate-800 dark:bg-slate-800/50" />
          <input v-model="passwordForm.mat_khau_moi_confirmation" type="password" placeholder="Xac nhan mat khau moi" class="w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-2.5 transition-all focus:border-[#2463eb] focus:ring-2 focus:ring-[#2463eb]/20 dark:border-slate-800 dark:bg-slate-800/50" />
          <button type="button" @click="submitPasswordChange" :disabled="changingPassword" class="w-full rounded-lg border border-[#2463eb]/20 bg-[#2463eb]/10 py-2.5 font-bold text-[#2463eb] transition-colors hover:bg-[#2463eb]/20 disabled:opacity-60">
            {{ changingPassword ? 'Dang doi mat khau...' : 'Cap nhat mat khau' }}
          </button>
        </div>
      </div>
    </aside>

    <div class="space-y-8 lg:col-span-8">
      <div
        v-if="error || success"
        class="rounded-xl border px-4 py-3 text-sm font-medium"
        :class="error ? 'border-red-200 bg-red-50 text-red-600' : 'border-emerald-200 bg-emerald-50 text-emerald-700'"
      >
        {{ error || success }}
      </div>

      <section class="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="mb-6 flex items-center justify-between">
          <h3 class="flex items-center gap-2 text-xl font-bold">
            <span class="material-symbols-outlined text-[#2463eb]">person</span>
            Thong tin ca nhan
          </h3>
          <span class="rounded-full bg-[#2463eb]/10 px-3 py-1 text-xs font-bold uppercase tracking-[0.2em] text-[#2463eb]">
            Ho so
          </span>
        </div>

        <div v-if="loading" class="grid grid-cols-1 gap-6 md:grid-cols-2">
          <div v-for="item in 6" :key="item" class="space-y-2">
            <div class="h-3 w-24 animate-pulse rounded bg-slate-100 dark:bg-slate-800"></div>
            <div class="h-11 w-full animate-pulse rounded-lg bg-slate-100 dark:bg-slate-800"></div>
          </div>
        </div>

        <div v-else class="grid grid-cols-1 gap-6 md:grid-cols-2">
          <div class="space-y-1">
            <label class="text-sm font-semibold text-slate-500 dark:text-slate-400">Ho ten</label>
            <input v-model="profileForm.ho_ten" class="w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-2.5 transition-all focus:border-[#2463eb] focus:ring-2 focus:ring-[#2463eb]/20 dark:border-slate-800 dark:bg-slate-800/50" type="text" />
          </div>
          <div class="space-y-1">
            <label class="text-sm font-semibold text-slate-500 dark:text-slate-400">Email</label>
            <input v-model="profileForm.email" class="w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-2.5 transition-all focus:border-[#2463eb] focus:ring-2 focus:ring-[#2463eb]/20 dark:border-slate-800 dark:bg-slate-800/50" type="email" />
          </div>
          <div class="space-y-1">
            <label class="text-sm font-semibold text-slate-500 dark:text-slate-400">So dien thoai</label>
            <input v-model="profileForm.so_dien_thoai" class="w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-2.5 transition-all focus:border-[#2463eb] focus:ring-2 focus:ring-[#2463eb]/20 dark:border-slate-800 dark:bg-slate-800/50" type="tel" />
          </div>
          <div class="space-y-1">
            <label class="text-sm font-semibold text-slate-500 dark:text-slate-400">Ngay sinh</label>
            <input v-model="profileForm.ngay_sinh" class="w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-2.5 transition-all focus:border-[#2463eb] focus:ring-2 focus:ring-[#2463eb]/20 dark:border-slate-800 dark:bg-slate-800/50" type="date" />
          </div>
          <div class="space-y-1">
            <label class="text-sm font-semibold text-slate-500 dark:text-slate-400">Gioi tinh</label>
            <select v-model="profileForm.gioi_tinh" class="w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-2.5 transition-all focus:border-[#2463eb] focus:ring-2 focus:ring-[#2463eb]/20 dark:border-slate-800 dark:bg-slate-800/50">
              <option value="">Chon gioi tinh</option>
              <option value="nam">Nam</option>
              <option value="nu">Nu</option>
              <option value="khac">Khac</option>
            </select>
          </div>
          <div class="space-y-1 md:col-span-2">
            <label class="text-sm font-semibold text-slate-500 dark:text-slate-400">Dia chi</label>
            <input v-model="profileForm.dia_chi" class="w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-2.5 transition-all focus:border-[#2463eb] focus:ring-2 focus:ring-[#2463eb]/20 dark:border-slate-800 dark:bg-slate-800/50" type="text" />
          </div>
        </div>
      </section>

      <div class="flex justify-end gap-4 pb-8">
        <button type="button" @click="loadProfile" class="rounded-lg border border-slate-300 px-8 py-3 font-bold transition-colors hover:bg-slate-100 dark:border-slate-700 dark:hover:bg-slate-800">
          Tai lai
        </button>
        <button type="button" @click="saveProfile" :disabled="savingProfile || loading" class="rounded-lg bg-[#2463eb] px-8 py-3 font-bold text-white transition-all hover:shadow-lg disabled:opacity-60">
          {{ savingProfile ? 'Dang luu...' : 'Luu thay doi' }}
        </button>
      </div>
    </div>
  </div>
</template>
