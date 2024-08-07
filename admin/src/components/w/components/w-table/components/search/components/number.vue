<template>
  <div>
    <w-input
      ref="minNumber"
      v-bind="(column.searchOption && column.searchOption.min) || column.searchOption"
      v-model="value[0]"
      type="number"
      :max="value[1]"
      class="number"
      placeholder="最小值"
      @keypress.enter.native="onSearch"
    />
    -
    <w-input
      v-bind="(column.searchOption && column.searchOption.max) || column.searchOption"
      v-model="value[1]"
      type="number"
      :min="value[0]"
      class="number"
      placeholder="最大值"
      @keypress.enter.native="onSearch"
    />
    <i class="el-input__icon el-icon-search cursor" @click="onSearch" />
  </div>
</template>

<script>
export default {
  name: 'searchNumber',
  props: {
    column: { type: Object, default: () => ({}) },
  },
  data() {
    return { value: [] }
  },
  methods: {
    onFocus() {
      this.$refs.minNumber.$refs.input.focus()
    },
    onEmit({ min, max }) {
      this.$emit(
        'search',
        min,
        {
          search: this.column.search,
          searchType: this.column.searchType === 'number' ? 'min' : 'percentageMin',
        },
        false
      )
      this.$emit(
        'search',
        max,
        {
          search: this.column.search,
          searchType: this.column.searchType === 'number' ? 'max' : 'percentageMax',
        },
        true
      )
      // this.$emit('search', between, this.column, true)
    },
    onSearch() {
      const [min = '', max = ''] = this.value
      if (min !== '' && max !== '' && min > max)
        return this.$message.warning('最大值不可小于最小值')
      if (min === '' && max === '') return this.onEmit({})
      if (min === '' && max !== '') return this.onEmit({ max })
      if (min !== '' && max === '') return this.onEmit({ min })
      return this.onEmit({ min, max })
    },
  },
}
</script>

<style lang="less" scoped>
.number {
  display: inline-block;
  width: 100px;
}
</style>
