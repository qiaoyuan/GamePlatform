<template>
  <w-date-time-picker
    ref="datetimerangeRef"
    v-bind="searchOption"
    v-model="value"
    is-range
    class="datetimerange"
    start-placeholder="开始"
    end-placeholder="结束"
    :append-to-body="false"
    @change="onSearch"
    @focus="onChangeFocus($event, true)"
    @blur="onChangeFocus($event)"
  />
</template>

<script>
import { merge } from 'lodash'
import dayjs from 'dayjs'
export default {
  name: 'searchDatetimerange',
  props: {
    column: { type: Object, default: () => ({}) },
  },
  computed: {
    today() {
      return new Date().setHours(0, 0, 0, 0)
    },
    yesterday() {
      return this.today - 86400000
    },
    tomorrow() {
      return this.today + 86400000
    },
    searchOption() {
      const popperClass =
        this.column.searchOption && this.column.searchOption.popperClass
          ? `y-header-search-datetimerange bz-header-search-datetimerange ${this.column.searchOption.popperClass}`
          : 'y-header-search-datetimerange bz-header-search-datetimerange'
      const defaultOption = {
        pickerOptions: {
          disabledDate: { end: 'today' },
          step: { minute: 10 },
          shortcuts: [
            { text: '今天', onClick: picker => this.onClickShortcuts(picker, 1) },
            { text: '昨天', onClick: picker => this.onClickShortcuts(picker, 2) },
            { text: '0~23时', onClick: picker => this.onClickShortcuts(picker, 3) },
          ],
        },
        popperClass,
      }
      return this.column.searchOption
        ? { ...merge(defaultOption, this.column.searchOption), popperClass }
        : defaultOption
    },
  },
  data() {
    return {
      value: [],
    }
  },
  methods: {
    onClickShortcuts(picker, type) {
      let min, max
      if (type === 1) {
        min = dayjs(this.today).format('YYYY-MM-DD 00:00:00')
        max = dayjs().format('YYYY-MM-DD 23:59:59')
      } else if (type === 2) {
        min = dayjs(this.yesterday).format('YYYY-MM-DD 00:00:00')
        max = dayjs(this.yesterday).format('YYYY-MM-DD 23:59:59')
      } else if (type === 3) {
        const [startDay, endDay] = [picker.minDate, picker.maxDate] || [this.today, this.today]
        min = dayjs(startDay ? startDay : this.today).format('YYYY-MM-DD 00:00:00')
        max = dayjs(endDay ? endDay : this.today).format('YYYY-MM-DD 23:59:59')
      }
      picker.minDate = new Date(min)
      picker.maxDate = new Date(max)
    },
    onFocus() {
      this.$nextTick(() => this.$refs.datetimerangeRef.$refs.dateTimePickerRef.focus())
    },
    onChangeFocus({ $el }, focus) {
      if (!focus) return ($el.style.marginBottom = 0)
      return ($el.style.marginBottom = '325px')
    },
    onSearch() {
      this.$emit('search', this.value || undefined, this.column)
    },
  },
}
</script>

<style lang="less" scoped>
.datetimerange {
  width: 462px !important;
}
</style>

<style lang="less">
.bz-header-search-datetimerange {
  width: 462px !important;
  .el-picker-panel__sidebar {
    width: 80px;
  }
  .el-picker-panel__body {
    margin-left: 91px;
  }
}

.y-header-search-datetimerange {
  width: 462px;
  border: none;
  box-shadow: none;
  overflow: hidden;

  .el-picker-panel__body {
    min-width: 0;

    .el-date-range-picker__time-header {
      padding: 1px 0;
      .el-date-range-picker__time-picker-wrap:first-child {
        display: none;
      }
      .el-date-range-picker__time-picker-wrap:not(:first-child) {
        padding: 0;
        input {
          height: 28px;
          line-height: 28px;
        }
      }
    }
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
