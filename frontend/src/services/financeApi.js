import { api } from '@/api/http'

const BASE = '/admin/finance'

function get(endpoint, params = {}) {
  return api(`${BASE}/${endpoint}`, {
    params: {
      ...params,
      livemode: 0
    }
  })
}

function write(endpoint, method, body = {}) {
  return api(`${BASE}/${endpoint}`, {
    method,
    body: {
      ...body,
      livemode: false
    }
  })
}

export const financeApi = {

  // Dashboard

  overview(filters = {}) {
    return get('overview', filters)
  },

  trends(filters = {}) {
    return get('trends', filters)
  },

  // Transactions

  transactions(filters = {}) {
    return get('transactions', filters)
  },

  transaction(id) {
    return get(`transactions/${id}`)
  },

  // Invoices

  invoices(filters = {}) {
    return get('invoices', filters)
  },

  invoice(id) {
    return get(`invoices/${id}`)
  },

  createInvoice(payload) {
    return write('invoices', 'POST', payload)
  },

  addInvoiceItem(id, payload) {
    return write(
      `invoices/${id}/items`,
      'POST',
      payload
    )
  },

  removeInvoiceItem(id, itemId) {
    return write(
      `invoices/${id}/items/${itemId}`,
      'DELETE'
    )
  },

  issueInvoice(id) {
    return write(`invoices/${id}/issue`, 'POST')
  },

  voidInvoice(id) {
    return write(`invoices/${id}/void`, 'POST')
  },

  // Subscription payments

  payments(filters = {}) {
    return get('payments', filters)
  },

  payment(id) {
    return get(`payments/${id}`)
  },

  // Expenses

  expenses(filters = {}) {
    return get('expenses', filters)
  },

  expenseCategories() {
    return get('expense-categories')
  },

  createExpense(payload) {
    return write('expenses', 'POST', payload)
  },

  updateExpense(id, payload) {
    return write(`expenses/${id}`, 'PUT', payload)
  },

  approveExpense(id) {
    return write(`expenses/${id}/approve`, 'POST')
  },

  rejectExpense(id, reason) {
    return write(`expenses/${id}/reject`, 'POST', {
      reason
    })
  },

  markExpensePaid(id, paymentMethod) {
    return write(`expenses/${id}/mark-paid`, 'POST', {
      payment_method: paymentMethod
    })
  },

  cancelExpense(id) {
    return write(`expenses/${id}/cancel`, 'POST')
  },

  // Credit notes

  creditNotes(filters = {}) {
    return get('credit-notes', filters)
  },

  creditNote(id) {
    return get(`credit-notes/${id}`)
  },

  createCreditNote(payload) {
    return write('credit-notes', 'POST', payload)
  },

  issueCreditNote(id) {
    return write(`credit-notes/${id}/issue`, 'POST')
  },

  voidCreditNote(id) {
    return write(`credit-notes/${id}/void`, 'POST')
  },

  // Tax

  taxRates() {
    return get('tax-rates')
  },

  // Financial reports

  reports(filters = {}) {
    return get('reports', filters)
  },

  // Reconciliation

  reconciliation(filters = {}) {
    return get('reconciliation', filters)
  },

  unreconciledTransactions(filters = {}) {
    return get('reconciliation/unreconciled', filters)
  },

  reconciliationAudit(filters = {}) {
    return get('reconciliation/audit', filters)
  }
}

export default financeApi