<template>
  <w-date-picker
    ref="daterange"
    v-bind="searchOption"
    v-model="value"
    :type="searchType"
    :class="searchType"
    :popperClass="`y-header-search-${searchType}`"
    start-placeholder="开始"
    end-placeholder="结束"
    @change="onSearch"
    @focus="onChangeFocus($event, true)"
    @blur="onChangeFocus($event)"
  />
</template>

<script>
import { merge } from 'lodash'
export default {
  name: 'searchDaterange',
  props: {
    column: { type: Object, default: () => ({}) },
  },
  computed: {
    searchType() {
      switch (this.column.searchType) {
        case 'dateint':
        case 'datebetween':
          return 'daterange'
        default:
          return this.column.searchType
      }
    },
    searchOption() {
      const defaultOption = { pickerOptions: { disabledDate: { end: 'today' } } }
      return this.column.searchOption
        ? merge(this.column.searchOption, defaultOption)
        : defaultOption
    },
  },
  data() {
    return { value: [] }
  },
  methods: {
    onFocus() {
      this.$refs.daterange.$refs.datePicker.focus()
    },
    onChangeFocus({ $el }, focus) {
      if (!focus) return ($el.style.marginBottom = 0)
      switch (this.searchType) {
        case 'daterange':
          return ($el.style.marginBottom = '260px')
        case 'monthrange':
          return ($el.style.marginBottom = '180px')
      }
    },
    onSearch() {
      this.$emit(
        'search',
        this.value
          ? this.column.searchType === 'dateint'
            ? this.value.map(i => i.replace(/-/g, '').replace(/:/g, ''))
            : this.value
          : undefined,
        this.column
      )
    },
  },
}
</script>

<style lang="less" scoped>
.daterange {
  width: 370px !important;
}

.monthrange {
  width: 350px !important;
}
</style>

<style lang="less">
.y-header-search-daterange {
  width: 370px;
  border: none;
  box-shadow: none;
  overflow: hidden;

  .el-picker-panel__body {
    min-width: 0;
  }

  .el-picker-panel__content.el-date-range-picker__content {
    padding: 0;
  }

  .el-date-range-picker__header {
    text-align: center;
    div {
      margin: 0;
    }
  }

  .el-date-table td {
    padding: 0;
  }
}

.y-header-search-monthrange {
  width: 350px;
  border: none;
  box-shadow: none;
  overflow: hidden;

  .el-picker-panel__body {
    min-width: 0;
  }

  .el-picker-panel__content.el-date-range-picker__content {
    padding: 0;
  }

  .el-date-range-picker__header {
    text-align: center;
    div {
      margin: 0;
    }
  }

  .el-month-table td {
    padding: 0;
    padding-left: 1px;
    .cell {
      width: auto;
    }
  }
}
</style>
