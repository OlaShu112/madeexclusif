<script setup>
import { ref, reactive, onMounted } from 'vue'
import { usePlatformStore } from '@/stores/platform'

const platform = usePlatformStore()

const form = reactive({})
const previews = reactive({})

const loading = ref(true)
const saving = ref(false)
const uploading = ref('')
const error = ref('')
const success = ref('')

const textFields = [
  ['platform_name', 'Platform Name'],
  ['tagline', 'Platform Tagline'],
  ['administration_title', 'Administration Title'],
  ['browser_title', 'Browser Title'],
  ['copyright_text', 'Copyright Text']
]

const colours = [
  ['primary_colour', 'Primary Colour'],
  ['secondary_colour', 'Secondary Colour'],
  ['accent_colour', 'Accent Colour'],
  ['background_colour', 'Page Background'],
  ['text_colour', 'Text Colour'],
  ['sidebar_colour', 'Sidebar Background'],
  ['sidebar_text_colour', 'Sidebar Text'],
  ['active_menu_colour', 'Active Menu'],
  ['header_colour', 'Header Background'],
  ['footer_colour', 'Footer Background'],
  ['button_colour', 'Button Background'],
  ['button_text_colour', 'Button Text'],
  ['login_background_colour', 'Login Background']
]

const mediaFields = [
  {
    key: 'primary_logo_id',
    relation: 'primaryLogo',
    label: 'Primary Logo',
    collection: 'logo'
  },
  {
    key: 'light_logo_id',
    relation: 'lightLogo',
    label: 'Light Background Logo',
    collection: 'logo'
  },
  {
    key: 'dark_logo_id',
    relation: 'darkLogo',
    label: 'Dark Background Logo',
    collection: 'logo'
  },
  {
    key: 'sidebar_logo_id',
    relation: 'sidebarLogo',
    label: 'Collapsed Sidebar Logo',
    collection: 'logo'
  },
  {
    key: 'favicon_id',
    relation: 'favicon',
    label: 'Favicon',
    collection: 'favicon'
  },
  {
    key: 'login_logo_id',
    relation: 'loginLogo',
    label: 'Login Page Logo',
    collection: 'login'
  },
  {
    key: 'login_background_id',
    relation: 'loginBackground',
    label: 'Login Background Image',
    collection: 'login'
  },
  {
    key: 'email_logo_id',
    relation: 'emailLogo',
    label: 'Email Logo',
    collection: 'email'
  }
]

function initialiseForm() {
  Object.assign(form, platform.branding)

  mediaFields.forEach(field => {
    previews[field.key] =
      platform.branding[field.relation] || null
  })
}

async function load() {
  loading.value = true
  error.value = ''

  try {
    await platform.loadBranding()
    initialiseForm()
  } catch (exception) {
    error.value = exception.message
  } finally {
    loading.value = false
  }
}

async function upload(event, field) {
  const file = event.target.files?.[0]

  if (!file) return

  uploading.value = field.key
  error.value = ''
  success.value = ''

  try {
    const response = await platform.uploadMedia(
      file,
      field.collection,
      field.label
    )

    form[field.key] = response.media.id

    previews[field.key] = response.media.url

    success.value =
      'Image uploaded. Save changes to apply it.'
  } catch (exception) {
    error.value = exception.message
  } finally {
    uploading.value = ''
    event.target.value = ''
  }
}

function removeImage(field) {
  form[field.key] = null
  previews[field.key] = null
}

async function save() {
  saving.value = true
  error.value = ''
  success.value = ''

  try {
    const keys = [
      ...textFields.map(field => field[0]),
      ...colours.map(field => field[0]),
      ...mediaFields.map(field => field.key),
      'font_family',
      'appearance'
    ]

    const payload = Object.fromEntries(
      keys.map(key => [key, form[key]])
    )

    await platform.saveBranding(payload)

    initialiseForm()

    success.value =
      'Platform branding saved successfully.'
  } catch (exception) {
    error.value = exception.message
  } finally {
    saving.value = false
  }
}

async function reset() {
  if (!window.confirm(
    'Restore the default IVISION branding?'
  )) return

  saving.value = true
  error.value = ''
  success.value = ''

  try {
    await platform.resetBranding()

    initialiseForm()

    success.value =
      'Default branding restored.'
  } catch (exception) {
    error.value = exception.message
  } finally {
    saving.value = false
  }
}

onMounted(load)
</script>

<template>
  <main class="branding-page">

    <header class="page-heading">
      <div>
        <p class="eyebrow">PLATFORM MANAGEMENT</p>

        <h1>Platform Branding</h1>

        <p>
          Manage the visual identity of your
          IVISION technology platform.
        </p>
      </div>
    </header>

    <p v-if="loading">
      Loading platform branding...
    </p>

    <div v-if="error" class="message error" role="alert">
      {{ error }}
    </div>

    <div
      v-if="success"
      class="message success"
      role="status"
    >
      {{ success }}
    </div>

    <form
      v-if="!loading"
      @submit.prevent="save"
    >

      <section class="panel">
        <h2>Platform Identity</h2>

        <div class="field-grid">
          <label
            v-for="field in textFields"
            :key="field[0]"
          >
            {{ field[1] }}

            <input
              v-model="form[field[0]]"
              type="text"
              required
            />
          </label>
        </div>
      </section>

      <section class="panel">
        <h2>Logos and Platform Media</h2>

        <p class="description">
          Upload PNG, JPEG or WebP images.
          Maximum file size: 5 MB.
        </p>

        <div class="media-grid">

          <article
            v-for="field in mediaFields"
            :key="field.key"
            class="media-card"
          >
            <h3>{{ field.label }}</h3>

            <div class="image-preview">
              <img
                v-if="previews[field.key]"
                :src="previews[field.key]"
                :alt="field.label"
              />

              <span v-else>
                No image selected
              </span>
            </div>

            <input
              type="file"
              accept="image/png,image/jpeg,image/webp"
              :aria-label="field.label"
              :disabled="!!uploading || saving"
              @change="upload($event, field)"
            />

            <p v-if="uploading === field.key">
              Uploading...
            </p>

            <button
              v-if="previews[field.key]"
              type="button"
              class="secondary-button"
              :disabled="saving || !!uploading"
              @click="removeImage(field)"
            >
              Remove Image
            </button>
          </article>

        </div>
      </section>

      <section class="panel">
        <h2>Platform Colours</h2>

        <div class="field-grid">
          <label
            v-for="field in colours"
            :key="field[0]"
          >
            {{ field[1] }}

            <div class="colour-field">
              <input
                v-model="form[field[0]]"
                type="color"
              />

              <input
                v-model="form[field[0]]"
                type="text"
                pattern="#[0-9A-Fa-f]{6}"
                maxlength="7"
                required
              />
            </div>
          </label>
        </div>
      </section>

      <section class="panel">
        <h2>Typography and Appearance</h2>

        <div class="field-grid">
          <label>
            Font Family

            <select v-model="form.font_family">
              <option value="system">
                System Default
              </option>

              <option value="Arial">Arial</option>
              <option value="Inter">Inter</option>
              <option value="Roboto">Roboto</option>
              <option value="Georgia">Georgia</option>
            </select>
          </label>

          <label>
            Appearance

            <select v-model="form.appearance">
              <option value="light">
                Light
              </option>

              <option value="dark">
                Dark
              </option>
            </select>
          </label>
        </div>
      </section>

      <section class="panel">
        <h2>Live Branding Preview</h2>

        <div
          class="preview"
          :style="{
            background: form.background_colour,
            color: form.text_colour,
            fontFamily:
              form.font_family === 'system'
                ? 'system-ui'
                : form.font_family
          }"
        >
          <aside
            :style="{
              background: form.sidebar_colour,
              color: form.sidebar_text_colour
            }"
          >
            <img
              v-if="
                previews.dark_logo_id ||
                previews.primary_logo_id
              "
              :src="
                previews.dark_logo_id ||
                previews.primary_logo_id
              "
              alt="Platform logo"
            />

            <h2 v-else>
              {{ form.platform_name }}
            </h2>

            <small>
              {{ form.administration_title }}
            </small>

            <div
              class="preview-menu active"
              :style="{
                background: form.active_menu_colour
              }"
            >
              Dashboard
            </div>

            <div class="preview-menu">
              Businesses
            </div>

            <div class="preview-menu">
              Customers
            </div>

            <div class="preview-menu">
              Platform Branding
            </div>
          </aside>

          <div class="preview-content">
            <header
              :style="{
                background: form.header_colour
              }"
            >
              {{ form.platform_name }}
              Administration
            </header>

            <div class="preview-body">
              <h2>Business Overview</h2>

              <p>{{ form.tagline }}</p>

              <button
                type="button"
                :style="{
                  background: form.button_colour,
                  color: form.button_text_colour
                }"
              >
                Preview Button
              </button>
            </div>

            <footer
              :style="{
                background: form.footer_colour
              }"
            >
              © {{ new Date().getFullYear() }}
              {{ form.platform_name }}.
              {{ form.copyright_text }}
            </footer>
          </div>
        </div>
      </section>

      <div class="actions">
        <button
          type="button"
          class="secondary-button"
          :disabled="saving || !!uploading"
          @click="reset"
        >
          Restore Defaults
        </button>

        <button
          type="submit"
          class="primary-button"
          :disabled="saving || !!uploading"
        >
          {{ saving ? 'Saving...' : 'Save Changes' }}
        </button>
      </div>

    </form>
  </main>
</template>

<style scoped>
.branding-page {
  max-width: 1400px;
  margin: 0 auto;
  padding: 24px;
  color: #172033;
}

.page-heading {
  margin-bottom: 28px;
}

.eyebrow {
  font-size: 11px;
  letter-spacing: 2px;
  font-weight: 700;
  color: #64748b;
}

h1 {
  font-size: 30px;
  margin: 8px 0;
}

h2 {
  font-size: 19px;
  margin: 0 0 20px;
}

h3 {
  font-size: 14px;
}

.panel {
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 24px;
  margin-bottom: 24px;
}

.field-grid,
.media-grid {
  display: grid;
  grid-template-columns:
    repeat(auto-fit, minmax(240px, 1fr));
  gap: 20px;
}

label {
  display: flex;
  flex-direction: column;
  gap: 8px;
  font-size: 13px;
  font-weight: 600;
}

input,
select {
  width: 100%;
  box-sizing: border-box;
  border: 1px solid #d7dde5;
  border-radius: 7px;
  padding: 11px;
  background: white;
  color: #172033;
  font: inherit;
}

input:focus,
select:focus {
  outline: 2px solid #64748b;
  outline-offset: 2px;
}

.colour-field {
  display: flex;
  align-items: center;
  gap: 10px;
}

.colour-field input[type="color"] {
  width: 48px;
  height: 44px;
  padding: 4px;
  cursor: pointer;
}

.media-card {
  border: 1px solid #e2e8f0;
  padding: 16px;
  border-radius: 10px;
  min-width: 0;
}

.image-preview {
  height: 130px;
  background: #f1f5f9;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 7px;
  margin: 14px 0;
  color: #64748b;
  font-size: 12px;
}

.image-preview img {
  max-width: 90%;
  max-height: 110px;
  object-fit: contain;
}

.description {
  color: #64748b;
  font-size: 13px;
  margin-bottom: 20px;
}

.preview {
  display: grid;
  grid-template-columns: 220px 1fr;
  min-height: 300px;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  overflow: hidden;
}

.preview aside {
  padding: 22px 15px;
}

.preview aside img {
  max-width: 160px;
  max-height: 70px;
  object-fit: contain;
}

.preview aside small {
  display: block;
  margin: 12px 0 25px;
}

.preview-menu {
  padding: 12px;
  font-size: 13px;
  border-radius: 6px;
  margin-bottom: 4px;
}

.preview-content {
  display: flex;
  flex-direction: column;
}

.preview-content header,
.preview-content footer {
  padding: 18px;
  font-size: 13px;
}

.preview-body {
  flex: 1;
  padding: 24px;
}

.preview-body button {
  padding: 12px 20px;
  border: 0;
  border-radius: 6px;
}

.actions {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  flex-wrap: wrap;
}

.primary-button,
.secondary-button {
  padding: 12px 20px;
  border-radius: 7px;
  font-weight: 600;
  cursor: pointer;
}

.primary-button {
  background: #111827;
  color: white;
  border: 1px solid #111827;
}

.secondary-button {
  background: white;
  color: #334155;
  border: 1px solid #cbd5e1;
}

.media-card .secondary-button {
  margin-top: 12px;
}

button:disabled {
  opacity: .55;
  cursor: not-allowed;
}

.message {
  padding: 14px;
  margin-bottom: 20px;
  border-radius: 7px;
}

.error {
  background: #fef2f2;
  color: #991b1b;
}

.success {
  background: #f0fdf4;
  color: #166534;
}

@media (max-width: 700px) {
  .branding-page {
    padding: 12px;
  }

  .panel {
    padding: 16px;
  }

  .preview {
    grid-template-columns: 1fr;
  }
}
</style>