<template>
  <w-tree
    ref="tree"
    v-bind="column.searchOption"
    v-model="value"
    :data="searchList"
    :treeType="treeType"
    class="tree"
    @change="onChange"
  />
</template>

<script>
export default {
  name: 'searchTree',
  props: {
    column: { type: Object, default: () => ({}) },
  },
  computed: {
    searchList() {
      return this.$w_fun.typeOf(this.column.searchList) === 'array' ? this.column.searchList : []
    },
    treeType() {
      switch (this.column.searchType) {
        case 'multiple':
          return 'treeSelect'
        case 'radio':
          return 'treeRadio'
        case 'tree':
          return 'tree'
        default:
          return 'treeSelect'
      }
    },
  },
  data() {
    return { value: [] }
  },
  methods: {
    onChange(v) {
      switch (this.treeType) {
        case 'tree':
        case 'treeSelect':
          return this.$emit('search', v.length ? v : undefined, this.column)
        case 'treeRadio':
          return this.$emit('search', v.length ? v[0] : undefined, this.column)
      }
    },
  },
}
</script>

<style lang="less" scoped>
.tree {
  width: 200px;
  /deep/ .tree-box {
    max-height: 300px;
    overflow: auto;
  }
}
</style>
