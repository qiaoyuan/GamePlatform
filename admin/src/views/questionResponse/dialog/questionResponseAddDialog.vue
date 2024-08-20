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
  name: 'QuestionResponseAddDialog',
  data() {
    return {
      module: 'questionResponse',
      formAction: '',
      form: {},
      readonly: false
    }
  },
  methods: {
    setForm({ id, lt, start, questionnaire_id, text }) {
      this.form = {
        start: { label: '起始值', value: start, formType: 'number' },
        lt: { label: '小于等于', value: lt, formType: 'number' },
        questionnaire_id: {
          label: '问卷ID',
          value: questionnaire_id,
          formType: 'select',
          options: '/questionnaires/select'
        },
        text: { label: '配置内容', value: text, formType: 'textarea' },
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
