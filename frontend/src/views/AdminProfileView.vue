
<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { api } from '@/api/http'

import {
  UserRound,
  Camera,
  Trash2,
  ShieldCheck,
  LockKeyhole,
  Eye,
  EyeOff,
  Save,
  Mail,
  CalendarDays,
} from 'lucide-vue-next'

const auth = useAuthStore()
const router = useRouter()

const profile = ref(null)
const loading = ref(true)
const busy = ref('')
const error = ref('')
const notice = ref('')
const fileInput = ref(null)
const showPasswords = ref(false)

const form = ref({
  name: '',
  phone: '',
})

const passwords = ref({
  current_password: '',
  password: '',
  password_confirmation: '',
})

function applyProfile(user) {
  profile.value = user

  form.value = {
    name: user.name || '',
    phone: user.phone || '',
  }

  auth.user = {
    ...auth.user,
    ...user,
  }
}

async function loadProfile() {
  loading.value = true
  error.value = ''

  try {
    const data = await api('/admin/profile')
    applyProfile(data.user)
  } catch (e) {
    error.value = e.message
  } finally {
    loading.value = false
  }
}

async function perform(action, callback) {
  if (busy.value) return

  busy.value = action
  error.value = ''
  notice.value = ''

  try {
    await callback()
  } catch (e) {
    error.value = e.message
  } finally {
    busy.value = ''
  }
}

async function saveProfile() {
  await perform('profile', async () => {
    const data = await api('/admin/profile', {
      method: 'PATCH',
      body: form.value,
    })

    applyProfile(data.user)
    notice.value = data.message
  })
}

async function uploadAvatar(event) {
  const file = event.target.files?.[0]

  event.target.value = ''

  if (!file) return

  error.value = ''
  notice.value = ''

  const allowedTypes = [
    'image/jpeg',
    'image/png',
    'image/webp',
  ]

  if (!allowedTypes.includes(file.type)) {
    error.value = 'Please select a JPG, PNG or WebP image.'
    return
  }

  if (file.size > 2 * 1024 * 1024) {
    error.value = 'The photograph must be 2MB or smaller.'
    return
  }

  await perform('avatar', async () => {
    const body = new FormData()
    body.append('avatar', file)

    const data = await api('/admin/profile/avatar', {
      method: 'POST',
      body,
    })

    profile.value = data.user

    auth.user = {
      ...auth.user,
      ...data.user,
    }

    notice.value = data.message
  })
}

async function removeAvatar() {
  if (!window.confirm('Remove your profile photograph?')) {
    return
  }

  await perform('remove-avatar', async () => {
    const data = await api('/admin/profile/avatar', {
      method: 'DELETE',
    })

    profile.value = data.user

    auth.user = {
      ...auth.user,
      ...data.user,
    }

    notice.value = data.message
  })
}

async function changePassword() {
  if (
    passwords.value.password !==
    passwords.value.password_confirmation
  ) {
    error.value = 'The new passwords do not match.'
    return
  }

  await perform('password', async () => {
    await api('/admin/profile/password', {
      method: 'PUT',
      body: passwords.value,
    })

    passwords.value = {
      current_password: '',
      password: '',
      password_confirmation: '',
    }

    await auth.logout()

    await router.replace({
      path: '/login',
      query: {
        redirect: '/admin/profile',
      },
    })
  })
}

function formatDate(value) {
  if (!value) return 'Not available'

  return new Intl.DateTimeFormat('en-GB', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
  }).format(new Date(value))
}

onMounted(loadProfile)
</script>

<template>
  <div class="profile-page">

    <div class="page-heading">
      <p class="eyebrow">
        MADE EXCLUSIF ADMINISTRATION
      </p>

      <h1>My Profile</h1>

      <p>
        Manage your personal information,
        profile photograph and account security.
      </p>
    </div>

    <div
      v-if="error"
      class="message error-message"
      role="alert"
    >
      {{ error }}
    </div>

    <div
      v-if="notice"
      class="message success-message"
      role="status"
    >
      {{ notice }}
    </div>

    <div v-if="loading" class="profile-card">
      Loading your profile...
    </div>

    <div v-else-if="!profile" class="profile-card">
      <p>Unable to load your profile.</p>

      <button
        class="primary-button"
        @click="loadProfile"
      >
        Try Again
      </button>
    </div>

    <template v-else>

      <div class="profile-grid">

        <!-- Profile summary -->

        <section class="profile-card summary-card">

          <div class="avatar">

            <img
              v-if="profile.avatar_url"
              :src="profile.avatar_url"
              alt="Your profile photograph"
            />

            <UserRound
              v-else
              :size="52"
            />

          </div>

          <h2>{{ profile.name }}</h2>

          <p class="email-text">
            {{ profile.email }}
          </p>

          <span class="role-badge">
            <ShieldCheck :size="15" />
            Administrator
          </span>

          <input
            ref="fileInput"
            type="file"
            accept="image/jpeg,image/png,image/webp"
            hidden
            @change="uploadAvatar"
          />

          <button
            type="button"
            class="secondary-button"
            :disabled="!!busy"
            @click="fileInput?.click()"
          >
            <Camera :size="17" />

            {{
              busy === 'avatar'
                ? 'Uploading...'
                : 'Change Photograph'
            }}
          </button>

          <button
            v-if="profile.avatar_url"
            type="button"
            class="remove-button"
            :disabled="!!busy"
            @click="removeAvatar"
          >
            <Trash2 :size="16" />
            Remove Photograph
          </button>

          <p class="image-hint">
            JPG, PNG or WebP. Maximum 2MB.
          </p>

          <div class="summary-divider"></div>

          <div class="account-detail">
            <Mail :size="19" />

            <div>
              <small>Email verification</small>

              <strong>
                {{
                  profile.email_verified_at
                    ? 'Verified'
                    : 'Not verified'
                }}
              </strong>
            </div>
          </div>

          <div class="account-detail">
            <CalendarDays :size="19" />

            <div>
              <small>Member since</small>

              <strong>
                {{ formatDate(profile.created_at) }}
              </strong>
            </div>
          </div>

        </section>

        <!-- Personal information -->

        <section class="profile-card">

          <div class="section-heading">
            <UserRound :size="22" />

            <div>
              <h2>Personal Information</h2>
              <p>Manage your administrator details.</p>
            </div>
          </div>

          <form @submit.prevent="saveProfile">

            <div class="form-grid">

              <div class="form-field">
                <label for="profile-name">
                  Full Name
                </label>

                <input
                  id="profile-name"
                  v-model.trim="form.name"
                  type="text"
                  maxlength="255"
                  autocomplete="name"
                  required
                />
              </div>

              <div class="form-field">
                <label for="profile-email">
                  Email Address
                </label>

                <input
                  id="profile-email"
                  :value="profile.email"
                  type="email"
                  readonly
                />

                <small>
                  Email changes require a separate
                  verification process.
                </small>
              </div>

              <div class="form-field">
                <label for="profile-phone">
                  Phone Number
                </label>

                <input
                  id="profile-phone"
                  v-model.trim="form.phone"
                  type="tel"
                  maxlength="30"
                  autocomplete="tel"
                  placeholder="+44"
                />
              </div>

              <div class="form-field">
                <label for="profile-role">
                  Account Role
                </label>

                <input
                  id="profile-role"
                  value="Administrator"
                  readonly
                />
              </div>

            </div>

            <div class="form-actions">
              <button
                class="primary-button"
                type="submit"
                :disabled="!!busy"
              >
                <Save :size="17" />

                {{
                  busy === 'profile'
                    ? 'Saving...'
                    : 'Save Changes'
                }}
              </button>
            </div>

          </form>

        </section>

      </div>

      <!-- Password management -->

      <section class="profile-card password-card">

        <div class="section-heading">
          <LockKeyhole :size="22" />

          <div>
            <h2>Change Password</h2>
            <p>Keep your administrator account secure.</p>
          </div>
        </div>

        <form @submit.prevent="changePassword">

          <div class="form-grid">

            <div class="form-field full-width">

              <label for="current-password">
                Current Password
              </label>

              <input
                id="current-password"
                v-model="passwords.current_password"
                :type="showPasswords ? 'text' : 'password'"
                autocomplete="current-password"
                required
              />

            </div>

            <div class="form-field">

              <label for="new-password">
                New Password
              </label>

              <input
                id="new-password"
                v-model="passwords.password"
                :type="showPasswords ? 'text' : 'password'"
                autocomplete="new-password"
                minlength="12"
                required
              />

            </div>

            <div class="form-field">

              <label for="confirm-password">
                Confirm New Password
              </label>

              <input
                id="confirm-password"
                v-model="passwords.password_confirmation"
                :type="showPasswords ? 'text' : 'password'"
                autocomplete="new-password"
                minlength="12"
                required
              />

            </div>

          </div>

          <button
            type="button"
            class="password-toggle"
            @click="showPasswords = !showPasswords"
          >
            <EyeOff v-if="showPasswords" :size="17" />
            <Eye v-else :size="17" />

            {{
              showPasswords
                ? 'Hide passwords'
                : 'Show passwords'
            }}
          </button>

          <div class="security-note">
            <ShieldCheck :size="20" />

            <p>
              Use at least 12 characters, including
              uppercase and lowercase letters,
              numbers and symbols.

              Changing your password will sign you
              out of all existing API sessions.
            </p>
          </div>

          <div class="form-actions">

            <button
              type="submit"
              class="primary-button"
              :disabled="!!busy"
            >
              <LockKeyhole :size="17" />

              {{
                busy === 'password'
                  ? 'Updating...'
                  : 'Update Password'
              }}
            </button>

          </div>

        </form>

      </section>

    </template>

  </div>
</template>

<style scoped>
.profile-page {
  max-width: 1250px;
  margin: 0 auto;
  color: #252525;
}

.page-heading {
  margin-bottom: 28px;
}

.eyebrow {
  font-size: 11px;
  letter-spacing: 1.6px;
  color: #98763e;
  font-weight: 600;
}

.page-heading h1 {
  font-size: 32px;
  margin: 10px 0;
  font-weight: 600;
}

.page-heading > p:last-child {
  font-size: 14px;
  color: #777;
}

.profile-grid {
  display: grid;
  grid-template-columns: 300px minmax(0, 1fr);
  gap: 24px;
  align-items: start;
}

.profile-card {
  background: #fff;
  border: 1px solid #e8e5df;
  border-radius: 14px;
  padding: 28px;
}

.summary-card {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
}

.avatar {
  width: 116px;
  height: 116px;
  border-radius: 50%;
  background: #f3ecdf;
  border: 3px solid #e9dfcc;
  display: grid;
  place-items: center;
  overflow: hidden;
  color: #a48346;
  margin-bottom: 20px;
}

.avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.summary-card h2 {
  font-size: 21px;
  margin: 0 0 8px;
}

.email-text {
  font-size: 13px;
  color: #777;
  overflow-wrap: anywhere;
  max-width: 100%;
}

.role-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: #f5efe3;
  color: #8a6933;
  border-radius: 6px;
  padding: 7px 12px;
  font-size: 12px;
  margin: 12px 0 22px;
}

.primary-button,
.secondary-button,
.remove-button,
.password-toggle {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 9px;
  cursor: pointer;
  font-size: 13px;
}

.primary-button {
  background: #171717;
  color: #fff;
  border: 1px solid #171717;
  border-radius: 7px;
  padding: 13px 20px;
}

.primary-button:hover {
  background: #a48346;
  border-color: #a48346;
}

.secondary-button {
  border: 1px solid #ddd6c9;
  background: #fff;
  color: #333;
  border-radius: 7px;
  padding: 12px 15px;
}

.secondary-button:hover {
  background: #f7f3ec;
}

.remove-button {
  border: none;
  background: transparent;
  color: #b43c3c;
  padding: 12px;
}

button:disabled {
  opacity: 0.55;
  cursor: not-allowed;
}

.image-hint {
  font-size: 11px;
  color: #777;
  margin-top: 12px;
}

.summary-divider {
  width: 100%;
  height: 1px;
  background: #eae7e1;
  margin: 24px 0;
}

.account-detail {
  display: flex;
  align-items: center;
  gap: 12px;
  width: 100%;
  text-align: left;
  margin-bottom: 18px;
  color: #98763e;
}

.account-detail small {
  display: block;
  font-size: 11px;
  color: #777;
  margin-bottom: 5px;
}

.account-detail strong {
  display: block;
  font-size: 13px;
  color: #333;
}

.section-heading {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  margin-bottom: 26px;
}

.section-heading > svg {
  color: #a48346;
  flex-shrink: 0;
}

.section-heading h2 {
  font-size: 19px;
  margin: 0 0 6px;
}

.section-heading p {
  color: #777;
  font-size: 13px;
  margin: 0;
}

.form-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 22px;
}

.form-field {
  display: flex;
  flex-direction: column;
  gap: 9px;
  min-width: 0;
}

.form-field label {
  font-size: 13px;
  font-weight: 600;
  color: #333;
}

.form-field input {
  box-sizing: border-box;
  width: 100%;
  min-height: 45px;
  border: 1px solid #ddd9d2;
  border-radius: 7px;
  padding: 12px;
  background: #fff;
  color: #222;
  font-size: 14px;
}

.form-field input:focus {
  outline: 2px solid #b89455;
  outline-offset: 1px;
}

.form-field input[readonly] {
  background: #f5f5f3;
  color: #666;
}

.form-field small {
  font-size: 11px;
  line-height: 1.5;
  color: #777;
}

.full-width {
  grid-column: 1 / -1;
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  margin-top: 26px;
}

.password-card {
  margin-top: 24px;
}

.password-toggle {
  border: none;
  background: transparent;
  color: #666;
  margin-top: 18px;
  padding: 0;
}

.security-note {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 15px;
  border-radius: 8px;
  background: #f8f5ee;
  color: #806431;
  margin-top: 22px;
}

.security-note svg {
  flex-shrink: 0;
}

.security-note p {
  margin: 0;
  font-size: 12px;
  line-height: 1.7;
}

.message {
  padding: 14px 18px;
  border-radius: 8px;
  font-size: 14px;
  margin-bottom: 20px;
}

.error-message {
  background: #fff0f0;
  border: 1px solid #f0caca;
  color: #a52b2b;
}

.success-message {
  background: #edf8ef;
  border: 1px solid #cce6d0;
  color: #276b38;
}

@media (max-width: 1100px) {
  .profile-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 600px) {
  .profile-card {
    padding: 20px;
  }

  .form-grid {
    grid-template-columns: 1fr;
  }

  .page-heading h1 {
    font-size: 26px;
  }

  .form-actions button {
    width: 100%;
  }
}
</style>
