<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { API_DOMAIN } from '@/config/api'
import { employerCompanyService, publicCatalogService } from '@/services/api'

const loading = ref(true)
const saving = ref(false)
const error = ref('')
const success = ref('')
const industries = ref([])
const logoFile = ref(null)
const currentCompany = ref(null)

const form = reactive({
  ten_cong_ty: '',
  ma_so_thue: '',
  dia_chi: '',
  mo_ta: '',
  quy_mo: '',
  website: '',
  nganh_nghe_id: ''
})

const unwrap = (payload) => payload?.data ?? payload ?? []
const asArray = (payload) => {
  const data = unwrap(payload)
  if (Array.isArray(data?.data)) return data.data
  if (Array.isArray(data)) return data
  return []
}

const logoUrl = computed(() => {
  const path = currentCompany.value?.logo
  if (!path) return ''
  if (String(path).startsWith('http')) return path
  return `${API_DOMAIN}${String(path).startsWith('/') ? path : `/storage/${path}`}`
})

const populateForm = (company) => {
  form.ten_cong_ty = company?.ten_cong_ty || ''
  form.ma_so_thue = company?.ma_so_thue || ''
  form.dia_chi = company?.dia_chi || ''
  form.mo_ta = company?.mo_ta || ''
  form.quy_mo = company?.quy_mo || ''
  form.website = company?.website || ''
  form.nganh_nghe_id = company?.nganh_nghe_id || ''
}

const loadPage = async () => {
  loading.value = true
  error.value = ''

  try {
    const [companyResponse, industryResponse] = await Promise.all([
      employerCompanyService.getMyCompany().catch(() => null),
      publicCatalogService.getIndustries()
    ])

    currentCompany.value = companyResponse ? unwrap(companyResponse) : null
    if (currentCompany.value) {
      populateForm(currentCompany.value)
    }
    industries.value = asArray(industryResponse)
  } catch (err) {
    error.value = err.message || 'Khong the tai thong tin cong ty'
  } finally {
    loading.value = false
  }
}

const onLogoChange = (event) => {
  logoFile.value = event.target.files?.[0] || null
}

const buildPayload = () => {
  const hasFile = Boolean(logoFile.value)

  if (!hasFile) {
    return {
      ten_cong_ty: form.ten_cong_ty.trim(),
      ma_so_thue: form.ma_so_thue.trim(),
      dia_chi: form.dia_chi.trim(),
      mo_ta: form.mo_ta.trim(),
      quy_mo: form.quy_mo,
      website: form.website.trim(),
      nganh_nghe_id: form.nganh_nghe_id || null
    }
  }

  const data = new FormData()
  data.append('ten_cong_ty', form.ten_cong_ty.trim())
  data.append('ma_so_thue', form.ma_so_thue.trim())
  data.append('dia_chi', form.dia_chi.trim())
  data.append('mo_ta', form.mo_ta.trim())
  data.append('quy_mo', form.quy_mo)
  data.append('website', form.website.trim())
  if (form.nganh_nghe_id) data.append('nganh_nghe_id', String(form.nganh_nghe_id))
  data.append('logo', logoFile.value)
  return data
}

const saveCompany = async () => {
  if (!form.ten_cong_ty.trim()) {
    error.value = 'Vui long nhap ten cong ty'
    return
  }

  saving.value = true
  error.value = ''
  success.value = ''

  try {
    const payload = buildPayload()
    if (currentCompany.value?.id) {
      await employerCompanyService.updateCompany(payload)
      success.value = 'Cap nhat thong tin cong ty thanh cong'
    } else {
      await employerCompanyService.createCompany(payload)
      success.value = 'Tao thong tin cong ty thanh cong'
    }
    logoFile.value = null
    await loadPage()
  } catch (err) {
    error.value = err.message || 'Khong the luu thong tin cong ty'
  } finally {
    saving.value = false
  }
}

onMounted(loadPage)
</script>

<template>
  <div class="space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
      <div>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Thong tin cong ty</h1>
        <p class="mt-1 text-sm text-slate-500">Cap nhat ho so doanh nghiep de thu hut ung vien phu hop hon.</p>
      </div>
      <button class="inline-flex items-center gap-2 rounded-lg bg-[#2463eb] px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-[#2463eb]/20 transition hover:bg-[#1d4fcc]" :disabled="saving" @click="saveCompany">
        <span class="material-symbols-outlined text-[20px]">save</span>
        {{ saving ? 'Dang luu...' : 'Luu thay doi' }}
      </button>
    </div>

    <div v-if="error" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900/40 dark:bg-red-950/30 dark:text-red-300">{{ error }}</div>
    <div v-if="success" class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700 dark:border-green-900/40 dark:bg-green-950/30 dark:text-green-300">{{ success }}</div>

    <div v-if="loading" class="rounded-2xl border border-slate-200 bg-white px-4 py-12 text-center text-sm text-slate-500 shadow-sm dark:border-slate-800 dark:bg-slate-900">
      Dang tai thong tin cong ty...
    </div>

    <div v-else class="grid grid-cols-1 gap-6 xl:grid-cols-[320px,1fr]">
      <aside class="space-y-6">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
          <div class="flex flex-col items-center text-center">
            <div class="flex h-28 w-28 items-center justify-center overflow-hidden rounded-2xl bg-slate-100 dark:bg-slate-800">
              <img v-if="logoUrl" :src="logoUrl" alt="Company logo" class="h-full w-full object-cover" />
              <span v-else class="material-symbols-outlined text-5xl text-slate-400">domain</span>
            </div>
            <h2 class="mt-4 text-xl font-bold text-slate-900 dark:text-white">{{ form.ten_cong_ty || 'Cong ty cua ban' }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ form.website || 'Bo sung website de tang do tin cay' }}</p>
            <label class="mt-4 inline-flex cursor-pointer items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800">
              <span class="material-symbols-outlined text-[18px]">photo_camera</span>
              Chon logo
              <input class="hidden" type="file" accept="image/*" @change="onLogoChange" />
            </label>
          </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
          <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Tom tat</h3>
          <div class="mt-4 space-y-3 text-sm text-slate-600 dark:text-slate-300">
            <div><span class="font-medium text-slate-900 dark:text-white">Ma so thue:</span> {{ form.ma_so_thue || 'Chua cap nhat' }}</div>
            <div><span class="font-medium text-slate-900 dark:text-white">Quy mo:</span> {{ form.quy_mo || 'Chua cap nhat' }}</div>
            <div><span class="font-medium text-slate-900 dark:text-white">Nganh:</span> {{ industries.find((item) => Number(item.id) === Number(form.nganh_nghe_id))?.ten_nganh || 'Chua cap nhat' }}</div>
          </div>
        </div>
      </aside>

      <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
          <label class="space-y-1 md:col-span-2">
            <span class="text-sm font-medium text-slate-600 dark:text-slate-300">Ten cong ty</span>
            <input v-model="form.ten_cong_ty" class="w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-2.5 dark:border-slate-700 dark:bg-slate-800/60" type="text" />
          </label>
          <label class="space-y-1">
            <span class="text-sm font-medium text-slate-600 dark:text-slate-300">Ma so thue</span>
            <input v-model="form.ma_so_thue" class="w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-2.5 dark:border-slate-700 dark:bg-slate-800/60" type="text" />
          </label>
          <label class="space-y-1">
            <span class="text-sm font-medium text-slate-600 dark:text-slate-300">Quy mo</span>
            <select v-model="form.quy_mo" class="w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-2.5 dark:border-slate-700 dark:bg-slate-800/60">
              <option value="">Chon quy mo</option>
              <option value="1-10">1-10</option>
              <option value="10-50">10-50</option>
              <option value="50-100">50-100</option>
              <option value="100-500">100-500</option>
              <option value="500+">500+</option>
            </select>
          </label>
          <label class="space-y-1">
            <span class="text-sm font-medium text-slate-600 dark:text-slate-300">Website</span>
            <input v-model="form.website" class="w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-2.5 dark:border-slate-700 dark:bg-slate-800/60" type="url" />
          </label>
          <label class="space-y-1">
            <span class="text-sm font-medium text-slate-600 dark:text-slate-300">Nganh nghe</span>
            <select v-model="form.nganh_nghe_id" class="w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-2.5 dark:border-slate-700 dark:bg-slate-800/60">
              <option value="">Chon nganh nghe</option>
              <option v-for="item in industries" :key="item.id" :value="item.id">{{ item.ten_nganh }}</option>
            </select>
          </label>
          <label class="space-y-1 md:col-span-2">
            <span class="text-sm font-medium text-slate-600 dark:text-slate-300">Dia chi</span>
            <input v-model="form.dia_chi" class="w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-2.5 dark:border-slate-700 dark:bg-slate-800/60" type="text" />
          </label>
          <label class="space-y-1 md:col-span-2">
            <span class="text-sm font-medium text-slate-600 dark:text-slate-300">Mo ta cong ty</span>
            <textarea v-model="form.mo_ta" rows="7" class="w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-2.5 dark:border-slate-700 dark:bg-slate-800/60"></textarea>
          </label>
        </div>
      </section>
    </div>
  </div>
</template>
