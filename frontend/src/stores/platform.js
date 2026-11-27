import { defineStore } from 'pinia'
import { api } from '@/api/http'

import {
  DEFAULT_PLATFORM_FONT,
  resolvePlatformFont,
} from '@/utils/platformTypography'

export const usePlatformStore = defineStore('platform', {

  state: () => ({

    branding: {

      platform_name: 'IVISION',
      tagline: 'Intelligent Digital Technology',

      administration_title:
        'Platform Administration',

      browser_title:
        'IVISION — Administration Portal',

      primary_colour: '#111827',
      secondary_colour: '#334155',
      accent_colour: '#C9A86A',
      background_colour: '#F5F6F9',
      text_colour: '#111827',

      sidebar_colour: '#111827',
      sidebar_text_colour: '#E5E7EB',
      active_menu_colour: '#374151',

      header_colour: '#FFFFFF',
      footer_colour: '#FFFFFF',
      button_colour: '#111827',
      button_text_colour: '#FFFFFF',

      login_background_colour: '#F5F6F9',

      font_family: DEFAULT_PLATFORM_FONT,

      appearance: 'light',

      copyright_text: 'All rights reserved.',

      primaryLogo: null,
      lightLogo: null,
      darkLogo: null,
      sidebarLogo: null,
      favicon: null,
      loginLogo: null,
      loginBackground: null,
      emailLogo: null,

    },

    settings: {},
    loading: false,
    loaded: false,
    error: null,

  }),

  getters: {

    platformName: state =>
      state.branding.platform_name,

    platformLogo: state =>
      state.branding.primaryLogo,

    sidebarLogo: state =>
      state.branding.sidebarLogo ||
      state.branding.darkLogo ||
      state.branding.primaryLogo,

    loginLogo: state =>
      state.branding.loginLogo ||
      state.branding.primaryLogo,

    platformFont: state =>
      resolvePlatformFont(
        state.branding.font_family
      ),

    themeVariables: state => ({

      '--platform-primary':
        state.branding.primary_colour,

      '--platform-secondary':
        state.branding.secondary_colour,

      '--platform-accent':
        state.branding.accent_colour,

      '--platform-background':
        state.branding.background_colour,

      '--platform-text':
        state.branding.text_colour,

      '--platform-sidebar':
        state.branding.sidebar_colour,

      '--platform-sidebar-text':
        state.branding.sidebar_text_colour,

      '--platform-active-menu':
        state.branding.active_menu_colour,

      '--platform-header':
        state.branding.header_colour,

      '--platform-footer':
        state.branding.footer_colour,

      '--platform-button':
        state.branding.button_colour,

      '--platform-button-text':
        state.branding.button_text_colour,

      '--platform-font':
        resolvePlatformFont(
          state.branding.font_family
        ),

    }),

  },

  actions: {

    async loadBranding() {

      this.loading = true
      this.error = null

      try {

        const response = await api(
          '/platform/branding'
        )

        this.branding = {
          ...this.branding,
          ...response,
        }

        this.loaded = true

        return this.branding

      } catch (error) {

        this.error = error.message

        throw error

      } finally {

        this.loading = false

      }

    },

    async saveBranding(data) {

      const response = await api(
        '/admin/platform/branding',
        {
          method: 'PUT',
          body: data,
        }
      )

      this.branding = {
        ...this.branding,
        ...response.branding,
      }

      return response

    },

    async resetBranding() {

      const response = await api(
        '/admin/platform/branding/reset',
        {
          method: 'POST',
        }
      )

      this.branding = {
        ...this.branding,
        ...response.branding,
      }

      return response

    },

    async loadSettings() {

      this.settings = await api(
        '/admin/platform/settings'
      )

      return this.settings

    },

    async saveSettings(group, data) {

      const response = await api(
        `/admin/platform/settings/${group}`,
        {
          method: 'PUT',
          body: data,
        }
      )

      this.settings = response.settings

      return response

    },

    async uploadMedia(
      file,
      collection,
      altText = ''
    ) {

      const body = new FormData()

      body.append('file', file)
      body.append('collection', collection)
      body.append('alt_text', altText)

      return api(
        '/admin/platform/media',
        {
          method: 'POST',
          body,
        }
      )

    },

  },

})