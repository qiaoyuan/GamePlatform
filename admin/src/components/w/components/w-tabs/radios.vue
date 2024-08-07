<template>
  <el-radio-group v-if="tabsList.length > 1 || (tabsList.length === 1 && tabsList[0].label !== '全部')" :value="currentTab" :size="size" @input="onInput">
    <el-radio-button v-for="(item, index) in tabsList" :key="index" :label="item.name">
      {{ item.label }}
      <span class="w-badge">
        <i v-if="item.total">{{ item.total }}</i>
      </span>
    </el-radio-button>
  </el-radio-group>
</template>

<script>
export default {
  props: {
    tabsList: { type: Array, default: () => [] },
    modelValue: { type: String, default: '' },
  },
  model: { prop: 'modelValue', event: 'update:modelValue' },
  computed: {
    size() {
      return this.$store.getters['size']
    },
    currentTab: {
      get() {
        return this.modelValue
      },
      set(v) {
        if (!v) return
        const currentTab = this.tabsList.find(({ name }) => name === v)
        if (currentTab === undefined) return
        if (currentTab.redirect) {
          this.$router.push(currentTab.redirect)
          return
        }
        this.$emit('update:modelValue', v)
      },
    },
  },
  watch: {
    '$route.path'(v) {
      this.setCurrentRadio(this.$route.query.wradio)
    },
    tabsList: {
      immediate: true,
      handler(v) {
        this.setCurrentRadio(this.$route.query.wradio)
      },
    },
  },
  methods: {
    setCurrentRadio(v) {
      // 无标签，不做任何处理
      if (this.tabsList.length === 0) return
      const currentItem =
        (v && this.tabsList.find(({ name, redirect }) => name === v && !redirect)) || undefined
      // radio 不存在或找不到，跳转至 默认标签 或 第一个非跳转标签
      if (!currentItem) {
        const { name } = this.tabsList.find(({ redirect }) => !redirect)
        this.$router.replace({ query: { ...this.$route.query, wradio: name } })
        return
      }
      this.currentTab = v
    },
    onInput(v) {
      const currentTab = this.tabsList.find(({ name }) => name === v)
      if (currentTab === undefined) return
      if (currentTab.redirect) {
        this.$router.push(currentTab.redirect)
        return
      }
      this.currentTab = v
      this.$router.push({
        query: {
          ...this.$route.query,
          wradio: currentTab.name,
          sort: undefined,
          page: undefined,
          filter: undefined,
        },
      })
    },
  },
}
</script>

<style lang="less" scoped>
/deep/ .el-radio-button__inner {
  .w-badge > i {
    background-color: #f46d6e;
  }
}
</style>
