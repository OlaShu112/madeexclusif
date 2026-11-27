/*
|--------------------------------------------------------------------------
| IVISION Platform Typography
|--------------------------------------------------------------------------
*/

export const DEFAULT_PLATFORM_FONT = 'Geist'

export const PLATFORM_FONTS = Object.freeze({

  Geist: {
    label: 'Geist — Recommended',
    family: '"Geist Variable", system-ui, sans-serif',
  },

  system: {
    label: 'System Default',
    family: 'system-ui, sans-serif',
  },

  Arial: {
    label: 'Arial',
    family: 'Arial, Helvetica, sans-serif',
  },

  Inter: {
    label: 'Inter',
    family: '"Inter Variable", system-ui, sans-serif',
  },

  Roboto: {
    label: 'Roboto',
    family: '"Roboto Variable", Arial, sans-serif',
  },

  Georgia: {
    label: 'Georgia',
    family: 'Georgia, "Times New Roman", serif',
  },

})

export function resolvePlatformFont(font) {

  return Object.hasOwn(PLATFORM_FONTS, font)

    ? PLATFORM_FONTS[font].family

    : PLATFORM_FONTS.Geist.family

}

export const PLATFORM_MONO_FONT =
  '"Geist Mono Variable", monospace'