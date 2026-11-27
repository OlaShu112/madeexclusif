export function formatMoney(amount, currency = 'GBP') {
  if (amount === null || amount === undefined) {
    return '—'
  }

  const number = Number(amount)

  if (!Number.isFinite(number)) {
    return '—'
  }

  return new Intl.NumberFormat('en-GB', {
    style: 'currency',
    currency: currency.toUpperCase()
  }).format(number / 100)
}

export function toMinorUnits(value) {
  const text = String(value ?? '').trim()

  if (!/^\d{1,9}(\.\d{1,2})?$/.test(text)) {
    throw new Error(
      'Enter a valid amount with up to two decimal places.'
    )
  }

  const [whole, decimal = ''] = text.split('.')

  return Number(whole) * 100 +
    Number(decimal.padEnd(2, '0'))
}

export function toMajorUnits(value) {
  return (Number(value ?? 0) / 100).toFixed(2)
}

export function formatNumber(value) {
  if (value === null || value === undefined) {
    return '—'
  }

  return new Intl.NumberFormat('en-GB')
    .format(Number(value))
}

export function formatDate(value) {
  if (!value) return '—'

  const date = new Date(value)

  if (Number.isNaN(date.getTime())) {
    return '—'
  }

  return date.toLocaleDateString('en-GB')
}

export function formatDateTime(value) {
  if (!value) return '—'

  const date = new Date(value)

  if (Number.isNaN(date.getTime())) {
    return '—'
  }

  return date.toLocaleString('en-GB')
}

export function humanise(value) {
  if (!value) return '—'

  return String(value)
    .replaceAll('_', ' ')
    .replaceAll('-', ' ')
    .replace(/\b\w/g, char => char.toUpperCase())
}

export function localDate(date = new Date()) {
  const year = date.getFullYear()

  const month = String(
    date.getMonth() + 1
  ).padStart(2, '0')

  const day = String(
    date.getDate()
  ).padStart(2, '0')

  return `${year}-${month}-${day}`
}

export function currentMonthStart() {
  const now = new Date()

  return localDate(
    new Date(now.getFullYear(), now.getMonth(), 1)
  )
}

export function financeError(error) {
  if (error?.errors) {
    return Object.values(error.errors)
      .flat()
      .join(' ')
  }

  return error?.message ||
    'Unable to complete the financial request.'
}

export function unpackFinance(response) {
  return response?.data ?? response
}