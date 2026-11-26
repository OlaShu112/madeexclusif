<script setup>
import { computed, reactive, ref } from 'vue'
import { useRoute } from 'vue-router'
import { api } from '@/api/http'
import { useAuthStore } from '@/stores/auth'
import { useBusinessContext } from '@/composables/useBusinessContext'

const route = useRoute()
const auth = useAuthStore()

const {
  businessName,
  storeSlug,
  isIndependentStore,
  loginRoute,
  forgotPasswordRoute
} = useBusinessContext()

const form = reactive({
  token: typeof route.query.token === 'string'
    ? route.query.token : '',
  email: typeof route.query.email === 'string'
    ? route.query.email : '',
  password: '',
  password_confirmation: '',
})

const busy = ref(false)
const error = ref('')
const success = ref('')
const showPassword = ref(false)

const validLink = computed(() =>
  Boolean(form.token && form.email)
)

async function submit() {
  if (busy.value || !validLink.value) return

  error.value = ''
  success.value = ''

  if (form.password !== form.password_confirmation) {
    error.value = 'Your passwords do not match.'
    return
  }

  busy.value = true

  try {
    const response = await api('/reset-password', {
      method: 'POST',
      body: {
        ...form,

        store: isIndependentStore.value
          ? storeSlug.value
          : null,
      },
    })

    await auth.logout()

    success.value = response.message

    form.password = ''
    form.password_confirmation = ''
    form.token = ''

  } catch (err) {
    error.value =
      err.message || 'Unable to reset your password.'
  } finally {
    busy.value = false
  }
}
</script>

<template>
  <main
    class="container section recovery-page"
    :class="{ 'business-recovery': isIndependentStore }"
  >

    <p class="eyebrow">{{ businessName }}</p>

    <h1>Reset Your Password</h1>

    <div v-if="success" class="success-message" role="status">

      <p>{{ success }}</p>

      <router-link
        :to="loginRoute()"
        class="btn btn--block"
      >
        Sign In
      </router-link>

    </div>

    <div v-else-if="!validLink" class="alert" role="alert">

      <p>Your password reset link is invalid or incomplete.</p>

      <router-link
        :to="forgotPasswordRoute()"
        class="linkbtn"
      >
        Request a New Reset Link
      </router-link>

    </div>

    <template v-else>

      <p class="muted">
        Create a new secure password for your
        {{ businessName }} account.
      </p>

      <div v-if="error" class="alert" role="alert">
        {{ error }}
      </div>

      <form @submit.prevent="submit">

        <div class="field">

          <label for="email">Email Address</label>

          <input
            id="email"
            v-model="form.email"
            type="email"
            class="input"
            autocomplete="email"
            readonly
            required
          />

        </div>

        <div class="field">

          <label for="password">New Password</label>

          <input
            id="password"
            v-model="form.password"
            :type="showPassword ? 'text' : 'password'"
            class="input"
            autocomplete="new-password"
            minlength="8"
            required
          />

        </div>

        <div class="field">

          <label for="password_confirmation">
            Confirm New Password
          </label>

          <input
            id="password_confirmation"
            v-model="form.password_confirmation"
            :type="showPassword ? 'text' : 'password'"
            class="input"
            autocomplete="new-password"
            minlength="8"
            required
          />

        </div>

        <label class="password-toggle">

          <input
            v-model="showPassword"
            type="checkbox"
          />

          Show passwords

        </label>

        <div class="password-requirements">

          <strong>Password requirements:</strong>

          <ul>
            <li>At least eight characters</li>
            <li>One uppercase letter</li>
            <li>One lowercase letter</li>
            <li>One number</li>
            <li>One special character</li>
          </ul>

        </div>

        <button
          type="submit"
          class="btn btn--block"
          :disabled="busy"
        >
          {{ busy ? 'Resetting...' : 'Reset Password' }}
        </button>

      </form>

    </template>

    <p class="back-link">

      <router-link
        :to="loginRoute()"
        class="linkbtn"
      >
        Back to Sign In
      </router-link>

    </p>

  </main>
</template>

<style scoped>
.recovery-page {
  max-width: 480px;
  margin: 0 auto;
  padding-top: 60px;
  padding-bottom: 80px;
}

.eyebrow {
  text-transform: uppercase;
  letter-spacing: 3px;
  color: #94713b;
  font-size: 12px;
  margin-bottom: 12px;
}

h1 {
  margin-bottom: 18px;
}

.muted {
  line-height: 1.7;
  margin-bottom: 25px;
}

.field {
  margin-bottom: 20px;
}

.password-toggle {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 20px;
  font-size: 14px;
}

.password-requirements {
  padding: 18px;
  background: #f7f7f7;
  border-left: 3px solid #c9a86a;
  margin-bottom: 25px;
  font-size: 14px;
  line-height: 1.8;
}

.password-requirements ul {
  padding-left: 20px;
  margin-top: 8px;
}

.success-message {
  padding: 20px;
  background: #eef8f0;
  color: #176c36;
  border: 1px solid #cce8d2;
  border-radius: 5px;
}

.success-message p {
  margin-bottom: 20px;
}

.back-link {
  margin-top: 25px;
  text-align: center;
}

button:disabled {
  opacity: 0.6;
  cursor: wait;
}

.business-recovery {
  color: var(--store-text);
}

.business-recovery .eyebrow,
.business-recovery h1,
.business-recovery label,
.business-recovery .linkbtn {
  color: var(--store-text);
}

.business-recovery .btn {
  background: var(--store-primary);
  color: var(--store-primary-text);
  border-color: var(--store-primary);
}

.business-recovery .input:focus {
  outline: 2px solid var(--store-primary);
  outline-offset: 2px;
}
</style>