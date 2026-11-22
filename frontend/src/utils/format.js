const gbp = new Intl.NumberFormat('en-GB', { style: 'currency', currency: 'GBP' })

export const money = (n) => gbp.format(Number(n) || 0)

export const dateShort = (d) =>
  new Date(d).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' })
