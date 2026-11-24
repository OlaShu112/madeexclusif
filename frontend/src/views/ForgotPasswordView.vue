<script setup>
import { ref } from 'vue'
import { api } from '@/api/http'

const email = ref('')
const busy = ref(false)
const error = ref('')
const success = ref('')

async function submit() {
  if (busy.value) return

  busy.value = true
  error.value = ''
  success.value = ''

  try {
    const response = await api('/forgot-password', {
      method: 'POST',
      body: {
        email: email.value.trim(),
      },
    })

    success.value = response.message
  } catch (err) {
    error.value =
      err.message || 'Unable to process your request. Please try again.'
  } finally {
    busy.value = false
  }
}
</script>

<template>
  <main class="container section recovery-page">

    <p class="eyebrow">Made Exclusif</p>

    <h1>Forgot Your Password?</h1>

    <p class="muted">
      Enter the email address associated with your account.
      We will send you a secure link to reset your password.
    </p>

    <div v-if="error" class="alert" role="alert">
      {{ error }}
    </div>

    <div
      v-if="success"
      class="success-message"
      role="status"
    >
      {{ success }}
    </div>

    <form @submit.prevent="submit">

      <div class="field">

        <label for="email">Email Address</label>

        <input
          id="email"
          v-model="email"
          type="email"
          class="input"
          autocomplete="email"
          placeholder="Enter your email address"
          maxlength="255"
          required
        />

      </div>

      <button
        type="submit"
        class="btn btn--block"
        :disabled="busy"
      >
        {{ busy ? 'Sending...' : 'Send Password Reset Link' }}
      </button>

    </form>

    <p class="back-link">

      Remember your password?

      <router-link
        :to="{ name: 'login' }"
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

.success-message {
  background: #eef8f0;
  color: #176c36;
  border: 1px solid #cce8d2;
  padding: 16px;
  border-radius: 5px;
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
</style>