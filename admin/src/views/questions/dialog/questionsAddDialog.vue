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
  name: 'QuestionsAddDialog',
  data() {
    return {
      module: 'questions',
      formAction: '',
      form: {},
      readonly: false
    }
  },
  methods: {
    setForm({ id, questionnaire_id, question_text, question_type, status }) {
      this.form = {
        questionnaire_id: {
          label: '问卷id',
          value: questionnaire_id,
          formType: 'select',
          options: '/questionnaires/select'
        },
        question_text: { label: '问题描述', value: question_text },
        // question_type: {
        //   label: '问题类型',
        //   value: question_type,
        //   formType: 'select',
        //   options: { url: '/questions/create', key: 'questionType' }
        // },
        // status: { label: '状态', value: status, formType: 'status' },
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
