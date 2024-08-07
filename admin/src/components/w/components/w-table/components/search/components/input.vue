<template>
  <w-input
    ref="input"
    v-bind="column.searchOption"
    v-model="value"
    :clearable="true"
    :placeholder="`请输入${column.label}`"
    @keypress.enter.native="onSearch"
    @clear="onSearch"
  >
    <template v-if="column.searchType === 'find'" #prepend>
      <w-select
        v-model="searchType"
        :data="searchTypeList"
        :clearable="false"
        style="width: 2.5em"
      />
    </template>
    <template #append>
      <i class="el-icon-search cursor" @click="onSearch" />
    </template>
  </w-input>
</template>

<script>
export default {
  name: 'searchInput',
  props: {
    column: { type: Object, default: () => ({}) },
  },
  data() {
    return {
      value: '',
      searchType: 'match',
      searchTypeList: [
        { label: '模糊', value: 'like' },
        { label: '精确', value: 'match' },
      ],
    }
  },
  methods: {
    onFocus() {
      this.$refs.input.$refs.input.focus()
    },
    onEmit(value, type) {
      this.$emit(
        'search',
        undefined,
        { ...this.column, searchType: type === 'like' ? 'match' : 'like' },
        false
      )
      this.$emit('search', value || undefined, { ...this.column, searchType: type }, true)
    },
    onSearch() {
      const column = { ...this.column }
      if (column.searchType === 'find') {
        this.onEmit(this.value, this.searchType)
        return
      }
      this.$emit('search', this.value || undefined, column)
    },
  },
}
</script>
<style lang="less" scoped>
/deep/ .el-input-group__append,
/deep/ .el-input-group__prepend {
  background: none;
}
</style>
