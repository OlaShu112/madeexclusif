/*
|--------------------------------------------------------------------------
| IVISION HTTP API Client
|--------------------------------------------------------------------------
*/

const BASE = (
  import.meta.env.VITE_API_URL || '/api'
).replace(/\/+$/, '')

const TOKEN_KEY = 'mx_token'

/*
|--------------------------------------------------------------------------
| Authentication Token Storage
|--------------------------------------------------------------------------
|
| Preserve the existing token key to avoid
| invalidating existing browser sessions.
|
*/

export const tokenStore = {

  get: () =>
    localStorage.getItem(TOKEN_KEY),

  set: (token) =>
    localStorage.setItem(TOKEN_KEY, token),

  clear: () =>
    localStorage.removeItem(TOKEN_KEY),

}

/*
|--------------------------------------------------------------------------
| Central API Request Handler
|--------------------------------------------------------------------------
*/

export async function api(

  path,

  {
    method = 'GET',
    body,
    params,
    timeout = 30000,
  } = {}

) {

  const url = new URL(
    BASE + path,
    window.location.origin
  )

  /*
  |--------------------------------------------------------------------------
  | Query Parameters
  |--------------------------------------------------------------------------
  */

  if (params) {

    Object.entries(params).forEach(([key, value]) => {

      if (
        value !== '' &&
        value !== null &&
        value !== undefined
      ) {

        url.searchParams.set(key, value)

      }

    })

  }

  /*
  |--------------------------------------------------------------------------
  | Request Headers
  |--------------------------------------------------------------------------
  */

  const isForm =
    typeof FormData !== 'undefined' &&
    body instanceof FormData

  const headers = {
    Accept: 'application/json',
  }

  if (body !== undefined && !isForm) {

    headers['Content-Type'] = 'application/json'

  }

  const token = tokenStore.get()

  if (token) {

    headers.Authorization = `Bearer ${token}`

  }

  /*
  |--------------------------------------------------------------------------
  | Request Timeout
  |--------------------------------------------------------------------------
  */

  const controller = new AbortController()

  const timeoutId = setTimeout(() => {

    controller.abort()

  }, timeout)

  try {

    const response = await fetch(url, {

      method,

      headers,

      signal: controller.signal,

      body: body !== undefined

        ? (
            isForm
              ? body
              : JSON.stringify(body)
          )

        : undefined,

    })

    /*
    |--------------------------------------------------------------------------
    | Parse API Response
    |--------------------------------------------------------------------------
    */

    const data = response.status === 204

      ? null

      : await response.json().catch(() => null)

    /*
    |--------------------------------------------------------------------------
    | HTTP Error Handling
    |--------------------------------------------------------------------------
    */

    if (!response.ok) {

      const errors = data?.errors || {}

      const firstError =
        Object.values(errors)[0]?.[0]

      const messages = {

        401: 'Your session has expired. Please sign in again.',

        403: 'You do not have permission to perform this action.',

        404: 'The requested resource was not found.',

        419: 'The request could not be verified.',

        422: 'Please check the information provided.',

        429: 'Too many requests. Please try again shortly.',

        500: 'A server error occurred. Please try again.',

        502: 'The API service is temporarily unavailable.',

        503: 'The service is temporarily unavailable.',

      }

      throw {

        status: response.status,

        message:
          firstError ||
          data?.message ||
          messages[response.status] ||
          'Something went wrong.',

        errors,

      }

    }

    return data

  } catch (error) {

    /*
    |--------------------------------------------------------------------------
    | Network Error Handling
    |--------------------------------------------------------------------------
    |
    | A network failure must not automatically
    | remove the authentication token.
    |
    */

    if (error?.name === 'AbortError') {

      throw {

        status: 0,

        message:
          'The request timed out. Please try again.',

        errors: {},

      }

    }

    if (error instanceof TypeError) {

      throw {

        status: 0,

        message:
          'Unable to connect to the IVISION API.',

        errors: {},

      }

    }

    throw error

  } finally {

    clearTimeout(timeoutId)

  }

}