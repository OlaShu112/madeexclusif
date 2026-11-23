<script setup>
import { computed, onMounted, ref } from 'vue'
import { api } from '@/api/http'

/**
 * Made Exclusif - Admin Store Management
 *
 * Store approval and suspension are controlled
 * by the Laravel backend.
 */

const stores = ref([])
const loading = ref(true)
const error = ref('')
const success = ref('')
const savingId = ref(null)
const selectedStore = ref(null)
const search = ref('')
const statusFilter = ref('all')

const statusOptions = ['pending', 'active', 'suspended']

const filteredStores = computed(() => {
  const term = search.value.trim().toLowerCase()

  return stores.value.filter((store) => {
    const matchesStatus =
      statusFilter.value === 'all' ||
      store.status === statusFilter.value

    const matchesSearch =
      !term ||
      String(store.name || '').toLowerCase().includes(term) ||
      String(store.slug || '').toLowerCase().includes(term) ||
      String(store.owner?.name || '').toLowerCase().includes(term) ||
      String(store.owner?.email || '').toLowerCase().includes(term)

    return matchesStatus && matchesSearch
  })
})

const pendingCount = computed(() =>
  stores.value.filter((store) => store.status === 'pending').length
)

const activeCount = computed(() =>
  stores.value.filter((store) => store.status === 'active').length
)

const suspendedCount = computed(() =>
  stores.value.filter((store) => store.status === 'suspended').length
)

function unwrapList(response) {
  if (Array.isArray(response)) return response
  if (Array.isArray(response?.data)) return response.data
  return []
}

function getErrorMessage(err) {
  return (
    err?.data?.message ||
    err?.response?.data?.message ||
    err?.message ||
    'Something went wrong. Please try again.'
  )
}

async function loadStores() {
  loading.value = true
  error.value = ''
  success.value = ''

  try {
    const response = await api('/admin/stores')

    stores.value = unwrapList(response)
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
    const response = await api(`/admin/stores/${store.id}`)

    selectedStore.value = response?.store ?? response
  } catch (err) {
    error.value = getErrorMessage(err)
  }
}

async function updateStatus(store, status) {
  if (savingId.value !== null) return

  if (store.is_main) {
    error.value = 'The main Made Exclusif store cannot be modified here.'
    return
  }

  if (!statusOptions.includes(status)) return

  const action =
    status === 'active'
      ? 'activate'
      : status === 'suspended'
        ? 'suspend'
        : 'set to pending'

  if (
    !window.confirm(
      `Are you sure you want to ${action} "${store.name}"?`
    )
  ) {
    return
  }

  savingId.value = store.id
  error.value = ''
  success.value = ''

  try {
    const response = await api(`/admin/stores/${store.id}`, {
      method: 'PATCH',
      body: {
        status: status,
      },
    })

    const updated = response?.store ?? response

    stores.value = stores.value.map((item) =>
      item.id === store.id
        ? { ...item, ...updated, status }
        : item
    )

    if (selectedStore.value?.id === store.id) {
      selectedStore.value = {
        ...selectedStore.value,
        ...updated,
        status,
      }
    }

    success.value = `"${store.name}" has been updated successfully.`
  } catch (err) {
    error.value = getErrorMessage(err)
  } finally {
    savingId.value = null
  }
}

onMounted(loadStores)
</script>

<template>
  <main class="container admin-stores">
    <div class="page-heading">
      <div>
        <p class="eyebrow">Made Exclusif Administration</p>
        <h1>Store Management</h1>
        <p>Review and manage independent seller storefronts.</p>
      </div>

      <button
        type="button"
        class="refresh-button"
        :disabled="loading"
        @click="loadStores"
      >
        {{ loading ? 'Loading...' : 'Refresh' }}
      </button>
    </div>

    <div class="stats">
      <div class="stat">
        <span>Total loaded</span>
        <strong>{{ stores.length }}</strong>
      </div>

      <div class="stat">
        <span>Pending</span>
        <strong>{{ pendingCount }}</strong>
      </div>

      <div class="stat">
        <span>Active</span>
        <strong>{{ activeCount }}</strong>
      </div>

      <div class="stat">
        <span>Suspended</span>
        <strong>{{ suspendedCount }}</strong>
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
          placeholder="Search stores or owners"
          aria-label="Search stores"
        />

        <select
          v-model="statusFilter"
          aria-label="Filter by store status"
        >
          <option value="all">All statuses</option>
          <option value="pending">Pending</option>
          <option value="active">Active</option>
          <option value="suspended">Suspended</option>
        </select>
      </div>

      <p v-if="loading">Loading stores...</p>

      <div v-else-if="filteredStores.length" class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Store</th>
              <th>Owner</th>
              <th>Type</th>
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
              </td>

              <td>
                {{ store.owner?.name || '—' }}
                <small>{{ store.owner?.email || '' }}</small>
              </td>

              <td class="capitalize">
                {{ store.type || '—' }}
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
                    @click="viewStore(store)"
                  >
                    Details
                  </button>

                  <template v-if="!store.is_main">
                    <button
                      v-if="store.status !== 'active'"
                      type="button"
                      :disabled="savingId !== null"
                      @click="updateStatus(store, 'active')"
                    >
                      Approve
                    </button>

                    <button
                      v-if="store.status === 'active'"
                      type="button"
                      :disabled="savingId !== null"
                      @click="updateStatus(store, 'suspended')"
                    >
                      Suspend
                    </button>
                  </template>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <p v-else>No stores match your search.</p>
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
          ✕
        </button>
      </div>

      <dl>
        <dt>Store ID</dt>
        <dd>{{ selectedStore.id }}</dd>

        <dt>Slug</dt>
        <dd>{{ selectedStore.slug }}</dd>

        <dt>Store Type</dt>
        <dd>{{ selectedStore.type }}</dd>

        <dt>Status</dt>
        <dd>{{ selectedStore.status }}</dd>

        <dt>Owner</dt>
        <dd>{{ selectedStore.owner?.name || '—' }}</dd>

        <dt>Owner Email</dt>
        <dd>{{ selectedStore.owner?.email || '—' }}</dd>

        <dt>Tagline</dt>
        <dd>{{ selectedStore.tagline || '—' }}</dd>
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
}

.page-heading {
  margin-bottom: 30px;
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
  grid-template-columns: repeat(4, minmax(0, 1fr));
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
  padding: 22px;
}

.stat span,
.stat strong {
  display: block;
}

.stat span {
  color: #666;
  font-size: 13px;
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

.capitalize,
.status {
  text-transform: capitalize;
}

.status {
  display: inline-block;
  padding: 6px 11px;
  border-radius: 20px;
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

.status--suspended {
  background: #ffe7e7;
  color: #a22;
}

.actions {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
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
}

button:disabled {
  opacity: 0.5;
  cursor: wait;
}

.refresh-button,
.store-link {
  text-decoration: none;
}

.alert {
  padding: 13px 16px;
  border-radius: 5px;
}

.error {
  color: #a22;
  background: #fff1f1;
}

.success {
  color: #176c36;
  background: #eef8f0;
}

.details-heading {
  margin-bottom: 20px;
}

dl {
  display: grid;
  grid-template-columns: 150px 1fr;
  gap: 14px;
  margin-bottom: 25px;
}

dt {
  font-weight: 600;
}

dd {
  margin: 0;
}

@media (max-width: 900px) {
  .stats {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 640px) {
  .page-heading {
    align-items: flex-start;
    flex-direction: column;
  }

  .stats {
    grid-template-columns: 1fr;
  }

  .filters {
    flex-direction: column;
  }

  dl {
    grid-template-columns: 1fr;
    gap: 6px;
  }
}
</style>

