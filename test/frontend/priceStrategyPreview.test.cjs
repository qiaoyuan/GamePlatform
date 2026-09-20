const { test } = require('node:test')
const assert = require('node:assert/strict')
const fs = require('node:fs')
const path = require('node:path')
const puppeteer = require('puppeteer')

test('competitor preview has compact three-line layout and independent warning colors', async () => {
  const root = path.resolve(__dirname, '../..')
  const source = fs.readFileSync(path.join(root, 'admin/src/views/priceStrategy/index.vue'), 'utf8')
  const slot = source.match(/<template #msgSlot="\{ row \}">([\s\S]*?)\n      <\/template>/)[1]
  const styles = source.match(/<style scoped>([\s\S]*?)<\/style>/)[1].replace(/\/deep\//g, '')
  const browser = await puppeteer.launch({ headless: true })
  try {
    const page = await browser.newPage()
    await page.setViewport({ width: 750, height: 420 })
    await page.setContent('<div id="app"></div>')
    await page.addScriptTag({ path: path.join(root, 'admin/node_modules/vue/dist/vue.js') })
    await page.addScriptTag({ path: path.join(root, 'admin/node_modules/element-ui/lib/index.js') })
    await page.addStyleTag({ path: path.join(root, 'admin/node_modules/element-ui/lib/theme-chalk/index.css') })
    await page.addStyleTag({ content: styles })
    await page.evaluate(({ slot }) => {
      const line = (id, price, shop, below_minimum) => ({ id, price, currency: 'USD', shop, below_minimum, text: `${price} USD · ${shop}` })
      new Vue({
        el: '#app',
        data: { rows: [
          { name: '策略一', msg_lines: [line(1, '0.7', '库存合格店铺', true), line(2, '0.77', '特别长的店铺名称'.repeat(5), false), line(3, '0.8', '<img src=x>', false)] },
          { name: '策略二', msg_lines: [line(4, '0.9', '另一店铺', false)] },
          { name: '策略三', msg_lines: [], msg: '暂无符合条件的竞品' }
        ] },
        template: `<div class="app-container"><el-table :data="rows" size="mini" border style="width: 560px">
          <el-table-column prop="name" label="策略名称" width="160" />
          <el-table-column label="msg（竞品低价前三）" width="260" class-name="competitor-preview-cell">
            <template slot-scope="{ row }">${slot}</template>
          </el-table-column></el-table></div>`
      })
    }, { slot })
    await page.waitForSelector('.competitor-preview-line')
    const result = await page.evaluate(() => {
      const cell = document.querySelector('td.competitor-preview-cell')
      const lines = Array.from(cell.querySelectorAll('.competitor-preview-line'))
      const shop = lines[1].querySelector('.competitor-preview-shop')
      return {
        count: lines.length, height: cell.getBoundingClientRect().height,
        font: getComputedStyle(lines[0]).fontSize,
        colors: lines.map(line => getComputedStyle(line).color),
        ellipsis: shop.scrollWidth > shop.clientWidth,
        title: lines[1].title,
        injectedImages: cell.querySelectorAll('img').length,
        emptyText: document.querySelectorAll('td.competitor-preview-cell')[2].textContent.trim()
      }
    })
    assert.equal(result.count, 3)
    assert.ok(result.height <= 48, `row too tall: ${result.height}`)
    assert.equal(result.font, '11px')
    assert.equal(result.colors[0], 'rgb(245, 108, 108)')
    assert.notEqual(result.colors[1], result.colors[0])
    assert.equal(result.ellipsis, true)
    assert.ok(result.title.includes('特别长的店铺名称'))
    assert.equal(result.injectedImages, 0)
    assert.equal(result.emptyText, '暂无符合条件的竞品')
    console.log(`three-line cell height: ${result.height}px`)
  } finally {
    await browser.close()
  }
})
