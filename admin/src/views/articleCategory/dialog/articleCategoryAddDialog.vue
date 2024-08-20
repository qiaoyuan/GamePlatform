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
  name: 'ArticleCategoryAddDialog',
  data() {
    return {
      module: 'articleCategory',
      formAction: '',
      form: {},
      readonly: false
    }
  },
  methods: {
    setForm({ id, title, module, status, parent_id, sort, icon_url }) {
      this.form = {
        module: { show: false, value: module },
        icon_url: { label: 'icon', value: icon_url, formType: 'upload'},
        title: { label: '分类名称', value: title },
        status: { label: '状态', value: status, formType: 'status' },
        sort: { label: '排序', value: sort, formType: 'number', required: false },
      }
      if (id) {
        this.form.id = { show: false, value: id }
        this.formAction = `${this.module}/edit`
      } else {
        this.formAction = `${this.module}/add`
      }
      this.$refs.wDialogForm.visible = true
    }, open(data, readonly = false) {
      this.setForm(data)
      this.readonly = readonly
    },
    getForm() {
      return this.$refs.wDialogForm.$refs.form
    }
  }
}
</script>
