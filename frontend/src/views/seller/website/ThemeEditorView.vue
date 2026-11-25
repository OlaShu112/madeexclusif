<script setup>
import {
  ref,
  computed,
  onMounted
} from 'vue'

import { api } from '@/api/http'

const loading = ref(true)
const saving = ref(false)
const error = ref('')
const success = ref('')

const store = ref(null)
const categories = ref([])
const fonts = ref([])
const form = ref({})
const hasDraft = ref(false)
const publishedAt = ref(null)

const activeSection = ref('colours')
const device = ref('desktop')

const sections = [
  { key: 'template', label: 'Website Template' },
  { key: 'colours', label: 'Brand & Colours' },
  { key: 'typography', label: 'Typography' },
  { key: 'announcement', label: 'Announcement Bar' },
  { key: 'header', label: 'Header Management' },
  { key: 'navigation', label: 'Navigation Management' },
  { key: 'homepage', label: 'Homepage Management' },
  { key: 'hero', label: 'Hero Banner' },
  { key: 'footer', label: 'Footer Management' },
  { key: 'contact', label: 'Contact Information' },
  { key: 'delivery', label: 'Delivery Information' },
  { key: 'social', label: 'Social Media' },
  { key: 'seo', label: 'SEO Settings' }
]

const colourFields = [
  { key: 'primary_colour', label: 'Primary colour' },
  { key: 'secondary_colour', label: 'Secondary colour' },
  { key: 'background_colour', label: 'Background colour' },
  { key: 'text_colour', label: 'Text colour' },
  { key: 'header_colour', label: 'Header colour' },
  { key: 'footer_colour', label: 'Footer colour' }
]

const headerFields = [
  { key: 'show_logo', label: 'Display business logo' },
  { key: 'show_business_name', label: 'Display business name' },
  { key: 'show_search', label: 'Display search' },
  { key: 'show_account', label: 'Display account' },
  { key: 'show_cart', label: 'Display shopping bag' },
  { key: 'sticky_header', label: 'Sticky header' }
]

const socialPlatforms = [
  'facebook',
  'instagram',
  'tiktok',
  'x',
  'linkedin',
  'youtube',
  'pinterest'
]

const editableFields = [
  ...colourFields.map(field => field.key),
  'font_family',
  'announcement_enabled',
  'announcement_text',
  'announcement_colour',
  'header_settings',
  'navigation_items',
  'hero_title',
  'hero_subtitle',
  'hero_button_text',
  'homepage_sections',
  'footer_description',
  'footer_columns',
  'contact_email',
  'contact_phone',
  'delivery_information',
  'social_links',
  'seo_title',
  'seo_description'
]

const currentSection = computed(() =>
  sections.find(item =>
    item.key === activeSection.value
  )?.label
)

const previewWidth = computed(() => ({
  desktop: '100%',
  tablet: '768px',
  mobile: '390px'
}[device.value]))

const enabledNavigation = computed(() =>
  (form.value.navigation_items || [])
    .filter(item => item.enabled)
)

const enabledSections = computed(() =>
  [...(form.value.homepage_sections || [])]
    .filter(item => item.enabled)
    .sort((a, b) => a.position - b.position)
)

const enabledFooter = computed(() =>
  (form.value.footer_columns || [])
    .filter(item => item.enabled)
)

function contrastColour(hex) {
  if (!/^#[0-9a-f]{6}$/i.test(hex || '')) {
    return '#ffffff'
  }

  const rgb = [1, 3, 5].map(index => {
    const value =
      parseInt(hex.substring(index, index + 2), 16) / 255

    return value <= 0.04045
      ? value / 12.92
      : Math.pow((value + 0.055) / 1.055, 2.4)
  })

  const luminance =
    0.2126 * rgb[0] +
    0.7152 * rgb[1] +
    0.0722 * rgb[2]

  return luminance > 0.179
    ? '#000000'
    : '#ffffff'
}

function applyResponse(response) {
  const theme = {
    ...(response.theme || {}),
    ...(response.draft || {})
  }

  form.value = {
    primary_colour: '#222222',
    secondary_colour: '#f5f5f5',
    background_colour: '#ffffff',
    text_colour: '#222222',
    header_colour: '#222222',
    footer_colour: '#222222',
    font_family: 'system',
    announcement_enabled: false,
    announcement_text: '',
    announcement_colour: '#222222',
    hero_title: '',
    hero_subtitle: '',
    hero_button_text: 'Shop Collection',
    footer_description: '',
    contact_email: '',
    contact_phone: '',
    delivery_information: '',
    seo_title: '',
    seo_description: '',
    ...theme,

    header_settings: {
      show_logo: true,
      show_business_name: true,
      show_search: true,
      show_account: true,
      show_cart: true,
      sticky_header: false,
      ...(theme.header_settings || {})
    },

    navigation_items: (
      theme.navigation_items || []
    ).map(item => ({
      ...item,
      query: {
        featured: Boolean(item.query?.featured)
      }
    })),

    homepage_sections: [
      ...(theme.homepage_sections || [
        {
          type: 'hero',
          title: 'Hero Banner',
          enabled: true,
          position: 1
        },
        {
          type: 'categories',
          title: 'Shop by Category',
          enabled: true,
          position: 2
        },
        {
          type: 'featured_products',
          title: 'Featured Products',
          enabled: true,
          position: 3
        },
        {
          type: 'about_business',
          title: 'About Us',
          enabled: true,
          position: 4
        }
      ])
    ].sort((a, b) => a.position - b.position),

    footer_columns: theme.footer_columns || [
      {
        type: 'business',
        title: 'About Us',
        enabled: true
      },
      {
        type: 'navigation',
        title: 'Shop',
        enabled: true
      },
      {
        type: 'contact',
        title: 'Contact Us',
        enabled: true
      },
      {
        type: 'delivery',
        title: 'Delivery',
        enabled: true
      }
    ],

    social_links: {
      ...(theme.social_links || {})
    }
  }

  hasDraft.value = Boolean(response.has_draft)
  publishedAt.value = response.published_at || null
}

async function loadTheme() {
  loading.value = true
  error.value = ''

  try {
    const response = await api('/seller/website/theme')

    store.value = response.store
    categories.value = response.categories || []
    fonts.value = response.available_fonts || ['system']

    applyResponse(response)
  } catch (err) {
    error.value =
      err?.message || 'Unable to load website settings.'
  } finally {
    loading.value = false
  }
}

function payload() {
  return Object.fromEntries(
    editableFields.map(key => [
      key,
      form.value[key]
    ])
  )
}

async function saveTheme(publish = false) {
  if (saving.value) return

  saving.value = true
  error.value = ''
  success.value = ''

  try {
    const response = await api(
      publish
        ? '/seller/website/theme/publish'
        : '/seller/website/theme',
      {
        method: publish ? 'POST' : 'PUT',
        body: payload()
      }
    )

    applyResponse(response)

    success.value = response.message
  } catch (err) {
    error.value =
      err?.message || 'Unable to save website settings.'
  } finally {
    saving.value = false
  }
}

async function resetTemplate() {
  if (saving.value) return

  if (!window.confirm(
    'Replace your current draft with the default business template?'
  )) return

  saving.value = true
  error.value = ''
  success.value = ''

  try {
    const response = await api(
      '/seller/website/theme/reset',
      { method: 'POST' }
    )

    applyResponse(response)

    success.value = response.message
  } catch (err) {
    error.value =
      err?.message || 'Unable to reset template.'
  } finally {
    saving.value = false
  }
}

function addNavigation() {
  if (form.value.navigation_items.length >= 30) {
    return
  }

  form.value.navigation_items.push({
    label: 'New menu item',
    type: 'shop',
    enabled: true,
    category_id: null,
    query: {
      featured: false
    }
  })
}

function removeNavigation(index) {
  form.value.navigation_items.splice(index, 1)
}

function moveItem(items, index, direction) {
  const destination = index + direction

  if (
    destination < 0 ||
    destination >= items.length
  ) return

  const [item] = items.splice(index, 1)

  items.splice(destination, 0, item)
}

function moveSection(index, direction) {
  moveItem(
    form.value.homepage_sections,
    index,
    direction
  )

  form.value.homepage_sections.forEach(
    (item, position) => {
      item.position = position + 1
    }
  )
}

onMounted(loadTheme)
</script>

<template>
  <main class="website-editor">

    <div class="editor-heading">
      <div>
        <RouterLink to="/seller">
          ← Business Dashboard
        </RouterLink>

        <h1>Website Builder</h1>

        <p>
          Manage and customise your independent
          business website.
        </p>
      </div>

      <RouterLink
        :to="{ name: 'seller-website-media' }"
      >
        Media Library
      </RouterLink>

      <RouterLink
        v-if="store"
        :to="`/s/${store.slug}`"
        target="_blank"
        rel="noopener"
      >
        View Published Website
      </RouterLink>
    </div>

    <details class="image-dimensions">
      <summary>Recommended website image dimensions</summary>
      <div class="image-dimensions-grid">
        <div class="setting-card">
          <strong>Business Logo</strong>
          <p>500 × 500 px</p>
          <small>Transparent PNG recommended</small>
        </div>
        <div class="setting-card">
          <strong>Hero Banner</strong>
          <p>1920 × 800 px</p>
          <small>Landscape JPG or WebP</small>
        </div>
        <div class="setting-card">
          <strong>Promotional Banner</strong>
          <p>1200 × 600 px</p>
          <small>Landscape JPG or WebP</small>
        </div>
        <div class="setting-card">
          <strong>Category Image</strong>
          <p>800 × 800 px</p>
          <small>Square JPG or WebP</small>
        </div>
      </div>
      <p>These dimensions are recommendations, not upload restrictions.</p>
    </details>

    <p v-if="loading">
      Loading website settings...
    </p>

    <p
      v-if="error"
      class="error-message"
      role="alert"
    >
      {{ error }}
    </p>

    <button
      v-if="error && !store && !loading"
      @click="loadTheme"
    >
      Retry
    </button>

    <p
      v-if="success"
      class="success-message"
      role="status"
    >
      {{ success }}
    </p>

    <template v-if="!loading && store">

      <div class="publication-bar">
        <div>
          <strong>{{ store.name }}</strong>

          <p>
            {{
              hasDraft
                ? 'Unpublished draft available'
                : 'No saved unpublished draft'
            }}
          </p>

          <small v-if="publishedAt">
            Last published:
            {{ new Date(publishedAt).toLocaleString() }}
          </small>
        </div>

        <div class="actions">
          <button
            :disabled="saving"
            @click="saveTheme(false)"
          >
            Save Draft
          </button>

          <button
            class="primary-button"
            :disabled="saving"
            @click="saveTheme(true)"
          >
            {{ saving ? 'Saving...' : 'Publish Website' }}
          </button>
        </div>
      </div>

      <div class="editor-grid">

        <nav
          class="editor-menu"
          aria-label="Website settings"
        >
          <button
            v-for="section in sections"
            :key="section.key"
            :class="{
              selected: activeSection === section.key
            }"
            @click="activeSection = section.key"
          >
            {{ section.label }}
          </button>
        </nav>

        <fieldset
          class="settings-panel"
          :disabled="saving"
        >

          <legend>{{ currentSection }}</legend>

          <!-- WEBSITE TEMPLATE -->

          <template v-if="activeSection === 'template'">
            <h3>{{ store.name }}</h3>

            <p>
              Business category:
              {{
                store.business_category?.name ||
                store.type
              }}
            </p>

            <p>
              Template:
              {{ form.template_key || 'General' }}
            </p>

            <p>
              Restore the default configuration
              for your business category.
            </p>

            <button @click="resetTemplate">
              Reset Template
            </button>
          </template>

          <!-- BRAND COLOURS -->

          <template v-if="activeSection === 'colours'">

            <div
              v-for="field in colourFields"
              :key="field.key"
              class="colour-field"
            >
              <label :for="field.key">
                {{ field.label }}
              </label>

              <input
                :id="field.key"
                v-model="form[field.key]"
                type="color"
              />

              <small>{{ form[field.key] }}</small>
            </div>

          </template>

          <!-- TYPOGRAPHY -->

          <template v-if="activeSection === 'typography'">

            <label for="font-family">
              Website font
            </label>

            <select
              id="font-family"
              v-model="form.font_family"
            >
              <option
                v-for="font in fonts"
                :key="font"
                :value="font"
              >
                {{ font }}
              </option>
            </select>

          </template>

          <!-- ANNOUNCEMENT -->

          <template v-if="activeSection === 'announcement'">

            <label class="check-field">
              <input
                v-model="form.announcement_enabled"
                type="checkbox"
              />
              Enable announcement bar
            </label>

            <label for="announcement-text">
              Announcement message
            </label>

            <textarea
              id="announcement-text"
              v-model="form.announcement_text"
              maxlength="500"
              rows="4"
            />

            <label for="announcement-colour">
              Announcement colour
            </label>

            <input
              id="announcement-colour"
              v-model="form.announcement_colour"
              type="color"
            />

          </template>

          <!-- HEADER -->

          <template v-if="activeSection === 'header'">

            <label
              v-for="field in headerFields"
              :key="field.key"
              class="check-field"
            >
              <input
                v-model="form.header_settings[field.key]"
                type="checkbox"
              />

              {{ field.label }}
            </label>

          </template>

          <!-- NAVIGATION -->

          <template v-if="activeSection === 'navigation'">

            <div
              v-for="(item, index) in form.navigation_items"
              :key="index"
              class="setting-card"
            >
              <label>
                Menu label
                <input
                  v-model="item.label"
                  maxlength="100"
                />
              </label>

              <label>
                Menu destination

                <select v-model="item.type">
                  <option value="home">
                    Homepage
                  </option>

                  <option value="shop">
                    Shop
                  </option>

                  <option value="category">
                    Product category
                  </option>
                </select>
              </label>

              <label v-if="item.type === 'category'">
                Category

                <select v-model="item.category_id">
                  <option :value="null">
                    Select category
                  </option>

                  <option
                    v-for="category in categories"
                    :key="category.id"
                    :value="category.id"
                  >
                    {{ category.name }}
                  </option>
                </select>
              </label>

              <label
                v-if="item.type === 'shop'"
                class="check-field"
              >
                <input
                  v-model="item.query.featured"
                  type="checkbox"
                />
                Featured products only
              </label>

              <label class="check-field">
                <input
                  v-model="item.enabled"
                  type="checkbox"
                />
                Display menu item
              </label>

              <div class="actions">
                <button
                  aria-label="Move menu item up"
                  :disabled="index === 0"
                  @click="moveItem(
                    form.navigation_items,
                    index,
                    -1
                  )"
                >
                  ↑
                </button>

                <button
                  aria-label="Move menu item down"
                  :disabled="
                    index === form.navigation_items.length - 1
                  "
                  @click="moveItem(
                    form.navigation_items,
                    index,
                    1
                  )"
                >
                  ↓
                </button>

                <button
                  class="danger-button"
                  @click="removeNavigation(index)"
                >
                  Remove
                </button>
              </div>
            </div>

            <button
              :disabled="form.navigation_items.length >= 30"
              @click="addNavigation"
            >
              + Add Menu Item
            </button>

          </template>

          <!-- HOMEPAGE -->

          <template v-if="activeSection === 'homepage'">

            <div
              v-for="(item, index) in form.homepage_sections"
              :key="item.type"
              class="setting-card"
            >
              <strong>
                {{ item.type.replaceAll('_', ' ') }}
              </strong>

              <label>
                Section title

                <input
                  v-model="item.title"
                  maxlength="150"
                />
              </label>

              <label class="check-field">
                <input
                  v-model="item.enabled"
                  type="checkbox"
                />
                Display section
              </label>

              <div class="actions">
                <button
                  :disabled="index === 0"
                  @click="moveSection(index, -1)"
                >
                  Move Up
                </button>

                <button
                  :disabled="
                    index === form.homepage_sections.length - 1
                  "
                  @click="moveSection(index, 1)"
                >
                  Move Down
                </button>
              </div>
            </div>

          </template>

          <!-- HERO BANNER -->

          <template v-if="activeSection === 'hero'">

            <label for="hero-title">
              Hero heading
            </label>

            <input
              id="hero-title"
              v-model="form.hero_title"
              maxlength="255"
            />

            <label for="hero-subtitle">
              Hero description
            </label>

            <textarea
              id="hero-subtitle"
              v-model="form.hero_subtitle"
              maxlength="2000"
              rows="5"
            />

            <label for="hero-button">
              Button text
            </label>

            <input
              id="hero-button"
              v-model="form.hero_button_text"
              maxlength="100"
            />

          </template>

          <!-- FOOTER -->

          <template v-if="activeSection === 'footer'">

            <label for="footer-description">
              Business description
            </label>

            <textarea
              id="footer-description"
              v-model="form.footer_description"
              maxlength="1000"
              rows="5"
            />

            <div
              v-for="(column, index) in form.footer_columns"
              :key="column.type"
              class="setting-card"
            >
              <label>
                Column heading

                <input
                  v-model="column.title"
                  maxlength="100"
                />
              </label>

              <label class="check-field">
                <input
                  v-model="column.enabled"
                  type="checkbox"
                />
                Display column
              </label>

              <div class="actions">
                <button
                  :disabled="index === 0"
                  @click="moveItem(
                    form.footer_columns,
                    index,
                    -1
                  )"
                >
                  ↑
                </button>

                <button
                  :disabled="
                    index === form.footer_columns.length - 1
                  "
                  @click="moveItem(
                    form.footer_columns,
                    index,
                    1
                  )"
                >
                  ↓
                </button>
              </div>
            </div>

          </template>

          <!-- CONTACT -->

          <template v-if="activeSection === 'contact'">

            <label for="contact-email">
              Public email address
            </label>

            <input
              id="contact-email"
              v-model="form.contact_email"
              type="email"
              maxlength="255"
            />

            <label for="contact-phone">
              Public telephone
            </label>

            <input
              id="contact-phone"
              v-model="form.contact_phone"
              type="tel"
              maxlength="50"
            />

          </template>

          <!-- DELIVERY -->

          <template v-if="activeSection === 'delivery'">

            <label for="delivery-information">
              Delivery information
            </label>

            <textarea
              id="delivery-information"
              v-model="form.delivery_information"
              maxlength="2000"
              rows="8"
            />

          </template>

          <!-- SOCIAL MEDIA -->

          <template v-if="activeSection === 'social'">

            <label
              v-for="platform in socialPlatforms"
              :key="platform"
              class="social-field"
            >
              {{ platform }}

              <input
                v-model="form.social_links[platform]"
                type="url"
                placeholder="https://"
                maxlength="500"
              />
            </label>

          </template>

          <!-- SEO -->

          <template v-if="activeSection === 'seo'">

            <label for="seo-title">
              Website title
            </label>

            <input
              id="seo-title"
              v-model="form.seo_title"
              maxlength="255"
            />

            <label for="seo-description">
              Meta description
            </label>

            <textarea
              id="seo-description"
              v-model="form.seo_description"
              maxlength="500"
              rows="5"
            />

          </template>

        </fieldset>

        <!-- WEBSITE PREVIEW -->

        <section class="preview-panel">

          <h2>Website Preview</h2>

          <div class="device-controls">
            <button
              v-for="mode in ['desktop', 'tablet', 'mobile']"
              :key="mode"
              :class="{ selected: device === mode }"
              @click="device = mode"
            >
              {{ mode }}
            </button>
          </div>

          <p class="preview-note">
            Design preview — changes are not public
            until published.
          </p>

          <div
            class="website-preview"
            :style="{
              width: previewWidth,
              background: form.background_colour,
              color: form.text_colour,
              fontFamily: form.font_family === 'system'
                ? 'system-ui, sans-serif'
                : form.font_family
            }"
          >

            <div
              v-if="form.announcement_enabled"
              class="preview-announcement"
              :style="{
                background: form.announcement_colour,
                color: contrastColour(
                  form.announcement_colour
                )
              }"
            >
              {{ form.announcement_text }}
            </div>

            <header
              class="preview-header"
              :style="{
                background: form.header_colour,
                color: contrastColour(form.header_colour)
              }"
            >

              <img
                v-if="
                  form.header_settings.show_logo &&
                  store.logo_url
                "
                :src="store.logo_url"
                :alt="store.name"
              />

              <h2
                v-if="form.header_settings.show_business_name"
              >
                {{ store.name }}
              </h2>

              <div class="preview-navigation">
                <span
                  v-for="(item, index) in enabledNavigation"
                  :key="index"
                >
                  {{ item.label }}
                </span>
              </div>

              <div class="preview-navigation">
                <span v-if="form.header_settings.show_search">
                  Search
                </span>

                <span v-if="form.header_settings.show_account">
                  Account
                </span>

                <span v-if="form.header_settings.show_cart">
                  Bag
                </span>
              </div>

            </header>

            <div
              v-for="section in enabledSections"
              :key="section.type"
            >

              <section
                v-if="section.type === 'hero'"
                class="preview-hero"
                :style="{
                  background: form.secondary_colour,
                  color: contrastColour(
                    form.secondary_colour
                  )
                }"
              >
                <h2>
                  {{ form.hero_title || store.name }}
                </h2>

                <p>{{ form.hero_subtitle }}</p>

                <span
                  class="preview-button"
                  :style="{
                    background: form.primary_colour,
                    color: contrastColour(
                      form.primary_colour
                    )
                  }"
                >
                  {{ form.hero_button_text }}
                </span>
              </section>

              <section
                v-else-if="section.type === 'categories'"
                class="preview-section"
              >
                <h3>
                  {{ section.title || 'Shop by Category' }}
                </h3>

                <div class="category-grid">
                  <div
                    v-for="category in categories"
                    :key="category.id"
                  >
                    {{ category.name }}
                  </div>
                </div>
              </section>

              <section
                v-else-if="
                  section.type === 'featured_products'
                "
                class="preview-section"
              >
                <h3>
                  {{ section.title || 'Featured Products' }}
                </h3>

                <p>
                  Published featured products
                  will appear here.
                </p>
              </section>

              <section
                v-else-if="
                  section.type === 'about_business'
                "
                class="preview-section"
              >
                <h3>
                  {{ section.title || 'About Us' }}
                </h3>

                <p>
                  {{
                    form.footer_description ||
                    store.tagline
                  }}
                </p>
              </section>

            </div>

            <footer
              class="preview-footer"
              :style="{
                background: form.footer_colour,
                color: contrastColour(form.footer_colour)
              }"
            >

              <div class="footer-grid">

                <div
                  v-for="column in enabledFooter"
                  :key="column.type"
                >
                  <h4>{{ column.title }}</h4>

                  <template v-if="column.type === 'business'">
                    <p>{{ store.name }}</p>
                    <p>{{ form.footer_description }}</p>
                  </template>

                  <template v-if="column.type === 'navigation'">
                    <p
                      v-for="(item, index) in enabledNavigation"
                      :key="index"
                    >
                      {{ item.label }}
                    </p>
                  </template>

                  <template v-if="column.type === 'contact'">
                    <p>{{ form.contact_email }}</p>
                    <p>{{ form.contact_phone }}</p>
                  </template>

                  <template v-if="column.type === 'delivery'">
                    <p>{{ form.delivery_information }}</p>
                  </template>
                </div>

              </div>

              <div class="preview-navigation">
                <template
                  v-for="platform in socialPlatforms"
                  :key="platform"
                >
                  <span v-if="form.social_links[platform]">
                    {{ platform }}
                  </span>
                </template>
              </div>

              <hr />

              <small>
                © {{ new Date().getFullYear() }}
                {{ store.name }}.
                All rights reserved.
              </small>

              <p>Powered by IVISION</p>

            </footer>

          </div>

        </section>

      </div>

    </template>

  </main>
</template>

<style scoped>
.website-editor {
  max-width: 1800px;
  margin: auto;
  padding: 28px;
  color: #222;
}

.editor-heading,
.publication-bar,
.actions {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  flex-wrap: wrap;
}

.editor-heading {
  margin-bottom: 25px;
}

.publication-bar {
  background: #fff;
  border: 1px solid #ddd;
  border-radius: 10px;
  padding: 20px;
  margin-bottom: 20px;
}

.publication-bar p {
  margin: 6px 0;
}

.editor-grid {
  display: grid;
  grid-template-columns:
    200px 300px minmax(0, 1fr);
  gap: 18px;
  align-items: start;
}

.editor-menu,
.settings-panel,
.preview-panel {
  background: #fff;
  border: 1px solid #ddd;
  border-radius: 10px;
  padding: 18px;
  min-width: 0;
}

.editor-menu {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

button {
  padding: 10px 14px;
  background: #fff;
  border: 1px solid #d5d5d5;
  border-radius: 6px;
  cursor: pointer;
  font: inherit;
}

button:disabled {
  opacity: .5;
  cursor: not-allowed;
}

.editor-menu button {
  text-align: left;
  border: none;
  font-size: 13px;
}

.selected,
.primary-button {
  background: #182333 !important;
  color: #fff !important;
}

.settings-panel {
  margin: 0;
}

.settings-panel legend {
  font-size: 17px;
  font-weight: 600;
  padding: 0 6px;
}

.settings-panel label {
  display: block;
  font-size: 14px;
  margin-bottom: 8px;
}

.settings-panel input:not([type="checkbox"]):not([type="color"]),
.settings-panel textarea,
.settings-panel select {
  width: 100%;
  box-sizing: border-box;
  border: 1px solid #ccc;
  border-radius: 6px;
  padding: 10px;
  margin: 8px 0 16px;
  font: inherit;
}

.colour-field {
  display: grid;
  grid-template-columns: 1fr 42px;
  gap: 8px;
  margin-bottom: 20px;
  align-items: center;
}

.colour-field small {
  grid-column: 1 / -1;
}

.colour-field input {
  width: 42px;
  height: 35px;
}

.check-field {
  display: flex !important;
  align-items: center;
  gap: 10px;
  margin: 16px 0;
}

.setting-card {
  padding: 14px;
  border: 1px solid #ddd;
  border-radius: 8px;
  margin-bottom: 16px;
}

.setting-card strong,
.social-field {
  text-transform: capitalize;
}

.danger-button {
  color: #a00000;
}

.device-controls {
  display: flex;
  justify-content: center;
  gap: 6px;
  flex-wrap: wrap;
}

.device-controls button {
  text-transform: capitalize;
}

.preview-note {
  font-size: 12px;
  text-align: center;
  color: #666;
}

.website-preview {
  max-width: 100%;
  margin: 20px auto 0;
  border: 1px solid #ddd;
  border-radius: 8px;
  overflow: hidden;
  box-sizing: border-box;
  overflow-wrap: anywhere;
}

.website-preview h2,
.website-preview h3,
.website-preview h4,
.website-preview p {
  color: inherit;
}

.website-preview p {
  white-space: pre-wrap;
}

.preview-announcement {
  padding: 10px;
  font-size: 12px;
  text-align: center;
}

.preview-header {
  padding: 20px;
  text-align: center;
}

.preview-header img {
  max-width: 100px;
  max-height: 65px;
  object-fit: contain;
}

.preview-navigation {
  display: flex;
  justify-content: center;
  flex-wrap: wrap;
  gap: 12px;
  font-size: 12px;
  margin-top: 12px;
}

.preview-hero {
  padding: 50px 20px;
  text-align: center;
}

.preview-button {
  display: inline-block;
  padding: 12px 20px;
  border-radius: 5px;
  margin-top: 12px;
}

.preview-section {
  padding: 24px;
}

.category-grid,
.footer-grid {
  display: grid;
  grid-template-columns:
    repeat(auto-fit, minmax(110px, 1fr));
  gap: 12px;
}

.category-grid div {
  border: 1px solid currentColor;
  border-radius: 6px;
  padding: 16px 8px;
  text-align: center;
  font-size: 12px;
}

.preview-footer {
  padding: 24px;
  font-size: 12px;
}

.preview-footer hr {
  opacity: .3;
  margin: 20px 0;
}

.error-message,
.success-message {
  padding: 14px;
  border-radius: 6px;
}

.error-message {
  background: #fff0f0;
  color: #a00000;
}

.success-message {
  background: #edf8ef;
  color: #176638;
}

@media (max-width: 1400px) {
  .editor-grid {
    grid-template-columns:
      180px minmax(0, 1fr);
  }

  .preview-panel {
    grid-column: 1 / -1;
  }
}

@media (max-width: 700px) {
  .website-editor {
    padding: 14px;
  }

  .editor-grid {
    grid-template-columns: 1fr;
  }

  .editor-menu {
    flex-direction: row;
    flex-wrap: wrap;
  }

  .preview-panel {
    grid-column: auto;
  }
}

.image-dimensions {
  background: #fff;
  border: 1px solid #ddd;
  border-radius: 10px;
  padding: 20px;
  margin-bottom: 20px;
}

.image-dimensions summary {
  font-weight: 600;
  cursor: pointer;
}

.image-dimensions-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(min(180px, 100%), 1fr));
  gap: 12px;
  margin-top: 20px;
}

.image-dimensions-grid .setting-card {
  margin-bottom: 0;
}

</style>