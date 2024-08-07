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
  name: 'AddressAddDialog',
  data() {
    return {
      module: 'address',
      formAction: '',
      form: {},
      readonly: false
    }
  },
  methods: {
    setForm({ id, name, code, pid, level, sort }) {
      this.form = {
        id: { show: false, value: id },
        pid: { show: false, value: pid },
        name: {
          label: '名称',
          value: name,
        },
        sort: {
          label: '排序',
          value: sort,
          formType: 'number',
          required: false,
          attrs: { min: 0 }
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
