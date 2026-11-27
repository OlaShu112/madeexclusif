<script setup>
import { ref, reactive, onMounted } from 'vue'
import { api } from '@/api/http'
import { usePlatformStore } from '@/stores/platform'

import {
  Search,
  RefreshCw,
  Eye,
  X
} from 'lucide-vue-next'

import '@/assets/platform-admin.css'

const platform = usePlatformStore()

const loading = ref(false)
const detailsLoading = ref(false)
const saving = ref(false)

const error = ref('')
const success = ref('')
const detailsError = ref('')

const statistics = ref({})
const stores = ref([])
const customers = ref({
  data: [],
  current_page: 1,
  last_page: 1,
  total: 0
})

const selectedCustomer = ref(null)
const detailsOpen = ref(false)

const membershipAction = ref(null)
const reason = ref('')

const filters = reactive({
  q: '',
  store_id: '',
  verification: '',
  per_page: 20
})

function formatDate(value) {
  if (!value) return '—'

  return new Date(value).toLocaleDateString('en-GB')
}

function formatMoney(value) {
  return new Intl.NumberFormat('en-GB', {
    style: 'currency',
    currency: 'GBP'
  }).format(Number(value || 0))
}

async function loadCustomers(page = 1) {
  if (loading.value) return

  loading.value = true
  error.value = ''

  try {
    const response = await api(
      '/admin/platform/customers',
      {
        params: {
          ...filters,
          page
        }
      }
    )

    statistics.value = response.statistics
    stores.value = response.stores
    customers.value = response.customers

  } catch (exception) {
    error.value =
      exception.message ||
      'Unable to load customers.'
  } finally {
    loading.value = false
  }
}

function resetFilters() {
  filters.q = ''
  filters.store_id = ''
  filters.verification = ''

  loadCustomers()
}

async function viewCustomer(id, page = 1) {
  if (detailsLoading.value) return

  detailsOpen.value = true
  detailsLoading.value = true
  detailsError.value = ''

  try {
    selectedCustomer.value = await api(
      `/admin/platform/customers/${id}`,
      {
        params: { page }
      }
    )
  } catch (exception) {
    detailsError.value =
      exception.message ||
      'Unable to load customer details.'
  } finally {
    detailsLoading.value = false
  }
}

function closeDetails() {
  if (saving.value || detailsLoading.value) return

  detailsOpen.value = false
  selectedCustomer.value = null
  membershipAction.value = null
  reason.value = ''
  detailsError.value = ''
}

function prepareMembershipAction(membership) {
  membershipAction.value = {
    ...membership,
    newStatus:
      membership.status === 'active'
        ? 'blocked'
        : 'active'
  }

  reason.value = ''
  detailsError.value = ''
}

async function updateMembership() {
  if (
    saving.value ||
    !membershipAction.value ||
    !selectedCustomer.value
  ) return

  if (reason.value.trim().length < 5) {
    detailsError.value =
      'Please provide a reason of at least five characters.'

    return
  }

  saving.value = true
  detailsError.value = ''
  success.value = ''

  const customerId =
    selectedCustomer.value.customer.id

  const membershipId =
    membershipAction.value.id

  try {
    const response = await api(
      `/admin/platform/customers/${customerId}/memberships/${membershipId}`,
      {
        method: 'PATCH',
        body: {
          status: membershipAction.value.newStatus,
          reason: reason.value.trim()
        }
      }
    )

    success.value = response.message

    membershipAction.value = null
    reason.value = ''

    await viewCustomer(customerId)

    await loadCustomers(
      customers.value.current_page
    )

  } catch (exception) {
    detailsError.value =
      exception.message ||
      'Unable to update membership.'
  } finally {
    saving.value = false
  }
}

onMounted(() => loadCustomers())
</script>

<template>
  <section class="platform-admin-page">

    <header class="page-heading">

      <p class="eyebrow">
        {{ platform.branding.platform_name }}
        PLATFORM ADMINISTRATION
      </p>

      <h1>Customers</h1>

      <p class="page-description">
        Manage customer accounts, business
        memberships and order history across
        your platform.
      </p>

    </header>

    <div class="statistics-grid">

      <div class="statistic-card">
        <span>Total Customers</span>
        <strong>
          {{ statistics.total_customers ?? '—' }}
        </strong>
      </div>

      <div class="statistic-card">
        <span>Verified Customers</span>
        <strong>
          {{ statistics.verified_customers ?? '—' }}
        </strong>
      </div>

      <div class="statistic-card">
        <span>Unverified Customers</span>
        <strong>
          {{ statistics.unverified_customers ?? '—' }}
        </strong>
      </div>

      <div class="statistic-card">
        <span>Business Memberships</span>
        <strong>
          {{ statistics.business_memberships ?? '—' }}
        </strong>
      </div>

    </div>

    <div
      v-if="error"
      class="message error"
      role="alert"
    >
      {{ error }}
    </div>

    <div
      v-if="success"
      class="message success"
      role="status"
    >
      {{ success }}
    </div>

    <div class="admin-panel">

      <div class="panel-heading">
        <h2>Customer Directory</h2>

        <button
          class="secondary-button"
          :disabled="loading"
          @click="loadCustomers(customers.current_page)"
        >
          <RefreshCw :size="16" />
          Refresh
        </button>
      </div>

      <form
        class="filter-bar"
        @submit.prevent="loadCustomers()"
      >

        <input
          v-model="filters.q"
          class="search-input"
          placeholder="Search name, email or telephone..."
          aria-label="Search customers"
        />

        <select
          v-model="filters.store_id"
          aria-label="Filter by business"
        >
          <option value="">
            All Businesses
          </option>

          <option
            v-for="store in stores"
            :key="store.id"
            :value="store.id"
          >
            {{ store.name }}
          </option>
        </select>

        <select
          v-model="filters.verification"
          aria-label="Filter by verification"
        >
          <option value="">All Customers</option>
          <option value="verified">Verified</option>
          <option value="unverified">Unverified</option>
        </select>

        <button
          type="submit"
          class="primary-button"
          :disabled="loading"
        >
          <Search :size="16" />
          Search
        </button>

        <button
          type="button"
          class="secondary-button"
          :disabled="loading"
          @click="resetFilters"
        >
          Reset
        </button>

      </form>

      <p
        v-if="loading"
        class="empty-state"
      >
        Loading customers...
      </p>

      <div
        v-else-if="!error"
        class="table-wrapper"
      >

        <table>

          <thead>
            <tr>
              <th>Customer</th>
              <th>Telephone</th>
              <th>Verification</th>
              <th>Businesses</th>
              <th>Orders</th>
              <th>Registered</th>
              <th>Actions</th>
            </tr>
          </thead>

          <tbody>

            <tr
              v-for="customer in customers.data"
              :key="customer.id"
            >
              <td>
                <strong>{{ customer.name }}</strong>
                <small>{{ customer.email }}</small>
              </td>

              <td>{{ customer.phone || '—' }}</td>

              <td>
                <span
                  class="status-label"
                  :class="
                    customer.email_verified_at
                      ? 'verified'
                      : 'unverified'
                  "
                >
                  {{
                    customer.email_verified_at
                      ? 'Verified'
                      : 'Unverified'
                  }}
                </span>
              </td>

              <td>{{ customer.businesses_count }}</td>

              <td>{{ customer.orders_count }}</td>

              <td>
                {{ formatDate(customer.created_at) }}
              </td>

              <td>
                <button
                  class="secondary-button"
                  @click="viewCustomer(customer.id)"
                >
                  <Eye :size="15" />
                  View
                </button>
              </td>
            </tr>

            <tr v-if="!customers.data.length">
              <td colspan="7" class="empty-state">
                No customers found.
              </td>
            </tr>

          </tbody>

        </table>

      </div>

      <div class="pagination">

        <span>
          {{ customers.total }} customers —
          Page {{ customers.current_page }}
          of {{ customers.last_page }}
        </span>

        <div class="pagination-buttons">

          <button
            class="secondary-button"
            :disabled="
              loading ||
              customers.current_page <= 1
            "
            @click="
              loadCustomers(customers.current_page - 1)
            "
          >
            Previous
          </button>

          <button
            class="secondary-button"
            :disabled="
              loading ||
              customers.current_page >= customers.last_page
            "
            @click="
              loadCustomers(customers.current_page + 1)
            "
          >
            Next
          </button>

        </div>

      </div>

    </div>

    <!-- Customer details -->

    <div
      v-if="detailsOpen"
      class="modal-overlay"
      @click.self="closeDetails"
      @keydown.esc="closeDetails"
    >

      <section
        class="modal-panel"
        role="dialog"
        aria-modal="true"
        aria-label="Customer details"
      >

        <div class="panel-heading">

          <h2>Customer Details</h2>

          <button
            class="secondary-button"
            aria-label="Close customer details"
            :disabled="saving || detailsLoading"
            @click="closeDetails"
          >
            <X :size="18" />
          </button>

        </div>

        <div
          v-if="detailsError"
          class="message error"
          role="alert"
        >
          {{ detailsError }}
        </div>

        <p v-if="detailsLoading">
          Loading customer details...
        </p>

        <template v-else-if="selectedCustomer">

          <div class="details-grid">

            <div>
              <span>Full Name</span>
              <strong>
                {{ selectedCustomer.customer.name }}
              </strong>
            </div>

            <div>
              <span>Email Address</span>
              <strong>
                {{ selectedCustomer.customer.email }}
              </strong>
            </div>

            <div>
              <span>Telephone</span>
              <strong>
                {{ selectedCustomer.customer.phone || '—' }}
              </strong>
            </div>

            <div>
              <span>Registration Date</span>
              <strong>
                {{
                  formatDate(
                    selectedCustomer.customer.created_at
                  )
                }}
              </strong>
            </div>

          </div>

          <h3>Business Memberships</h3>

          <div class="table-wrapper">

            <table>

              <thead>
                <tr>
                  <th>Business</th>
                  <th>Status</th>
                  <th>Joined</th>
                  <th>Action</th>
                </tr>
              </thead>

              <tbody>

                <tr
                  v-for="membership in selectedCustomer.memberships"
                  :key="membership.id"
                >

                  <td>
                    {{ membership.business_name }}
                  </td>

                  <td>
                    <span
                      class="status-label"
                      :class="membership.status"
                    >
                      {{ membership.status }}
                    </span>
                  </td>

                  <td>
                    {{ formatDate(membership.joined_at) }}
                  </td>

                  <td>
                    <button
                      :class="
                        membership.status === 'active'
                          ? 'danger-button'
                          : 'secondary-button'
                      "
                      :disabled="saving"
                      @click="
                        prepareMembershipAction(membership)
                      "
                    >
                      {{
                        membership.status === 'active'
                          ? 'Block'
                          : 'Reactivate'
                      }}
                    </button>
                  </td>

                </tr>

                <tr
                  v-if="!selectedCustomer.memberships.length"
                >
                  <td colspan="4" class="empty-state">
                    No business memberships.
                  </td>
                </tr>

              </tbody>

            </table>

          </div>

          <!-- Membership confirmation -->

          <form
            v-if="membershipAction"
            @submit.prevent="updateMembership"
          >

            <h3>
              Confirm Membership Change
            </h3>

            <p>
              Change membership at
              <strong>
                {{ membershipAction.business_name }}
              </strong>
              to
              <strong>
                {{ membershipAction.newStatus }}
              </strong>?
            </p>

            <label for="membership-reason">
              Reason for change
            </label>

            <textarea
              id="membership-reason"
              v-model="reason"
              rows="3"
              minlength="5"
              maxlength="500"
              required
              :disabled="saving"
              style="width: 100%; margin: 12px 0;"
            />

            <div class="action-buttons">

              <button
                type="submit"
                class="primary-button"
                :disabled="saving"
              >
                {{ saving ? 'Saving...' : 'Confirm Change' }}
              </button>

              <button
                type="button"
                class="secondary-button"
                :disabled="saving"
                @click="membershipAction = null"
              >
                Cancel
              </button>

            </div>

          </form>

          <!-- Customer orders -->

          <h3>Order History</h3>

          <div class="table-wrapper">

            <table>

              <thead>
                <tr>
                  <th>Order</th>
                  <th>Business</th>
                  <th>Status</th>
                  <th>Payment</th>
                  <th>Total</th>
                  <th>Date</th>
                </tr>
              </thead>

              <tbody>

                <tr
                  v-for="order in selectedCustomer.orders.data"
                  :key="order.id"
                >
                  <td>{{ order.number }}</td>

                  <td>
                    {{ order.business_name || 'Unassigned' }}
                  </td>

                  <td>
                    <span class="status-label">
                      {{ order.status }}
                    </span>
                  </td>

                  <td>
                    <span
                      class="status-label"
                      :class="order.payment_status"
                    >
                      {{ order.payment_status }}
                    </span>
                  </td>

                  <td>{{ formatMoney(order.total) }}</td>

                  <td>{{ formatDate(order.created_at) }}</td>
                </tr>

                <tr
                  v-if="!selectedCustomer.orders.data.length"
                >
                  <td colspan="6" class="empty-state">
                    No registered customer orders.
                  </td>
                </tr>

              </tbody>

            </table>

          </div>

          <div class="pagination">

            <span>
              {{ selectedCustomer.orders.total }} orders
            </span>

            <div class="pagination-buttons">

              <button
                class="secondary-button"
                :disabled="
                  saving ||
                  selectedCustomer.orders.current_page <= 1
                "
                @click="
                  viewCustomer(
                    selectedCustomer.customer.id,
                    selectedCustomer.orders.current_page - 1
                  )
                "
              >
                Previous
              </button>

              <button
                class="secondary-button"
                :disabled="
                  saving ||
                  selectedCustomer.orders.current_page >=
                  selectedCustomer.orders.last_page
                "
                @click="
                  viewCustomer(
                    selectedCustomer.customer.id,
                    selectedCustomer.orders.current_page + 1
                  )
                "
              >
                Next
              </button>

            </div>

          </div>

        </template>

      </section>

    </div>

  </section>
</template>