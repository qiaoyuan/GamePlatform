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
    setForm({ id, name, url, category, status, game_product_id, game_product_name, version }) {
      this.form = {
        name: { label: '任务名称', value: name, rules: [{ required: true, message: '请输入任务名称', trigger: 'blur' }] },
        version: {
          label: id ? '数据版本（编辑不可修改）' : '数据版本',
          value: version ?? 0,
          readonly: Boolean(id),
          readonlyValue: String(version ?? 0),
          formType: 'number',
          attrs: { min: 0, step: 1 },
          rules: [{ required: true, type: 'number', min: 0, message: '请输入有效的数据版本', trigger: 'blur' }],
        },
        game_product_id: {
          label: '游戏产品',
          value: game_product_id || '',
          oldValueToShow: game_product_name,
          formType: 'select',
          options: '/gameProduct/select',
          attrs: { clearable: false },
          rules: [{ required: true, message: '请选择游戏产品', trigger: 'change' }],
        },
        url: { label: '目标链接', value: url, formType: 'textarea', rows: 3, rules: [{ required: true, message: '请输入目标链接', trigger: 'blur' }] },
        category: {
          label: '产品分类',
          value: category || '物品',
          formType: 'select',
          options: [
            { label: 'G2G物品', value: '物品' },
            { label: 'G2G游戏币', value: '游戏币' },
          ],
          rules: [{ required: true, message: '请选择产品分类', trigger: 'change' }],
        },
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
