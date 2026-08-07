<template>
  <w-dialog-form
    ref="wDialogForm"
    :module="module"
    :form="form"
    :action="formAction"
    :show-close="false"
    @done="onDone"
  />
</template>

<script>
export default {
  name: 'CrawlAddDialog',
  props: {
    module: {
      type: String,
      default: 'crawl',
    },
  },
  data() {
    return {
      form: {},
      formAction: '',
    }
  },
  methods: {
    open(row) {
      this.setForm(row)
    },
    setForm({ id, name, url, category, status }) {
      this.form = {
        name: { label: '任务名称', value: name, rules: [{ required: true, message: '请输入任务名称', trigger: 'blur' }] },
        url: { label: '目标链接', value: url, formType: 'textarea', rows: 3, rules: [{ required: true, message: '请输入目标链接', trigger: 'blur' }] },
        category: { label: '产品分类', value: category, rules: [{ required: true, message: '请输入产品分类', trigger: 'blur' }] },
        status: { label: '状态', value: status ?? 1, formType: 'status' },
      }
      if (id) {
        this.form.id = { show: false, value: id }
        this.formAction = `${this.module}/edit`
      } else {
        this.formAction = `${this.module}/add`
      }
      this.$refs.wDialogForm.visible = true
    },
    onDone() {
      this.$emit('done')
    },
  },
}
</script>
