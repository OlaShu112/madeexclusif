/*
|--------------------------------------------------------------------------
| IVISION — Application Entry Point
|--------------------------------------------------------------------------
| Vue 3
| Pinia State Management
| Vue Router
| Authentication Initialisation
| Global Typography
| Global Application Styles
|--------------------------------------------------------------------------
*/

import { createApp } from 'vue'
import { createPinia } from 'pinia'

/*
|--------------------------------------------------------------------------
| Global Font Families
|--------------------------------------------------------------------------
*/

import '@fontsource-variable/geist'
import '@fontsource-variable/geist-mono'
import '@fontsource-variable/inter'
import '@fontsource-variable/roboto'

/*
|--------------------------------------------------------------------------
| Global Stylesheets
|--------------------------------------------------------------------------
*/

import './styles/main.css'
import './styles/typography.css'

/*
|--------------------------------------------------------------------------
| Application Components
|--------------------------------------------------------------------------
*/

import App from './App.vue'
import router from './router'

/*
|--------------------------------------------------------------------------
| Application Stores
|--------------------------------------------------------------------------
*/

import { useAuthStore } from './stores/auth'

/*
|--------------------------------------------------------------------------
| Initialise IVISION Application
|--------------------------------------------------------------------------
*/

async function bootstrap() {

  const app = createApp(App)

  /*
  |--------------------------------------------------------------------------
  | Initialise Pinia
  |--------------------------------------------------------------------------
  */

  const pinia = createPinia()

  app.use(pinia)

  /*
  |--------------------------------------------------------------------------
  | Restore Authentication Session
  |--------------------------------------------------------------------------
  */

  const auth = useAuthStore(pinia)

  try {

    await auth.init()

  } catch (error) {

    console.error(
      '[IVISION] Authentication initialisation failed:',
      error
    )

  }

  /*
  |--------------------------------------------------------------------------
  | Register Vue Router
  |--------------------------------------------------------------------------
  */

  app.use(router)

  /*
  |--------------------------------------------------------------------------
  | Wait for Router Initialisation
  |--------------------------------------------------------------------------
  */

  await router.isReady()

  /*
  |--------------------------------------------------------------------------
  | Mount IVISION Application
  |--------------------------------------------------------------------------
  */

  app.mount('#app')

}

/*
|--------------------------------------------------------------------------
| Start Application
|--------------------------------------------------------------------------
*/

bootstrap().catch(error => {

  console.error(
    '[IVISION] Application initialisation failed:',
    error
  )

})