<template>
  <w-dialog
    v-model="visible"
    title="修改价格"
    :width="400"
    :show-close="!loading"
    @cancel="onCancel"
  >
    <el-form ref="form" :model="form" :rules="rules" label-width="80px">
      <el-form-item label="产品名称">
        <span>{{ row.title }}</span>
      </el-form-item>
      <el-form-item label="当前价格">
        <span>{{ row.price }} {{ row.currency }}</span>
      </el-form-item>
      <el-form-item label="新价格" prop="price">
        <el-input-number
          v-model="form.price"
          :precision="6"
          :min="0.000001"
          :step="0.0001"
          :disabled="loading"
          style="width: 100%"
        />
        <div style="font-size:12px;color:#909399">支持最多 6 位小数，如 0.00063</div>
      </el-form-item>
    </el-form>

    <!-- 自定义 footer：改价要调平台接口，慢的时候 1-2 秒，
         期间按钮进入 loading（element-ui 的 loading 按钮本身不可点击），
         同时禁用取消、隐藏右上角关闭，避免重复提交或中途关闭弹窗 -->
    <template #footer>
      <el-button
        type="primary"
        :size="size"
        :loading="loading"
        @click="onConfirm"
      >
        {{ loading ? '改价中…' : '确认' }}
      </el-button>
      <el-button :size="size" :disabled="loading" @click="onCancel">取消</el-button>
    </template>
  </w-dialog>
</template>

<script>
import { post } from '@/libs/request'

export default {
  name: 'GameProductPriceDialog',
  data() {
    return {
      visible: false,
      loading: false,
      row: {},
      form: { id: '', price: 0 },
      rules: {
        price: [{ required: true, message: '请输入新价格', trigger: 'blur' }],
      },
    }
  },
  computed: {
    // 与 w-dialog 内部按钮保持同一尺寸
    size() {
      return this.$store.getters['size']
    },
  },
  methods: {
    open(row) {
      this.row = row
      this.form = { id: row.id, price: row.price }
      // 重置，避免上次异常退出残留 loading 导致按钮一直转
      this.loading = false
      this.visible = true
    },
    onCancel() {
      // 请求还在飞就不允许关闭，防止用户以为取消了、实际平台已改价
      if (this.loading) return
      this.visible = false
    },
    onConfirm() {
      this.$refs.form.validate(async valid => {
        if (!valid || this.loading) return
        this.loading = true
        try {
          await post('gameProduct/updatePrice', this.form)
          this.visible = false
          this.$emit('done')
        } finally {
          this.loading = false
        }
      })
    },
  },
}
</script>
