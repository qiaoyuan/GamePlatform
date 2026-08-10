<template>
  <w-dialog
    v-model="visible"
    title="绑定产品"
    :width="560"
    @confirm="onConfirm"
    @cancel="visible = false"
  >
    <div class="mb10 tip">
      为策略「{{ strategy.name }}」绑定产品；一个产品同时只能归属一套策略，选中的产品若已属于其它策略会被移动到本策略。
    </div>
    <el-select
      v-model="selected"
      multiple
      filterable
      remote
      reserve-keyword
      :remote-method="remoteSearch"
      :loading="searching"
      placeholder="输入产品名称/ID搜索并选择"
      style="width: 100%"
    >
      <el-option v-for="o in options" :key="o.value" :label="o.label" :value="o.value" />
    </el-select>
    <div class="mt10 tip">已选 {{ selected.length }} 个产品</div>
  </w-dialog>
</template>

<script>
import { post } from '@/libs/request'

export default {
  name: 'PriceStrategyProductDialog',
  data() {
    return {
      visible: false,
      loading: false,
      searching: false,
      strategy: {},
      selected: [],
      options: [],
    }
  },
  methods: {
    async open(row) {
      this.strategy = row || {}
      this.selected = []
      this.options = []
      await this.loadBound()
      this.visible = true
    },
    // 载入已绑定产品作为初始选中项与初始选项（保证已选项能显示名称）
    async loadBound() {
      try {
        const res = await post('priceStrategy/boundProducts', { id: this.strategy.id }, {}, false, false)
        const list = res.data.list || []
        this.options = list.slice()
        this.selected = list.map(o => o.value)
      } catch (_) {
        this.options = []
        this.selected = []
      }
    },
    async remoteSearch(k) {
      this.searching = true
      try {
        const res = await post('gameProduct/select', { k }, {}, false, false)
        const list = res.data.list || []
        // 合并已选项，避免搜索后已选项标签丢失
        const map = {}
        ;[...this.options, ...list].forEach(o => { map[o.value] = o })
        this.options = Object.values(map)
      } catch (_) {
        // ignore
      } finally {
        this.searching = false
      }
    },
    onConfirm() {
      if (this.loading) return
      this.loading = true
      post('priceStrategy/bindProducts', { id: this.strategy.id, product_ids: this.selected }, {}, false, true)
        .then(() => {
          this.visible = false
          this.$emit('done')
        })
        .catch(() => {})
        .finally(() => { this.loading = false })
    },
  },
}
</script>

<style lang="less" scoped>
.tip {
  font-size: 12px;
  color: #909399;
}
</style>
