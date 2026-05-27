import { chromium } from 'playwright'
import path from 'path'
import { fileURLToPath } from 'url'

const __dirname = path.dirname(fileURLToPath(import.meta.url))
const OUT_DIR = path.resolve(__dirname, '..', 'web')
const BASE = process.env.BASE_URL || 'http://localhost:5174'
const EMAIL = process.env.LOGIN_EMAIL || 'chain@fastbite.pt'
const PASSWORD = process.env.LOGIN_PASSWORD || 'password'

const VIEWS = [
  { id: 'login',         url: '/restaurant/login',          beforeLogin: true,  label: 'Login Restaurante' },
  { id: 'dashboard',     url: '/restaurant/dashboard',      label: 'Dashboard / Fila de Encomendas' },
  { id: 'kitchen',       url: '/restaurant/kitchen',        label: 'Cozinha Virtual' },
  { id: 'history',       url: '/restaurant/history',        label: 'Historico de Encomendas' },
  { id: 'stats',         url: '/restaurant/stats',          label: 'Estatisticas' },
  { id: 'menu',          url: '/restaurant/menu',           label: 'Menu do Restaurante' },
  { id: 'chain-catalog', url: '/restaurant/chain-catalog',  label: 'Catalogo da Cadeia' },
  { id: 'campaigns',     url: '/restaurant/campaigns',      label: 'Campanhas e Cupoes' },
  { id: 'reviews',       url: '/restaurant/reviews',        label: 'Avaliacoes' },
  { id: 'chat',          url: '/restaurant/chat',           label: 'Chat' },
  { id: 'notifications', url: '/restaurant/notifications',  label: 'Notificacoes' },
  { id: 'profile',       url: '/restaurant/profile',        label: 'Perfil' },
]

;(async () => {
  const browser = await chromium.launch({ headless: true })
  const context = await browser.newContext({
    viewport: { width: 1440, height: 900 },
    deviceScaleFactor: 2,
    locale: 'pt-PT',
  })
  const page = await context.newPage()
  page.setDefaultTimeout(15_000)

  page.on('pageerror', (err) => console.log(`[pageerror] ${err.message}`))
  page.on('console', (msg) => {
    if (msg.type() === 'error') console.log(`[browser-console-error] ${msg.text()}`)
  })

  // --- Capture login screen first ---
  console.log(`[*] -> ${BASE}/restaurant/login`)
  await page.goto(`${BASE}/restaurant/login`, { waitUntil: 'networkidle' })
  await page.waitForTimeout(800)
  await page.screenshot({ path: path.join(OUT_DIR, '01_login.png'), fullPage: true })
  console.log(`[ok] 01_login.png`)

  // --- Login ---
  console.log(`[*] Login as ${EMAIL}...`)
  await page.fill('input[type="email"]', EMAIL)
  await page.fill('input[type="password"]', PASSWORD)
  await page.click('button[type="submit"]')

  // Wait for navigation away from /login (max 15s)
  try {
    await page.waitForURL((u) => !u.toString().includes('/login'), { timeout: 15000 })
    console.log(`[ok] Logged in. Now at: ${page.url()}`)
  } catch (e) {
    console.log(`[err] Login navigation did not happen: ${e.message}`)
    await page.screenshot({ path: path.join(OUT_DIR, '_login_fail.png'), fullPage: true })
    await browser.close()
    process.exit(1)
  }

  await page.waitForTimeout(1500) // give realtime/queries time

  // --- Capture each authenticated view ---
  let idx = 2
  for (const v of VIEWS) {
    if (v.beforeLogin) continue
    const filename = `${String(idx).padStart(2, '0')}_${v.id}.png`
    const target = `${BASE}${v.url}`
    console.log(`[*] -> ${target}`)
    try {
      await page.goto(target, { waitUntil: 'networkidle', timeout: 15_000 })
    } catch (e) {
      console.log(`[warn] networkidle timeout: ${e.message}, capturing anyway`)
    }
    await page.waitForTimeout(1200) // wait for animations/queries
    await page.screenshot({ path: path.join(OUT_DIR, filename), fullPage: true })
    const url = page.url()
    console.log(`[ok] ${filename}  (page url: ${url})`)
    idx++
  }

  await browser.close()
  console.log(`[done] Saved to ${OUT_DIR}`)
})().catch((e) => {
  console.error(e)
  process.exit(1)
})
