
<script setup>
import { computed, onMounted, ref } from 'vue'
import { api } from '@/api/http'
import { usePlatformStore } from '@/stores/platform'


const platform = usePlatformStore()
const stores = ref([])
const loading = ref(true)
const error = ref('')
const success = ref('')
const savingId = ref(null)
const selectedStore = ref(null)
const search = ref('')
const statusFilter = ref('all')

const page = ref(1)
const lastPage = ref(1)
const total = ref(0)

const statusOptions = [
  'pending',
  'active',
  'suspended',
  'rejected'
]

const transitions = {
  pending: ['active', 'rejected'],
  active: ['suspended'],
  suspended: ['active'],
  rejected: ['pending']
}

const filteredStores = computed(() => {
  const term = search.value.trim().toLowerCase()

  return stores.value.filter(store => {
    const matchesStatus =
      statusFilter.value === 'all' ||
      store.status === statusFilter.value

    const matchesSearch = [
      store.name,
      store.slug,
      store.owner?.name,
      store.owner?.email,
      store.business_category?.name,
      store.type
    ].some(value =>
      String(value || '').toLowerCase().includes(term)
    )

    return matchesStatus && (!term || matchesSearch)
  })
})

function countStatus(status) {
  return stores.value.filter(
    store => store.status === status
  ).length
}

function getErrorMessage(err) {
  return err?.message ||
    'Something went wrong. Please try again.'
}

function formatDate(value) {
  if (!value) return 'Not recorded'

  const date = new Date(value)

  return Number.isNaN(date.getTime())
    ? 'Not recorded'
    : date.toLocaleString('en-GB')
}

async function loadStores(targetPage = 1) {
  loading.value = true
  error.value = ''

  try {
    const response = await api('/admin/stores', {
      params: {
        page: targetPage,
        per_page: 30,
        status: statusFilter.value === 'all'
          ? ''
          : statusFilter.value
      }
    })

    stores.value = Array.isArray(response)
      ? response
      : response.data || []

    page.value = response.current_page || 1
    lastPage.value = response.last_page || 1
    total.value = response.total ?? stores.value.length

  } catch (err) {
    error.value = getErrorMessage(err)
  } finally {
    loading.value = false
  }
}

async function viewStore(store) {
  selectedStore.value = store
  error.value = ''

  try {
    const response = await api(
      `/admin/stores/${store.id}`
    )

    if (selectedStore.value?.id === store.id) {
      selectedStore.value = response?.store ?? response
    }

  } catch (err) {
    error.value = getErrorMessage(err)
  }
}

function actionLabel(store, status) {
  if (status === 'active') {
    return store.status === 'suspended'
      ? 'Reactivate'
      : 'Approve'
  }

  if (status === 'rejected') return 'Reject'
  if (status === 'suspended') return 'Suspend'
  if (status === 'pending') return 'Reopen'

  return status
}

async function updateStatus(store, status) {
  if (savingId.value !== null) return

  error.value = ''
  success.value = ''

  if (store.is_main) {
    error.value =
      'The main Made Exclusif store cannot be modified here.'
    return
  }

  if (!transitions[store.status]?.includes(status)) {
    error.value = 'Invalid store status transition.'
    return
  }

  const action = actionLabel(store, status)
  let reason = null

  if (['rejected', 'suspended'].includes(status)) {
    reason = window.prompt(
      `Enter the reason to ${action.toLowerCase()} "${store.name}". This reason will be visible to the business owner.`
    )

    if (reason === null) return

    reason = reason.trim()

    if (!reason || reason.length > 2000) {
      error.value =
        'Please provide a reason between 1 and 2000 characters.'
      return
    }
  }

  if (!window.confirm(
    `Are you sure you want to ${action.toLowerCase()} "${store.name}"?`
  )) return

  savingId.value = store.id

  try {
    const response = await api(
      `/admin/stores/${store.id}`,
      {
        method: 'PATCH',
        body: {
          status,
          ...(reason ? { reason } : {})
        }
      }
    )

    const updated = response?.store ?? response

    stores.value = stores.value.map(item =>
      item.id === store.id
        ? { ...item, ...updated }
        : item
    )

    if (selectedStore.value?.id === store.id) {
      selectedStore.value = {
        ...selectedStore.value,
        ...updated
      }
    }

    success.value = response?.message ||
      `"${store.name}" has been updated successfully.`

    await loadStores(page.value)

    if (page.value > lastPage.value) {
      await loadStores(lastPage.value)
    }

  } catch (err) {
    error.value = getErrorMessage(err)
  } finally {
    savingId.value = null
  }
}

onMounted(() => loadStores())
</script>

<template>
  <main class="container admin-stores">

    <div class="page-heading">
      <div>
        <p class="eyebrow">
          Made Exclusif Administration
        </p>

        <h1>Store Management</h1>

        <p>
          Review business applications and manage
          independent seller storefronts.
        </p>
      </div>

      <button
        type="button"
        :disabled="loading || savingId !== null"
        @click="loadStores(page)"
      >
        {{ loading ? 'Loading...' : 'Refresh' }}
      </button>
    </div>

    <div class="stats">
      <div class="stat">
        <span>Total matching stores</span>
        <strong>{{ total }}</strong>
      </div>

      <div
        v-for="status in statusOptions"
        :key="status"
        class="stat"
      >
        <span>{{ status }} (this page)</span>
        <strong>{{ countStatus(status) }}</strong>
      </div>
    </div>

    <p v-if="error" class="alert error" role="alert">
      {{ error }}
    </p>

    <p v-if="success" class="alert success" role="status">
      {{ success }}
    </p>

    <section class="management-panel">

      <div class="filters">
        <input
          v-model="search"
          type="search"
          placeholder="Search this page"
          aria-label="Search stores on this page"
        />

        <select
          v-model="statusFilter"
          aria-label="Filter by store status"
          :disabled="loading || savingId !== null"
          @change="loadStores(1)"
        >
          <option value="all">All statuses</option>

          <option
            v-for="status in statusOptions"
            :key="status"
            :value="status"
          >
            {{ status }}
          </option>
        </select>
      </div>

      <p v-if="loading" role="status">
        Loading stores...
      </p>

      <div
        v-else-if="filteredStores.length"
        class="table-wrap"
      >
        <table>
          <thead>
            <tr>
              <th>Store</th>
              <th>Owner</th>
              <th>Category</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>

          <tbody>
            <tr
              v-for="store in filteredStores"
              :key="store.id"
            >
              <td>
                <strong>{{ store.name }}</strong>
                <small>{{ store.slug }}</small>
                <small v-if="store.is_main">
                  Main marketplace store
                </small>
              </td>

              <td>
                {{ store.owner?.name || '—' }}
                <small>
                  {{ store.owner?.email || '' }}
                </small>
              </td>

              <td>
                {{
                  store.business_category?.name ||
                  store.type ||
                  '—'
                }}
              </td>

              <td>
                <span
                  class="status"
                  :class="`status--${store.status}`"
                >
                  {{ store.status }}
                </span>
              </td>

              <td>
                <div class="actions">
                  <button
                    type="button"
                    :disabled="savingId !== null"
                    @click="viewStore(store)"
                  >
                    Details
                  </button>

                  <template v-if="!store.is_main">
                    <button
                      v-for="status in transitions[store.status] || []"
                      :key="status"
                      type="button"
                      :class="{
                        danger: ['rejected', 'suspended'].includes(status)
                      }"
                      :disabled="savingId !== null"
                      @click="updateStatus(store, status)"
                    >
                      {{
                        savingId === store.id
                          ? 'Updating...'
                          : actionLabel(store, status)
                      }}
                    </button>
                  </template>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <p v-else>No stores match your search.</p>

      <div class="pagination">
        <button
          :disabled="page <= 1 || loading || savingId !== null"
          @click="loadStores(page - 1)"
        >
          Previous
        </button>

        <span>Page {{ page }} of {{ lastPage }}</span>

        <button
          :disabled="page >= lastPage || loading || savingId !== null"
          @click="loadStores(page + 1)"
        >
          Next
        </button>
      </div>

    </section>

    <section
      v-if="selectedStore"
      class="details-panel"
      aria-label="Selected store details"
    >
      <div class="details-heading">
        <h2>{{ selectedStore.name }}</h2>

        <button
          type="button"
          aria-label="Close store details"
          @click="selectedStore = null"
        >
          Close
        </button>
      </div>

      <dl>
        <dt>Store ID</dt>
        <dd>{{ selectedStore.id }}</dd>

        <dt>Slug</dt>
        <dd>{{ selectedStore.slug }}</dd>

        <dt>Business category</dt>
        <dd>
          {{
            selectedStore.business_category?.name ||
            selectedStore.type
          }}
        </dd>

        <dt>Status</dt>
        <dd>{{ selectedStore.status }}</dd>

        <dt>Owner</dt>
        <dd>{{ selectedStore.owner?.name || '—' }}</dd>

        <dt>Owner email</dt>
        <dd>{{ selectedStore.owner?.email || '—' }}</dd>

        <dt>Tagline</dt>
        <dd>{{ selectedStore.tagline || '—' }}</dd>

        <dt>Application date</dt>
        <dd>{{ formatDate(selectedStore.created_at) }}</dd>

        <dt>Approval date</dt>
        <dd>{{ formatDate(selectedStore.approved_at) }}</dd>

        <dt>Rejection date</dt>
        <dd>{{ formatDate(selectedStore.rejected_at) }}</dd>

        <dt>Last rejection reason</dt>
        <dd>{{ selectedStore.rejection_reason || '—' }}</dd>

        <dt>Suspension date</dt>
        <dd>{{ formatDate(selectedStore.suspended_at) }}</dd>

        <dt>Last suspension reason</dt>
        <dd>{{ selectedStore.suspension_reason || '—' }}</dd>

        <dt>Products</dt>
        <dd>{{ selectedStore.products_count ?? '—' }}</dd>

        <dt>Orders</dt>
        <dd>{{ selectedStore.orders_count ?? '—' }}</dd>
      </dl>

      <router-link
        v-if="selectedStore.status === 'active'"
        :to="{
          name: 'store-home',
          params: { store: selectedStore.slug }
        }"
        class="store-link"
      >
        View Public Store
      </router-link>

    </section>
  </main>
</template>

<style scoped>
.admin-stores {
  padding-top: 40px;
  padding-bottom: 80px;
}

.page-heading,
.details-heading {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 20px;
  margin-bottom: 25px;
}

.page-heading h1 {
  margin: 8px 0;
  font-size: clamp(28px, 4vw, 40px);
}

.page-heading p {
  color: #666;
}

.eyebrow {
  text-transform: uppercase;
  letter-spacing: 2px;
  font-size: 12px;
}

.stats {
  display: grid;
  grid-template-columns: repeat(5, minmax(0, 1fr));
  gap: 16px;
  margin-bottom: 30px;
}

.stat,
.management-panel,
.details-panel {
  border: 1px solid #e8e8e8;
  border-radius: 10px;
  background: #fff;
}

.stat {
  padding: 20px;
}

.stat span,
.stat strong {
  display: block;
}

.stat span {
  color: #666;
  font-size: 13px;
  text-transform: capitalize;
}

.stat strong {
  margin-top: 10px;
  font-size: 30px;
}

.management-panel,
.details-panel {
  padding: 24px;
  margin-bottom: 25px;
}

.filters {
  display: flex;
  gap: 12px;
  margin-bottom: 25px;
}

.filters input,
.filters select {
  padding: 11px 12px;
  border: 1px solid #ddd;
  border-radius: 5px;
  font: inherit;
}

.filters input {
  flex: 1;
  min-width: 0;
}

.table-wrap {
  overflow-x: auto;
}

table {
  width: 100%;
  border-collapse: collapse;
}

th,
td {
  padding: 15px 12px;
  text-align: left;
  border-bottom: 1px solid #eee;
}

th {
  color: #666;
  font-size: 12px;
  text-transform: uppercase;
}

td small {
  display: block;
  margin-top: 5px;
  color: #777;
}

.status {
  display: inline-block;
  padding: 6px 11px;
  border-radius: 20px;
  text-transform: capitalize;
  background: #eee;
}

.status--pending {
  background: #fff2d8;
  color: #805300;
}

.status--active {
  background: #e4f5e9;
  color: #176c36;
}

.status--suspended,
.status--rejected {
  background: #ffe7e7;
  color: #a22;
}

.actions,
.pagination {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 8px;
}

.pagination {
  justify-content: center;
  margin-top: 25px;
}

button,
.store-link {
  padding: 9px 13px;
  border: 1px solid #ccc;
  border-radius: 5px;
  background: #fff;
  color: #222;
  cursor: pointer;
  font: inherit;
  text-decoration: none;
}

button.danger {
  color: #a22;
  border-color: #d8a6a6;
}

button:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.alert {
  padding: 13px 16px;
  border-radius: 5px;
  margin-bottom: 16px;
}

.error {
  color: #a22;
  background: #fff1f1;
}

.success {
  color: #176c36;
  background: #eef8f0;
}

dl {
  display: grid;
  grid-template-columns: 180px 1fr;
  gap: 14px;
  margin-bottom: 25px;
}

dt {
  font-weight: 600;
}

dd {
  margin: 0;
  overflow-wrap: anywhere;
  white-space: pre-wrap;
}

@media (max-width: 900px) {
  .stats {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 640px) {
  .page-heading,
  .filters {
    flex-direction: column;
    align-items: stretch;
  }

  .stats {
    grid-template-columns: 1fr;
  }

  dl {
    grid-template-columns: 1fr;
    gap: 6px;
  }
}
</style>
