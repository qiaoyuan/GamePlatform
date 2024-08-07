<template>
  <div class="app-container">
    <w-tabs-table
      ref="wTable"
      :operates="operates"
      :module="module"
      k
      @add="onAdd"
      @edit="onEdit"
    >
    </w-tabs-table>
    <AdminAddDialog ref="adminAddDialog" @done="getList" />
  </div>
</template>

<script>
import AdminAddDialog from './dialog/adminAddDialog'

export default {
  name: 'AdminIndex',
  components: { AdminAddDialog },
  data() {
    return {
      module: 'admin',
      operates: {
        del: true,
        look: false,
        add: true,
        edit: true,
        multiDel: true,
        // recycle: { autoLink: true },
      }
    }
  },
  methods: {
    getList() {
      this.$store.dispatch('cleanColumnOptions', this.module + '/select')
      this.$refs.wTable.getList()
    },
    onEdit(row) {
      this.$refs.adminAddDialog.open(row)
    },
    onAdd() {
      this.$refs.adminAddDialog.open({})
    }
  }
}
</script>
