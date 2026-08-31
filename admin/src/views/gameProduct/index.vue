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

// 平台枚举，与后端 GameAccount::PLATFORM_ELDORADO 保持一致
const PLATFORM_ELDORADO = 2

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
          {
            title: '同步线上数据',
            type: 'success',
            p: 'gameProduct/syncOffer',
            // 仅 Eldorado 平台有该接口，G2G 不显示这个按钮
            show: row => Number(row.platform) === PLATFORM_ELDORADO,
            click: row => this.syncOffer(row),
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
    },
    // 同步 ELD 线上平台数据（价格/库存/币种 + offer_data）
    async syncOffer(row) {
      const loading = this.$loading({
        lock: true,
        text: `正在同步 ${row.title || row.product_id}...`,
        spinner: 'el-icon-loading',
        background: 'rgba(0, 0, 0, 0.3)',
      })
      try {
        const res = await this.$w_fun.post(`${this.module}/syncOffer`, { id: row.id }, {}, false, false)
        loading.close()
        this.$message.success(res.message || '同步成功')
        this.getList()
      } catch (e) {
        loading.close()
        this.$message.error(e?.message || '同步失败')
      }
    },
  }
}
</script>
