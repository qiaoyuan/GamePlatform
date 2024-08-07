<template>
  <div class="o-y-a" style="max-height: 300px">
    <div v-if="isTags && nodes.children.length" class="tags-box component-scrollbar">
      <el-tag
        v-for="(item, index) in nodes.children"
        :key="item.value"
        :title="item.label"
        :size="size"
        class="tag"
        :closable="true"
        @close="onClose(item.value)"
      >
        {{ isTags === true ? item.label : ellipsisStr(item.label, isTags) }}
      </el-tag>
    </div>
    <div v-if="isFilter_" class="input-box">
      <w-input v-model="filterText" :clearable="true" placeholder="请输入关键字过滤" />
      <el-button :size="size" type="text" class="clear-all" @click="onClear">
        <i class="el-icon-close" />
      </el-button>
    </div>
    <el-tree
      ref="tree"
      v-bind="{ ...$attrs, ...$props }"
      v-on="$listeners"
      class="component-scrollbar"
      :data="data_"
      :show-checkbox="true"
      :checkStrictly="checkStrictly"
      :filter-node-method="filterNodeMethod"
      @check="onCheck"
    >
      <template #default="{ node, data }">
        <div :title="node.label" :class="{ ellipsis: true, red: data.plain }">
          {{ node.label }}
        </div>
      </template>
    </el-tree>
  </div>
</template>

<script>
import { debounce } from 'lodash'
import { ellipsisStr } from '@/utils/w'
export default {
  name: 'wTree',
  inheritAttrs: false,
  props: {
    // 选择类型 tree 正常选择，所有 单选 半选 值均为有效值
    // treeRadio 单选，只保留最底级  treeRadioFree 单选，无层级关系
    // treeSelect 多选，只保留最底级  treeSelectFree 多选，无层级关系
    treeType: { type: String, default: 'tree' },
    // 用于支持 v-model
    modelValue: { type: Array, default: () => [] },
    // 是否开启关键词过滤，number 选项数量超过该数字则自动开启
    isFilter: { type: [Number, Boolean], default: 7 },
    // 是否显示已选中数据的标签
    isTags: { type: [Number, Boolean], default: 4 },
    // 是否显示一键清空
    clearable: { type: Boolean, default: true },
    // 禁用
    disabled: { type: Boolean, default: false },
    // 展开项，boolean true 全部展开， number 展开级数，array 展开项 key 数组
    expanded: { type: [Boolean, Number, Array], default: false },
    data: { type: Array, default: () => [] },
    nodeKey: { type: String, default: 'value' },
    checkOnClickNode: { type: Boolean, default: true },
    showCheckbox: { type: Boolean, default: true },
  },
  model: { prop: 'modelValue', event: 'update:modelValue' },
  computed: {
    size() {
      return this.$store.getters['size']
    },
    value: {
      get() {
        return this.modelValue
      },
      set(v) {
        this.$emit('update:modelValue', v)
      },
    },
    // 父子节点是否不关联
    checkStrictly() {
      return ['treeRadioFree', 'treeSelectFree'].includes(this.treeType)
    },
    data_() {
      return this.disabledData(this.data, this.disabled, this.treeType)
    },
    plainData() {
      return this.toPlain(this.data_)
    },
    isFilter_() {
      return typeof this.isFilter === 'boolean'
        ? this.isFilter
        : this.isFilter <= this.plainData.length
    },
  },
  data() {
    return {
      nodes: { parents: [], children: [] },
      filterText: '',
    }
  },
  watch: {
    filterText: debounce(function (v) {
      this.$refs.tree.filter(v)
    }, 300),
    modelValue: {
      immediate: true,
      handler() {
        this.setCheckedKeys_()
      },
    },
    data_: {
      immediate: true,
      handler() {
        this.setCheckedKeys_()
        // 等待 nodes 更新
        this.$nextTick(() => this.setExpandKeys())
      },
    },
  },
  methods: {
    ellipsisStr(str = '', length = 4) {
      return ellipsisStr(str, length)
    },
    // 选中
    setCheckedKeys(keys, leafOnly) {
      this.$refs.tree.setCheckedKeys(keys, leafOnly)
    },
    // 设置选中项
    setCheckedKeys_() {
      // 同步传输 data 时，总是要等待元素渲染
      this.$nextTick(() => {
        // 若 data 数据为远程请求，这时 data 数据还未传入
        if (!this.$refs.tree) return
        switch (this.treeType) {
          case 'tree':
          case 'treeRadio':
          case 'treeSelect':
            this.setCheckedKeys(
              this.value.filter(v => this.plainData.every(({ pid }) => v === 0 || pid !== v))
            )
            break
          case 'treeRadioFree':
          case 'treeSelectFree':
            this.setCheckedKeys(this.value)
            break
        }
        this.$nextTick(() => {
          this.nodes = this.getCheckedNodes_()
          this.$emit('nodeChange', this.nodes)
        })
      })
    },
    // 展开
    expandTree(keys = []) {
      keys = keys.map(i => `${i}`)
      const nodesMap = this.$refs.tree ? this.$refs.tree.store.nodesMap : {}
      Object.keys(nodesMap).forEach(i => (nodesMap[i].expanded = keys.includes(i)))
    },
    // 设置展开项
    setExpandKeys() {
      if (this.expanded === false) return this.expandTree()
      // 总是可能需要等待节点选中
      this.$nextTick(() => {
        let expandedKeys = []
        switch (typeof this.expanded) {
          case 'boolean':
            expandedKeys = this.plainData
              .filter(({ children = [] }) => children.length)
              .map(({ value }) => value)
            break
          case 'number':
            this.expanded > 0 &&
              (expandedKeys = this.plainData
                .filter(({ level }) => level <= this.expanded)
                .map(({ value }) => value))
            break
          case 'object':
            expandedKeys = this.expanded
            break
        }
        this.expandTree([...expandedKeys, ...this.nodeToKey(this.nodes.parents)])
      })
    },
    // 筛选
    filterNodeMethod(v, { label }) {
      return !v || `${label}`.includes(v)
    },
    // 获取已选中节点
    getCheckedNodes(leafOnly, includeHalfChecked) {
      return this.$refs.tree ? this.$refs.tree.getCheckedNodes(leafOnly, includeHalfChecked) : []
    },
    // 获取半选节点
    getHalfCheckedNodes() {
      return this.$refs.tree ? this.$refs.tree.getHalfCheckedNodes() : []
    },
    // 根据 value 向上查找 node 直到顶层（非关联选择时，确定元素父级，用作默认展开）
    findParents(nodes, path = []) {
      nodes.forEach(({ pid }) => {
        // 父元素已添加，不再处理（防止同一父级下的多个子元素反复查找）
        if (path.some(({ value }) => value === pid)) return
        const parent = pid !== 0 && this.plainData.find(({ value }) => value === pid)
        if (parent) {
          path.push(parent)
          path = this.findParents([parent], path)
        }
      })
      return path
    },
    // 获取组件需要的节点格式
    getCheckedNodes_() {
      const checkedNodes = this.getCheckedNodes()
      switch (this.treeType) {
        case 'tree':
        case 'treeRadio':
        case 'treeSelect':
          return checkedNodes.reduce(
            (pre, item) => {
              pre[item.children.length ? 'parents' : 'children'].push(item)
              return pre
            },
            { parents: this.getHalfCheckedNodes(), children: [] }
          )
        case 'treeRadioFree':
        case 'treeSelectFree':
          return { parents: this.findParents(checkedNodes), children: checkedNodes }
      }
    },
    // 将节点转换为 id
    nodeToKey(nodes) {
      return nodes.map(({ value }) => value)
    },
    // 禁用选项
    disabledData(list = [], disabled, treeType) {
      return list.map(({ label, value, disabled: itemDisabled, plain, children = [] }) => ({
        label,
        value,
        disabled: disabled || (treeType === 'treeRadio' && children.length) || itemDisabled,
        plain,
        children: this.disabledData(children, disabled, treeType),
      }))
    },
    // 扁平化树形结构，pid 父级id 用于确定关系，level 层级 用于确定展开层数
    toPlain(list = [], pid = 0, level = 1, result = []) {
      list.forEach(item => {
        result.push({ ...item, pid, level })
        item.children &&
          item.children.length &&
          (result = this.toPlain(item.children, item.value, level + 1, result))
      })
      return result
    },
    // 设置值
    setValue() {
      const { parents, children } = this.getCheckedNodes_()
      let newValue = []
      switch (this.treeType) {
        case 'tree':
          newValue = this.nodeToKey([...parents, ...children])
          break
        case 'treeRadio':
        case 'treeRadioFree':
        case 'treeSelect':
        case 'treeSelectFree':
          newValue = this.nodeToKey(children)
          break
      }
      this.value = newValue
      this.$emit('change', newValue, { parents, children })
    },
    // 点击标签关闭按钮
    onClose(v) {
      this.setCheckedKeys(this.nodeToKey(this.nodes.children.filter(({ value }) => value !== v)))
      this.$nextTick(() => this.setValue())
    },
    // 点击清空按钮
    onClear() {
      this.setCheckedKeys([])
      this.$nextTick(() => this.setValue())
    },
    // 点击选中框
    onCheck({ value }, { checkedKeys }) {
      if (!['treeRadio', 'treeRadioFree'].includes(this.treeType)) return this.setValue()
      this.setCheckedKeys(checkedKeys.includes(value) ? [value] : [])
      this.$nextTick(() => this.setValue())
    },
  },
}
</script>

<style lang="less" scoped>
.tags-box {
  margin: 5px 0px;
  max-height: 45px;
  overflow-y: auto;
  line-height: 1rem !important;
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

  .tag {
    margin: 1px 2px 1px 0;
  }
}

.input-box {
  display: flex;
  margin: 5px 0;

  .clear-all {
    width: 30px;
    text-align: right !important;
  }
}

/deep/ .el-tag__close.el-icon-close {
  margin-left: -10px;
}

.ellipsis {
  overflow: hidden;
  white-space: nowrap;
  text-overflow: ellipsis;
}
</style>
