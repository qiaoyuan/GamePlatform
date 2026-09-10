<template>
  <w-dialog
    v-model="visible"
    :title="mode === 'price' ? '批量修改绑定产品价格' : '批量修改绑定产品库存'"
    :width="460"
    :show-close="!loading"
    @cancel="onCancel"
  >
    <el-form ref="form" :model="form" :rules="rules" label-width="90px" size="mini">
      <el-form-item label="策略">
        <span>{{ strategy.name }}</span>
      </el-form-item>
      <el-form-item label="绑定产品">
        <span>{{ strategy.products_count || 0 }} 个</span>
      </el-form-item>
      <el-form-item v-if="mode === 'price'" label="新价格" prop="price">
        <el-input-number
          v-model="form.price"
          :precision="6"
          :min="0.000001"
          :step="0.0001"
          :disabled="loading"
          style="width: 100%"
        />
        <div class="tip">已自动带入该策略的最低价 {{ strategy.filter_price ?? '--' }}</div>
      </el-form-item>
      <el-form-item v-else label="新库存" prop="stock">
        <el-input-number
          v-model="form.stock"
          :precision="0"
          :min="1"
          :step="1000"
          :disabled="loading"
          style="width: 100%"
        />
        <div class="tip">已自动带入该策略第一个绑定产品的当前库存</div>
      </el-form-item>
      <div class="warning">
        {{ mode === 'price'
          ? '确认后将逐个更新该策略绑定的所有产品，ELD 产品会同步到线上平台。'
          : '确认后仅更新本地库存及 offer_data 库存，不会同步到线上平台。' }}
      </div>
    </el-form>

    <template #footer>
      <el-button type="primary" :size="size" :loading="loading" @click="onConfirm">
        {{ loading ? '批量处理中…' : '确认' }}
      </el-button>
      <el-button :size="size" :disabled="loading" @click="onCancel">取消</el-button>
    </template>
  </w-dialog>
</template>

<script>
import { post } from '@/libs/request'

export default {
  name: 'PriceStrategyBatchProductDialog',
  data() {
    return {
      visible: false,
      loading: false,
      mode: 'price',
      strategy: {},
      form: { price: undefined, stock: undefined },
      rules: {
        price: [{ required: true, message: '请输入新价格', trigger: 'change' }],
        stock: [{ required: true, message: '请输入新库存', trigger: 'change' }],
      },
    }
  },
  computed: {
    size() {
      return this.$store.getters['size']
    },
  },
  methods: {
    async open(row, mode) {
      this.strategy = row || {}
      this.mode = mode === 'stock' ? 'stock' : 'price'
      const minimumPrice = Number(this.strategy.filter_price)
      this.form = {
        price: Number.isFinite(minimumPrice) && minimumPrice > 0 ? minimumPrice : undefined,
        stock: undefined,
      }
      this.loading = false

      if (this.mode === 'stock') {
        try {
          const res = await post('priceStrategy/batchProductStock', {
            id: this.strategy.id,
            preview: 1,
          }, {}, false, false)
          const stock = Number(res.data?.stock)
          this.form.stock = Number.isInteger(stock) && stock > 0 ? stock : undefined
        } catch (_) {
          // 错误已由请求库统一提示；仍打开弹窗，允许手动填写库存。
        }
      }

      this.visible = true
      this.$nextTick(() => this.$refs.form && this.$refs.form.clearValidate())
    },
    onCancel() {
      if (this.loading) return
      this.visible = false
    },
    onConfirm() {
      this.$refs.form.validate(async valid => {
        if (!valid || this.loading) return
        this.loading = true
        const isPrice = this.mode === 'price'
        const endpoint = isPrice
          ? 'priceStrategy/batchProductPrice'
          : 'priceStrategy/batchProductStock'
        const data = { id: this.strategy.id }
        data[isPrice ? 'price' : 'stock'] = Number(isPrice ? this.form.price : this.form.stock)
        try {
          const res = await post(endpoint, data, { timeout: 120000 }, false, false)
          const stat = res.data || {}
          const message = `成功 ${stat.success || 0}，跳过 ${stat.skip || 0}，失败 ${stat.fail || 0}`
          if (stat.fail > 0) {
            this.$message.warning(message)
          } else {
            this.$message.success(message)
          }
          this.visible = false
          this.$emit('done')
        } catch (e) {
          this.$message.error(e?.message || '批量操作失败')
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
  margin-top: 4px;
  font-size: 12px;
  color: #909399;
}

.warning {
  font-size: 12px;
  line-height: 20px;
  color: #e6a23c;
}
</style>
