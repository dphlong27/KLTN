const APP_TIMEZONE = 'Asia/Ho_Chi_Minh'

const toDate = (value) => {
  if (!value) return null
  const parsed = new Date(value)
  return Number.isNaN(parsed.getTime()) ? null : parsed
}

const buildFormatter = (options = {}) =>
  new Intl.DateTimeFormat('vi-VN', {
    timeZone: APP_TIMEZONE,
    ...options,
  })

export const formatDateTimeVN = (value, fallback = 'Chưa cập nhật') => {
  const date = toDate(value)
  if (!date) return fallback

  return buildFormatter({
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  }).format(date)
}

export const formatDateVN = (value, fallback = 'Chưa cập nhật') => {
  const date = toDate(value)
  if (!date) return fallback

  return buildFormatter({
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  }).format(date)
}

export const toDateTimeLocalInputVN = (value) => {
  const date = toDate(value)
  if (!date) return ''

  const parts = new Intl.DateTimeFormat('en-CA', {
    timeZone: APP_TIMEZONE,
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
    hour12: false,
  }).formatToParts(date)

  const map = Object.fromEntries(parts.map((part) => [part.type, part.value]))
  return `${map.year}-${map.month}-${map.day}T${map.hour}:${map.minute}`
}
