import {
  test,
  expect,
} from '@playwright/test'

test('IVISION public website is accessible', async ({ page }) => {

  const response = await page.goto('/')

  expect(response.status()).toBeLessThan(400)

  await expect(page.locator('body')).toBeVisible()

})

test('Administrator login page is accessible', async ({ page }) => {

  await page.goto('/admin/login')

  await expect(page).toHaveURL(/\/admin\/login/)

  await expect(page.locator('body')).toBeVisible()

})

test('Unauthenticated users cannot access admin dashboard', async ({ page }) => {

  await page.goto('/admin')

  await expect(page).toHaveURL(/\/admin\/login/)

})

test('Unauthenticated users cannot access Communication', async ({ page }) => {

  await page.goto('/admin/communication')

  await expect(page).toHaveURL(/\/admin\/login/)

})

test('Unauthenticated users cannot access Finance', async ({ page }) => {

  await page.goto('/admin/finance')

  await expect(page).toHaveURL(/\/admin\/login/)

})