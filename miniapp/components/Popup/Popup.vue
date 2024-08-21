<template>
  <view
    class="popup-container"
    :style="{ zIndex: zIndex }">
    <view
      class="popup-content-container"
      :class="{ 'display-popup': displayPopup }">
      <slot></slot>
    </view>
    <MaskLayer
      v-if="displayMask"
      :display="displayPopup"
      @clickMask="onClickMask" />
  </view>
</template>

<script>
export default {
  name: 'Popup',
  props: {
    display: {
      type: Boolean,
      default: false,
    },
    displayMask: {
      type: Boolean,
      default: true,
    },
    zIndex: {
      type: Number,
      default: 9999,
    },
    disableClickMask: {
      type: Boolean,
      default: false,
    },
  },

  watch: {
    display: {
      handler(isDisplay) {
        this.displayPopup = isDisplay
      },
    },
  },

  data() {
    return {
      displayPopup: this.display,
    }
  },

  methods: {
    /**
     * 点击弹出层遮罩方法
     */
    onClickMask() {
      if (this.disableClickMask) return
      this.$emit('clickMask')
    },
  },
}
</script>

<style lang="scss" scoped>
.popup-container {
  display: flex;
  flex-direction: column;
  width: 100%;

  .popup-content-container {
    position: fixed;
    bottom: -70%;
    z-index: 2;
    width: 100%;
    background-color: #fff;
    border-top-left-radius: 20rpx;
    border-top-right-radius: 20rpx;
    transition: all 0.2s;
  }

  .display-popup {
    bottom: 0;
  }
}
</style>
