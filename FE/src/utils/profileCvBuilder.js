export const cvTemplateOptions = [
  { value: 'classic', label: 'Classic', description: 'Cân bằng, dễ đọc, hợp đa số ngành nghề.' },
  { value: 'minimal', label: 'Minimal', description: 'Tinh gọn, ưu tiên nội dung và độ sạch.' },
  { value: 'executive', label: 'Executive', description: 'Trang trọng, hợp khối quản trị và tài chính.' },
  { value: 'modern', label: 'Modern', description: 'Hiện đại, rõ kỹ năng và kinh nghiệm.' },
  { value: 'creative', label: 'Creative', description: 'Nổi bật hơn, hợp truyền thông và thiết kế.' },
  { value: 'compact', label: 'Compact', description: 'Cô đọng, hợp hồ sơ cần quét nhanh.' },
]

export const cvStylePreferenceOptions = [
  { value: 'balanced', label: 'Cân bằng' },
  { value: 'formal', label: 'Trang trọng' },
  { value: 'creative', label: 'Nổi bật' },
  { value: 'compact', label: 'Cô đọng' },
]

export const cvSkillLevelOptions = [
  { value: 'co_ban', label: 'Cơ bản' },
  { value: 'kha', label: 'Khá' },
  { value: 'tot', label: 'Tốt' },
  { value: 'chuyen_sau', label: 'Chuyên sâu' },
]

const asArray = (value) => Array.isArray(value) ? value.filter(Boolean) : []

export const hasBuilderCv = (profile) => {
  if (!profile) return false
  if (profile.nguon_ho_so && profile.nguon_ho_so !== 'upload') return true

  return [
    profile.ky_nang_json,
    profile.kinh_nghiem_json,
    profile.hoc_van_json,
    profile.du_an_json,
    profile.chung_chi_json,
  ].some((items) => asArray(items).length > 0)
}

export const cvTemplateLabel = (value) =>
  cvTemplateOptions.find((item) => item.value === value)?.label || 'Classic'

const cvTemplateThemes = {
  classic: {
    accent: '#2463eb',
    accentSoft: '#dbeafe',
    panel: '#eff6ff',
    text: '#0f172a',
    hero: 'linear-gradient(135deg, #2463eb 0%, #60a5fa 100%)',
  },
  minimal: {
    accent: '#0f172a',
    accentSoft: '#e2e8f0',
    panel: '#f8fafc',
    text: '#0f172a',
    hero: 'linear-gradient(135deg, #0f172a 0%, #334155 100%)',
  },
  executive: {
    accent: '#7c3aed',
    accentSoft: '#ede9fe',
    panel: '#f5f3ff',
    text: '#1e1b4b',
    hero: 'linear-gradient(135deg, #6d28d9 0%, #a78bfa 100%)',
  },
  modern: {
    accent: '#0f766e',
    accentSoft: '#ccfbf1',
    panel: '#f0fdfa',
    text: '#134e4a',
    hero: 'linear-gradient(135deg, #0f766e 0%, #2dd4bf 100%)',
  },
  creative: {
    accent: '#db2777',
    accentSoft: '#fce7f3',
    panel: '#fff1f2',
    text: '#831843',
    hero: 'linear-gradient(135deg, #db2777 0%, #fb7185 100%)',
  },
  compact: {
    accent: '#c2410c',
    accentSoft: '#ffedd5',
    panel: '#fff7ed',
    text: '#7c2d12',
    hero: 'linear-gradient(135deg, #c2410c 0%, #fb923c 100%)',
  },
}

export const getCvTemplateTheme = (value) => cvTemplateThemes[value] || cvTemplateThemes.classic

export const cvSkillLevelLabel = (value) =>
  cvSkillLevelOptions.find((item) => item.value === value)?.label || 'Tự đánh giá'

export const formatCvPeriod = (start, end) => {
  const from = String(start || '').trim()
  const to = String(end || '').trim()

  if (from && to) return `${from} - ${to}`
  if (from) return `${from} - Hiện tại`
  if (to) return to
  return 'Chưa cập nhật'
}

const escapeHtml = (value) =>
  String(value || '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;')

const renderList = (items, renderItem) => asArray(items).map(renderItem).join('')

export const buildProfileCvPrintHtml = ({ profile, owner }) => {
  const fullName = owner?.ho_ten || 'Ứng viên'
  const email = owner?.email || 'Chưa cập nhật email'
  const phone = owner?.so_dien_thoai || 'Chưa cập nhật số điện thoại'
  const template = profile?.mau_cv || 'classic'
  const theme = getCvTemplateTheme(template)
  const accent = theme.accent

  return `<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8" />
  <title>${escapeHtml(profile?.tieu_de_ho_so || 'CV hệ thống')}</title>
  <style>
    * { box-sizing: border-box; }
    body { font-family: Arial, sans-serif; margin: 0; color: #0f172a; background: #eef2ff; }
    .page { max-width: 960px; margin: 24px auto; background: white; border-radius: 24px; overflow: hidden; box-shadow: 0 10px 35px rgba(15,23,42,.12); }
    .hero { padding: 32px; background: ${theme.hero}; color: white; }
    .hero h1 { margin: 0; font-size: 34px; }
    .hero p { margin: 8px 0 0; opacity: .92; }
    .meta { display: flex; flex-wrap: wrap; gap: 12px; margin-top: 16px; font-size: 14px; }
    .meta span { background: rgba(255,255,255,.16); padding: 8px 12px; border-radius: 999px; }
    .content { padding: 28px 32px 36px; }
    .grid { display: grid; gap: 18px; grid-template-columns: 1fr 1fr; }
    .section { border: 1px solid #e2e8f0; border-radius: 18px; padding: 18px; background: #fff; }
    .section.full { grid-column: 1 / -1; }
    .section h2 { margin: 0 0 14px; color: ${accent}; font-size: 16px; letter-spacing: .06em; text-transform: uppercase; }
    .item { border-top: 1px solid #e2e8f0; padding-top: 12px; margin-top: 12px; }
    .item:first-child { border-top: 0; padding-top: 0; margin-top: 0; }
    .row { display: flex; justify-content: space-between; gap: 12px; align-items: baseline; }
    .title { font-weight: 700; }
    .sub { color: #475569; margin-top: 4px; }
    .chips { display: flex; flex-wrap: wrap; gap: 8px; }
    .chip { padding: 7px 12px; border-radius: 999px; background: ${theme.panel}; color: ${accent}; font-size: 13px; font-weight: 600; }
    .muted { color: #64748b; }
    .text { white-space: pre-wrap; line-height: 1.7; }
    @media print {
      body { background: white; }
      .page { margin: 0; box-shadow: none; max-width: none; border-radius: 0; }
    }
  </style>
</head>
<body>
  <div class="page">
    <div class="hero">
      <h1>${escapeHtml(fullName)}</h1>
      <p>${escapeHtml(profile?.tieu_de_ho_so || 'Hồ sơ ứng tuyển')}</p>
      <div class="meta">
        <span>${escapeHtml(email)}</span>
        <span>${escapeHtml(phone)}</span>
        <span>${escapeHtml(cvTemplateLabel(template))}</span>
      </div>
    </div>
    <div class="content">
      <div class="grid">
        <div class="section full">
          <h2>Mục tiêu nghề nghiệp</h2>
          <div class="text">${escapeHtml(profile?.muc_tieu_nghe_nghiep || 'Chưa cập nhật mục tiêu nghề nghiệp.')}</div>
        </div>
        <div class="section">
          <h2>Giới thiệu</h2>
          <div class="text">${escapeHtml(profile?.mo_ta_ban_than || 'Chưa cập nhật mô tả bản thân.')}</div>
        </div>
        <div class="section">
          <h2>Tóm tắt hồ sơ</h2>
          <div class="text">Trình độ: ${escapeHtml(profile?.trinh_do || 'Chưa cập nhật')}\nKinh nghiệm: ${escapeHtml(String(profile?.kinh_nghiem_nam ?? 0))} năm\nNguồn CV: ${escapeHtml(profile?.nguon_ho_so || 'builder')}</div>
        </div>
        <div class="section full">
          <h2>Kỹ năng</h2>
          <div class="chips">
            ${renderList(profile?.ky_nang_json, (item) => `<span class="chip">${escapeHtml(item?.ten || '')}${item?.muc_do ? ` • ${escapeHtml(cvSkillLevelLabel(item.muc_do))}` : ''}</span>`)}
          </div>
          ${!asArray(profile?.ky_nang_json).length ? '<p class="muted">Chưa cập nhật kỹ năng.</p>' : ''}
        </div>
        <div class="section full">
          <h2>Kinh nghiệm</h2>
          ${renderList(profile?.kinh_nghiem_json, (item) => `
            <div class="item">
              <div class="row"><div class="title">${escapeHtml(item?.vi_tri || '')}</div><div class="muted">${escapeHtml(formatCvPeriod(item?.bat_dau, item?.ket_thuc))}</div></div>
              <div class="sub">${escapeHtml(item?.cong_ty || 'Chưa cập nhật công ty')}</div>
              <div class="text">${escapeHtml(item?.mo_ta || '')}</div>
            </div>
          `)}
          ${!asArray(profile?.kinh_nghiem_json).length ? '<p class="muted">Chưa cập nhật kinh nghiệm.</p>' : ''}
        </div>
        <div class="section">
          <h2>Học vấn</h2>
          ${renderList(profile?.hoc_van_json, (item) => `
            <div class="item">
              <div class="row"><div class="title">${escapeHtml(item?.truong || '')}</div><div class="muted">${escapeHtml(formatCvPeriod(item?.bat_dau, item?.ket_thuc))}</div></div>
              <div class="sub">${escapeHtml(item?.chuyen_nganh || 'Chưa cập nhật chuyên ngành')}</div>
              <div class="text">${escapeHtml(item?.mo_ta || '')}</div>
            </div>
          `)}
          ${!asArray(profile?.hoc_van_json).length ? '<p class="muted">Chưa cập nhật học vấn.</p>' : ''}
        </div>
        <div class="section">
          <h2>Chứng chỉ</h2>
          ${renderList(profile?.chung_chi_json, (item) => `
            <div class="item">
              <div class="row"><div class="title">${escapeHtml(item?.ten || '')}</div><div class="muted">${escapeHtml(item?.nam || '')}</div></div>
              <div class="sub">${escapeHtml(item?.don_vi || 'Chưa cập nhật đơn vị cấp')}</div>
            </div>
          `)}
          ${!asArray(profile?.chung_chi_json).length ? '<p class="muted">Chưa cập nhật chứng chỉ.</p>' : ''}
        </div>
        <div class="section full">
          <h2>Dự án</h2>
          ${renderList(profile?.du_an_json, (item) => `
            <div class="item">
              <div class="row"><div class="title">${escapeHtml(item?.ten || '')}</div><div class="muted">${escapeHtml(item?.vai_tro || '')}</div></div>
              <div class="sub">${escapeHtml(item?.cong_nghe || 'Chưa cập nhật công nghệ')}</div>
              <div class="text">${escapeHtml(item?.mo_ta || '')}</div>
              ${item?.link ? `<div class="sub">${escapeHtml(item.link)}</div>` : ''}
            </div>
          `)}
          ${!asArray(profile?.du_an_json).length ? '<p class="muted">Chưa cập nhật dự án.</p>' : ''}
        </div>
      </div>
    </div>
  </div>
  <script>window.onload = () => window.print();</script>
</body>
</html>`
}

const industryPresetMatchers = [
  {
    keywords: ['cntt', 'công nghệ', 'it', 'software', 'developer', 'backend', 'frontend', 'mobile', 'data', 'ai'],
    templates: {
      balanced: 'modern',
      formal: 'executive',
      creative: 'modern',
      compact: 'compact',
    },
    suggestedTitle: 'CV Kỹ sư phần mềm',
    suggestedObjective:
      'Tập trung vào kỹ năng công nghệ, dự án thực tế và khả năng triển khai sản phẩm. Ưu tiên thể hiện stack chính, kết quả đo lường được và khả năng cộng tác.',
    suggestedSkills: ['Phân tích yêu cầu', 'Làm việc nhóm', 'Giải quyết vấn đề'],
  },
  {
    keywords: ['thiết kế', 'design', 'ui', 'ux', 'creative', 'multimedia', 'branding'],
    templates: {
      balanced: 'creative',
      formal: 'minimal',
      creative: 'creative',
      compact: 'modern',
    },
    suggestedTitle: 'CV Thiết kế sản phẩm số',
    suggestedObjective:
      'Nhấn mạnh tư duy thẩm mỹ, khả năng giải quyết bài toán người dùng và các sản phẩm hoặc case study đã triển khai.',
    suggestedSkills: ['Wireframing', 'User research', 'Design system'],
  },
  {
    keywords: ['marketing', 'truyền thông', 'content', 'seo', 'digital', 'social'],
    templates: {
      balanced: 'creative',
      formal: 'classic',
      creative: 'creative',
      compact: 'compact',
    },
    suggestedTitle: 'CV Marketing tổng hợp',
    suggestedObjective:
      'Tập trung vào tăng trưởng, chiến dịch đã triển khai, chỉ số hiệu quả và khả năng phối hợp đa kênh.',
    suggestedSkills: ['Lập kế hoạch chiến dịch', 'Phân tích dữ liệu', 'Content strategy'],
  },
  {
    keywords: ['tài chính', 'kế toán', 'finance', 'accounting', 'kiểm toán', 'audit', 'ngân hàng'],
    templates: {
      balanced: 'executive',
      formal: 'executive',
      creative: 'classic',
      compact: 'compact',
    },
    suggestedTitle: 'CV Tài chính - Kế toán',
    suggestedObjective:
      'Ưu tiên độ chính xác, tư duy kiểm soát và kinh nghiệm xử lý số liệu, báo cáo hoặc chuẩn tuân thủ.',
    suggestedSkills: ['Phân tích số liệu', 'Báo cáo tài chính', 'Kiểm soát rủi ro'],
  },
  {
    keywords: ['nhân sự', 'recruitment', 'hr', 'hành chính', 'operations', 'vận hành', 'customer service'],
    templates: {
      balanced: 'classic',
      formal: 'executive',
      creative: 'modern',
      compact: 'compact',
    },
    suggestedTitle: 'CV Vận hành - Nhân sự',
    suggestedObjective:
      'Làm rõ khả năng tổ chức, phối hợp và theo dõi quy trình vận hành hoặc tuyển dụng đầu cuối.',
    suggestedSkills: ['Điều phối công việc', 'Giao tiếp nội bộ', 'Quản lý quy trình'],
  },
]

const normalizeIndustryName = (value) =>
  String(value || '')
    .toLowerCase()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')

const resolveIndustryPreset = (industryName) => {
  const normalized = normalizeIndustryName(industryName)
  return industryPresetMatchers.find((item) => item.keywords.some((keyword) => normalized.includes(keyword))) || null
}

export const suggestCvTemplate = (industryName, preference = 'balanced') => {
  const preset = resolveIndustryPreset(industryName)
  if (!preset) {
    const fallback = {
      balanced: 'classic',
      formal: 'executive',
      creative: 'creative',
      compact: 'compact',
    }
    return fallback[preference] || fallback.balanced
  }

  return preset.templates?.[preference] || preset.templates?.balanced || 'classic'
}

export const buildCvIndustryPreset = (industryName, preference = 'balanced') => {
  const preset = resolveIndustryPreset(industryName)
  const template = suggestCvTemplate(industryName, preference)

  return {
    template,
    suggestedTitle: preset?.suggestedTitle || 'CV Ứng tuyển chuyên nghiệp',
    suggestedObjective:
      preset?.suggestedObjective ||
      'Tập trung vào điểm mạnh cốt lõi, kết quả nổi bật và mức độ phù hợp với vị trí mục tiêu.',
    suggestedSkills: preset?.suggestedSkills || ['Giao tiếp', 'Làm việc nhóm', 'Chủ động'],
  }
}
