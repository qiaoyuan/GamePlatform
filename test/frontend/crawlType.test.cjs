const { test } = require('node:test')
const assert = require('node:assert/strict')
const fs = require('node:fs')
const path = require('node:path')
const vm = require('node:vm')

const source = fs.readFileSync(path.resolve(__dirname,
  '../../admin/src/views/crawl/dialog/crawlAddDialog.vue'), 'utf8')
const script = source.match(/<script>([\s\S]*?)<\/script>/)[1]
const sandbox = { module: { exports: {} } }
vm.runInNewContext(script.replace('export default', 'module.exports ='), sandbox)
const component = sandbox.module.exports

test('new crawl targets default to default type and empty enhanced stores', () => {
  const state = { module: 'crawl', $refs: { wDialogForm: {} } }
  component.methods.setForm.call(state, {})
  assert.equal(state.form.crawl_type.value, 0)
  assert.equal(state.form.enhance_stores.value, '')
  assert.equal(state.form.crawl_type.options[1].label, '店铺加强')
  assert.equal(state.formAction, 'crawl/add')
  assert.deepEqual(Array.from(state.form.crawl_type.options, row => row.value), [0, 1])
  assert.equal(state.$refs.wDialogForm.visible, true)
})

test('editing enhancement preserves type and store bindings while version stays readonly', () => {
  const state = { module: 'crawl', $refs: { wDialogForm: {} } }
  component.methods.setForm.call(state, { id: 10, crawl_type: 1, version: 8, enhance_stores: 'Player,JIANONE' })
  assert.equal(state.form.crawl_type.value, 1)
  assert.equal(state.form.enhance_stores.value, 'Player,JIANONE')
  assert.equal(state.formAction, 'crawl/edit')
  assert.equal(state.form.id.value, 10)
  assert.equal(state.form.version.readonly, true)
})
