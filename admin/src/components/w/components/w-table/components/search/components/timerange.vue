<template>
  <w-time-picker
    ref="timePickerRef"
    v-bind="column.searchOption"
    v-model="value"
    startPlaceholder="开始"
    endPlaceholder="结束"
    popperClass="y-header-search-timerange"
    is-range
    :pickerOptions="{ step: { minute: 15 } }"
    style="width: 354px"
    @change="onChange"
    @focus="onChangeFocus($event, true)"
    @blur="onChangeFocus($event)"
  />
</template>

<script>
export default {
  name: 'searchTimerange',
  props: {
    column: { type: Object, default: () => ({}) },
  },
  computed: {
    size() {
      return this.$store.getters['size']
    },
  },
  data() {
    return {
      value: undefined,
    }
  },
  methods: {
    onFocus() {
      this.$refs.timePickerRef.$refs.timePickerRef.focus()
    },
    onChangeFocus({ $el }, focus) {
      if (!focus) return ($el.style.marginBottom = 0)
      $el.style.marginBottom = '230px'
    },
    onChange() {
      if (!this.value) return this.$emit('search', undefined, this.column)
      let [start, end] = this.value
      this.$emit(
        'search',
        [start, end].map(i => i.replace(/:/g, '')),
        this.column
      )
    },
  },
}
</script>

<style lang="less" scoped>
.timerange {
  width: 100px;
}
</style>

<style lang="less">
.y-header-search-timerange {
  border: none;
  box-shadow: none;
  overflow: hidden;
  .el-time-range-picker__content {
    padding: 0;
  }
  .el-time-range-picker__cell {
    &:first-child {
      padding: 0 7px 7px 0;
    }
    &:last-child {
      padding: 0 0 7px 7px;
    }
  }
  .el-time-range-picker__header {
    display: none;
  }
}
</style>
