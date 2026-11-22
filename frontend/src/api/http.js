const BASE = import.meta.env.VITE_API_URL || '/api'

export const tokenStore = {
  get: () => localStorage.getItem('mx_token'),
  set: (t) => localStorage.setItem('mx_token', t),
  clear: () => localStorage.removeItem('mx_token'),
}

/**
 * Thin fetch wrapper. Accepts a plain object (sent as JSON) or a FormData (file uploads).
 * Throws { status, message, errors } on non-2xx so views can show Laravel's validation messages.
 */
export async function api(path, { method = 'GET', body, params } = {}) {
  const url = new URL(BASE + path, window.location.origin)
  if (params) {
    Object.entries(params).forEach(([k, v]) => {
      if (v !== '' && v !== null && v !== undefined) url.searchParams.set(k, v)
    })
  }

  const isForm = typeof FormData !== 'undefined' && body instanceof FormData
  const headers = { Accept: 'application/json' }
  if (body && !isForm) headers['Content-Type'] = 'application/json' // browser sets the multipart boundary itself
  const token = tokenStore.get()
  if (token) headers.Authorization = `Bearer ${token}`

  const res = await fetch(url, {
    method,
    headers,
    body: body ? (isForm ? body : JSON.stringify(body)) : undefined,
  })
  const data = res.status === 204 ? null : await res.json().catch(() => null)

  if (!res.ok) {
    const errors = data?.errors || {}
    const first = Object.values(errors)[0]?.[0]
    throw { status: res.status, message: first || data?.message || 'Something went wrong.', errors }
  }
  return data
}