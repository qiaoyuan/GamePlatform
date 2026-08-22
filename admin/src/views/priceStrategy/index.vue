<template>
  <div class="app-container">
    <w-tabs-table
      ref="wTable"
      :module="module"
      :operates="operates"
      @add="onAdd"
      @edit="onEdit"
    >
      <template #multiOperate="{ selection }">
        <el-button
          v-if="selection.length"
          v-p="'priceStrategy/batchPrice'"
          type="primary"
          size="mini"
          @click="onBatchPrice(selection)"
        >
          批量更新价格
        </el-button>
      </template>
    </w-tabs-table>
    <PriceStrategyFormDialog ref="formDialog" @done="getList" />
    <PriceStrategyProductDialog ref="productDialog" @done="getList" />
    <PriceStrategyPriceDialog ref="priceDialog" @done="getList" />
  </div>
</template>

<script>
import PriceStrategyFormDialog from './dialog/priceStrategyFormDialog'
import PriceStrategyProductDialog from './dialog/priceStrategyProductDialog'
import PriceStrategyPriceDialog from './dialog/priceStrategyPriceDialog'

export default {
  name: 'PriceStrategyIndex',
  components: {
    PriceStrategyFormDialog,
    PriceStrategyProductDialog,
    PriceStrategyPriceDialog,
  },
  data() {
    return {
      module: 'priceStrategy',
      operates: {
        del: true,
        look: false,
        add: true,
        edit: true,
        multiDel: true,
        other: [
          {
            title: '绑定产品',
            type: 'primary',
            p: 'priceStrategy/bindProducts',
            click: row => this.$refs.productDialog.open(row),
          },
          {
            title: '执行',
            type: 'warning',
            p: 'priceStrategy/execute',
            click: row => this.doExecute(row),
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
    onAdd() {
      this.$refs.formDialog.open({})
    },
    onEdit(row) {
      this.$refs.formDialog.open(row)
    },
    onBatchPrice(selection) {
      this.$refs.priceDialog.open(selection)
    },
    async doExecute(row) {
      const loading = this.$loading({
        lock: true,
        text: `正在执行策略 ${row.name}...`,
        spinner: 'el-icon-loading',
        background: 'rgba(0, 0, 0, 0.3)',
      })
      try {
        const res = await this.$w_fun.post(`${this.module}/execute`, { id: row.id }, {}, false, false)
        loading.close()
        const d = res.data || {}
        this.$message.success(`执行完成：成功 ${d.success || 0}，跳过 ${d.skip || 0}，失败 ${d.fail || 0}`)
        this.getList()
      } catch (e) {
        loading.close()
        this.$message.error(e?.message || '执行失败')
      }
    },
  },
}
</script>
