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
    <UserChannelAddDialog ref="userChannelAddDialog" @done="getList" />
  </div>
</template>

<script>
import UserChannelAddDialog from './dialog/userChannelAddDialog'

export default {
  name: 'UserChannelIndex',
  components: { UserChannelAddDialog },
  data() {
    return {
      module: 'userChannel',
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
      this.$refs.userChannelAddDialog.open(row)
    },
    onAdd() {
      this.$refs.userChannelAddDialog.open({})
    }
  }
}
</script>
