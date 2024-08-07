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
  name: 'SmsReportAddDialog',
  data() {
    return {
      module: 'smsReport',
      formAction: '',
      form: {},
      readonly: false
    }
  },
  methods: {
    setForm({ id, phone, id_code, msg, user_id, status, ip, return_data, check_count, type }) {
      this.form = {
        phone: { label: '手机', value: phone },
        id_code: { label: '验证码', value: id_code },
        msg: { label: '短信内容', value: msg },
        user_id: {
          label: '发送人',
          value: user_id,
          formType: 'select',
          options: '/user/select'
        },
        status: { label: '发送状态', value: status, formType: 'status' },
        ip: { label: '发送IP', value: ip },
        return_data: { label: '返回详情', value: return_data },
        check_count: { label: '验证次数', value: check_count, formType: 'number' },
        type: { label: '短信类型', value: type, formType: 'number' },
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
