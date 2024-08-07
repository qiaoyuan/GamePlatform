<template>
  <!-- 这个傻逼组件，默认值会给一个 '0'，导致 component is 树形错误，并且每次切换会触发两次 set -->
  <!-- 所以此处不使用双向绑定，自己手动控制值得改变 -->
  <el-tabs
    v-if="tabsList.length > 1 || (tabsList.length === 1 && tabsList[0].label !== '全部')"
    :value="currentTab"
    :class="[size === 'mini' ? 'w-tabs-mini' : '', tabsList.length > 1 ? '' : 'prepareContainer']"
    @tab-click="onTabClick"
  >
    <el-tab-pane v-for="(item, index) in tabsList" :key="index" v-bind="item" />
  </el-tabs>
</template>

<script>
import { queryToObj, objToQuery } from '@/utils/w'

const sortQuery = (query = {}) => {
  const { tabIndex, wradio } = query
  delete query.tabIndex
  delete query.wradio
  return objToQuery({ tabIndex, wradio, ...query })
}
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
        this.$emit('update:modelValue', v)
        const [path, query = ''] = location.href.split('?')
        const queryObj = queryToObj(query)
        if (queryObj.wradio) return
        const currentItem = this.tabsList.find(({ name }) => name === v)
        if (!currentItem) return
        queryObj.tabIndex = currentItem.name
        queryObj.wradio = currentItem.wradio
        window.history.replaceState(null, null, `${path}${sortQuery(queryObj)}`)
      },
    },
  },
  actived() {
    this.enter()
  },
  mounted() {
    this.enter()
  },
  watch: {
    '$route.query.tabIndex'() {
      const [, query = ''] = location.href.split('?')
      const queryObj = queryToObj(query)
      if (queryObj.tabIndex) return (this.currentTab = queryObj.tabIndex)
      if (this.currentTab && this.tabsList.find(({ name }) => name === this.currentTab)) return
      if (this.tabsList.length === 0) return
      this.currentTab = this.tabsList[0].name
    },
  },
  methods: {
    enter() {
      if (this.tabsList.length === 0) return
      const [path, query = ''] = location.href.split('?')
      const queryObj = queryToObj(query)
      const currentItem =
        (queryObj.tabIndex && this.tabsList.find(({ name }) => name === queryObj.tabIndex)) ||
        (this.currentTab && this.tabsList.find(({ name }) => name === this.currentTab)) ||
        this.tabsList[0]
      this.currentTab = queryObj.tabIndex = currentItem.name
      !queryObj.wradio && (queryObj.wradio = currentItem.wradio)
      window.history.replaceState(null, null, `${path}${sortQuery(queryObj)}`)
    },
    onTabClick({ name, $attrs: { wradio } }) {
      const [path, query = ''] = location.href.split('?')
      const queryObj = queryToObj(query)
      this.currentTab = queryObj.tabIndex = name
      queryObj.wradio = wradio
      window.history.replaceState(null, null, `${path}${sortQuery(queryObj)}`)
      this.$emit('change', this.currentTab)
    },
  },
}
</script>

<style lang="less" scoped>
.prepareContainer /deep/ .el-tabs__active-bar {
  transform: translateX(0) !important;
}
</style>
