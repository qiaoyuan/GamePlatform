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
  name: 'AdminSettingAddDialog',
  data() {
    return {
      module: 'adminSetting',
      formAction: '',
      form: {},
      readonly: false
    }
  },
  methods: {
    setForm({ id, module, title, sort, status }) {
      this.module = module
      this.form = {
        title: { label: '名称', value: title },
        sort: { label: '排序', value: sort, formType: 'number', required: false },
        status: { label: '状态', value: status, formType: 'status' },
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
