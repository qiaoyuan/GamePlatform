<template>
  <div class="app-container">
    <w-tabs-table
      ref="wTable"
      :operates="operates"
      :module="module"
      :actions="{ tabs: module + '/tabs' }"
      k
      @add="onAdd"
      @edit="onEdit"
      @changeTab="changeTab"
    >
    </w-tabs-table>
    <AdminSettingAddDialog ref="adminSettingAddDialog" @done="getList" />
  </div>
</template>

<script>
import AdminSettingAddDialog from './dialog/adminSettingAddDialog'

export default {
  name: 'AdminSettingIndex',
  components: { AdminSettingAddDialog },
  data() {
    return {
      module: 'adminSetting',
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
    changeTab(v) {
      this.module = v
    },
    getList() {
      this.$store.dispatch('cleanColumnOptions', this.module + '/select')
      this.$refs.wTable.getList()
    },
    onEdit(row) {
      this.$refs.adminSettingAddDialog.open(row)
    },
    onAdd() {
      this.$refs.adminSettingAddDialog.open({ module: this.module })
    }
  }
}
</script>
