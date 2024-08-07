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
    <UserAddDialog ref="userAddDialog" @done="getList" />
  </div>
</template>

<script>
import UserAddDialog from './dialog/userAddDialog'
import fa from "element-ui/src/locale/lang/fa";

export default {
  name: 'UserIndex',
  components: { UserAddDialog },
  data() {
    return {
      module: 'user',
      operates: {
        del: false,
        look: false,
        add: false,
        edit: true,
        multiDel: false,
        // recycle: { autoLink: true },
      }
    }
  },
  methods: {
    getList() {
      this.$refs.wTable.getList()
    },
    onEdit(row) {
      this.$refs.userAddDialog.open(row)
    },
    onAdd() {
      this.$refs.userAddDialog.open({})
    }
  }
}
</script>
