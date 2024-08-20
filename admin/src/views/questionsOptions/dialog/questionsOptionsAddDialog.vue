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
  name: 'QuestionsOptionsAddDialog',
  data() {
    return {
      module: 'questionsOptions',
      formAction: '',
      form: {},
      readonly: false
    }
  },
  methods: {
    setForm({ id, question_id, title, score }) {
      this.form = {
        question_id: {
          label: '问题id',
          value: question_id,
          formType: 'select',
          options: '/questions/select'
        },
        title: { label: '选项名称', value: title },
        score: { label: '分数', value: score, formType: 'number' },
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
