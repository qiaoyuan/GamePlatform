const { test } = require('node:test')
const assert = require('node:assert/strict')
const fs = require('node:fs')
const path = require('node:path')
const vm = require('node:vm')
const { createRequire } = require('node:module')
const adminRequire = createRequire(path.resolve(__dirname, '../../admin/package.json'))
const parser = adminRequire('@babel/parser')
const generate = adminRequire('@babel/generator').default
const compiler = adminRequire('vue-template-compiler')
const source = fs.readFileSync(path.resolve(__dirname, '../../admin/src/components/w/components/w-table/index.vue'), 'utf8')
const ast = parser.parse(compiler.parseComponent(source).script.content, { sourceType: 'module' })
const component = ast.program.body.find(node => node.type === 'ExportDefaultDeclaration').declaration
const methods = component.properties.find(node => node.key.name === 'methods').value.properties
const saveMethod = methods.find(node => node.key.name === 'saveVisibleRowOrder')

function harness(oldIndex, newIndex, { sort = '', fail = false } = {}) {
  const calls = []
  const method = vm.runInNewContext(`({ ${generate(saveMethod).code} }).saveVisibleRowOrder`, {
    post: async (url, data) => {
      calls.push(JSON.parse(JSON.stringify({ url, data })))
      if (fail) throw new Error('request failed')
    }
  })
  const nodes = [6, 5, 4].map(id => ({ id }))
  const moved = nodes[oldIndex]
  const children = nodes.slice()
  children.splice(newIndex, 0, children.splice(oldIndex, 1)[0])
  const from = {
    children,
    removeChild(item) { children.splice(children.indexOf(item), 1) },
    insertBefore(item, reference) { children.splice(reference ? children.indexOf(reference) : children.length, 0, item) }
  }
  const disabled = [[], []]
  let refreshed = 0
  const warnings = []
  const context = {
    data: nodes, primaryKey: 'id', sort, query: {}, actions_: { sort: 'priceStrategy/sort' },
    rowSortables: disabled.map(states => ({ option: (_, value) => states.push(value) })),
    $message: { warning: text => warnings.push(text) },
    getList: async () => { refreshed++ }
  }
  return {
    run: () => method.call(context, { item: moved, from, newIndex, oldIndex }),
    calls, children, disabled, warnings, refreshed: () => refreshed
  }
}

test('dragging up/down submits visible IDs and restores Vue-owned DOM', async () => {
  for (const [oldIndex, newIndex, expected] of [[2, 0, [4, 6, 5]], [0, 2, [5, 4, 6]]]) {
    const h = harness(oldIndex, newIndex)
    await h.run()
    assert.deepEqual(h.calls[0], { url: 'priceStrategy/sort', data: { before_ids: [6, 5, 4], after_ids: expected } })
    assert.deepEqual(h.children.map(node => node.id), [6, 5, 4])
    assert.deepEqual(h.disabled, [[true, false], [true, false]])
    assert.equal(h.refreshed(), 1)
  }
})

test('failed save restores DOM, reloads persisted order and unlocks every fixed table', async () => {
  const h = harness(0, 2, { fail: true })
  await h.run()
  assert.deepEqual(h.children.map(node => node.id), [6, 5, 4])
  assert.deepEqual(h.disabled, [[true, false], [true, false]])
  assert.equal(h.refreshed(), 1)
})

test('column-sorted view cannot overwrite manual ordering', async () => {
  const h = harness(0, 2, { sort: '-id' })
  await h.run()
  assert.equal(h.calls.length, 0)
  assert.equal(h.warnings.length, 1)
  assert.deepEqual(h.children.map(node => node.id), [6, 5, 4])
})

test('unchanged drag does not send a request', async () => {
  const h = harness(1, 1)
  await h.run()
  assert.equal(h.calls.length, 0)
  assert.equal(h.refreshed(), 0)
})
