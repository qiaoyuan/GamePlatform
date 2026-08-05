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
  name: 'GameAccountAddDialog',
  data() {
    return {
      module: 'gameAccount',
      formAction: '',
      form: {},
      readonly: false
    }
  },
  methods: {
    setForm({ id, user_id, account_name, platform, active_device_token, long_lived_token, refresh_token, status }) {
      this.form = {
        user_id: { label: '用户ID', value: user_id },
        account_name: { label: '账号名称', value: account_name, required: false },
        platform: {
          label: '平台',
          value: platform || 1,
          formType: 'select',
          options: [{ label: 'G2G', value: 1 }],
        },
        active_device_token: { label: '设备活跃令牌', value: active_device_token, required: false },
        long_lived_token: { label: '长期访问令牌', value: long_lived_token, required: false },
        refresh_token: { label: '刷新令牌', value: refresh_token, required: false },
        status: { label: '状态', value: status ?? 1, formType: 'status' },
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
