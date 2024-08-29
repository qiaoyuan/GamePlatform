<template>
  <view class="nav-bar-container">
    <slot>
      <i class="back iconfont icon-back"
        @click="onClickBack" />
    </slot>
	<text>{{ title }}</text>
    <slot>
      <view class="back"></view>
    </slot>
  </view>
</template>

<script>
export default {
  name: 'NavBar',
  props: {
    title: {
      type: String,
      default: '导航栏',
    },
  },

  data() {
    return {}
  },

  methods: {
    /**
     * 点击返回方法
     */
    onClickBack() {
      this.$emit('back')
    },
  },
}
</script>

<style lang="scss" scoped>
.nav-bar-container {
  display: flex;
  align-items: center;
  justify-content: space-between;
  height: 180rpx;
  vertical-align: bottom;

  .back {
    display: flex;
    align-items: center;
    height: 100%;
    padding: 0 20rpx;
    font-size: 32rpx;
    color: #000;
  }
}
</style>