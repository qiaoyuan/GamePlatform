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
  name: 'AdminDepartmentAddDialog',
  data() {
    return {
      module: 'adminDepartment',
      formAction: '',
      form: {},
      readonly: false
    }
  },
  methods: {
    setForm({ id, title, description, parent_id, admin_id, level }) {
      this.form = {
        title: { label: '名称', value: title },
        description: { label: '描述', value: description, required: false },
        parent_id: {
          label: '上级部门',
          value: parent_id,
          formType: 'treeRadioFree',
          options: '/adminDepartment/selectTree',
          required: false
        },
        admin_id: {
          label: '部门负责人',
          value: admin_id,
          formType: 'select',
          options: '/admin/select'
        },
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
