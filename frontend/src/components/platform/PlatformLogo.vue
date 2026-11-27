<script setup>

import { computed, ref, watch } from 'vue'
import { RouterLink } from 'vue-router'
import { usePlatformStore } from '@/stores/platform'

const platform = usePlatformStore()

const props = defineProps({

  variant: {
    type: String,
    default: 'light',
    validator: value =>
      ['light', 'dark', 'primary'].includes(value),
  },

  showName: {
    type: Boolean,
    default: true,
  },

  size: {
    type: Number,
    default: 42,
  },

  to: {
    type: String,
    default: '/',
  },

})

const imageFailed = ref(false)

const platformName = computed(() =>
  platform.branding.platform_name || 'IVISION'
)

function resolveLogo(value) {

  if (typeof value === 'string') {
    return value
  }

  if (value && typeof value.url === 'string') {
    return value.url
  }

  return null

}

const logoUrl = computed(() => {

  const branding = platform.branding

  const primary = resolveLogo(
    branding.primaryLogo
  )

  const light = resolveLogo(
    branding.lightLogo
  )

  const dark = resolveLogo(
    branding.darkLogo
  )

  if (props.variant === 'dark') {
    return dark || primary || light
  }

  if (props.variant === 'primary') {
    return primary || light || dark
  }

  return light || primary || dark

})

const fallbackLetter = computed(() =>
  platformName.value.charAt(0).toUpperCase()
)

watch(logoUrl, () => {
  imageFailed.value = false
})

</script>

<template>

  <RouterLink
    :to="to"
    class="ivision-platform-logo"
    :aria-label="`${platformName} homepage`"
    :style="{
      '--logo-size': `${size}px`
    }"
  >

    <img
      v-if="logoUrl && !imageFailed"
      :src="logoUrl"
      :alt="`${platformName} logo`"
      class="ivision-logo-image"
      @error="imageFailed = true"
    />

    <span
      v-else
      class="ivision-logo-fallback"
      aria-hidden="true"
    >
      {{ fallbackLetter }}
    </span>

    <span
      v-if="showName"
      class="ivision-logo-name"
    >
      {{ platformName }}
    </span>

  </RouterLink>

</template>

<style scoped>

.ivision-platform-logo {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  text-decoration: none;
  color: inherit;
  flex-shrink: 0;
}

.ivision-logo-image {
  display: block;
  width: var(--logo-size);
  height: var(--logo-size);
  object-fit: contain;
  flex-shrink: 0;
}

.ivision-logo-fallback {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: var(--logo-size);
  height: var(--logo-size);
  background: var(--platform-primary, #111827);
  color: #ffffff;
  border-radius: 8px;
  font-size: 24px;
  font-weight: 700;
  flex-shrink: 0;
}

.ivision-logo-name {
  font-family: var(
    --platform-font,
    'Geist Variable',
    sans-serif
  );
  font-size: 23px;
  font-weight: 750;
  letter-spacing: 1.5px;
  line-height: 1;
  white-space: nowrap;
}

@media (max-width: 768px) {

  .ivision-logo-image,
  .ivision-logo-fallback {
    width: calc(var(--logo-size) * 0.85);
    height: calc(var(--logo-size) * 0.85);
  }

  .ivision-logo-name {
    font-size: 19px;
  }

}

</style>