import { api } from '@/api/http'

const BASE_URL = '/admin/communication'

/*
|--------------------------------------------------------------------------
| Build Query Parameters
|--------------------------------------------------------------------------
*/

function buildQuery(params = {}) {
  const query = new URLSearchParams()

  Object.entries(params).forEach(([key, value]) => {
    if (
      value !== undefined &&
      value !== null &&
      value !== ''
    ) {
      query.set(
        key,
        typeof value === 'boolean'
          ? Number(value)
          : value
      )
    }
  })

  const result = query.toString()

  return result ? `?${result}` : ''
}

/*
|--------------------------------------------------------------------------
| Communication API
|--------------------------------------------------------------------------
*/

export const communicationApi = {

  overview() {
    return api(`${BASE_URL}/overview`)
  },

  messages(params = {}) {
    return api(
      `${BASE_URL}/messages${buildQuery(params)}`
    )
  },

  templates(params = {}) {
    return api(
      `${BASE_URL}/templates${buildQuery(params)}`
    )
  },

  announcements(params = {}) {
    return api(
      `${BASE_URL}/announcements${buildQuery(params)}`
    )
  },

  notifications(params = {}) {
    return api(
      `${BASE_URL}/notifications${buildQuery(params)}`
    )
  },

  recipients(params = {}) {
    return api(
      `${BASE_URL}/recipients${buildQuery(params)}`
    )
  },

  delivery(params = {}) {
    return api(
      `${BASE_URL}/delivery${buildQuery(params)}`
    )
  },

  automation(params = {}) {
    return api(
      `${BASE_URL}/automation${buildQuery(params)}`
    )
  },

  preferences(params = {}) {
    return api(
      `${BASE_URL}/preferences${buildQuery(params)}`
    )
  },

  settings() {
    return api(`${BASE_URL}/settings`)
  },

  permissions() {
    return api(`${BASE_URL}/permissions`)
  },

}

export default communicationApi