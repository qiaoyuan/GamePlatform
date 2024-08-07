<template>
  <div class="app-container">
    <w-tabs-table
      ref="wTable"
      :operates="operates"
      :module="module"
      :tree-props="{ children: 'children', hasChildren: 'id' }"
      :lazy="true"
      :load="onLoad"
      :pagination="false"
      k
      @add="onAdd"
      @edit="onEdit"
    >
    </w-tabs-table>
    <AdminDepartmentAddDialog ref="adminDepartmentAddDialog" @done="getList" />
  </div>
</template>

<script>
import AdminDepartmentAddDialog from './dialog/adminDepartmentAddDialog'

export default {
  name: 'AdminDepartmentIndex',
  components: { AdminDepartmentAddDialog },
  data() {
    return {
      module: 'adminDepartment',
      operates: {
        del: true,
        look: false,
        add: true,
        edit: true,
        multiDel: true,
        other: [
          { title: '新增', click: (row, index) => this.onAdd({ parent_id:  row.id }) }
        ],
        // recycle: { autoLink: true },
      },
      maps: {},
      nodeId: 0
    }
  },
  methods: {
    getList() {
      this.$store.dispatch('cleanColumnOptions', this.module)
      const { tree, resolve } = this.maps[this.nodeId] || {}
      if (tree) {
        this.loadNode(this.nodeId, resolve)
        return
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
      this.$refs.adminDepartmentAddDialog.open(row)
    },
    onAdd() {
      this.$refs.adminDepartmentAddDialog.open({})
    }
  }
}
</script>
