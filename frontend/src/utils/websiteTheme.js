export function safeColour(value, fallback = '#222222') {
  return /^#[0-9a-fA-F]{6}$/.test(value || '')
    ? value
    : fallback
}

export function contrastColour(hex) {
  const colour = safeColour(hex)

  const rgb = [1, 3, 5].map(index => {
    const channel =
      parseInt(colour.substring(index, index + 2), 16) / 255

    return channel <= 0.04045
      ? channel / 12.92
      : Math.pow((channel + 0.055) / 1.055, 2.4)
  })

  const luminance =
    0.2126 * rgb[0] +
    0.7152 * rgb[1] +
    0.0722 * rgb[2]

  return luminance > 0.179
    ? '#000000'
    : '#ffffff'
}

export const availableFonts = [
  'system',
  'Arial',
  'Georgia',
  'Verdana',
  'Tahoma',
  'Trebuchet MS',
  'Times New Roman'
]

export function resolveFont(value) {
  if (!availableFonts.includes(value) || value === 'system') {
    return 'system-ui, sans-serif'
  }

  return `"${value}", sans-serif`
}

export function websiteVariables(store) {
  const theme = store?.website_theme || {}

  const primary = safeColour(
    theme.primary_colour || store?.accent
  )

  const header = safeColour(
    theme.header_colour,
    primary
  )

  const footer = safeColour(
    theme.footer_colour,
    primary
  )

  const announcement = safeColour(
    theme.announcement_colour,
    primary
  )

  const secondary = safeColour(
    theme.secondary_colour,
    '#f5f5f5'
  )

  return {
    '--store-primary': primary,
    '--store-primary-text': contrastColour(primary),

    '--store-secondary': secondary,
    '--store-secondary-text': contrastColour(secondary),

    '--store-background': safeColour(
      theme.background_colour,
      '#ffffff'
    ),

    '--store-text': safeColour(
      theme.text_colour,
      '#222222'
    ),

    '--store-header': header,
    '--store-header-text': contrastColour(header),

    '--store-footer': footer,
    '--store-footer-text': contrastColour(footer),

    '--store-announcement': announcement,
    '--store-announcement-text':
      contrastColour(announcement),

    '--store-font': resolveFont(theme.font_family)
  }
}

export function navigationItems(store) {
  const configured = store?.website_theme?.navigation_items

  const defaults = [
    {
      label: 'Home',
      type: 'home',
      enabled: true
    },
    {
      label: 'Shop All',
      type: 'shop',
      enabled: true
    },
    {
      label: 'Featured',
      type: 'shop',
      enabled: true,
      query: { featured: 1 }
    }
  ]

  return (
    Array.isArray(configured)
      ? configured
      : defaults
  ).filter(item => item.enabled !== false)
}

export function navigationDestination(store, item) {
  const params = {
    store: store.slug
  }

  if (item.type === 'home') {
    return {
      name: 'store-home',
      params
    }
  }

  if (item.type === 'category' && item.category_slug) {
    return {
      name: 'store-shop',
      params,
      query: {
        category: item.category_slug
      }
    }
  }

  return {
    name: 'store-shop',
    params,
    query: item.query?.featured
      ? { featured: 1 }
      : {}
  }
}

export function safeExternalUrl(value) {
  try {
    const url = new URL(value)

    return url.protocol === 'https:'
      ? url.href
      : null
  } catch {
    return null
  }
}