import { defineStore } from 'pinia'
import communicationApi from '@/services/communicationApi'

export const useCommunicationStore = defineStore(
  'communication',
  {
    state: () => ({
      overview: null,

      permissions: [],

      settings: null,

      resources: {},

      loading: {},

      errors: {},

      requestVersions: {},
    }),

    getters: {

      hasPermission: (state) => (permission) =>
        state.permissions.includes(permission),

      resource: (state) => (name) =>
        state.resources[name] ?? {
          data: [],
          meta: {},
        },

      isLoading: (state) => (name) =>
        Boolean(state.loading[name]),

      errorFor: (state) => (name) =>
        state.errors[name] ?? null,

    },

    actions: {

      async execute(name, callback, onSuccess) {

        const version =
          (this.requestVersions[name] ?? 0) + 1

        this.requestVersions[name] = version
        this.loading[name] = true
        this.errors[name] = null

        try {

          const response = await callback()

          if (this.requestVersions[name] === version) {
            onSuccess(response)
          }

          return response

        } catch (error) {

          if (this.requestVersions[name] === version) {
            this.errors[name] =
              error?.message ||
              'Unable to load Communication data.'
          }

          throw error

        } finally {

          if (this.requestVersions[name] === version) {
            this.loading[name] = false
          }

        }
      },

      async loadPermissions() {

        this.permissions = []

        return this.execute(
          'permissions',
          () => communicationApi.permissions(),
          response => {
            this.permissions =
              response?.data?.permissions ?? []
          }
        )
      },

      async loadOverview() {

        return this.execute(
          'overview',
          () => communicationApi.overview(),
          response => {
            this.overview =
              response?.data ?? response
          }
        )
      },

      async loadSettings() {

        return this.execute(
          'settings',
          () => communicationApi.settings(),
          response => {
            this.settings =
              response?.data ?? response
          }
        )
      },

      async loadResource(name, params = {}) {

        const allowedResources = [
          'messages',
          'templates',
          'announcements',
          'notifications',
          'recipients',
          'delivery',
          'automation',
          'preferences',
        ]

        if (!allowedResources.includes(name)) {
          throw new Error(
            `Unsupported Communication resource: ${name}`
          )
        }

        return this.execute(
          name,
          () => communicationApi[name](params),
          response => {

            this.resources[name] = {
              data: response?.data ?? [],
              meta: response?.meta ?? {},
            }

          }
        )
      },

      clear() {

        Object.keys(this.requestVersions).forEach(name => {
          this.requestVersions[name]++
        })

        this.overview = null
        this.permissions = []
        this.settings = null
        this.resources = {}
        this.loading = {}
        this.errors = {}
      },

    },
  }
)