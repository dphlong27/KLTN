export const APPLICATION_STATUS = {
  PENDING: 0,
  REVIEWED: 1,
  INTERVIEW_SCHEDULED: 2,
  INTERVIEW_PASSED: 3,
  HIRED: 4,
  REJECTED: 5,
  OFFER_SENT: 6,
  ONBOARDED: 7,
  OFFER_DECLINED: 8,
}

export const FINAL_APPLICATION_STATUSES = [
  APPLICATION_STATUS.REJECTED,
  APPLICATION_STATUS.ONBOARDED,
  APPLICATION_STATUS.OFFER_DECLINED,
]

export const APPLICATION_STATUS_OPTIONS = [
  { value: APPLICATION_STATUS.PENDING, label: 'Đang chờ' },
  { value: APPLICATION_STATUS.REVIEWED, label: 'Đã xem' },
  { value: APPLICATION_STATUS.INTERVIEW_SCHEDULED, label: 'Đã hẹn phỏng vấn' },
  { value: APPLICATION_STATUS.INTERVIEW_PASSED, label: 'Qua phỏng vấn' },
  { value: APPLICATION_STATUS.HIRED, label: 'Trúng tuyển' },
  { value: APPLICATION_STATUS.REJECTED, label: 'Từ chối' },
  { value: APPLICATION_STATUS.OFFER_SENT, label: 'Đã gửi offer' },
  { value: APPLICATION_STATUS.ONBOARDED, label: 'Đã nhận việc' },
  { value: APPLICATION_STATUS.OFFER_DECLINED, label: 'Từ chối offer' },
]

export const getApplicationStatusMeta = (status) => {
  switch (Number(status)) {
    case APPLICATION_STATUS.REVIEWED:
      return {
        label: 'Đã xem',
        classes: 'bg-sky-500/10 text-sky-600 dark:text-sky-300',
        dot: 'bg-sky-500',
      }
    case APPLICATION_STATUS.INTERVIEW_SCHEDULED:
      return {
        label: 'Đã hẹn phỏng vấn',
        classes: 'bg-violet-500/10 text-violet-700 dark:text-violet-300',
        dot: 'bg-violet-500',
      }
    case APPLICATION_STATUS.INTERVIEW_PASSED:
      return {
        label: 'Qua phỏng vấn',
        classes: 'bg-indigo-500/10 text-indigo-700 dark:text-indigo-300',
        dot: 'bg-indigo-500',
      }
    case APPLICATION_STATUS.HIRED:
      return {
        label: 'Trúng tuyển',
        classes: 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300',
        dot: 'bg-emerald-500',
      }
    case APPLICATION_STATUS.OFFER_SENT:
      return {
        label: 'Đã gửi offer',
        classes: 'bg-teal-500/10 text-teal-700 dark:text-teal-300',
        dot: 'bg-teal-500',
      }
    case APPLICATION_STATUS.ONBOARDED:
      return {
        label: 'Đã nhận việc',
        classes: 'bg-green-600/10 text-green-700 dark:text-green-300',
        dot: 'bg-green-600',
      }
    case APPLICATION_STATUS.OFFER_DECLINED:
      return {
        label: 'Từ chối offer',
        classes: 'bg-orange-500/10 text-orange-700 dark:text-orange-300',
        dot: 'bg-orange-500',
      }
    case APPLICATION_STATUS.REJECTED:
      return {
        label: 'Từ chối',
        classes: 'bg-rose-500/10 text-rose-700 dark:text-rose-300',
        dot: 'bg-rose-500',
      }
    case APPLICATION_STATUS.PENDING:
    default:
      return {
        label: 'Đang chờ',
        classes: 'bg-amber-500/10 text-amber-700 dark:text-amber-300',
        dot: 'bg-amber-500',
      }
  }
}

export const getApplicationStatusLabel = (status) => getApplicationStatusMeta(status).label

export const isFinalApplicationStatus = (status) =>
  FINAL_APPLICATION_STATUSES.includes(Number(status))
