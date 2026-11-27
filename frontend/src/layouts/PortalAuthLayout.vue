<script setup>
import { computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { usePlatformStore } from '@/stores/platform'

import {
  ShieldCheck,
  Store,
  LockKeyhole
} from 'lucide-vue-next'

const route = useRoute()
const platform = usePlatformStore()

const isAdmin = computed(() =>
  route.path.startsWith('/admin/')
)

const platformName = computed(() =>
  platform.branding.platform_name || 'IVISION'
)

const portalName = computed(() =>
  isAdmin.value
    ? platform.branding.administration_title ||
      'Platform Administration'
    : 'Business Portal'
)

const portalLogo = computed(() =>
  isAdmin.value
    ? platform.branding.loginLogo ||
      platform.branding.primaryLogo
    : platform.branding.primaryLogo
)

const portalStyles = computed(() => ({
  ...platform.themeVariables,

  '--portal-background':
    platform.branding.login_background_colour ||
    '#F5F6F9'
}))

const contentStyles = computed(() => {
  const image = platform.branding.loginBackground

  if (!isAdmin.value || !image) {
    return {}
  }

  return {
    backgroundImage: `url("${image}")`,
    backgroundSize: 'cover',
    backgroundPosition: 'center',
    backgroundRepeat: 'no-repeat'
  }
})

onMounted(async () => {
  try {
    await platform.loadBranding()
  } catch (error) {
    console.error(
      'Unable to load portal branding:',
      error
    )
  }
})
</script>

<template>
  <div
    class="portal-auth"
    :style="portalStyles"
  >

    <header class="portal-header">

      <div class="portal-brand">

        <RouterLink
          to="/"
          class="ivision-brand"
          :aria-label="platformName"
        >
          <img
            v-if="portalLogo"
            :src="portalLogo"
            :alt="platformName"
            class="portal-logo"
          />

          <template v-else>
            <component
              :is="isAdmin ? ShieldCheck : Store"
              :size="30"
            />

            <strong>
              {{ platformName }}
            </strong>
          </template>
        </RouterLink>

        <span>
          {{ portalName }}
        </span>

      </div>

      <div class="secure-label">
        <LockKeyhole :size="15" />
        Secure Portal
      </div>

    </header>

    <main
      class="portal-content"
      :style="contentStyles"
    >
      <RouterView />
    </main>

    <footer class="portal-footer">

      <p>
        &copy; {{ new Date().getFullYear() }}
        {{ platformName }}.
        {{ platform.branding.copyright_text }}
      </p>

      <p>
        Secure Authentication & Account Management
      </p>

    </footer>

  </div>
</template>

<style scoped>
.portal-auth {
  min-height: 100dvh;
  display: flex;
  flex-direction: column;

  background:
    var(--portal-background, #F5F6F9);

  color:
    var(--platform-text, #111827);

  font-family:
    var(--platform-font, system-ui, sans-serif);
}

.portal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 22px 40px;

  background:
    var(--platform-header, #FFFFFF);

  border-bottom: 1px solid #e5e7eb;
}

.portal-brand {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 12px;
  font-size: 20px;
  font-weight: 700;
}

.ivision-brand {
  display: flex;
  align-items: center;
  gap: 10px;

  color:
    var(--platform-primary, #111827);

  font-weight: 800;
  letter-spacing: 2px;
  text-decoration: none;
}

.portal-logo {
  display: block;
  max-width: 220px;
  max-height: 65px;
  object-fit: contain;
}

.portal-brand span {
  font-size: 13px;
  font-weight: 500;

  color:
    var(--platform-text, #111827);

  opacity: 0.7;
}

.secure-label {
  display: flex;
  align-items: center;
  gap: 7px;
  font-size: 13px;
  color: inherit;
  opacity: 0.75;
}

.portal-content {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 50px 20px;
}

.portal-footer {
  padding: 24px;
  text-align: center;

  background:
    var(--platform-footer, #FFFFFF);

  border-top: 1px solid #e5e7eb;
  font-size: 13px;
  color: inherit;
}

.portal-footer p {
  margin: 6px 0;
  opacity: 0.75;
}

@media (max-width: 600px) {
  .portal-header {
    padding: 18px;
  }

  .portal-brand {
    font-size: 16px;
  }

  .portal-logo {
    max-width: 160px;
    max-height: 50px;
  }

  .secure-label {
    display: none;
  }

  .portal-content {
    padding: 30px 16px;
  }
}
</style>