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
    <GameAccountAddDialog ref="gameAccountAddDialog" @done="getList" />
  </div>
</template>

<script>
import GameAccountAddDialog from './dialog/gameAccountAddDialog'

export default {
  name: 'GameAccountIndex',
  components: { GameAccountAddDialog },
  data() {
    return {
      module: 'gameAccount',
      operates: {
        del: true,
        look: false,
        add: true,
        edit: true,
        multiDel: true,
      },
    }
  },
  methods: {
    getList() {
      this.$store.dispatch('cleanColumnOptions', this.module)
      this.$refs.wTable.getList()
    },
    onEdit(row) {
      this.$refs.gameAccountAddDialog.open(row)
    },
    onAdd() {
      this.$refs.gameAccountAddDialog.open({})
    }
  }
}
</script>
