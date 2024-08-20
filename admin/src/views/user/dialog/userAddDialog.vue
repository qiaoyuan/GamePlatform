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
  name: 'UserAddDialog',
  data() {
    return {
      module: 'user',
      formAction: '',
      form: {},
      readonly: false
    }
  },
  methods: {
    setForm({ id, status, password, last_login_ip, last_login_at, username, nickname, phone, channel_id, open_id }) {
      this.form = {
        status: { label: '', value: status, formType: 'status' },
        last_login_ip: { label: '', value: last_login_ip },
        last_login_at: { label: '', value: last_login_at },
        username: { label: '账号', value: username },
        nickname: { label: '名称', value: nickname },
        phone: { label: '电话', value: phone },
        channel_id: {
          label: '渠道id',
          value: channel_id,
        },
        open_id: {
          label: '对于微信商家唯一标',
          value: open_id,
        },
      }
      // if (id) {
      //   this.form.id = { show: false, value: id }
      //   this.formAction = `${this.module}/edit`
      // } else {
      //   this.formAction = `${this.module}/add`
      // }
      this.$refs.wDialogForm.visible = false
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
