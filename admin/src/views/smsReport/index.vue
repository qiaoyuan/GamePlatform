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
    <SmsReportAddDialog ref="smsReportAddDialog" @done="getList" />
  </div>
</template>

<script>
import SmsReportAddDialog from './dialog/smsReportAddDialog'

export default {
  name: 'SmsReportIndex',
  components: { SmsReportAddDialog },
  data() {
    return {
      module: 'smsReport',
      operates: {
        del: true,
        look: false,
        add: true,
        edit: true,
        multiDel: true,
        // recycle: { autoLink: true },
      },
    }
  },
  methods: {
    getList() {
      this.$store.dispatch('cleanColumnOptions', this.module)
      this.$refs.wTable.getList()
    },
    onEdit(row) {
      this.$refs.smsReportAddDialog.open(row)
    },
    onAdd() {
      this.$refs.smsReportAddDialog.open({})
    }
  }
}
</script>
