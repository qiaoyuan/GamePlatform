<template>
  <el-drawer v-bind="{ ...$attrs, ...$props }" v-on="$listeners" :visible.sync="visible">
    <template #title>
      <slot name="title">
        <div class="f16">{{ title }}</div>
      </slot>
    </template>
    <slot />
    <div class="drawer-footer">
      <slot name="footer">
        <el-button :size="wSize" @click="toggleVisible(false)" :plain="true">关闭</el-button>
      </slot>
    </div>
  </el-drawer>
</template>

<script>
export default {
  name: 'wDrawer',
  inheritAttrs: false,
  props: {
    modelValue: { type: Boolean, default: false },
    title: { type: String, default: '' },
    size: { type: [String, Number], default: '500px' },
    appendToBody: { type: Boolean, default: true },
  },
  model: { prop: 'modelValue', event: 'update:modelValue' },
  computed: {
    wSize() {
      return this.$store.getters['size']
    },
    visible: {
      get() {
        return this.modelValue
      },
      set(v) {
        this.$emit('update:modelValue', v)
      },
    },
  },
  methods: {
    toggleVisible(status) {
      this.visible = status === undefined ? !this.visible : status
    },
  },
}
</script>
<style lang="less" scoped>
/deep/ .el-drawer__header {
  padding: 10px 20px;
  border-bottom: 1px solid #f1f1f1;
  margin-bottom: 0;
  box-shadow: 0 0 3px 1px #f1f1f1;
}

/deep/ .el-drawer__body {
  padding: 10px 20px;
  margin-bottom: 50px;
}

.drawer-footer {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  padding: 10px;
  text-align: center;
  border-top: 1px solid #f1f1f1;
  box-shadow: 0 0 3px 1px #f1f1f1;
  background-color: #fff;
}
</style>
