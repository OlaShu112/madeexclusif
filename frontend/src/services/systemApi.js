import { api } from '@/api/http'

export const systemApi = {

  health() {
    return api('/admin/system/health')
  },

}

export default systemApi