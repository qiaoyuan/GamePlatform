<template>
  <div
    class="dp-f align-items-center border-E4E7ED-bottom w-tab-right-handle"
  >
    <!-- 全屏 -->
    <i
      v-show="false"
      title="全屏"
      class="el-icon-full-screen ml5 mr5 cursor"
      :class="[fontSize, full ? 'green' : '']"
      @click="onClick('full')"
    />
    <!-- 刷新 -->
    <i
      title="刷新"
      class="el-icon-refresh-right ml5 mr5 cursor"
      :class="[fontSize]"
      @click="onClick('refresh')"
    />
  </div>
</template>
<script>
export default {
  name: 'pageOperate',
  props: {
    currentTab: { type: String, default: '' },
  },
  computed: {
    size: {
      get() {
        return this.$store.getters['size']
      },
      set(val) {
        this.$store.dispatch('w/setSize', val)
      },
    },
    isMini() {
      return this.size === 'mini'
    },
    fontSize() {
      return this.isMini ? 'f16' : 'f18'
    },
    full: {
      get() {
        return this.$store.state.app.isTaskListFullScreen
      },
      set(v) {
        this.$store.commit('toggleFullScreenState', v)
      },
    },
  },
  data() {
    return {
      api: '',
      visible: false,
      isEdit: false,
      defaultInfo: '',
      info: '',
    }
  },
  methods: {
    // 按钮点击事件
    onClick(key) {
      switch (key) {
        case 'full':
          this.full = !this.full
          break
        case 'size':
          this.size = this.isMini ? 'small' : 'mini'
          break
      }
      this.$emit('refresh')
    }
  },
}
</script>
<style lang="less" scoped>
.w-tab-right-handle {
  height: 35px;
  padding-bottom: 5px;
  &.w-mini {
    height: 27px;
  }
}

.editor /deep/ .body {
  height: calc(100% - 40px) !important;
}
</style>
