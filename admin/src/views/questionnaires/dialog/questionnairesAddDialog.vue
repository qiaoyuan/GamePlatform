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
    setForm({ id, title, description, status, img_url, article_category_id, easy, exact, utility, sort,price,content }) {
      this.form = {
        title: { label: '问卷名称', value: title },
        description: { label: '问卷简述', value: description, required: false },
        img_url: { label: '图片', value: img_url, formType:'upload'},
        article_category_id: {
          label: '类型',
          value: article_category_id,
          formType: 'select',
          options: '/articleCategory/select'
        },
        price: { width: '50%', label: '价格', value: price, formType: 'number'},
        easy: {  width: '50%', label: '题目易懂', value: easy },
        exact: {   width: '50%', label: '结果准确性', value: exact },
        utility: {  width: '50%', label: '建议实用性', value: utility },
        sort: { label: '排序', value: sort},
        content: { label: '内容', value: content, formType: 'editor'},
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
