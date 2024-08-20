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
  name: 'QuestionAnswersAddDialog',
  data() {
    return {
      module: 'questionAnswers',
      formAction: '',
      form: {},
      readonly: false
    }
  },
  methods: {
    setForm({ id, question_id, answer_text, option_id, uid, questionnaire_id }) {
      this.form = {
        question_id: {
          label: '问题ID',
          value: question_id,
          formType: 'select',
          options: '/question/select'
        },
        answer_text: { label: '用户回答文案，选择题默认为空', value: answer_text },
        option_id: {
          label: '选项id',
          value: option_id,
          formType: 'select',
          options: '/questionsOptions/select'
        },
        uid: { label: '用户id', value: uid, formType: 'number' },
        questionnaire_id: {
          label: '选择问卷',
          value: questionnaire_id,
          formType: 'select',
          options: '/questionnaires/select'
        },
      }
      if (id) {
        this.form.id = { show: false, value: id }
        this.formAction = `${this.module}/edit`
      } else {
        this.formAction = `${this.module}/add`
      }
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
