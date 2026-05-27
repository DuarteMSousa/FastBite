import { chromium } from 'playwright'
import path from 'path'
import { pathToFileURL, fileURLToPath } from 'url'

const __dirname = path.dirname(fileURLToPath(import.meta.url))
const html = path.join(__dirname, 'folder_tree.html')
const out = path.resolve(__dirname, '..', '..', 'Diagramas_Final', 'png', '00_folder_tree.png')

;(async () => {
  const browser = await chromium.launch({ headless: true })
  const context = await browser.newContext({
    viewport: { width: 1200, height: 1400 },
    deviceScaleFactor: 2,
  })
  const page = await context.newPage()
  await page.goto(pathToFileURL(html).href, { waitUntil: 'load' })
  await page.waitForTimeout(500)
  // Capture just the .wrap container for tighter cropping
  const el = await page.$('.wrap')
  await el.screenshot({ path: out, type: 'png' })
  console.log(`Saved: ${out}`)
  await browser.close()
})().catch((e) => { console.error(e); process.exit(1) })
