<script setup>
import { computed } from 'vue'
import {
  cvSkillLevelLabel,
  cvTemplateLabel,
  formatCvPeriod,
  getCvTemplateTheme,
} from '@/utils/profileCvBuilder'

const props = defineProps({
  profile: {
    type: Object,
    required: true,
  },
  owner: {
    type: Object,
    default: () => ({}),
  },
  compact: {
    type: Boolean,
    default: false,
  },
})

const degreeOptions = {
  trung_hoc: 'Trung học',
  trung_cap: 'Trung cấp',
  cao_dang: 'Cao đẳng',
  dai_hoc: 'Đại học',
  thac_si: 'Thạc sĩ',
  tien_si: 'Tiến sĩ',
  khac: 'Khác',
}

const theme = computed(() => getCvTemplateTheme(props.profile?.mau_cv || 'classic'))
const template = computed(() => props.profile?.mau_cv || 'classic')

const fullName = computed(() => props.owner?.ho_ten || 'Ứng viên')
const email = computed(() => props.owner?.email || 'Chưa cập nhật email')
const phone = computed(() => props.owner?.so_dien_thoai || 'Chưa cập nhật số điện thoại')
const title = computed(() => props.profile?.tieu_de_ho_so || 'Hồ sơ ứng tuyển trên hệ thống')
const objective = computed(() => props.profile?.muc_tieu_nghe_nghiep || 'Chưa cập nhật mục tiêu nghề nghiệp.')
const summary = computed(() => props.profile?.mo_ta_ban_than || 'Chưa cập nhật mô tả bản thân.')
const degreeLabel = computed(() => degreeOptions[props.profile?.trinh_do] || 'Chưa cập nhật')
const years = computed(() => `${props.profile?.kinh_nghiem_nam || 0} năm`)
const skills = computed(() => Array.isArray(props.profile?.ky_nang_json) ? props.profile.ky_nang_json.filter((item) => item?.ten) : [])
const experiences = computed(() => Array.isArray(props.profile?.kinh_nghiem_json) ? props.profile.kinh_nghiem_json.filter((item) => item?.vi_tri) : [])
const educations = computed(() => Array.isArray(props.profile?.hoc_van_json) ? props.profile.hoc_van_json.filter((item) => item?.truong) : [])
const projects = computed(() => Array.isArray(props.profile?.du_an_json) ? props.profile.du_an_json.filter((item) => item?.ten) : [])
const certificates = computed(() => Array.isArray(props.profile?.chung_chi_json) ? props.profile.chung_chi_json.filter((item) => item?.ten) : [])

const primaryCards = computed(() => [
  { label: 'Trình độ', value: degreeLabel.value },
  { label: 'Kinh nghiệm', value: years.value },
  { label: 'Template', value: cvTemplateLabel(template.value) },
])
</script>

<template>
  <div class="overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-sm" :style="{ color: theme.text }">
    <template v-if="template === 'classic'">
      <div class="px-6 py-6 text-white" :style="{ background: theme.hero }">
        <h3 class="text-3xl font-black">{{ fullName }}</h3>
        <p class="mt-2 text-sm font-medium opacity-90">{{ title }}</p>
        <div class="mt-4 flex flex-wrap gap-2 text-xs font-semibold">
          <span class="rounded-full px-3 py-1.5" :style="{ backgroundColor: 'rgba(255,255,255,0.18)' }">{{ email }}</span>
          <span class="rounded-full px-3 py-1.5" :style="{ backgroundColor: 'rgba(255,255,255,0.18)' }">{{ phone }}</span>
        </div>
      </div>
      <div class="grid grid-cols-1 gap-4 p-5 lg:grid-cols-2">
        <div class="rounded-3xl border p-4 lg:col-span-2" :style="{ borderColor: theme.accentSoft, backgroundColor: theme.panel }">
          <p class="text-xs font-semibold uppercase tracking-[0.24em]" :style="{ color: theme.accent }">Mục tiêu nghề nghiệp</p>
          <p class="mt-3 whitespace-pre-wrap text-sm leading-7">{{ objective }}</p>
        </div>
        <div class="rounded-3xl border p-4" :style="{ borderColor: theme.accentSoft }">
          <p class="text-xs font-semibold uppercase tracking-[0.24em]" :style="{ color: theme.accent }">Giới thiệu</p>
          <p class="mt-3 whitespace-pre-wrap text-sm leading-7">{{ summary }}</p>
        </div>
        <div class="rounded-3xl border p-4" :style="{ borderColor: theme.accentSoft }">
          <p class="text-xs font-semibold uppercase tracking-[0.24em]" :style="{ color: theme.accent }">Tóm tắt hồ sơ</p>
          <div class="mt-3 space-y-2 text-sm">
            <p v-for="card in primaryCards" :key="card.label">{{ card.label }}: <span class="font-semibold">{{ card.value }}</span></p>
          </div>
        </div>
        <div class="rounded-3xl border p-4 lg:col-span-2" :style="{ borderColor: theme.accentSoft }">
          <p class="text-xs font-semibold uppercase tracking-[0.24em]" :style="{ color: theme.accent }">Kỹ năng</p>
          <div class="mt-3 flex flex-wrap gap-2">
            <span v-for="(item, index) in skills" :key="`classic-skill-${index}`" class="rounded-full px-3 py-1.5 text-xs font-semibold" :style="{ backgroundColor: theme.panel, color: theme.accent }">
              {{ item.ten }}<span v-if="item.muc_do"> • {{ cvSkillLevelLabel(item.muc_do) }}</span>
            </span>
            <span v-if="!skills.length" class="text-sm text-slate-500">Chưa có kỹ năng nào được thêm.</span>
          </div>
        </div>
        <div class="rounded-3xl border p-4 lg:col-span-2" :style="{ borderColor: theme.accentSoft }">
          <p class="text-xs font-semibold uppercase tracking-[0.24em]" :style="{ color: theme.accent }">Kinh nghiệm nổi bật</p>
          <div v-if="experiences.length" class="mt-3 space-y-3">
            <div v-for="(item, index) in experiences.slice(0, compact ? 2 : 3)" :key="`classic-exp-${index}`" class="rounded-2xl p-4" :style="{ backgroundColor: theme.panel }">
              <div class="flex flex-col gap-2 md:flex-row md:justify-between">
                <div>
                  <p class="text-sm font-bold">{{ item.vi_tri }}</p>
                  <p class="text-sm text-slate-500">{{ item.cong_ty || 'Chưa cập nhật công ty' }}</p>
                </div>
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">{{ formatCvPeriod(item.bat_dau, item.ket_thuc) }}</p>
              </div>
              <p v-if="item.mo_ta" class="mt-3 text-sm leading-7 text-slate-600">{{ item.mo_ta }}</p>
            </div>
          </div>
          <p v-else class="mt-3 text-sm text-slate-500">Chưa có kinh nghiệm nào được thêm.</p>
        </div>
      </div>
    </template>

    <template v-else-if="template === 'minimal'">
      <div class="border-b border-slate-200 px-6 py-6">
        <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
          <div>
            <h3 class="text-3xl font-black">{{ fullName }}</h3>
            <p class="mt-2 text-sm font-semibold" :style="{ color: theme.accent }">{{ title }}</p>
          </div>
          <div class="space-y-1 text-sm text-slate-500">
            <p>{{ email }}</p>
            <p>{{ phone }}</p>
          </div>
        </div>
      </div>
      <div class="space-y-6 p-6">
        <section class="border-l-4 pl-4" :style="{ borderColor: theme.accent }">
          <p class="text-xs font-semibold uppercase tracking-[0.24em]" :style="{ color: theme.accent }">Mục tiêu nghề nghiệp</p>
          <p class="mt-3 whitespace-pre-wrap text-sm leading-7">{{ objective }}</p>
        </section>
        <section class="grid grid-cols-1 gap-5 lg:grid-cols-[240px_minmax(0,1fr)]">
          <div class="space-y-5">
            <div>
              <p class="text-xs font-semibold uppercase tracking-[0.24em]" :style="{ color: theme.accent }">Tóm tắt</p>
              <div class="mt-3 space-y-2 text-sm">
                <p v-for="card in primaryCards" :key="card.label">{{ card.label }}: <span class="font-semibold">{{ card.value }}</span></p>
              </div>
            </div>
            <div>
              <p class="text-xs font-semibold uppercase tracking-[0.24em]" :style="{ color: theme.accent }">Kỹ năng</p>
              <div class="mt-3 flex flex-wrap gap-2">
                <span v-for="(item, index) in skills" :key="`minimal-skill-${index}`" class="rounded-full bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-700">
                  {{ item.ten }}
                </span>
              </div>
            </div>
          </div>
          <div class="space-y-5">
            <div>
              <p class="text-xs font-semibold uppercase tracking-[0.24em]" :style="{ color: theme.accent }">Giới thiệu</p>
              <p class="mt-3 whitespace-pre-wrap text-sm leading-7">{{ summary }}</p>
            </div>
            <div>
              <p class="text-xs font-semibold uppercase tracking-[0.24em]" :style="{ color: theme.accent }">Kinh nghiệm</p>
              <div v-if="experiences.length" class="mt-3 space-y-4">
                <div v-for="(item, index) in experiences.slice(0, compact ? 2 : 3)" :key="`minimal-exp-${index}`" class="border-t border-slate-200 pt-4 first:border-0 first:pt-0">
                  <div class="flex flex-col gap-1">
                    <p class="text-sm font-bold">{{ item.vi_tri }}</p>
                    <p class="text-sm text-slate-500">{{ item.cong_ty || 'Chưa cập nhật công ty' }}</p>
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">{{ formatCvPeriod(item.bat_dau, item.ket_thuc) }}</p>
                  </div>
                  <p v-if="item.mo_ta" class="mt-2 text-sm leading-7 text-slate-600">{{ item.mo_ta }}</p>
                </div>
              </div>
            </div>
          </div>
        </section>
      </div>
    </template>

    <template v-else-if="template === 'executive'">
      <div class="grid grid-cols-1 lg:grid-cols-[280px_minmax(0,1fr)]">
        <div class="px-6 py-6 text-white" :style="{ background: theme.hero }">
          <h3 class="text-3xl font-black">{{ fullName }}</h3>
          <p class="mt-2 text-sm font-semibold opacity-90">{{ title }}</p>
          <div class="mt-6 space-y-4">
            <div>
              <p class="text-xs font-semibold uppercase tracking-[0.24em] text-white/70">Liên hệ</p>
              <div class="mt-2 space-y-2 text-sm">
                <p>{{ email }}</p>
                <p>{{ phone }}</p>
              </div>
            </div>
            <div>
              <p class="text-xs font-semibold uppercase tracking-[0.24em] text-white/70">Hồ sơ</p>
              <div class="mt-2 space-y-2 text-sm">
                <p v-for="card in primaryCards" :key="card.label">{{ card.label }}: {{ card.value }}</p>
              </div>
            </div>
            <div>
              <p class="text-xs font-semibold uppercase tracking-[0.24em] text-white/70">Kỹ năng chính</p>
              <div class="mt-3 flex flex-wrap gap-2">
                <span v-for="(item, index) in skills.slice(0, compact ? 5 : 8)" :key="`executive-skill-${index}`" class="rounded-full bg-white/15 px-3 py-1.5 text-xs font-semibold">
                  {{ item.ten }}
                </span>
              </div>
            </div>
          </div>
        </div>
        <div class="space-y-5 p-6">
          <section class="rounded-3xl border p-4" :style="{ borderColor: theme.accentSoft, backgroundColor: theme.panel }">
            <p class="text-xs font-semibold uppercase tracking-[0.24em]" :style="{ color: theme.accent }">Giới thiệu tổng quan</p>
            <p class="mt-3 whitespace-pre-wrap text-sm leading-7">{{ summary }}</p>
          </section>
          <section class="rounded-3xl border p-4" :style="{ borderColor: theme.accentSoft }">
            <p class="text-xs font-semibold uppercase tracking-[0.24em]" :style="{ color: theme.accent }">Mục tiêu nghề nghiệp</p>
            <p class="mt-3 whitespace-pre-wrap text-sm leading-7">{{ objective }}</p>
          </section>
          <section class="rounded-3xl border p-4" :style="{ borderColor: theme.accentSoft }">
            <p class="text-xs font-semibold uppercase tracking-[0.24em]" :style="{ color: theme.accent }">Kinh nghiệm quản trị / chuyên môn</p>
            <div v-if="experiences.length" class="mt-3 space-y-4">
              <div v-for="(item, index) in experiences.slice(0, compact ? 2 : 4)" :key="`executive-exp-${index}`" class="border-t border-slate-200 pt-4 first:border-0 first:pt-0">
                <div class="flex flex-col gap-2 md:flex-row md:justify-between">
                  <div>
                    <p class="text-sm font-bold">{{ item.vi_tri }}</p>
                    <p class="text-sm text-slate-500">{{ item.cong_ty || 'Chưa cập nhật công ty' }}</p>
                  </div>
                  <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">{{ formatCvPeriod(item.bat_dau, item.ket_thuc) }}</p>
                </div>
                <p v-if="item.mo_ta" class="mt-2 text-sm leading-7 text-slate-600">{{ item.mo_ta }}</p>
              </div>
            </div>
          </section>
        </div>
      </div>
    </template>

    <template v-else-if="template === 'modern'">
      <div class="grid grid-cols-1 gap-0 lg:grid-cols-[minmax(0,1fr)_220px]">
        <div>
          <div class="px-6 py-6 text-white" :style="{ background: theme.hero }">
            <h3 class="text-3xl font-black">{{ fullName }}</h3>
            <p class="mt-2 text-sm font-medium opacity-90">{{ title }}</p>
            <p class="mt-4 max-w-2xl whitespace-pre-wrap text-sm leading-7 text-white/85">{{ summary }}</p>
          </div>
          <div class="grid grid-cols-1 gap-4 p-5">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
              <div v-for="card in primaryCards" :key="card.label" class="rounded-3xl p-4" :style="{ backgroundColor: theme.panel }">
                <p class="text-xs font-semibold uppercase tracking-[0.2em]" :style="{ color: theme.accent }">{{ card.label }}</p>
                <p class="mt-2 text-sm font-bold">{{ card.value }}</p>
              </div>
            </div>
            <div class="rounded-3xl border p-4" :style="{ borderColor: theme.accentSoft }">
              <p class="text-xs font-semibold uppercase tracking-[0.24em]" :style="{ color: theme.accent }">Mục tiêu nghề nghiệp</p>
              <p class="mt-3 whitespace-pre-wrap text-sm leading-7">{{ objective }}</p>
            </div>
            <div class="rounded-3xl border p-4" :style="{ borderColor: theme.accentSoft }">
              <p class="text-xs font-semibold uppercase tracking-[0.24em]" :style="{ color: theme.accent }">Kinh nghiệm nổi bật</p>
              <div v-if="experiences.length" class="mt-3 grid grid-cols-1 gap-3">
                <div v-for="(item, index) in experiences.slice(0, compact ? 2 : 3)" :key="`modern-exp-${index}`" class="rounded-2xl p-4" :style="{ backgroundColor: theme.panel }">
                  <div class="flex flex-col gap-2 md:flex-row md:justify-between">
                    <div>
                      <p class="text-sm font-bold">{{ item.vi_tri }}</p>
                      <p class="text-sm text-slate-500">{{ item.cong_ty || 'Chưa cập nhật công ty' }}</p>
                    </div>
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">{{ formatCvPeriod(item.bat_dau, item.ket_thuc) }}</p>
                  </div>
                  <p v-if="item.mo_ta" class="mt-2 text-sm leading-7 text-slate-600">{{ item.mo_ta }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="border-t border-slate-200 p-5 lg:border-l lg:border-t-0">
          <div class="space-y-5">
            <div>
              <p class="text-xs font-semibold uppercase tracking-[0.24em]" :style="{ color: theme.accent }">Liên hệ</p>
              <div class="mt-3 space-y-2 text-sm text-slate-600">
                <p>{{ email }}</p>
                <p>{{ phone }}</p>
              </div>
            </div>
            <div>
              <p class="text-xs font-semibold uppercase tracking-[0.24em]" :style="{ color: theme.accent }">Kỹ năng</p>
              <div class="mt-3 flex flex-wrap gap-2">
                <span v-for="(item, index) in skills" :key="`modern-skill-${index}`" class="rounded-full px-3 py-1.5 text-xs font-semibold" :style="{ backgroundColor: theme.panel, color: theme.accent }">
                  {{ item.ten }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </template>

    <template v-else-if="template === 'creative'">
      <div class="grid grid-cols-1 lg:grid-cols-[320px_minmax(0,1fr)]">
        <div class="p-6 text-white" :style="{ background: theme.hero }">
          <div class="rounded-[28px] bg-white/10 p-5 backdrop-blur">
            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-white/70">Creative CV</p>
            <h3 class="mt-3 text-3xl font-black">{{ fullName }}</h3>
            <p class="mt-2 text-sm font-semibold opacity-90">{{ title }}</p>
            <div class="mt-5 space-y-2 text-sm">
              <p>{{ email }}</p>
              <p>{{ phone }}</p>
            </div>
          </div>
          <div class="mt-5 rounded-[28px] bg-white/10 p-5 backdrop-blur">
            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-white/70">Kỹ năng mũi nhọn</p>
            <div class="mt-3 flex flex-wrap gap-2">
              <span v-for="(item, index) in skills.slice(0, compact ? 6 : 9)" :key="`creative-skill-${index}`" class="rounded-full bg-white/15 px-3 py-1.5 text-xs font-semibold">
                {{ item.ten }}
              </span>
            </div>
          </div>
        </div>
        <div class="space-y-5 p-6">
          <section class="rounded-[28px] p-5" :style="{ backgroundColor: theme.panel }">
            <p class="text-xs font-semibold uppercase tracking-[0.24em]" :style="{ color: theme.accent }">Mục tiêu nghề nghiệp</p>
            <p class="mt-3 whitespace-pre-wrap text-sm leading-7">{{ objective }}</p>
          </section>
          <section class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div class="rounded-3xl border p-4" :style="{ borderColor: theme.accentSoft }">
              <p class="text-xs font-semibold uppercase tracking-[0.24em]" :style="{ color: theme.accent }">Tóm tắt</p>
              <p class="mt-3 whitespace-pre-wrap text-sm leading-7">{{ summary }}</p>
            </div>
            <div class="rounded-3xl border p-4" :style="{ borderColor: theme.accentSoft }">
              <p class="text-xs font-semibold uppercase tracking-[0.24em]" :style="{ color: theme.accent }">Thông tin nhanh</p>
              <div class="mt-3 space-y-2 text-sm">
                <p v-for="card in primaryCards" :key="card.label">{{ card.label }}: <span class="font-semibold">{{ card.value }}</span></p>
              </div>
            </div>
          </section>
          <section class="rounded-3xl border p-4" :style="{ borderColor: theme.accentSoft }">
            <p class="text-xs font-semibold uppercase tracking-[0.24em]" :style="{ color: theme.accent }">Kinh nghiệm / Case study</p>
            <div v-if="experiences.length" class="mt-3 space-y-4">
              <div v-for="(item, index) in experiences.slice(0, compact ? 2 : 3)" :key="`creative-exp-${index}`" class="rounded-2xl border border-white/70 bg-white px-4 py-4 shadow-sm">
                <div class="flex flex-col gap-2 md:flex-row md:justify-between">
                  <div>
                    <p class="text-sm font-bold">{{ item.vi_tri }}</p>
                    <p class="text-sm text-slate-500">{{ item.cong_ty || 'Chưa cập nhật công ty' }}</p>
                  </div>
                  <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">{{ formatCvPeriod(item.bat_dau, item.ket_thuc) }}</p>
                </div>
                <p v-if="item.mo_ta" class="mt-2 text-sm leading-7 text-slate-600">{{ item.mo_ta }}</p>
              </div>
            </div>
          </section>
        </div>
      </div>
    </template>

    <template v-else>
      <div class="border-b border-slate-200 px-5 py-4">
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
          <div>
            <h3 class="text-2xl font-black">{{ fullName }}</h3>
            <p class="mt-1 text-sm font-semibold" :style="{ color: theme.accent }">{{ title }}</p>
          </div>
          <div class="grid grid-cols-1 gap-1 text-right text-xs text-slate-500">
            <span>{{ email }}</span>
            <span>{{ phone }}</span>
          </div>
        </div>
      </div>
      <div class="grid grid-cols-1 gap-4 p-5 lg:grid-cols-[220px_minmax(0,1fr)]">
        <div class="space-y-4">
          <div class="rounded-3xl p-4" :style="{ backgroundColor: theme.panel }">
            <p class="text-xs font-semibold uppercase tracking-[0.24em]" :style="{ color: theme.accent }">Snapshot</p>
            <div class="mt-3 space-y-2 text-sm">
              <p v-for="card in primaryCards" :key="card.label">{{ card.label }}: <span class="font-semibold">{{ card.value }}</span></p>
            </div>
          </div>
          <div class="rounded-3xl p-4" :style="{ backgroundColor: theme.panel }">
            <p class="text-xs font-semibold uppercase tracking-[0.24em]" :style="{ color: theme.accent }">Kỹ năng</p>
            <div class="mt-3 flex flex-wrap gap-2">
              <span v-for="(item, index) in skills.slice(0, compact ? 5 : 8)" :key="`compact-skill-${index}`" class="rounded-full bg-white px-3 py-1.5 text-xs font-semibold shadow-sm">
                {{ item.ten }}
              </span>
            </div>
          </div>
        </div>
        <div class="space-y-4">
          <div class="rounded-3xl border p-4" :style="{ borderColor: theme.accentSoft }">
            <p class="text-xs font-semibold uppercase tracking-[0.24em]" :style="{ color: theme.accent }">Mục tiêu nghề nghiệp</p>
            <p class="mt-3 whitespace-pre-wrap text-sm leading-7">{{ objective }}</p>
          </div>
          <div class="rounded-3xl border p-4" :style="{ borderColor: theme.accentSoft }">
            <p class="text-xs font-semibold uppercase tracking-[0.24em]" :style="{ color: theme.accent }">Giới thiệu</p>
            <p class="mt-3 whitespace-pre-wrap text-sm leading-7">{{ summary }}</p>
          </div>
          <div class="rounded-3xl border p-4" :style="{ borderColor: theme.accentSoft }">
            <p class="text-xs font-semibold uppercase tracking-[0.24em]" :style="{ color: theme.accent }">Kinh nghiệm nổi bật</p>
            <div v-if="experiences.length" class="mt-3 space-y-3">
              <div v-for="(item, index) in experiences.slice(0, compact ? 2 : 3)" :key="`compact-exp-${index}`" class="rounded-2xl p-4" :style="{ backgroundColor: theme.panel }">
                <div class="flex flex-col gap-1">
                  <p class="text-sm font-bold">{{ item.vi_tri }}</p>
                  <p class="text-sm text-slate-500">{{ item.cong_ty || 'Chưa cập nhật công ty' }}</p>
                  <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">{{ formatCvPeriod(item.bat_dau, item.ket_thuc) }}</p>
                </div>
                <p v-if="item.mo_ta" class="mt-2 text-sm leading-7 text-slate-600">{{ item.mo_ta }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>
