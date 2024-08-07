<template>
  <el-radio-group v-model="value" :size="size">
    <template v-if="type === 'button'">
      <el-radio-button
        v-for="(item, index) in data"
        :key="index"
        :label="item.value"
        :name="item.name"
        @click.native.prevent="onClick(item.value)"
      >
        {{ item.label }}
      </el-radio-button>
    </template>

    <template v-if="type === 'radio'">
      <el-radio
        v-for="(item, index) in data"
        :key="index"
        :label="item.value"
        :name="item.name"
        @click.native.prevent="onClick(item.value)"
      >
        {{ item.label }}
      </el-radio>
    </template>
  </el-radio-group>
</template>

<script>
export default {
  name: 'radioGroup',
  props: {
    modelValue: { type: String, default: '' },
    data: { type: Array, default: () => [] },
    disabled: { type: Boolean, default: false },
    isCancel: { type: Boolean, default: false },
    type: { type: String, default: 'button' }
  },
  model: { prop: 'modelValue', event: 'update:modelValue' },
  computed: {
    size() {
      return this.$store.getters['w/size']
    },
    value: {
      get() {
        return this.modelValue
      },
      set(v) {
        this.$emit('update:modelValue', v)
      },
    },
  },
  methods: {
    onClick(v) {
      if (this.disabled) return
      const result = this.modelValue === v && !this.isCancel ? '' : v
      this.value = result
      this.$emit('change', result)
    },
  },
}
</script>

<style lang="less" scoped>
// radio：
/deep/ .el-radio:focus:not(.is-focus):not(:active):not(.is-disabled) .el-radio__inner{
  box-shadow: none;
  -webkit-box-shadow: none;
}

// button：
/deep/ .el-radio-button {
  &:focus:not(.is-focus):not(:active):not(.is-disabled) {
    box-shadow: none;
    -webkit-box-shadow: none;
  }

  .el-radio-button__inner {
    background-color: transparent;
    padding: 4px 8px;
    color: #606266;
    border-color: #dcdfe6;
    -webkit-box-shadow: none;
  }

  &.is-active {
    .el-radio-button__inner {
      background-color: #19aa8d;
      color: #fff;
      border-color: #19aa8d;
      -webkit-box-shadow: -1px 0 0 0 #19aa8d;
    }
  }

  &.is-disabled {
    .el-radio-button__inner {
      background-color: #fff;
      color: #c0c4cc;
      border-color: #ebeef5;
    }
  }

  &.is-disabled.is-active {
    .el-radio-button__inner {
      background-color: #f2f6fc;
      color: #c0c4cc;
      -webkit-box-shadow: none;
    }
  }
}
</style>
