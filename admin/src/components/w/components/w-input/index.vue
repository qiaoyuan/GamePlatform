<template>
  <el-input
    ref="input"
    v-bind="{ ...$attrs, ...$props }"
    v-on="$listeners"
    v-model="inputValue"
    :size="$attrs.size || wSize"
    :showWordLimit="showWordLimit_"
    :maxlength="maxlength_"
    :autosize="autosize_"
    :class="{ 'limit-input': isLimitInput }"
  >
    <template v-if="prepend && !$slots.prepend" #prepend>
      {{ prepend }}
    </template>
    <template v-if="append && !$slots.append" #append>
      {{ append }}
    </template>
    <template v-for="(_slot, slotName) in $slots" #[slotName]>
      <slot :name="slotName" />
    </template>
  </el-input>
</template>

<script>
export default {
  name: 'wInput',
  props: {
    modelValue: { type: [String, Number], default: '' },
    type: { type: String, default: 'text' },
    // 输入框前面的插槽
    prepend: { type: String, default: '' },
    // 输入框后面的插槽
    append: { type: String, default: '' },
    showWordLimit: { type: Boolean, default: true },
    maxlength: { type: Number },
    autosize: { type: [Object, Boolean], default: true }
  },
  model: { prop: 'modelValue', event: 'update:modelValue' },
  computed: {
    wSize() {
      return this.$store.getters['size']
    },
    inputValue: {
      get() {
        return this.modelValue
      },
      set(v) {
        this.$emit('update:modelValue', v)
      }
    },
    showWordLimit_() {
      return this.showWordLimit && this.maxlength > 0 && ['text', 'textarea'].includes(this.type)
    },
    maxlength_() {
      return this.showWordLimit ? this.maxlength : undefined
    },
    autosize_() {
      switch (this.autosize) {
        case false:
          return false
        case true:
          return this.computedRows(this.maxlength_)
        default:
          return this.autosize
      }
    },
    isLimitInput() {
      return this.showWordLimit_ && this.maxlength_ > 0 && this.type === 'text'
    }
  },
  methods: {
    computedRows(maxlength) {
      if (!(maxlength > 0)) return { minRows: 6 /* , maxRows: 12 */ }
      const base = Math.ceil(maxlength / 100)
      if (base === 1) return { minRows: 3 /* , maxRows: 6 */ }
      if (base <= 5) return { minRows: base * 2 /* , maxRows: base * 3 */ }
      return { minRows: 10 /* , maxRows: 16 */ }
    }
  }
}
</script>

<style lang="less" scoped>
.limit-input {
  /deep/ .el-input__inner {
    padding-right: 50px !important;
  }
}
/deep/ .el-textarea {
  position: relative;
}
/deep/ .el-input__count {
  background: none !important;
  height: 1.5rem;
  // position: absolute;
  // bottom: -2px;
  // right: 8px;
}

/deep/ .el-textarea__inner {
  &::-webkit-scrollbar {
    width: 5px;
    height: 5px;
  }

  &::-webkit-scrollbar-thumb {
    border-radius: 5px;
    background: #666666;
  }

  &::-webkit-scrollbar-track {
    border-radius: 5px;
    background: #dedede;
  }
}

/deep/ .el-input-group__append {
  // background-color: rgba(255, 255, 255, 0);
  padding: 0 10px;
}
</style>
