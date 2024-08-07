<template>
  <div class="app-container">
    <w-tabs-table
      ref="wTable"
      :operates="operates"
      :tree-props="{ children: 'children', hasChildren: 'has_children' }"
      :lazy="true"
      :load="onLoad"
      :module="module"
      k
      @add="onAdd"
      @edit="onEdit"
    >
    </w-tabs-table>
    <AddressAddDialog ref="addressAddDialog" @done="getList(true)" />
  </div>
</template>

<script>
import AddressAddDialog from './dialog/addressAddDialog'

export default {
  name: 'AddressIndex',
  components: { AddressAddDialog },
  data() {
    return {
      module: 'address',
      operates: {
        del: true,
        look: false,
        add: true,
        edit: true,
        multiDel: true,
        other: [
          { title: '新增', click: row => this.onAdd({ pid:  row.pid}) }
        ],
        // recycle: { autoLink: true },
      },
      maps: {},
      nodeId: 0
    }
  },
  methods: {
    getList(node = false) {
      this.$store.dispatch('cleanColumnOptions', this.module)
      if (node) {
        const { tree, resolve } = this.maps[this.nodeId] || {}
        if (tree) {
          this.loadNode(this.nodeId, resolve)
          return
        }
      }
      this.$refs.wTable.getList()
    },
    onLoad(tree, treeNode, resolve) {
      const pid = tree.id
      this.maps[pid] = { tree, treeNode, resolve }
      this.loadNode(pid, resolve)
    },
    loadNode(pid, resolve) {
      this.$w_fun.post(this.module + '/index', { parent_id: pid }).then(
        ({
           data: { list },
         }) =>
          resolve(list.map((itm, idx) => ({...itm, tableIndex: `${pid}.${idx + 1}`})))
      )
    },
    onEdit(row) {
      this.$refs.addressAddDialog.open(row)
    },
    onAdd() {
      this.$refs.addressAddDialog.open({})
    }
  }
}
</script>
