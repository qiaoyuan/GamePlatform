<template>
  <w-dialog
    v-model="visible"
    title="修改价格"
    :width="400"
    @confirm="onConfirm"
    @cancel="visible = false"
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
          :precision="2"
          :min="0.01"
          :step="0.1"
          style="width: 100%"
        />
      </el-form-item>
    </el-form>
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
  methods: {
    open(row) {
      this.row = row
      this.form = { id: row.id, price: row.price }
      this.visible = true
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
