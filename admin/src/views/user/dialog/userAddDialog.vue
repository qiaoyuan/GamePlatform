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
    setForm({ id, nickname, avatar, phone, password, point, total_point, amount, frozen_amount, frozen_type, invite_by_id, is_id_card_verify, status }) {
      this.form = {
        nickname: { label: '昵称', value: nickname },
        avatar: { label: '头像', value: [{ path: avatar, url: avatar }], formType: 'upload' },
        phone: { label: '电话', value: phone, pattern: 'phone' },
        password: { label: '密码', value: password, required: false, tooltip: '不修改密码请留空' },
        status: { label: '状态', value: status, formType: 'status' },
        is_id_card_verify: { label: '是否实名认证', value: is_id_card_verify, formType: 'boolean' },
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
