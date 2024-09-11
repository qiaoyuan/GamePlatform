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
  name: 'UserChannelAddDialog',
  data() {
    return {
      module: 'userChannel',
      formAction: '',
      form: {},
      readonly: false
    }
  },
  methods: {
    setForm({ id, title, img_url, remark, platform}) {
      this.form = {
        title: { label: '渠道名称', value: title },
        platform: {
          label: '平台',
          value: platform,
          formType: 'select',
          options: [
            {
              label: '微信小程序',
              value: 1
            },{
              label: '抖音小程序',
              value: 2
            }
          ]
        },
        remark: {label: '备注', value: remark, required: false, formType: 'textarea' }
        // img_url: { label: '渠道名称', value: img_url, formType:'upload' },
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
