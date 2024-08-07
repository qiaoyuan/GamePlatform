<template>
  <codemirror v-model="inputValue" v-bind="{ ...$attrs, ...$props }" v-on="$listeners"></codemirror>
</template>

<script>
import { codemirror } from 'vue-codemirror-lite'
require('codemirror/mode/htmlmixed/htmlmixed')
export default {
  name: 'wCode',
  components: { codemirror },
  props: {
    modelValue: { type: String, default: '' },
    maxlength: { type: Number },
  },
  model: { prop: 'modelValue', event: 'update:modelValue' },
  computed: {
    inputValue: {
      get() {
        return this.modelValue
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

<style lang="less">
.vue-codemirror-wrap {
  border: 1px solid;
}
</style>
