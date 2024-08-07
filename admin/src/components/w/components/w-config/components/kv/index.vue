<template>
  <w-form ref="wForm" :form="form" class="mt20" @done="v => $emit('done', v)"></w-form>
</template>

<script>
export default {
  name: 'kv',
  props: {
    config: { type: Object, default: {} },
  },
  watch: {
    'config.key': {
      handler(v) {
        if (v) {
          this.getDetail()
        }
      }
    }
  },
  data() {
    return {
      value: {},
      form: {},
    }
  },
  methods: {
    getValue() {
      return this.$refs.wForm.model
    },
    getDetail() {
      this.$w_fun.post('/setting/get', { key: this.config.key }).then(res => {
        this.value = res.data.detail && res.data.detail.value
        this.value = this.value || {}
        this.setForm()
      })
    },
    setForm() {
      const form = {}
      for (const item of this.config.options) {
        form[item.key] = {
          label: item.title,
          value: this.value[item.key] || '',
          attrs: { placeholder: '请输入值' },
          required: false
        }
      }
      this.form = form
    }
  }
}
</script>
