<template>
  <div class="app-container">
    <w-tabs-table
      ref="wTable"
      :module="module"
      :operates="operates"
      :actions="{ sort: canSort }"
      :query="{ _strategy_list_version: 4 }"
      sort-visible-rows
      sort-handle=".table-index-sort div, .strategy-sort-handle"
      @getColumns="placePriceColumn"
      @add="onAdd"
      @edit="onEdit"
    >
      <template #headerOperate>
        <el-tooltip content="拖动策略名称或左侧序号自动保存顺序；按列排序时请先取消列排序。价格取该策略最近一条成功日志的改价后价格，多产品策略也仅显示最近一条。">
          <span
            class="mr20"
            style="color: #909399; font-size: 12px"
          >拖动策略名称排序 · 价格仅供查看</span>
        </el-tooltip>
      </template>
      <template #nameSlot="{ row }">
        <span
          class="strategy-sort-handle"
          :style="{ cursor: canSort ? 'move' : 'default' }"
        >
          <i
            v-if="canSort"
            class="el-icon-rank"
          /> {{ row.name }}
        </span>
      </template>
      <template #msgSlot="{ row }">
        <div class="competitor-preview">
          <template v-if="row.msg_lines && row.msg_lines.length">
            <div
              v-for="line in row.msg_lines"
              :key="line.id"
              class="competitor-preview-line"
              :class="{ 'is-below-minimum': line.below_minimum }"
              :title="line.text"
            >
              <strong class="competitor-preview-price">{{ line.price }}</strong>
              <span class="competitor-preview-currency">{{ line.currency }}</span>
              <span class="competitor-preview-shop">{{ line.shop }}</span>
            </div>
          </template>
          <span v-else>{{ row.msg || '暂无符合条件的竞品' }}</span>
        </div>
      </template>
      <template #multiOperate="{ selection }">
        <el-button
          v-if="selection.length"
          v-p="'priceStrategy/batchPrice'"
          type="primary"
          size="mini"
          @click="onBatchPrice(selection)"
        >
          批量设置策略最低价
        </el-button>
      </template>
    </w-tabs-table>
    <PriceStrategyFormDialog
      ref="formDialog"
      @done="getList"
    />
    <PriceStrategyProductDialog
      ref="productDialog"
      @done="getList"
    />
    <PriceStrategyPriceDialog
      ref="priceDialog"
      @done="getList"
    />
    <PriceStrategyBatchProductDialog
      ref="batchProductDialog"
      @done="getList"
    />
  </div>
</template>

<script>
import { hasPermission } from '@/directive/w/directive/p'
import PriceStrategyFormDialog from './dialog/priceStrategyFormDialog'
import PriceStrategyProductDialog from './dialog/priceStrategyProductDialog'
import PriceStrategyPriceDialog from './dialog/priceStrategyPriceDialog'
import PriceStrategyBatchProductDialog from './dialog/priceStrategyBatchProductDialog'

export default {
  name: 'PriceStrategyIndex',
  components: {
    PriceStrategyFormDialog,
    PriceStrategyProductDialog,
    PriceStrategyPriceDialog,
    PriceStrategyBatchProductDialog
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
            click: row => this.$refs.productDialog.open(row)
          },
          {
            title: '执行',
            type: 'warning',
            p: 'priceStrategy/execute',
            click: row => this.doExecute(row)
          },
          {
            title: '批量改库存',
            type: 'primary',
            p: 'priceStrategy/batchProductStock',
            click: row => this.$refs.batchProductDialog.open(row, 'stock')
          }
        ]
      }
    }
  },
  computed: {
    canSort() {
      return hasPermission('priceStrategy/sort')
    }
  },
  methods: {
    placePriceColumn({ list }) {
      // 已保存过列布局的后台也将新增价格列放到最低价旁边。
      for (const [field, previous] of [['last_change_price', 'filter_price'], ['msg', 'last_change_price']]) {
        const index = list.findIndex(column => column.v === field)
        if (index < 0) continue
        const [column] = list.splice(index, 1)
        const previousIndex = list.findIndex(item => item.v === previous)
        list.splice(previousIndex < 0 ? list.length : previousIndex + 1, 0, column)
      }
    },
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
        background: 'rgba(0, 0, 0, 0.3)'
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
    }
  }
}
</script>

<style scoped>
.competitor-preview {
  font-size: 11px;
  line-height: 14px;
  text-align: left;
  font-variant-numeric: tabular-nums;
}
.competitor-preview-line {
  display: flex;
  align-items: baseline;
  gap: 5px;
  height: 14px;
  white-space: nowrap;
}
.competitor-preview-price {
  flex-shrink: 0;
  font-weight: 600;
}
.competitor-preview-currency {
  flex-shrink: 0;
  opacity: 0.65;
  font-size: 10px;
}
.competitor-preview-shop {
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
}
.competitor-preview-line.is-below-minimum {
  color: #f56c6c;
}
.app-container /deep/ td.competitor-preview-cell {
  padding-top: 2px !important;
  padding-bottom: 2px !important;
}
</style>
