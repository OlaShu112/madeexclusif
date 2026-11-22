<script setup>
// Placeholder bottle drawn in SVG, tinted by the product's accent colour.
// Swap for real photography by rendering <img :src="product.image"> where this is used.
defineProps({ accent: { type: String, default: '#c9a86a' }, label: { type: String, default: 'MX' } })
const uid = Math.random().toString(36).slice(2, 8)
</script>

<template>
  <svg viewBox="0 0 120 200" role="img" :aria-label="`${label} fragrance bottle`" style="width:100%;max-width:190px;height:auto">
    <defs>
      <linearGradient :id="`g${uid}`" x1="0" x2="1">
        <stop offset="0" :stop-color="accent" stop-opacity=".75" />
        <stop offset=".45" :stop-color="accent" />
        <stop offset="1" :stop-color="accent" stop-opacity=".7" />
      </linearGradient>
      <linearGradient :id="`c${uid}`" x1="0" x2="1">
        <stop offset="0" stop-color="#8d8f93" /><stop offset=".5" stop-color="#e8e9ec" /><stop offset="1" stop-color="#8d8f93" />
      </linearGradient>
    </defs>
    <rect x="42" y="8" width="36" height="30" rx="2" :fill="`url(#c${uid})`" />
    <rect x="52" y="38" width="16" height="8" fill="#b9bbbf" />
    <rect x="14" y="46" width="92" height="140" rx="8" :fill="`url(#g${uid})`" />
    <rect x="22" y="52" width="8" height="128" rx="4" fill="#fff" opacity=".22" />
    <text x="60" y="122" text-anchor="middle" font-family="Cormorant Garamond, serif" font-size="30" fill="#fff" opacity=".92">MX</text>
  </svg>
</template>
