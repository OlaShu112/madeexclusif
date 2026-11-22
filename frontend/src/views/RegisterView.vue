<script setup>
import { reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const route = useRoute()
const router = useRouter()
const form = reactive({ name: '', email: '', password: '', password_confirmation: '' })
const error = ref('')
const busy = ref(false)

async function submit() {
  busy.value = true
  error.value = ''
  try {
    await auth.register(form)
    router.push(route.query.redirect || { name: 'account' })
  } catch (e) {
    error.value = e.message
  } finally {
    busy.value = false
  }
}
</script>

<template>
  <div class="container section" style="max-width:440px">
    <h1>Create account</h1>
    <div v-if="error" class="alert">{{ error }}</div>
    <form @submit.prevent="submit">
      <div class="field"><label for="n">Name</label><input id="n" v-model="form.name" class="input" required autocomplete="name" /></div>
      <div class="field"><label for="e">Email</label><input id="e" v-model="form.email" type="email" class="input" required autocomplete="email" /></div>
      <div class="field"><label for="p">Password (min. 8 characters)</label><input id="p" v-model="form.password" type="password" minlength="8" class="input" required autocomplete="new-password" /></div>
      <div class="field"><label for="p2">Confirm password</label><input id="p2" v-model="form.password_confirmation" type="password" class="input" required autocomplete="new-password" /></div>
      <button class="btn btn--block" :disabled="busy">{{ busy ? 'Creating…' : 'Create account' }}</button>
    </form>
    <p class="muted" style="margin-top:20px">Already registered? <router-link :to="{ name: 'login', query: route.query }" class="linkbtn">Sign in</router-link></p>
  </div>
</template>
