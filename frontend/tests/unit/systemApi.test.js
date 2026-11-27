import {
  describe,
  it,
  expect,
  vi,
} from 'vitest'

vi.mock('@/api/http', () => ({
  api: vi.fn(),
}))

import { api } from '@/api/http'

import systemApi from '@/services/systemApi'

describe('IVISION System API', () => {

  it('requests the system health endpoint', async () => {

    api.mockResolvedValue({
      success: true,
      data: {
        healthy: true,
      },
    })

    const response = await systemApi.health()

    expect(api).toHaveBeenCalledWith(
      '/admin/system/health'
    )

    expect(response.success).toBe(true)

  })

})