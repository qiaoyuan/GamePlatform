<template>
  <w-dialog
    v-model="visible"
    title="价格设置"
    :width="430"
    @confirm="onConfirm"
    @cancel="visible = false"
  >
    <el-form ref="form" :model="form" :rules="rules" label-width="90px" size="mini">
      <el-form-item label="最低价" prop="filter_price">
        <el-input-number
          v-model="form.filter_price"
          :min="0"
          :precision="6"
          :step="0.0001"
          placeholder="请输入最低价"
          style="width: 100%"
        />
      </el-form-item>
      <el-checkbox v-model="updateAll" :disabled="ids.length <= 1">
        更新所有选定的列表
      </el-checkbox>
      <div class="tip">
        已选择 {{ ids.length }} 个策略；价格小于等于此值的竞品不参与最低价计算。
      </div>
    </el-form>
  </w-dialog>
</template>

<script>
import { post } from '@/libs/request'

export default {
  name: 'PriceStrategyPriceDialog',
  data() {
    return {
      visible: false,
      loading: false,
      ids: [],
      updateAll: true,
      form: { filter_price: undefined },
      rules: {
        filter_price: [{ required: true, message: '请输入最低价', trigger: 'change' }],
      },
    }
  },
  methods: {
    open(rows) {
      this.ids = (rows || [])
        .map(row => Number(row.id))
        .filter(id => Number.isInteger(id) && id > 0)
      this.updateAll = true
      this.form = { filter_price: undefined }
      this.visible = this.ids.length > 0
      this.$nextTick(() => this.$refs.form && this.$refs.form.clearValidate())
    },
    onConfirm() {
      this.$refs.form.validate(async valid => {
        if (!valid || this.loading || !this.ids.length) return
        this.loading = true
        const ids = this.updateAll ? this.ids : this.ids.slice(0, 1)
        try {
          await post('priceStrategy/batchPrice', {
            ids,
            filter_price: Number(this.form.filter_price),
          }, {}, false, true)
          this.visible = false
          this.$emit('done')
        } catch (_) {
          // 错误已由请求库统一提示
        } finally {
          this.loading = false
        }
      })
    },
  },
}
</script>

<style lang="less" scoped>
.tip {
  margin-top: 10px;
  font-size: 12px;
  color: #909399;
}
</style>
