const { test } = require('node:test')
const assert = require('node:assert/strict')
const fs = require('node:fs')
const vm = require('node:vm')
const path = require('node:path')
const source = fs.readFileSync(path.resolve(__dirname, '../../admin/src/views/crawl/index.vue'), 'utf8')
const script = source.match(/<script>([\s\S]*?)<\/script>/)[1].replace(/import CrawlAddDialog from .*\n/, '')
const sandbox = { module: { exports: {} }, CrawlAddDialog: {} }
vm.runInNewContext(script.replace('export default', 'module.exports ='), sandbox)
const methods = sandbox.module.exports.methods
const state = () => ({ ...methods, statsLoaded: false, $route: { query: {} } })

test('server counts render platform objects and legacy zero totals', () => {
  const s = state()
  s.updateStats({ stats: { main_server: { g2g: '2', eld: 3 }, crawler_2: { g2g: 0, eld: 4 } } })
  assert.equal(s.statsLoaded, true)
  assert.equal(s.formatServerStats(s.stats.main_server), 'G2G 2　ELD 3')
  s.updateStats({ stats: { main_server: 0, crawler_2: 5 } })
  assert.equal(s.statsLoaded, true)
  assert.equal(s.formatServerStats(s.stats.main_server), '0 个')
  assert.equal(s.formatServerStats(s.stats.crawler_2), '5 个')
})

test('invalid counts never render objects or NaN', () => {
  const s = state()
  assert.equal(s.formatServerStats({ g2g: {}, eld: -1 }), 'G2G --　ELD --')
  assert.equal(s.formatServerStats(null), '-- 个')
})

test('fallback cannot count filtered or incomplete pages as global totals', () => {
  const s = state()
  s.updateStats({ list: { total: 20, data: [{ status: 1, crawl_server: 1, category: '物品' }] } })
  assert.equal(s.statsLoaded, false)
  s.$route.query.filter = '{"status_multiple":[1]}'
  s.updateStats({ list: [{ status: 1, crawl_server: 1, category: '物品' }] })
  assert.equal(s.statsLoaded, false)
})

const editableState = post => ({
  ...state(), module: 'crawl',
  $set: (row, key, value) => { row[key] = value },
  $delete: (row, key) => { delete row[key] },
  $w_fun: { post },
  $refs: { wTable: { getList() {} } },
})

test('inline type changes save numeric zero without changing server or status', async () => {
  const row = { id: 9, crawl_type: 1, crawl_server: 2, status: 1 }
  let requests = 0
  const s = editableState(async (url, payload) => {
    requests++
    assert.equal(url, 'crawl/status')
    assert.equal(payload.id, 9)
    assert.equal(payload.crawl_type, 0)
    assert.equal(Object.keys(payload).length, 2)
    assert.equal(row._crawlTypeSaving, true)
    return { data: { crawl_type_name: '默认爬虫' } }
  })
  await s.changeCrawlType(row, 0)
  assert.equal(requests, 1)
  assert.equal(row.crawl_type, 0)
  assert.equal(row.crawl_server, 2)
  assert.equal(row.status, 1)
  assert.equal(row._crawlTypeSaving, undefined)
})

test('failed type changes preserve original value', async () => {
  const row = { id: 9, crawl_type: 0 }
  const s = editableState(async () => { throw new Error('save failed') })
  await s.changeCrawlType(row, 1)
  assert.equal(row.crawl_type, 0)
  assert.equal(row._crawlTypeSaving, undefined)
})

test('unchanged and pending type changes do not submit', async () => {
  const s = editableState(async () => { assert.fail('unexpected request') })
  await s.changeCrawlType({ crawl_type: 0 }, 0)
  await s.changeCrawlType({ crawl_type: 0, _crawlTypeSaving: true }, 1)
})
