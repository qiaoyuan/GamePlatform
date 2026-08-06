<template>
  <w-dialog-form
    ref="wDialogForm"
    title="添加/编辑"
    :form="form"
    :action="formAction"
    :width="$w_fun.isMobile() ? '100%' : '60%'"
    @done="v => $emit('done', v)"
  />
</template>

<script>
export default {
  name: 'GameProductAddDialog',
  data() {
    return {
      module: 'gameProduct',
      formAction: '',
      form: {},
      readonly: false
    }
  },
  methods: {
    setForm({ id, game_account_id, product_id, title, platform, price, stock, currency, sold_count, sales_amount, status }) {
      this.form = {
        game_account_id: {
          label: '关联账号',
          value: game_account_id,
          formType: 'select',
          options: '/gameAccount/select'
        },
        product_id: { label: '产品ID', value: product_id },
        title: { label: '产品名称', value: title },
        platform: {
          label: '平台',
          value: platform || 1,
          formType: 'select',
          options: [{ label: 'G2G', value: 1 }],
        },
        // 新增时价格可填写初始值；编辑已有产品时价格只读，修改须用列表「改价」按钮（会同步 G2G 平台）
        price: id
          ? { label: '价格（不可编辑，请用改价按钮）', value: price, formType: 'number', readonly: true }
          : { label: '价格', value: price, formType: 'number' },
        stock: { label: '库存', value: stock, formType: 'number' },
        currency: { label: '货币', value: currency || 'USD' },
        status: { label: '状态', value: status ?? 1, formType: 'status' },
      }
      if (id) {
        this.form.id = { show: false, value: id }
        // 已出售数/销售金额为统计字段，编辑时展示但不可修改
        this.form.sold_count = { label: '已出售数', value: sold_count, formType: 'number', readonly: true }
        this.form.sales_amount = { label: '销售金额', value: sales_amount, formType: 'number', readonly: true }
        this.formAction = `${this.module}/edit`
      } else {
        this.formAction = `${this.module}/add`
      }
      this.$refs.wDialogForm.visible = true
    },
    open(data, readonly = false) {
      this.setForm(data)
      this.readonly = readonly
    },
    getForm() {
      return this.$refs.wDialogForm.$refs.form
    }
  }
}
</script>
