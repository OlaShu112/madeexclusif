
<script setup>
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { Store, ArrowRight } from 'lucide-vue-next'

const router = useRouter()
const auth = useAuthStore()

const busy = ref(false)
const error = ref('')

const form = reactive({
  name: '',
  email: '',
  password: '',
  password_confirmation: ''
})

async function submit() {
  if (busy.value) return

  error.value = ''

  if (form.password !== form.password_confirmation) {
    error.value = 'Your passwords do not match.'
    return
  }

  busy.value = true

  try {
    await auth.register({
      name: form.name.trim(),
      email: form.email.trim(),
      password: form.password,
      password_confirmation: form.password_confirmation
    })

    await router.replace('/open-store')

  } catch (err) {
    error.value = err?.message ||
      'Unable to create your business account.'
  } finally {
    busy.value = false
  }
}
</script>

<template>
  <section class="business-register">

    <div class="register-heading">
      <div class="register-icon">
        <Store :size="30" />
      </div>

      <span>IVISION BUSINESS</span>

      <h1>Start Your Business</h1>

      <p>
        Create your account and take the first step
        towards launching your online store.
      </p>
    </div>

    <div v-if="error" class="register-error" role="alert">
      {{ error }}
    </div>

    <form @submit.prevent="submit">

      <label for="business-name">Full Name</label>
      <input
        id="business-name"
        v-model="form.name"
        autocomplete="name"
        maxlength="255"
        required
      />

      <label for="business-email">Email Address</label>
      <input
        id="business-email"
        v-model="form.email"
        type="email"
        autocomplete="email"
        required
      />

      <label for="business-password">Password</label>
      <input
        id="business-password"
        v-model="form.password"
        type="password"
        minlength="12"
        autocomplete="new-password"
        required
      />

      <small>Use at least 12 characters.</small>

      <label for="business-confirm">
        Confirm Password
      </label>

      <input
        id="business-confirm"
        v-model="form.password_confirmation"
        type="password"
        autocomplete="new-password"
        required
      />

      <button type="submit" :disabled="busy">
        {{ busy ? 'Creating Account...' : 'Create Business Account' }}
        <ArrowRight :size="18" />
      </button>

    </form>

    <p class="register-note">
      Already have an account?
      <RouterLink to="/seller/login">
        Sign In
      </RouterLink>
    </p>

    <p class="register-note">
      No subscription payment is collected
      during this registration stage.
    </p>

  </section>
</template>

<style scoped>
.business-register {
  width: 100%;
  max-width: 470px;
  background: white;
  padding: 35px;
  border: 1px solid #e4e7ec;
  border-radius: 14px;
  box-shadow: 0 10px 40px rgba(20, 32, 51, .06);
  box-sizing: border-box;
}

.register-heading {
  text-align: center;
  margin-bottom: 28px;
}

.register-icon {
  width: 64px;
  height: 64px;
  display: grid;
  place-items: center;
  background: #edf2ff;
  color: #315fc5;
  border-radius: 14px;
  margin: 0 auto 20px;
}

.register-heading span {
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 2px;
  color: #315fc5;
}

.register-heading h1 {
  font-size: 28px;
  margin: 15px 0;
}

.register-heading p,
.register-note {
  font-size: 14px;
  color: #667085;
  line-height: 1.7;
}

.business-register label {
  display: block;
  font-size: 14px;
  font-weight: 600;
  margin: 18px 0 8px;
}

.business-register input {
  width: 100%;
  padding: 13px;
  border: 1px solid #d0d5dd;
  border-radius: 7px;
  box-sizing: border-box;
  background: #fff;
}

.business-register input:focus {
  outline: 2px solid #315fc5;
  outline-offset: 1px;
}

.business-register small {
  display: block;
  margin-top: 7px;
  color: #667085;
}

.business-register button {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  background: #142033;
  color: #fff;
  border: 0;
  border-radius: 7px;
  padding: 15px;
  margin-top: 25px;
  cursor: pointer;
  font-weight: 600;
}

.business-register button:disabled {
  opacity: .6;
  cursor: not-allowed;
}

.register-note {
  text-align: center;
  margin-top: 20px;
}

.register-note a {
  color: #315fc5;
  font-weight: 600;
}

.register-error {
  padding: 12px;
  background: #fff1f1;
  color: #a61b1b;
  border-radius: 6px;
  font-size: 14px;
}

@media (max-width: 480px) {
  .business-register {
    padding: 24px;
  }
}
</style>
