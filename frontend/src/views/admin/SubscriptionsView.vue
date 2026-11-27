
<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { api } from '@/api/http'
import { usePlatformStore } from '@/stores/platform'

import {
  CreditCard,
  Plus,
  RefreshCw,
  Search,
  Pencil,
  Percent,
  Ticket,
  Users,
  History,
  ChartBar,
  LayoutDashboard,
  X
} from 'lucide-vue-next'

const platform = usePlatformStore()

const base = '/admin/platform/subscriptions'

const activeTab = ref('plans')
const loading = ref(false)
const saving = ref(false)
const error = ref('')
const success = ref('')
const search = ref('')

const plans = ref([])
const records = ref([])
const statistics = ref({})
const pagination = ref(null)

const editor = ref(null)
const editingId = ref(null)
const form = reactive({})

const tabs = [
  { key: 'overview', label: 'Overview', icon: LayoutDashboard },
  { key: 'plans', label: 'Subscription Plans', icon: CreditCard },
  { key: 'businesses', label: 'Business Subscriptions', icon: Users },
  { key: 'offers', label: 'Promotional Offers', icon: Percent },
  { key: 'coupons', label: 'Discount Coupons', icon: Ticket },
  { key: 'price-history', label: 'Price History', icon: History },
  { key: 'usage', label: 'Usage and Limits', icon: ChartBar }
]

const titles = {
  plans: 'Subscription Plans',
  businesses: 'Business Subscriptions',
  offers: 'Promotional Offers',
  coupons: 'Discount Coupons',
  'price-history': 'Price History',
  usage: 'Usage and Limits'
}

const columns = {
  plans: [
    'Plan', 'Monthly Price', 'Annual Price',
    'Product Limit', 'Status', 'Actions'
  ],
  businesses: [
    'Business', 'Plan', 'Billing Interval',
    'Agreed Price', 'Status', 'Renewal Date'
  ],
  offers: [
    'Offer', 'Plan', 'Discount',
    'Duration', 'End Date', 'Status', 'Actions'
  ],
  coupons: [
    'Coupon Code', 'Offer', 'Redemptions',
    'End Date', 'Status', 'Actions'
  ],
  'price-history': [
    'Plan', 'Action', 'Previous Price',
    'New Price', 'Date'
  ],
  usage: [
    'Business', 'Metric', 'Quantity', 'Period'
  ]
}

const canCreate = computed(() =>
  ['plans', 'offers', 'coupons'].includes(activeTab.value)
)

const createLabel = computed(() => ({
  plans: 'Create Plan',
  offers: 'Create Offer',
  coupons: 'Create Coupon'
}[activeTab.value]))

const filteredRecords = computed(() => {
  const query = search.value.trim().toLowerCase()

  if (!query) return records.value

  return records.value.filter(record =>
    JSON.stringify(record).toLowerCase().includes(query)
  )
})

function money(value) {
  if (value === null || value === undefined) return '—'

  return new Intl.NumberFormat('en-GB', {
    style: 'currency',
    currency: 'GBP'
  }).format(Number(value) / 100)
}

function pounds(value) {
  return value === null || value === undefined
    ? ''
    : (Number(value) / 100).toFixed(2)
}

function minor(value) {
  const text = String(value ?? '').trim()

  if (!/^\d{1,8}(\.\d{1,2})?$/.test(text)) {
    throw new Error('Enter a valid non-negative price with up to two decimal places.')
  }

  const [whole, decimal = ''] = text.split('.')

  return Number(whole) * 100 +
    Number(decimal.padEnd(2, '0'))
}

function nullableMinor(value) {
  return value === '' || value === null
    ? null
    : minor(value)
}

function nullableInteger(value) {
  if (value === '' || value === null || value === undefined) {
    return null
  }

  const number = Number(value)

  if (!Number.isSafeInteger(number) || number < 0) {
    throw new Error('Resource limits must be non-negative whole numbers.')
  }

  return number
}

function date(value) {
  if (!value) return '—'

  const result = new Date(value)

  return Number.isNaN(result.getTime())
    ? value
    : result.toLocaleDateString('en-GB')
}

function localDateTime(value) {
  if (!value) return ''

  const result = new Date(value)

  if (Number.isNaN(result.getTime())) return ''

  return new Date(
    result.getTime() - result.getTimezoneOffset() * 60000
  ).toISOString().slice(0, 16)
}

function isoDate(value) {
  return value ? new Date(value).toISOString() : null
}

function humanise(value) {
  return String(value || '—').replaceAll('_', ' ')
}

function slugify(value) {
  return value
    .toLowerCase()
    .trim()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-|-$/g, '')
}

function getError(exception) {
  if (exception.errors) {
    return Object.values(exception.errors).flat().join(' ')
  }

  return exception.message || 'The request could not be completed.'
}

function unpack(response, key) {
  const result =
    response?.[key] ??
    response?.data ??
    response

  if (Array.isArray(result)) {
    return { rows: result, pagination: null }
  }

  if (Array.isArray(result?.data)) {
    return {
      rows: result.data,
      pagination: result
    }
  }

  throw new Error('Unexpected response from the subscription API.')
}

async function loadPlans() {
  const response = await api(`${base}/plans`, {
    params: { per_page: 100 }
  })

  plans.value = unpack(response, 'plans').rows
}

async function loadOverview() {
  const response = await api(`${base}/overview`)

  statistics.value = response.statistics ?? response
}

async function load(page = 1) {
  if (loading.value) return

  loading.value = true
  error.value = ''

  const tab = activeTab.value

  try {
    if (tab === 'overview') {
      await loadOverview()
      return
    }

    const response = await api(`${base}/${tab}`, {
      params: {
        page,
        per_page: 20
      }
    })

    const key = {
      businesses: 'subscriptions',
      'price-history': 'histories'
    }[tab] ?? tab

    const result = unpack(response, key)

    records.value = result.rows
    pagination.value = result.pagination

  } catch (exception) {
    records.value = []
    pagination.value = null
    error.value = getError(exception)

  } finally {
    loading.value = false
  }
}

async function changeTab(tab) {
  if (loading.value || saving.value || editor.value) return

  activeTab.value = tab
  search.value = ''
  success.value = ''
  records.value = []
  pagination.value = null

  await load()
}

function resetForm(values) {
  Object.keys(form).forEach(key => delete form[key])
  Object.assign(form, values)
}

async function openPlan(record = null) {
  editingId.value = record?.id ?? null

  resetForm({
    name: record?.name ?? '',
    slug: record?.slug ?? '',
    description: record?.description ?? '',
    monthly_price: pounds(record?.monthly_price_minor ?? 0),
    annual_price: pounds(record?.annual_price_minor ?? 0),
    product_limit: record?.product_limit ?? '',
    storage_limit_bytes: record?.storage_limit_bytes ?? '',
    ai_monthly_limit: record?.ai_monthly_limit ?? '',
    features: Array.isArray(record?.features)
      ? record.features.join('\n')
      : '',
    is_active: Boolean(record?.is_active)
  })

  editor.value = 'plans'
}

async function openOffer(record = null) {
  error.value = ''

  try {
    await loadPlans()
  } catch (exception) {
    error.value = getError(exception)
    return
  }

  editingId.value = record?.id ?? null

  resetForm({
    subscription_plan_id: record?.subscription_plan_id ?? '',
    name: record?.name ?? '',
    slug: record?.slug ?? '',
    description: record?.description ?? '',
    discount_type: record?.discount_type ?? 'fixed_price',
    monthly_discount_price: pounds(record?.monthly_discount_price_minor),
    annual_discount_price: pounds(record?.annual_discount_price_minor),
    discount_amount: pounds(record?.discount_amount_minor),
    discount_percentage: record?.discount_percentage ?? '',
    billing_interval: record?.billing_interval ?? 'both',
    duration_type: record?.duration_type ?? 'first_period',
    duration_periods: record?.duration_periods ?? 3,
    eligibility: record?.eligibility ?? 'new',
    eligible_store_ids: (record?.eligible_store_ids ?? []).join(', '),
    starts_at: localDateTime(record?.starts_at),
    ends_at: localDateTime(record?.ends_at),
    redemption_limit: record?.redemption_limit ?? '',
    requires_coupon: Boolean(record?.requires_coupon),
    is_active: Boolean(record?.is_active),
    show_on_website: Boolean(record?.show_on_website),
    badge_text: record?.badge_text ?? '',
    banner_text: record?.banner_text ?? ''
  })

  editor.value = 'offers'
}

const availableOffers = ref([])

async function openCoupon(record = null) {
  error.value = ''

  try {
    const response = await api(`${base}/offers`, {
      params: { per_page: 100 }
    })

    availableOffers.value = unpack(response, 'offers').rows

  } catch (exception) {
    error.value = getError(exception)
    return
  }

  editingId.value = record?.id ?? null

  resetForm({
    subscription_offer_id: record?.subscription_offer_id ?? '',
    code: record?.code ?? '',
    name: record?.name ?? '',
    description: record?.description ?? '',
    starts_at: localDateTime(record?.starts_at),
    ends_at: localDateTime(record?.ends_at),
    redemption_limit: record?.redemption_limit ?? '',
    max_uses_per_store: record?.max_uses_per_store ?? 1,
    is_active: Boolean(record?.is_active)
  })

  editor.value = 'coupons'
}

function openEditor(record = null) {
  error.value = ''
  success.value = ''

  if (activeTab.value === 'plans') return openPlan(record)
  if (activeTab.value === 'offers') return openOffer(record)
  if (activeTab.value === 'coupons') return openCoupon(record)
}

function closeEditor() {
  if (saving.value) return

  editor.value = null
  editingId.value = null
  error.value = ''
}

const selectedPlan = computed(() =>
  plans.value.find(plan =>
    Number(plan.id) === Number(form.subscription_plan_id)
  )
)

const pricingPreview = computed(() => {
  if (editor.value !== 'offers' || !selectedPlan.value) {
    return []
  }

  const intervals = form.billing_interval === 'both'
    ? ['monthly', 'annual']
    : [form.billing_interval]

  return intervals.map(interval => {
    const standard = Number(
      selectedPlan.value[`${interval}_price_minor`]
    )

    let discounted = standard

    try {
      if (form.discount_type === 'fixed_price') {
        discounted = minor(form[`${interval}_discount_price`])

      } else if (form.discount_type === 'fixed_amount') {
        discounted = standard - minor(form.discount_amount)

      } else {
        const percentage = Number(form.discount_percentage)

        if (
          form.discount_percentage === '' ||
          !Number.isFinite(percentage) ||
          percentage < 0 ||
          percentage > 100
        ) {
          throw new Error('Invalid percentage.')
        }

        discounted = Math.round(
          standard * (10000 - Math.round(percentage * 100)) / 10000
        )
      }

      if (discounted < 0 || discounted > standard) {
        throw new Error('Invalid promotional price.')
      }

      return {
        interval,
        standard,
        discounted,
        saving: standard - discounted,
        percentage: standard > 0
          ? ((standard - discounted) / standard * 100).toFixed(1)
          : '0.0',
        renewal: form.duration_type === 'lifetime'
          ? discounted
          : standard,
        valid: true
      }

    } catch {
      return {
        interval,
        valid: false
      }
    }
  })
})

function planPayload() {
  return {
    name: form.name.trim(),
    slug: form.slug.trim() || slugify(form.name),
    description: form.description,
    monthly_price_minor: minor(form.monthly_price),
    annual_price_minor: minor(form.annual_price),
    currency: 'gbp',
    product_limit: nullableInteger(form.product_limit),
    storage_limit_bytes: nullableInteger(form.storage_limit_bytes),
    ai_monthly_limit: nullableInteger(form.ai_monthly_limit),
    features: form.features
      .split('\n')
      .map(feature => feature.trim())
      .filter(Boolean),
    is_active: form.is_active
  }
}

function validateDates() {
  if (
    form.starts_at &&
    form.ends_at &&
    new Date(form.ends_at) <= new Date(form.starts_at)
  ) {
    throw new Error('The end date must be after the start date.')
  }
}

function offerPayload() {
  validateDates()

  if (
    !pricingPreview.value.length ||
    pricingPreview.value.some(item => !item.valid)
  ) {
    throw new Error('Please configure valid promotional prices.')
  }

  const selectedStores = form.eligible_store_ids
    .split(',')
    .map(value => value.trim())
    .filter(Boolean)
    .map(Number)

  if (
    form.eligibility === 'selected' &&
    (
      !selectedStores.length ||
      selectedStores.some(id => !Number.isSafeInteger(id) || id < 1)
    )
  ) {
    throw new Error('Enter valid eligible business IDs.')
  }

  return {
    subscription_plan_id: Number(form.subscription_plan_id),
    name: form.name.trim(),
    slug: form.slug.trim() || slugify(form.name),
    description: form.description,
    discount_type: form.discount_type,

    monthly_discount_price_minor:
      form.discount_type === 'fixed_price' &&
      form.billing_interval !== 'annual'
        ? nullableMinor(form.monthly_discount_price)
        : null,

    annual_discount_price_minor:
      form.discount_type === 'fixed_price' &&
      form.billing_interval !== 'monthly'
        ? nullableMinor(form.annual_discount_price)
        : null,

    discount_amount_minor:
      form.discount_type === 'fixed_amount'
        ? minor(form.discount_amount)
        : null,

    discount_percentage:
      form.discount_type === 'percentage'
        ? Number(form.discount_percentage)
        : null,

    billing_interval: form.billing_interval,
    duration_type: form.duration_type,

    duration_periods:
      form.duration_type === 'limited_periods'
        ? nullableInteger(form.duration_periods)
        : null,

    eligibility: form.eligibility,

    eligible_store_ids:
      form.eligibility === 'selected'
        ? selectedStores
        : null,

    starts_at: isoDate(form.starts_at),
    ends_at: isoDate(form.ends_at),
    redemption_limit: nullableInteger(form.redemption_limit),
    requires_coupon: form.requires_coupon,
    is_active: form.is_active,
    show_on_website: form.show_on_website,
    badge_text: form.badge_text || null,
    banner_text: form.banner_text || null
  }
}

function couponPayload() {
  validateDates()

  return {
    subscription_offer_id: Number(form.subscription_offer_id),
    code: form.code.trim().toUpperCase(),
    name: form.name,
    description: form.description,
    starts_at: isoDate(form.starts_at),
    ends_at: isoDate(form.ends_at),
    redemption_limit: nullableInteger(form.redemption_limit),
    max_uses_per_store: nullableInteger(form.max_uses_per_store),
    is_active: form.is_active
  }
}

async function save() {
  if (saving.value) return

  saving.value = true
  error.value = ''
  success.value = ''

  try {
    const payload = editor.value === 'plans'
      ? planPayload()
      : editor.value === 'offers'
        ? offerPayload()
        : couponPayload()

    const path = `${base}/${editor.value}` +
      (editingId.value ? `/${editingId.value}` : '')

    await api(path, {
      method: editingId.value ? 'PUT' : 'POST',
      body: payload
    })

    editor.value = null
    editingId.value = null

    success.value = 'Changes saved successfully.'

    await load()

  } catch (exception) {
    error.value = getError(exception)

  } finally {
    saving.value = false
  }
}

async function toggleStatus(record) {
  const nextStatus = !record.is_active

  if (!window.confirm(
    `${nextStatus ? 'Activate' : 'Deactivate'} this record?`
  )) return

  saving.value = true
  error.value = ''
  success.value = ''

  try {
    await api(`${base}/${activeTab.value}/${record.id}/status`, {
      method: 'PATCH',
      body: { is_active: nextStatus }
    })

    success.value = 'Status updated successfully.'

    await load(pagination.value?.current_page ?? 1)

  } catch (exception) {
    error.value = getError(exception)

  } finally {
    saving.value = false
  }
}

function offerDiscount(record) {
  if (record.discount_type === 'percentage') {
    return `${record.discount_percentage}% off`
  }

  if (record.discount_type === 'fixed_amount') {
    return `${money(record.discount_amount_minor)} off`
  }

  return [
    record.monthly_discount_price_minor !== null &&
    record.monthly_discount_price_minor !== undefined
      ? `${money(record.monthly_discount_price_minor)}/month`
      : null,

    record.annual_discount_price_minor !== null &&
    record.annual_discount_price_minor !== undefined
      ? `${money(record.annual_discount_price_minor)}/year`
      : null
  ].filter(Boolean).join(' · ')
}

function offerDuration(record) {
  if (record.duration_type === 'lifetime') return 'Lifetime'

  if (record.duration_type === 'first_period') {
    return 'First billing period'
  }

  return `${record.duration_periods} billing periods`
}

onMounted(load)
</script>

<template>
  <section
    class="subscription-page"
    :style="platform.themeVariables"
  >
    <header class="page-heading">
      <div>
        <span class="eyebrow">
          {{ platform.branding.platform_name }}
          PLATFORM ADMINISTRATION
        </span>

        <h1>Subscriptions</h1>

        <p>
          Manage subscription plans, business subscriptions,
          promotional pricing and usage limits.
        </p>
      </div>

      <div class="heading-actions">
        <button
          class="secondary-button"
          :disabled="loading || saving"
          @click="load()"
        >
          <RefreshCw :size="17" />
          Refresh
        </button>

        <button
          v-if="canCreate"
          class="primary-button"
          :disabled="loading || saving"
          @click="openEditor()"
        >
          <Plus :size="18" />
          {{ createLabel }}
        </button>
      </div>
    </header>

    <nav class="subscription-tabs" aria-label="Subscription sections">
      <button
        v-for="tab in tabs"
        :key="tab.key"
        :class="{ selected: activeTab === tab.key }"
        :disabled="loading || saving || !!editor"
        :aria-pressed="activeTab === tab.key"
        @click="changeTab(tab.key)"
      >
        <component :is="tab.icon" :size="17" />
        {{ tab.label }}
      </button>
    </nav>

    <div v-if="error && !editor" class="message error" role="alert">
      {{ error }}
    </div>

    <div v-if="success" class="message success" role="status">
      {{ success }}
    </div>

    <div v-if="loading" class="empty-state">
      Loading subscription information...
    </div>

    <!-- Overview -->

    <template v-else-if="activeTab === 'overview'">
      <div class="statistics-grid">
        <article
          v-for="item in [
            ['Total Subscriptions', statistics.total_subscriptions],
            ['Active Subscriptions', statistics.active_subscriptions],
            ['Subscription Plans', statistics.total_plans],
            ['Active Offers', statistics.active_offers]
          ]"
          :key="item[0]"
          class="stat-card"
        >
          <span>{{ item[0] }}</span>
          <strong>{{ item[1] ?? '—' }}</strong>
        </article>
      </div>

      <div class="panel overview-panel">
        <h2>Subscription Management</h2>
        <p>
          Configure your subscription packages and promotional
          offers without modifying the application source code.
        </p>

        <div class="quick-actions">
          <button
            class="secondary-button"
            @click="changeTab('plans')"
          >
            <CreditCard :size="18" />
            Manage Plans
          </button>

          <button
            class="secondary-button"
            @click="changeTab('offers')"
          >
            <Percent :size="18" />
            Manage Offers
          </button>

          <button
            class="secondary-button"
            @click="changeTab('coupons')"
          >
            <Ticket :size="18" />
            Manage Coupons
          </button>
        </div>
      </div>
    </template>

    <!-- Data tables -->

    <section v-else class="panel">
      <div class="panel-heading">
        <div>
          <h2>{{ titles[activeTab] }}</h2>
          <p>Manage and review subscription information.</p>
        </div>

        <div class="search-field">
          <Search :size="18" />

          <input
            v-model="search"
            placeholder="Search this page..."
            aria-label="Search current page"
          />
        </div>
      </div>

      <div class="table-wrapper">
        <table>
          <thead>
            <tr>
              <th
                v-for="column in columns[activeTab]"
                :key="column"
              >
                {{ column }}
              </th>
            </tr>
          </thead>

          <tbody>
            <tr
              v-for="record in filteredRecords"
              :key="record.id"
            >
              <!-- Subscription plans -->

              <template v-if="activeTab === 'plans'">
                <td>
                  <strong>{{ record.name }}</strong>
                  <small>{{ record.slug }}</small>
                </td>

                <td>{{ money(record.monthly_price_minor) }}</td>
                <td>{{ money(record.annual_price_minor) }}</td>
                <td>{{ record.product_limit ?? 'Unlimited' }}</td>
              </template>

              <!-- Business subscriptions -->

              <template v-else-if="activeTab === 'businesses'">
                <td>
                  {{ record.store?.name ?? record.business_name ?? '—' }}
                </td>

                <td>{{ record.plan?.name ?? record.plan_name ?? '—' }}</td>

                <td class="capitalise">
                  {{ record.billing_interval }}
                </td>

                <td>
                  {{
                    money(
                      record.discounted_price_minor ??
                      record.standard_price_minor
                    )
                  }}
                </td>

                <td>
                  <span class="status capitalise">
                    {{ humanise(record.status) }}
                  </span>
                </td>

                <td>{{ date(record.current_period_end) }}</td>
              </template>

              <!-- Promotional offers -->

              <template v-else-if="activeTab === 'offers'">
                <td>
                  <strong>{{ record.name }}</strong>
                  <small>{{ record.slug }}</small>
                </td>

                <td>{{ record.plan?.name ?? record.plan_name ?? '—' }}</td>
                <td>{{ offerDiscount(record) }}</td>
                <td>{{ offerDuration(record) }}</td>
                <td>{{ date(record.ends_at) }}</td>
              </template>

              <!-- Discount coupons -->

              <template v-else-if="activeTab === 'coupons'">
                <td>
                  <strong>{{ record.code }}</strong>
                  <small>{{ record.name }}</small>
                </td>

                <td>{{ record.offer?.name ?? record.offer_name ?? '—' }}</td>

                <td>
                  {{ record.redemptions_count ?? 0 }}
                  /
                  {{ record.redemption_limit ?? 'Unlimited' }}
                </td>

                <td>{{ date(record.ends_at) }}</td>
              </template>

              <!-- Price history -->

              <template v-else-if="activeTab === 'price-history'">
                <td>{{ record.plan?.name ?? record.plan_name ?? '—' }}</td>
                <td class="capitalise">{{ humanise(record.action) }}</td>
                <td>{{ money(record.previous_price_minor) }}</td>
                <td>{{ money(record.new_price_minor) }}</td>
                <td>{{ date(record.created_at) }}</td>
              </template>

              <!-- Usage -->

              <template v-else-if="activeTab === 'usage'">
                <td>{{ record.store?.name ?? record.business_name ?? '—' }}</td>
                <td class="capitalise">{{ humanise(record.metric) }}</td>
                <td>{{ record.quantity }}</td>
                <td>{{ date(record.period_start) }}</td>
              </template>

              <!-- Editable record status and actions -->

              <template v-if="canCreate">
                <td>
                  <span
                    class="status"
                    :class="record.is_active ? 'active' : 'inactive'"
                  >
                    {{ record.is_active ? 'Enabled' : 'Disabled' }}
                  </span>
                </td>

                <td>
                  <div class="table-actions">
                    <button
                      class="icon-button"
                      aria-label="Edit record"
                      :disabled="saving"
                      @click="openEditor(record)"
                    >
                      <Pencil :size="16" />
                    </button>

                    <button
                      class="small-button"
                      :disabled="saving"
                      @click="toggleStatus(record)"
                    >
                      {{ record.is_active ? 'Deactivate' : 'Activate' }}
                    </button>
                  </div>
                </td>
              </template>
            </tr>

            <tr v-if="!filteredRecords.length">
              <td
                :colspan="columns[activeTab]?.length ?? 1"
                class="empty-state"
              >
                {{ error ? 'Unable to load records.' : 'No records found.' }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="pagination" class="pagination">
        <span>
          Page {{ pagination.current_page }}
          of {{ pagination.last_page }}
        </span>

        <button
          class="secondary-button"
          :disabled="loading || pagination.current_page <= 1"
          @click="load(pagination.current_page - 1)"
        >
          Previous
        </button>

        <button
          class="secondary-button"
          :disabled="loading || pagination.current_page >= pagination.last_page"
          @click="load(pagination.current_page + 1)"
        >
          Next
        </button>
      </div>
    </section>

    <!-- Create and edit forms -->

    <section
      v-if="editor"
      class="editor-panel panel"
      aria-labelledby="subscription-editor-title"
    >
      <div class="editor-heading">
        <div>
          <h2 id="subscription-editor-title">
            {{ editingId ? 'Edit' : 'Create' }}
            {{
              editor === 'plans'
                ? 'Subscription Plan'
                : editor === 'offers'
                  ? 'Promotional Offer'
                  : 'Discount Coupon'
            }}
          </h2>

          <p>Configure the settings below and save your changes.</p>
        </div>

        <button
          class="icon-button"
          aria-label="Close editor"
          :disabled="saving"
          @click="closeEditor"
        >
          <X :size="22" />
        </button>
      </div>

      <div v-if="error" class="message error" role="alert">
        {{ error }}
      </div>

      <form @submit.prevent="save">

        <!-- Subscription plan form -->

        <template v-if="editor === 'plans'">
          <h3>General Information</h3>

          <div class="form-grid">
            <label>
              Plan Name
              <input
                v-model="form.name"
                required
                maxlength="255"
                placeholder="Professional"
              />
            </label>

            <label>
              Plan Slug
              <input
                v-model="form.slug"
                placeholder="professional"
              />
            </label>

            <label class="full-width">
              Description
              <textarea
                v-model="form.description"
                rows="3"
              />
            </label>
          </div>

          <h3>Subscription Pricing</h3>

          <div class="form-grid">
            <label>
              Monthly Price (£)
              <input
                v-model="form.monthly_price"
                type="number"
                min="0"
                step="0.01"
                required
              />
            </label>

            <label>
              Annual Price (£)
              <input
                v-model="form.annual_price"
                type="number"
                min="0"
                step="0.01"
                required
              />
            </label>
          </div>

          <p class="help-text">
            Enter zero to configure a free price.
            Promotional prices are configured under Promotional Offers.
          </p>

          <h3>Resource Limits</h3>

          <div class="form-grid">
            <label>
              Product Limit
              <input
                v-model="form.product_limit"
                type="number"
                min="0"
                step="1"
                placeholder="Unlimited"
              />
            </label>

            <label>
              Storage Limit (bytes)
              <input
                v-model="form.storage_limit_bytes"
                type="number"
                min="0"
                step="1"
                placeholder="Unlimited"
              />
            </label>

            <label>
              Monthly AI Request Limit
              <input
                v-model="form.ai_monthly_limit"
                type="number"
                min="0"
                step="1"
                placeholder="Unlimited"
              />
            </label>

            <label class="full-width">
              Subscription Features
              <textarea
                v-model="form.features"
                rows="6"
                placeholder="Website builder&#10;Business dashboard&#10;AI assistant"
              />

              <small>Enter one feature per line.</small>
            </label>
          </div>
        </template>

        <!-- Promotional offer form -->

        <template v-else-if="editor === 'offers'">
          <h3>Offer Information</h3>

          <div class="form-grid">
            <label>
              Offer Name
              <input v-model="form.name" required />
            </label>

            <label>
              Offer Slug
              <input v-model="form.slug" />
            </label>

            <label class="full-width">
              Subscription Plan
              <select
                v-model="form.subscription_plan_id"
                required
              >
                <option value="">Select a plan</option>

                <option
                  v-for="plan in plans"
                  :key="plan.id"
                  :value="plan.id"
                >
                  {{ plan.name }}
                </option>
              </select>
            </label>

            <label class="full-width">
              Description
              <textarea v-model="form.description" rows="3" />
            </label>
          </div>

          <h3>Discount Pricing</h3>

          <div class="form-grid">
            <label>
              Discount Method
              <select v-model="form.discount_type">
                <option value="fixed_price">Set Discounted Price</option>
                <option value="fixed_amount">Fixed Amount Discount</option>
                <option value="percentage">Percentage Discount</option>
              </select>
            </label>

            <label>
              Billing Interval
              <select v-model="form.billing_interval">
                <option value="both">Monthly and Annual</option>
                <option value="monthly">Monthly Only</option>
                <option value="annual">Annual Only</option>
              </select>
            </label>

            <template v-if="form.discount_type === 'fixed_price'">
              <label v-if="form.billing_interval !== 'annual'">
                Discounted Monthly Price (£)
                <input
                  v-model="form.monthly_discount_price"
                  type="number"
                  min="0"
                  step="0.01"
                  required
                />
              </label>

              <label v-if="form.billing_interval !== 'monthly'">
                Discounted Annual Price (£)
                <input
                  v-model="form.annual_discount_price"
                  type="number"
                  min="0"
                  step="0.01"
                  required
                />
              </label>
            </template>

            <label v-if="form.discount_type === 'fixed_amount'">
              Discount Amount (£)
              <input
                v-model="form.discount_amount"
                type="number"
                min="0"
                step="0.01"
                required
              />
            </label>

            <label v-if="form.discount_type === 'percentage'">
              Discount Percentage (%)
              <input
                v-model="form.discount_percentage"
                type="number"
                min="0"
                max="100"
                step="0.01"
                required
              />
            </label>
          </div>

          <!-- Live pricing preview -->

          <div v-if="pricingPreview.length" class="pricing-preview">
            <h3>Live Pricing Preview</h3>

            <div class="preview-grid">
              <article
                v-for="preview in pricingPreview"
                :key="preview.interval"
                class="preview-card"
              >
                <h4 class="capitalise">{{ preview.interval }} Plan</h4>

                <template v-if="preview.valid">
                  <span class="original-price">
                    {{ money(preview.standard) }}
                  </span>

                  <strong class="discount-price">
                    {{ money(preview.discounted) }}
                  </strong>

                  <span class="saving-text">
                    Save {{ money(preview.saving) }}
                    ({{ preview.percentage }}%)
                  </span>

                  <small>
                    Subsequent standard renewal:
                    {{ money(preview.renewal) }}
                  </small>
                </template>

                <p v-else class="invalid-price">
                  Enter a valid promotional price.
                </p>
              </article>
            </div>
          </div>

          <h3>Promotion Duration and Eligibility</h3>

          <div class="form-grid">
            <label>
              Discount Duration
              <select v-model="form.duration_type">
                <option value="first_period">First Billing Period</option>
                <option value="limited_periods">Limited Billing Periods</option>
                <option value="lifetime">Lifetime Discount</option>
              </select>
            </label>

            <label v-if="form.duration_type === 'limited_periods'">
              Number of Billing Periods
              <input
                v-model="form.duration_periods"
                type="number"
                min="1"
                step="1"
                required
              />
            </label>

            <label>
              Eligibility
              <select v-model="form.eligibility">
                <option value="new">New Subscribers</option>
                <option value="existing">Existing Subscribers</option>
                <option value="all">All Eligible Businesses</option>
                <option value="selected">Selected Businesses</option>
              </select>
            </label>

            <label v-if="form.eligibility === 'selected'">
              Eligible Business IDs
              <input
                v-model="form.eligible_store_ids"
                placeholder="1, 2, 3"
                required
              />
            </label>

            <label>
              Offer Start Date
              <input
                v-model="form.starts_at"
                type="datetime-local"
              />
            </label>

            <label>
              Offer End Date
              <input
                v-model="form.ends_at"
                type="datetime-local"
              />
            </label>

            <label>
              Redemption Limit
              <input
                v-model="form.redemption_limit"
                type="number"
                min="1"
                step="1"
                placeholder="Unlimited"
              />
            </label>
          </div>

          <h3>Website Promotion</h3>

          <div class="form-grid">
            <label>
              Promotional Badge
              <input
                v-model="form.badge_text"
                maxlength="100"
                placeholder="Special Launch Offer"
              />
            </label>

            <label>
              Promotional Banner
              <input
                v-model="form.banner_text"
                maxlength="255"
                placeholder="Save on your first three months"
              />
            </label>
          </div>

          <label class="checkbox-field">
            <input
              v-model="form.show_on_website"
              type="checkbox"
            />
            Show this promotion on the public website
          </label>

          <label class="checkbox-field">
            <input
              v-model="form.requires_coupon"
              type="checkbox"
            />
            Require a valid coupon code
          </label>
        </template>

        <!-- Coupon form -->

        <template v-else-if="editor === 'coupons'">
          <h3>Coupon Information</h3>

          <div class="form-grid">
            <label>
              Coupon Code
              <input
                v-model="form.code"
                required
                maxlength="100"
                pattern="[A-Za-z0-9_-]+"
                placeholder="IVISION20"
                class="uppercase"
              />
            </label>

            <label>
              Coupon Name
              <input v-model="form.name" />
            </label>

            <label class="full-width">
              Associated Promotional Offer
              <select
                v-model="form.subscription_offer_id"
                required
              >
                <option value="">Select an offer</option>

                <option
                  v-for="offer in availableOffers"
                  :key="offer.id"
                  :value="offer.id"
                >
                  {{ offer.name }}
                </option>
              </select>
            </label>

            <label class="full-width">
              Description
              <textarea v-model="form.description" rows="3" />
            </label>

            <label>
              Start Date
              <input
                v-model="form.starts_at"
                type="datetime-local"
              />
            </label>

            <label>
              End Date
              <input
                v-model="form.ends_at"
                type="datetime-local"
              />
            </label>

            <label>
              Total Redemption Limit
              <input
                v-model="form.redemption_limit"
                type="number"
                min="1"
                step="1"
                placeholder="Unlimited"
              />
            </label>

            <label>
              Maximum Uses Per Business
              <input
                v-model="form.max_uses_per_store"
                type="number"
                min="1"
                step="1"
                required
              />
            </label>
          </div>
        </template>

        <div class="activation-section">
          <label class="checkbox-field">
            <input
              v-model="form.is_active"
              type="checkbox"
            />
            Enable this record
          </label>

          <small>
            Disabled records will not be available for new purchases
            when backend subscription enforcement is connected.
          </small>
        </div>

        <footer class="editor-footer">
          <button
            type="button"
            class="secondary-button"
            :disabled="saving"
            @click="closeEditor"
          >
            Cancel
          </button>

          <button
            type="submit"
            class="primary-button"
            :disabled="saving"
          >
            {{ saving ? 'Saving...' : 'Save Changes' }}
          </button>
        </footer>
      </form>
    </section>
  </section>
</template>

<style scoped>
.subscription-page {
  font-family: var(--platform-font, Arial, sans-serif);
  color: var(--platform-text, #111827);
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.page-heading,
.panel-heading,
.editor-heading {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 20px;
}

.eyebrow {
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 1.5px;
  color: #64748b;
}

h1 {
  font-size: 30px;
  margin: 8px 0;
}

h2 {
  font-size: 19px;
  margin: 0 0 6px;
}

h3 {
  font-size: 16px;
  margin: 24px 0 16px;
}

p {
  font-size: 14px;
  color: #64748b;
  line-height: 1.6;
  margin: 0;
}

.heading-actions,
.table-actions,
.quick-actions,
.editor-footer {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
}

button {
  font: inherit;
  cursor: pointer;
}

button:disabled {
  cursor: not-allowed;
  opacity: .55;
}

.primary-button,
.secondary-button,
.small-button,
.icon-button {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  border-radius: 7px;
  font-size: 13px;
  font-weight: 600;
  padding: 11px 16px;
  white-space: nowrap;
}

.primary-button {
  background: var(--platform-button, #111827);
  color: var(--platform-button-text, #fff);
  border: 1px solid transparent;
}

.secondary-button,
.small-button,
.icon-button {
  background: #fff;
  border: 1px solid #e2e8f0;
  color: #334155;
}

.small-button,
.icon-button {
  padding: 7px 10px;
  font-size: 12px;
}

.subscription-tabs {
  display: flex;
  gap: 5px;
  padding: 8px;
  overflow-x: auto;
  border: 1px solid #e2e8f0;
  background: #fff;
  border-radius: 10px;
}

.subscription-tabs button {
  display: flex;
  align-items: center;
  gap: 8px;
  border: none;
  background: transparent;
  color: #64748b;
  border-radius: 6px;
  padding: 12px 15px;
  font-size: 13px;
  white-space: nowrap;
}

.subscription-tabs button.selected {
  background: var(--platform-primary, #111827);
  color: #fff;
}

.statistics-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 18px;
}

.stat-card,
.panel {
  background: #fff;
  color: #111827;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
}

.stat-card {
  padding: 24px;
}

.stat-card span {
  font-size: 13px;
  color: #64748b;
}

.stat-card strong {
  display: block;
  font-size: 30px;
  margin-top: 12px;
}

.panel-heading,
.editor-heading {
  padding: 24px;
  border-bottom: 1px solid #e2e8f0;
}

.overview-panel {
  padding: 24px;
}

.quick-actions {
  margin-top: 20px;
}

.search-field {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 0 12px;
  border: 1px solid #e2e8f0;
  border-radius: 7px;
  color: #64748b;
}

.search-field input {
  border: none;
  outline: none;
  padding: 11px 0;
  width: 180px;
}

.table-wrapper {
  overflow-x: auto;
}

table {
  width: 100%;
  border-collapse: collapse;
  text-align: left;
  font-size: 13px;
}

th {
  padding: 15px 20px;
  background: #f8fafc;
  color: #64748b;
  font-size: 12px;
  white-space: nowrap;
}

td {
  padding: 18px 20px;
  border-top: 1px solid #eef2f7;
}

td small {
  display: block;
  margin-top: 5px;
  color: #64748b;
}

.status {
  display: inline-block;
  border-radius: 20px;
  padding: 5px 10px;
  background: #f1f5f9;
  color: #475569;
  font-size: 11px;
  font-weight: 600;
}

.status.active {
  background: #dcfce7;
  color: #166534;
}

.status.inactive {
  background: #f1f5f9;
  color: #64748b;
}

.empty-state {
  text-align: center;
  padding: 45px 20px;
  color: #64748b;
}

.pagination {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 12px;
  padding: 18px;
  font-size: 13px;
}

.message {
  padding: 14px 18px;
  border-radius: 8px;
  font-size: 13px;
}

.message.error {
  background: #fef2f2;
  color: #991b1b;
  border: 1px solid #fecaca;
}

.message.success {
  background: #f0fdf4;
  color: #166534;
  border: 1px solid #bbf7d0;
}

.editor-panel {
  scroll-margin-top: 20px;
}

.editor-panel form {
  padding: 24px;
}

.editor-panel > .message {
  margin: 20px 24px 0;
}

.form-grid,
.preview-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 20px;
}

.form-grid label {
  display: flex;
  flex-direction: column;
  gap: 8px;
  font-size: 13px;
  font-weight: 600;
}

.form-grid input,
.form-grid select,
.form-grid textarea {
  width: 100%;
  box-sizing: border-box;
  border: 1px solid #dbe2ea;
  border-radius: 7px;
  background: #fff;
  color: #111827;
  padding: 12px;
  font: inherit;
  font-weight: 400;
}

.form-grid textarea {
  resize: vertical;
}

.full-width {
  grid-column: 1 / -1;
}

.form-grid small,
.help-text,
.activation-section small {
  color: #64748b;
  font-size: 12px;
  font-weight: 400;
}

.help-text {
  margin-top: 12px;
}

.checkbox-field {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-top: 18px;
  font-size: 13px;
}

.checkbox-field input {
  width: 17px;
  height: 17px;
  accent-color: var(--platform-primary, #111827);
}

.pricing-preview {
  margin-top: 24px;
  padding: 20px;
  background: #f8fafc;
  border-radius: 10px;
  border: 1px solid #e2e8f0;
}

.pricing-preview h3 {
  margin-top: 0;
}

.preview-card {
  background: #fff;
  padding: 20px;
  border: 1px solid #e2e8f0;
  border-radius: 9px;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.preview-card h4 {
  margin: 0 0 8px;
}

.original-price {
  text-decoration: line-through;
  color: #94a3b8;
}

.discount-price {
  font-size: 30px;
  color: #111827;
}

.saving-text {
  color: #15803d;
  font-size: 13px;
}

.invalid-price {
  color: #b91c1c;
}

.activation-section {
  border-top: 1px solid #e2e8f0;
  margin-top: 28px;
  padding: 8px 0 24px;
}

.activation-section small {
  display: block;
  margin-top: 10px;
}

.editor-footer {
  justify-content: flex-end;
  border-top: 1px solid #e2e8f0;
  padding-top: 20px;
}

.capitalise {
  text-transform: capitalize;
}

.uppercase {
  text-transform: uppercase;
}

button:focus-visible,
input:focus-visible,
select:focus-visible,
textarea:focus-visible {
  outline: 2px solid var(--platform-primary, #111827);
  outline-offset: 2px;
}

@media (max-width: 1100px) {
  .statistics-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 650px) {
  .page-heading,
  .panel-heading {
    align-items: flex-start;
    flex-direction: column;
  }

  .form-grid,
  .preview-grid {
    grid-template-columns: 1fr;
  }

  .editor-panel form,
  .editor-heading,
  .panel-heading {
    padding: 16px;
  }

  .stat-card {
    padding: 16px;
  }

  .search-field {
    width: 100%;
    box-sizing: border-box;
  }

  .search-field input {
    width: 100%;
  }

  .pagination {
    flex-wrap: wrap;
  }
}
</style>
