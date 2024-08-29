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
  name: 'QuestionnairesOrderAddDialog',
  data() {
    return {
      module: 'questionnairesOrder',
      formAction: '',
      form: {},
      readonly: false
    }
  },
  methods: {
    setForm({ id, questionnaire_id, order_id, uid, status, input_data, price, pay_status, pay_extent }) {
      this.form = {
        questionnaire_id: {
          label: '问卷id',
          value: questionnaire_id,
          formType: 'select',
          options: '/questionnaires/select'
        },
        order_id: {
          label: '订单号',
          value: order_id,
          formType: 'input',
        },
        uid: { label: '用户', value: uid, formType: 'number' },
        price: { label: '问卷价格', value: price, formType: 'number' },
        pay_status: {
          label: '支付情况',
          value: pay_status,
          formType: 'select',
          options: [
            {
              label: '未支付',
              value: 0
            },{
              label: '已支付',
              value: 1
            }
          ]
        }
        // pay_status: { label: '0：未支付，1：支付', value: pay_status, formType: 'number' },
      }
      if (id) {
        this.form.id = { show: false, value: id }
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
