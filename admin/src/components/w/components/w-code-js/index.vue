<template>
  <codemirror v-model="inputValue" v-bind="{ ...$attrs, ...$props }" v-on="$listeners"></codemirror>
</template>

<script>
import { codemirror } from 'vue-codemirror-lite'
require('codemirror/mode/javascript/javascript')
export default {
  name: 'wCodeJs',
  components: { codemirror },
  props: {
    modelValue: { type: String, default: '' },
    maxlength: { type: Number },
  },
  model: { prop: 'modelValue', event: 'update:modelValue' },
  computed: {
    inputValue: {
      get() {
        return this.modelValue || ''
      },
      set(v) {
        this.$emit('update:modelValue', v)
      }
    },
  },
  methods: {
  }
}
</script>

<style lang="less" scoped>

</style>
