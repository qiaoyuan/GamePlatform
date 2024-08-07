<template>
  <w-date-picker
    ref="date"
    v-bind="searchOption"
    v-model="value"
    :type="column.searchType"
    class="date"
    popperClass="y-header-search-date"
    :placeholder="`请选择${column.label}`"
    @change="onSearch"
    @focus="onChangeFocus($event, true)"
    @blur="onChangeFocus($event)"
  />
</template>

<script>
import { merge } from 'lodash'
export default {
  name: 'searchDate',
  props: {
    column: { type: Object, default: () => ({}) },
  },
  computed: {
    searchOption() {
      const defaultOption = { pickerOptions: { disabledDate: { end: 'today' } } }
      return this.column.searchOption
        ? merge(this.column.searchOption, defaultOption)
        : defaultOption
    },
  },
  data() {
    return { value: '' }
  },
  methods: {
    onFocus() {
      this.$refs.date.$refs.datePicker.focus()
    },
    onChangeFocus({ $el }, focus) {
      if (!focus) return ($el.style.marginBottom = 0)
      switch (this.column.searchType) {
        case 'date':
          return ($el.style.marginBottom = '260px')
        case 'month':
          return ($el.style.marginBottom = '180px')
        case 'year':
          return ($el.style.marginBottom = '180px')
      }
    },
    onSearch() {
      this.$emit('search', this.value ? this.value : undefined, this.column)
    },
  },
}
</script>

<style lang="less" scoped>
.date {
  width: 200px !important;
}
</style>

<style lang="less">
.y-header-search-date {
  width: 200px;
  border: none;
  box-shadow: none;
  overflow: hidden;

  .el-picker-panel__body {
    min-width: 0;
  }

  .el-date-picker__header {
    margin: 0;
    padding-bottom: 0;
  }

  .el-picker-panel__content {
    width: auto;
    margin: 0;
  }

  .el-date-table td {
    padding: 0;
  }

  .el-month-table td {
    padding: 0;
    .cell {
      width: auto;
    }

    &.current .cell {
      color: #fff !important;
      background-color: #19aa8d;
      border-radius: 18px;
    }
  }

  .el-year-table td {
    padding: 8px 0;

    .cell {
      width: auto;
    }

    &.current .cell {
      color: #fff !important;
      background-color: #19aa8d;
      border-radius: 16px;
    }
  }
}
</style>
