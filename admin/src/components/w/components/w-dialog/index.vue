<template>
  <el-dialog
    v-bind="{ ...$props, ...$attrs }"
    v-on="$listeners"
    :visible.sync="visible"
    :width="width_"
    top="0"
  >
    <template #title>
      <slot name="title">
        <div class="f16">{{ title }}</div>
      </slot>
    </template>

    <div ref="dialogContent" class="y-dialog-content p20" @scroll="handleScroll">
      <slot />
    </div>

    <div class="footer">
      <slot name="footer">
        <el-button type="primary" :size="size" @click="confirm">确认</el-button>
        <el-button :size="size" @click="cancel">取消</el-button>
      </slot>
    </div>
  </el-dialog>
</template>
<script>
export default {
  props: {
    modelValue: { type: Boolean, default: false },
    title: { type: String, default: '' },
    width: { type: [String, Number], default: 500 },
    modalAppendToBody: { type: Boolean, default: true },
    appendToBody: { type: Boolean, default: true },
    closeOnClickModal: { type: Boolean, default: false },
    closeOnPressEscape: { type: Boolean, default: false },
  },
  model: { prop: 'modelValue', event: 'update:modelValue' },
  computed: {
    size() {
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
    width_() {
      return typeof this.width === 'number' ? `${this.width}px` : this.width
    },
  },
  methods: {
    cancel() {
      this.visible = false
      this.$emit('cancel')
    },
    confirm() {
      this.$emit('confirm')
    },
    handleScroll() {
      const dialogContent = this.$refs.dialogContent
      this.$emit('onscroll', dialogContent)
      let scrollTop = dialogContent.scrollTop
      let scrollHeight = dialogContent.scrollHeight
      let height = dialogContent.getBoundingClientRect().height
      if(Math.ceil(scrollTop + height) >= scrollHeight){
        this.$emit('onReachBottom', dialogContent)
      }
    },
  },
}
</script>

<style lang="less" scoped>
/deep/ .el-dialog {
  position: absolute;
  left: 50%;
  top: 50%;
  transform: translate(-50%, -50%);
  display: flex;
  flex-direction: column;
  overflow: hidden;
  max-width: 98vw !important;
  max-height: 98vh !important;

  .el-dialog__header {
    z-index: 1;
    padding: 10px 20px;
    border-bottom: 1px solid #f1f1f1;
    box-shadow: 0 0 3px #f1f1f1;
  }
  .el-dialog__headerbtn {
    top: 10px;
  }
  .el-dialog__body {
    padding: 0;
    flex: 1;
    display: flex;
    flex-direction: column;
    overflow: hidden;

    .y-dialog-content {
      flex: 1;
      overflow: auto;
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

    .footer {
      z-index: 1;
      padding: 10px 20px;
      text-align: center;
      border-top: 1px solid #f1f1f1;
      box-shadow: 0 0 3px #f1f1f1;
    }
  }
}
</style>
