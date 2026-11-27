<script setup>
import { ref, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { api } from '@/api/http'
import { usePlatformStore } from '@/stores/platform'

import {
  ShieldCheck,
  Store,
  Eye,
  EyeOff,
  LockKeyhole
} from 'lucide-vue-next'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const platform = usePlatformStore()

const email = ref('')
const password = ref('')
const showPassword = ref(false)
const loading = ref(false)
const error = ref('')

const isAdmin = computed(() =>
  route.path.startsWith('/admin/')
)

const heading = computed(() =>
  isAdmin.value
    ? 'Administrator Sign In'
    : 'Business Sign In'
)

async function login() {
  if (loading.value) return

  loading.value = true
  error.value = ''

  try {
    await auth.login({
      email: email.value.trim(),
      password: password.value,
      portal: isAdmin.value ? 'admin' : 'seller'
    })

    if (isAdmin.value) {
      await router.replace('/admin')
      return
    }

    const response = await api('/my-store')

    const store = response?.store

    if (store?.status === 'active') {
      await router.replace('/seller')
    } else {
      await router.replace('/open-store')
    }

  } catch (err) {
    error.value =
      err?.message ||
      'Unable to sign in. Please check your credentials.'

  } finally {
    loading.value = false
  }
}
</script>

<template>
  <section class="login-card">

    <div class="login-heading">

      <div class="portal-icon">
        <component
          :is="isAdmin ? ShieldCheck : Store"
          :size="36"
        />
      </div>

      <h1>{{ heading }}</h1>

      <p v-if="isAdmin">
        Secure access to the platform administration portal.
      </p>

      <p v-else>
        Sign in to manage your business, products,
        customers and orders.
      </p>

    </div>

    <div
      v-if="error"
      class="error-message"
      role="alert"
    >
      {{ error }}
    </div>

    <form @submit.prevent="login">

      <div class="field">

        <label for="portal-email">
          Email Address
        </label>

        <input
          id="portal-email"
          v-model="email"
          type="email"
          autocomplete="username"
          placeholder="Enter your email address"
          required
          :disabled="loading"
        />

      </div>

      <div class="field">

        <label for="portal-password">
          Password
        </label>

        <div class="password-field">

          <input
            id="portal-password"
            v-model="password"
            :type="showPassword ? 'text' : 'password'"
            autocomplete="current-password"
            placeholder="Enter your password"
            required
            :disabled="loading"
          />

          <button
            type="button"
            class="password-toggle"
            :aria-label="showPassword
              ? 'Hide password'
              : 'Show password'"
            @click="showPassword = !showPassword"
          >
            <EyeOff v-if="showPassword" :size="19" />
            <Eye v-else :size="19" />
          </button>

        </div>

      </div>

      <button
        type="submit"
        class="login-button"
        :disabled="loading"
      >
        {{ loading ? 'Signing In...' : 'Sign In' }}
      </button>

    </form>

    <div v-if="!isAdmin" class="register-section">

      <p>New to {{ platform.branding.platform_name }}?</p>

      <RouterLink to="/business/register">
        Create Your Business Account
      </RouterLink>

    </div>

    <div class="security-notice">
      <LockKeyhole :size="15" />

      <span>
        {{ isAdmin
          ? 'Authorised administrators only'
          : 'Secure business account access' }}
      </span>
    </div>

  </section>
</template>

<style scoped>
.login-card {
  width: 100%;
  max-width: 440px;
  background: #fff;
  padding: 38px;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  box-shadow: 0 8px 30px rgba(0, 0, 0, .05);
}

.login-heading {
  text-align: center;
  margin-bottom: 30px;
}

.portal-icon {
  width: 76px;
  height: 76px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f1f2f4;
  border-radius: 18px;
  margin: 0 auto 22px;
}

.login-heading h1 {
  font-size: 27px;
  font-weight: 700;
  margin-bottom: 12px;
}

.login-heading p {
  font-size: 14px;
  line-height: 1.6;
  color: #777;
}

.field {
  display: flex;
  flex-direction: column;
  gap: 9px;
  margin-bottom: 22px;
}

.field label {
  font-size: 14px;
  font-weight: 600;
}

.field input {
  width: 100%;
  padding: 14px;
  border: 1px solid #d6d8dc;
  border-radius: 6px;
  font-size: 14px;
  box-sizing: border-box;
  background: #fff;
}

.field input:focus {
  outline: 2px solid #555;
  outline-offset: 1px;
}

.password-field {
  position: relative;
}

.password-field input {
  padding-right: 48px;
}

.password-toggle {
  position: absolute;
  right: 12px;
  top: 50%;
  transform: translateY(-50%);
  border: none;
  background: transparent;
  color: #666;
  cursor: pointer;
}

.login-button {
  width: 100%;
  padding: 15px;
  border: none;
  border-radius: 6px;
  background: #142033;
  color: #fff;
  font-size: 15px;
  font-weight: 600;
  cursor: pointer;
}

.login-button:disabled {
  opacity: .6;
  cursor: not-allowed;
}

.error-message {
  background: #fff1f1;
  border: 1px solid #efcccc;
  color: #a61b1b;
  padding: 13px;
  border-radius: 6px;
  margin-bottom: 20px;
  font-size: 14px;
}

.register-section {
  margin-top: 24px;
  text-align: center;
  font-size: 14px;
}

.register-section a {
  display: inline-block;
  margin-top: 7px;
  color: #171717;
  font-weight: 600;
}

.security-notice {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 7px;
  border-top: 1px solid #eee;
  margin-top: 28px;
  padding-top: 20px;
  font-size: 12px;
  color: #777;
}

@media (max-width: 600px) {
  .login-card {
    padding: 25px;
  }

  .login-heading h1 {
    font-size: 23px;
  }
}
/* Dynamic platform login branding */

.login-card {
  color: #111827;
  background: #FFFFFF;
  font-family:
    var(--platform-font, system-ui, sans-serif);
}

.portal-icon {
  background:
    var(--platform-primary, #111827);

  color:
    var(--platform-button-text, #FFFFFF);
}

.login-button {
  background:
    var(--platform-button, #111827);

  color:
    var(--platform-button-text, #FFFFFF);
}

.field input:focus {
  outline-color:
    var(--platform-primary, #111827);
}
</style>