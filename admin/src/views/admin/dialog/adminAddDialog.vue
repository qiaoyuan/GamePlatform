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
  name: 'AdminAddDialog',
  data() {
    return {
      module: 'admin',
      formAction: '',
      form: {},
      readonly: false
    }
  },
  methods: {
    setForm({ id, reg_admin_id, reg_ip, reg_at, status, password, last_login_ip, last_login_at, username, nickname, phone, admin_department_id, is_leader }) {
      this.form = {
        id: { show: false, value: id },
        nickname: { label: '姓名', value: nickname },
        username: { label: '账号', value: username },
        phone: { label: '电话', value: phone },
        admin_department_id: {
          label: '部门',
          value: admin_department_id,
          formType: 'treeRadioFree',
          options: '/adminDepartment/selectTree',
        },
        password: { label: '密码', value: password, required: false, tooltip: '不修改密码请留空' },
        status: { label: '状态', value: status, formType: 'status' }
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
