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
  name: 'QuestionnairesAddDialog',
  data() {
    return {
      module: 'questionnaires',
      formAction: '',
      form: {},
      readonly: false
    }
  },
  methods: {
    setForm({ id, title, description, status, img_url, article_category_id, easy, exact, utility, sort }) {
      this.form = {
        title: { label: '测试名称', value: title },
        description: { label: '测试描述', value: description, required: false },
        status: { label: '', value: status, formType: 'status' },
        img_url: { label: '图片', value: img_url, formType:'upload'},
        article_category_id: {
          label: '类型',
          value: article_category_id,
          formType: 'select',
          options: '/articleCategory/select'
        },
        easy: { label: '题目易懂', value: easy },
        exact: { label: '结果准确性', value: exact },
        utility: { label: '建议实用性', value: utility },
        sort: { label: '排序', value: sort},
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
