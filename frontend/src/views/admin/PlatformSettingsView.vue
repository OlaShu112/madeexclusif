<script setup>
import {
  ref,
  reactive,
  computed,
  onMounted
} from 'vue'

import { usePlatformStore } from '@/stores/platform'

import {
  Settings,
  Building2,
  Mail,
  Globe,
  Save
} from 'lucide-vue-next'

const platform = usePlatformStore()

const loading = ref(true)
const saving = ref(false)
const error = ref('')
const success = ref('')
const activeGroup = ref('general')

const form = reactive({
  general: {},
  company: {},
  contact: {},
  website: {}
})

const groups = [
  {
    key: 'general',
    label: 'General Settings',
    icon: Settings,
    description:
      'Manage your platform domain, language, currency and timezone.',

    fields: [
      {
        key: 'domain',
        label: 'Platform Domain',
        type: 'text',
        required: true
      },
      {
        key: 'timezone',
        label: 'Timezone',
        type: 'text',
        required: true
      },
      {
        key: 'language',
        label: 'Default Language',
        type: 'select',
        options: [
          ['en-GB', 'English (United Kingdom)'],
          ['en-US', 'English (United States)']
        ]
      },
      {
        key: 'currency',
        label: 'Default Currency',
        type: 'select',
        options: [
          ['GBP', 'British Pound (GBP)']
        ]
      }
    ]
  },

  {
    key: 'company',
    label: 'Company Information',
    icon: Building2,
    description:
      'Manage your registered company information.',

    fields: [
      {
        key: 'registered_name',
        label: 'Registered Company Name',
        type: 'text',
        required: true
      },
      {
        key: 'registration_number',
        label: 'Company Registration Number',
        type: 'text'
      },
      {
        key: 'vat_number',
        label: 'VAT Registration Number',
        type: 'text'
      },
      {
        key: 'address',
        label: 'Registered Address',
        type: 'textarea'
      },
      {
        key: 'telephone',
        label: 'Company Telephone',
        type: 'tel'
      }
    ]
  },

  {
    key: 'contact',
    label: 'Contact Information',
    icon: Mail,
    description:
      'Configure your platform contact email addresses.',

    fields: [
      {
        key: 'support_email',
        label: 'Customer Support Email',
        type: 'email'
      },
      {
        key: 'administration_email',
        label: 'Administration Email',
        type: 'email'
      },
      {
        key: 'billing_email',
        label: 'Billing Email',
        type: 'email'
      },
      {
        key: 'privacy_email',
        label: 'Privacy Email',
        type: 'email'
      }
    ]
  },

  {
    key: 'website',
    label: 'Website and SEO',
    icon: Globe,
    description:
      'Manage website metadata and social media links.',

    fields: [
      {
        key: 'seo_title',
        label: 'Website SEO Title',
        type: 'text'
      },
      {
        key: 'seo_description',
        label: 'Website SEO Description',
        type: 'textarea'
      },
      {
        key: 'facebook',
        label: 'Facebook URL',
        type: 'url'
      },
      {
        key: 'instagram',
        label: 'Instagram URL',
        type: 'url'
      },
      {
        key: 'linkedin',
        label: 'LinkedIn URL',
        type: 'url'
      },
      {
        key: 'youtube',
        label: 'YouTube URL',
        type: 'url'
      }
    ]
  }
]

const selectedGroup = computed(() =>
  groups.find(group =>
    group.key === activeGroup.value
  )
)

function populate(settings) {
  groups.forEach(group => {
    form[group.key] = {
      ...(settings[group.key] || {})
    }
  })
}

function selectGroup(group) {
  activeGroup.value = group
  error.value = ''
  success.value = ''
}

async function load() {
  loading.value = true
  error.value = ''

  try {
    const settings =
      await platform.loadSettings()

    populate(settings)
  } catch (exception) {
    error.value = exception.message
  } finally {
    loading.value = false
  }
}

async function save() {
  if (saving.value || loading.value) return

  saving.value = true
  error.value = ''
  success.value = ''

  const group = activeGroup.value

  try {
    const response = await platform.saveSettings(
      group,
      { ...form[group] }
    )

    form[group] = {
      ...response.settings[group]
    }

    success.value =
      response.message ||
      'Settings saved successfully.'
  } catch (exception) {
    error.value = exception.message
  } finally {
    saving.value = false
  }
}

onMounted(load)
</script>

<template>
  <section class="settings-page">

    <header class="page-heading">

      <p class="eyebrow">
        {{ platform.platformName }}
        PLATFORM ADMINISTRATION
      </p>

      <h1>Platform Settings</h1>

      <p>
        Manage the central configuration of your
        intelligent digital technology platform.
      </p>

    </header>

    <div class="settings-notice">
      Settings saved here are stored in the
      platform database. Runtime configuration
      and external services require separate
      integration.
    </div>

    <div
      v-if="error"
      class="message error"
      role="alert"
    >
      {{ error }}

      <button
        v-if="!platform.settings.general"
        type="button"
        @click="load"
      >
        Retry
      </button>
    </div>

    <div
      v-if="success"
      class="message success"
      role="status"
    >
      {{ success }}
    </div>

    <p v-if="loading">
      Loading platform settings...
    </p>

    <div
      v-else-if="platform.settings.general"
      class="settings-layout"
    >

      <nav
        class="settings-navigation"
        aria-label="Settings sections"
      >

        <button
          v-for="group in groups"
          :key="group.key"
          type="button"
          :disabled="saving"
          :class="{
            active: activeGroup === group.key
          }"
          :aria-pressed="activeGroup === group.key"
          @click="selectGroup(group.key)"
        >
          <component
            :is="group.icon"
            :size="19"
          />

          {{ group.label }}
        </button>

      </nav>

      <form
        class="settings-panel"
        @submit.prevent="save"
      >

        <h2>
          {{ selectedGroup.label }}
        </h2>

        <p class="description">
          {{ selectedGroup.description }}
        </p>

        <fieldset :disabled="saving">

          <div class="fields">

            <label
              v-for="field in selectedGroup.fields"
              :key="field.key"
            >
              {{ field.label }}

              <textarea
                v-if="field.type === 'textarea'"
                v-model="form[activeGroup][field.key]"
                rows="4"
              />

              <select
                v-else-if="field.type === 'select'"
                v-model="form[activeGroup][field.key]"
              >
                <option
                  v-for="option in field.options"
                  :key="option[0]"
                  :value="option[0]"
                >
                  {{ option[1] }}
                </option>
              </select>

              <input
                v-else
                v-model="form[activeGroup][field.key]"
                :type="field.type"
                :required="field.required"
              />

            </label>

          </div>

        </fieldset>

        <div class="form-actions">

          <button
            type="submit"
            class="save-button"
            :disabled="saving"
          >
            <Save :size="17" />

            {{
              saving
                ? 'Saving...'
                : 'Save Changes'
            }}
          </button>

        </div>

      </form>

    </div>

  </section>
</template>

<style scoped>
.settings-page {
  max-width: 1400px;
  margin: 0 auto;
}

.page-heading {
  margin-bottom: 28px;
}

.eyebrow {
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 1.5px;
  opacity: 0.7;
}

h1 {
  margin: 10px 0;
  font-size: 30px;
}

.settings-notice {
  padding: 15px;
  background: #F1F5F9;
  color: #475569;
  border: 1px solid #E2E8F0;
  border-radius: 8px;
  margin-bottom: 24px;
  font-size: 13px;
  line-height: 1.6;
}

.settings-layout {
  display: grid;
  grid-template-columns: 250px minmax(0, 1fr);
  gap: 24px;
  align-items: start;
}

.settings-navigation,
.settings-panel {
  background: #FFFFFF;
  color: #111827;
  border: 1px solid #E2E8F0;
  border-radius: 12px;
}

.settings-navigation {
  padding: 10px;
}

.settings-navigation button {
  display: flex;
  align-items: center;
  gap: 12px;
  width: 100%;
  padding: 14px;
  margin-bottom: 4px;
  border: 0;
  border-radius: 7px;
  background: transparent;
  color: #475569;
  font-size: 13px;
  text-align: left;
  cursor: pointer;
}

.settings-navigation button:hover {
  background: #F1F5F9;
}

.settings-navigation button.active {
  background:
    var(--platform-button, #111827);

  color:
    var(--platform-button-text, #FFFFFF);
}

.settings-panel {
  padding: 28px;
}

.settings-panel h2 {
  margin: 0 0 10px;
  font-size: 21px;
}

.description {
  font-size: 14px;
  color: #64748B;
  margin-bottom: 28px;
}

fieldset {
  border: 0;
  padding: 0;
  margin: 0;
  min-width: 0;
}

.fields {
  display: grid;
  grid-template-columns:
    repeat(auto-fit, minmax(220px, 1fr));

  gap: 22px;
}

label {
  display: flex;
  flex-direction: column;
  gap: 9px;
  font-size: 13px;
  font-weight: 600;
}

input,
textarea,
select {
  box-sizing: border-box;
  width: 100%;
  padding: 12px;
  border: 1px solid #CBD5E1;
  border-radius: 7px;
  background: #FFFFFF;
  color: #111827;
  font: inherit;
}

textarea {
  resize: vertical;
}

input:focus,
textarea:focus,
select:focus {
  outline: 2px solid #64748B;
  outline-offset: 2px;
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  margin-top: 30px;
  padding-top: 22px;
  border-top: 1px solid #E2E8F0;
}

.save-button {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 9px;
  padding: 13px 22px;
  border: 0;
  border-radius: 7px;

  background:
    var(--platform-button, #111827);

  color:
    var(--platform-button-text, #FFFFFF);

  font-weight: 600;
  cursor: pointer;
}

button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.message {
  padding: 15px;
  margin-bottom: 20px;
  border-radius: 7px;
  font-size: 14px;
}

.error {
  background: #FEF2F2;
  color: #991B1B;
}

.success {
  background: #F0FDF4;
  color: #166534;
}

@media (max-width: 800px) {
  .settings-layout {
    grid-template-columns: 1fr;
  }

  .settings-panel {
    padding: 18px;
  }
}
</style>