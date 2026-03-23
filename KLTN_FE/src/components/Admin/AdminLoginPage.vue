<script setup>
import { computed, reactive } from 'vue'
import { useAuth } from '@/composables/useAuth'
import { ADMIN_LOGIN_ENDPOINT, API_BASE_URL, API_DOMAIN } from '@/config/api'

const { loginAdmin, isLoading, error } = useAuth()

const form = reactive({
  email: '',
  password: '',
  remember: true
})

const endpointPreview = computed(() => `${API_BASE_URL}${ADMIN_LOGIN_ENDPOINT}`)
const backendPreview = computed(() => API_DOMAIN)

const handleSubmit = async () => {
  await loginAdmin(form.email, form.password)
}
</script>

<template>
  <section class="relative min-h-screen overflow-hidden bg-[radial-gradient(circle_at_top,_rgba(36,99,235,0.18),_transparent_40%),linear-gradient(135deg,#f8fbff_0%,#eef4ff_45%,#f8fafc_100%)]">
    <div class="absolute inset-0 opacity-70">
      <div class="absolute -left-20 top-16 h-72 w-72 rounded-full bg-[#2463eb]/15 blur-3xl"></div>
      <div class="absolute right-0 top-0 h-80 w-80 rounded-full bg-cyan-400/15 blur-3xl"></div>
      <div class="absolute bottom-0 left-1/3 h-64 w-64 rounded-full bg-amber-300/20 blur-3xl"></div>
    </div>

    <div class="relative mx-auto flex min-h-screen max-w-7xl flex-col lg:flex-row">
      <div class="flex w-full flex-col justify-between px-6 py-10 lg:w-[54%] lg:px-12 lg:py-14">
        <div class="inline-flex w-fit items-center gap-3 rounded-full border border-white/60 bg-white/70 px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm backdrop-blur">
          <span class="material-symbols-outlined text-[#2463eb]">shield_lock</span>
          Admin Control Center
        </div>

        <div class="max-w-2xl py-10 lg:py-0">
          <p class="mb-4 text-sm font-semibold uppercase tracking-[0.3em] text-[#2463eb]">Secure operations</p>
          <h1 class="text-4xl font-black tracking-tight text-slate-900 sm:text-5xl lg:text-6xl">
            Dang nhap de quan tri he thong tuyen dung AI
          </h1>
          <p class="mt-6 max-w-xl text-lg leading-8 text-slate-600">
            Mot diem dang nhap danh rieng cho quan tri vien, toi uu cho kiem soat nguoi dung, doanh nghiep va du lieu van hanh.
          </p>

          <div class="mt-10 grid gap-4 sm:grid-cols-3">
            <div class="rounded-2xl border border-white/60 bg-white/75 p-5 shadow-sm backdrop-blur">
              <p class="text-3xl font-black text-slate-900">24/7</p>
              <p class="mt-2 text-sm text-slate-500">Theo doi hoat dong va xu ly su co lien tuc.</p>
            </div>
            <div class="rounded-2xl border border-white/60 bg-white/75 p-5 shadow-sm backdrop-blur">
              <p class="text-3xl font-black text-slate-900">1 noi</p>
              <p class="mt-2 text-sm text-slate-500">Quan ly users, jobs, skills va thong ke tap trung.</p>
            </div>
            <div class="rounded-2xl border border-white/60 bg-white/75 p-5 shadow-sm backdrop-blur">
              <p class="text-3xl font-black text-slate-900">Bao mat</p>
              <p class="mt-2 text-sm text-slate-500">Luong dang nhap rieng cho admin de tach biet quyen truy cap.</p>
            </div>
          </div>
        </div>

        <div class="rounded-3xl border border-slate-200/70 bg-slate-900 px-6 py-5 text-white shadow-2xl shadow-slate-900/10">
          <div class="flex items-start gap-3">
            <span class="material-symbols-outlined mt-0.5 text-cyan-300">lan</span>
            <div>
              <p class="text-sm font-semibold text-slate-200">API cau hinh tach rieng domain</p>
              <p class="mt-1 break-all text-sm text-slate-400">Domain: {{ backendPreview }}</p>
              <p class="mt-1 break-all text-sm text-slate-400">Login URL: {{ endpointPreview }}</p>
            </div>
          </div>
        </div>
      </div>

      <div class="flex w-full items-center justify-center px-6 py-10 lg:w-[46%] lg:px-12">
        <div class="w-full max-w-md rounded-[2rem] border border-white/70 bg-white/85 p-8 shadow-2xl shadow-[#2463eb]/10 backdrop-blur-xl">
          <div class="mb-8 flex items-center gap-4">
            <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-900 text-white shadow-lg shadow-slate-900/20">
              <span class="material-symbols-outlined text-[28px]">admin_panel_settings</span>
            </div>
            <div>
              <p class="text-sm font-semibold uppercase tracking-[0.2em] text-slate-400">Admin login</p>
              <h2 class="text-2xl font-black text-slate-900">Chao mung quay lai</h2>
            </div>
          </div>

          <form class="space-y-5" @submit.prevent="handleSubmit">
            <div class="space-y-2">
              <label for="admin-email" class="block text-sm font-semibold text-slate-700">Email quan tri</label>
              <div class="relative">
                <span class="material-symbols-outlined pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">mail</span>
                <input
                  id="admin-email"
                  v-model="form.email"
                  type="email"
                  autocomplete="email"
                  placeholder="admin@example.com"
                  class="w-full rounded-2xl border border-slate-200 bg-slate-50 py-3.5 pl-12 pr-4 text-slate-900 outline-none transition focus:border-[#2463eb] focus:bg-white focus:ring-4 focus:ring-[#2463eb]/10"
                  required
                />
              </div>
            </div>

            <div class="space-y-2">
              <div class="flex items-center justify-between">
                <label for="admin-password" class="block text-sm font-semibold text-slate-700">Mat khau</label>
                <span class="text-xs font-medium text-slate-400">Phien quan tri bao mat cao</span>
              </div>
              <div class="relative">
                <span class="material-symbols-outlined pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">lock</span>
                <input
                  id="admin-password"
                  v-model="form.password"
                  type="password"
                  autocomplete="current-password"
                  placeholder="Nhap mat khau"
                  class="w-full rounded-2xl border border-slate-200 bg-slate-50 py-3.5 pl-12 pr-4 text-slate-900 outline-none transition focus:border-[#2463eb] focus:bg-white focus:ring-4 focus:ring-[#2463eb]/10"
                  required
                />
              </div>
            </div>

            <label class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
              <input
                v-model="form.remember"
                type="checkbox"
                class="h-4 w-4 rounded border-slate-300 text-[#2463eb] focus:ring-[#2463eb]/20"
              />
              Ghi nho phien dang nhap tren thiet bi nay
            </label>

            <div
              v-if="error"
              class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-600"
            >
              {{ error }}
            </div>

            <button
              type="submit"
              :disabled="isLoading"
              class="flex w-full items-center justify-center gap-2 rounded-2xl bg-slate-900 px-4 py-4 text-base font-bold text-white shadow-lg shadow-slate-900/15 transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-70"
            >
              <span class="material-symbols-outlined text-[20px]">{{ isLoading ? 'progress_activity' : 'login' }}</span>
              {{ isLoading ? 'Dang dang nhap...' : 'Dang nhap Admin' }}
            </button>
          </form>

          <div class="mt-8 rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-4 text-sm text-slate-500">
            Endpoint hien tai duoc goi tu frontend:
            <span class="mt-1 block break-all font-semibold text-slate-700">{{ endpointPreview }}</span>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>
