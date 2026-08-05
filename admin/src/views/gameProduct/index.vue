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
    <GameProductAddDialog ref="gameProductAddDialog" @done="getList" />
    <GameProductPriceDialog ref="gameProductPriceDialog" @done="getList" />
  </div>
</template>

<script>
import GameProductAddDialog from './dialog/gameProductAddDialog'
import GameProductPriceDialog from './dialog/gameProductPriceDialog'

export default {
  name: 'GameProductIndex',
  components: { GameProductAddDialog, GameProductPriceDialog },
  data() {
    return {
      module: 'gameProduct',
      operates: {
        del: true,
        look: false,
        add: true,
        edit: true,
        multiDel: true,
        other: [
          {
            title: '改价',
            type: 'warning',
            p: 'gameProduct/updatePrice',
            click: row => this.$refs.gameProductPriceDialog.open(row),
          },
        ],
      },
    }
  },
  methods: {
    getList() {
      this.$store.dispatch('cleanColumnOptions', this.module)
      this.$refs.wTable.getList()
    },
    onEdit(row) {
      this.$refs.gameProductAddDialog.open(row)
    },
    onAdd() {
      this.$refs.gameProductAddDialog.open({})
    }
  }
}
</script>
