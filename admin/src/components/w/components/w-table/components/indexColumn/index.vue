<template>
  <ElTableColumn
    prop="tableIndex"
    type=""
    :width="width"
    align="center"
    fixed="left"
    class-name="table-index-sort"
  >
    <template #header>
      <el-checkbox
        v-if="selectable"
        v-model="checkAll"
        :true-label="2"
        :false-label="0"
        :indeterminate="checkAll === 1"
      />
      <span v-else>序号<i v-if="sortable" class="el-icon-sort" /></span>
      <el-tooltip ref="tooltip" placement="top" :content="activateContent" />
    </template>
    <template #default="{ row }">
      <div
        style="overflow: hidden; text-overflow: ellipsis; word-break: keep-all"
        :style="{ cursor: sortable ? 'move' : 'default' }"
        :class="selectable ? (isChecked(row) ? 'column-selected' : 'column-hover') : ''"
        @mouseenter="onMouseenter($event, row)"
        @mouseleave="onMouseleave"
      >
        <el-checkbox
          v-if="selectable"
          v-model="selectionId"
          :key="row[primaryKey]"
          :label="row[primaryKey]"
          :disabled="isDisabled(row)"
        />
        <span class="index"> {{ row.tableIndex }} </span>
      </div>
    </template>
  </ElTableColumn>
</template>

<script>
import { toPlain } from '@/utils/w'
import debounce from 'throttle-debounce/debounce'
export default {
  props: {
    width: { type: Number, defualt: 0 },
    selectable: { type: [Boolean, Function], default: false },
    sortable: { type: Boolean, default: false },
    primaryKey: { type: String, default: 'id' },
    data: { type: Array, default: () => [] },
  },
  computed: {
    // 扁平化是为了确定树形的数据
    plainData() {
      return toPlain(this.data)
    },
    // 可选的数据
    selectableData() {
      if (this.selectable === true) return this.plainData
      if (this.selectable)
        return this.plainData.filter((row, $index) => this.selectable(row, $index))
      return []
    },
    // 全选状态
    checkAll: {
      get() {
        if (this.selectionId.length === 0) return 0
        if (this.selectionId.length === this.selectableData.length) return 2
        return 1
      },
      set(v) {
        if (v === 0) return (this.selectionId = [])
        this.selectionId = this.selectableData.map(row => row[this.primaryKey])
      },
    },
  },
  data() {
    return {
      selectionId: [],
      activateContent: '',
      activateTooltip: '',
    }
  },
  created() {
    this.activateTooltip = debounce(50, tooltip => tooltip.handleShowPopper())
  },
  methods: {
    isDisabled(row) {
      return this.selectableData.every(item => item[this.primaryKey] !== row[this.primaryKey])
    },
    isChecked(row) {
      return this.selectionId.includes(row[this.primaryKey])
    },
    onMouseenter(e, row) {
      this.activateContent = row.tableIndex
      const div = e.target
      const range = document.createRange()
      range.setStart(div, 0)
      range.setEnd(div, div.childNodes.length)
      if (div.getBoundingClientRect().width > range.getBoundingClientRect().width) return
      const tooltip = this.$refs.tooltip
      tooltip.show()
      tooltip.referenceElm = div
      tooltip.$refs.popper && (tooltip.$refs.popper.style.display = 'none')
      tooltip.doDestroy()
      tooltip.setExpectedState(true)
      this.activateTooltip(tooltip)
    },
    onMouseleave() {
      const tooltip = this.$refs.tooltip
      tooltip.setExpectedState(false)
      tooltip.handleClosePopper()
    },
  },
  watch: {
    data() {
      this.checkAll = 0
      this.selectionId = []
    },
    selectionId(v) {
      this.$emit(
        'selected',
        this.selectableData.filter(item => v.includes(item[this.primaryKey]))
      )
    },
  },
}
</script>

<style lang="less" scoped>
/deep/ .el-checkbox {
  .el-checkbox__label {
    display: none;
  }
  .el-checkbox__input {
    line-height: 13px;
  }
}

.column-selected .index {
  display: none;
}

.column-hover {
  &:not(:hover) .el-checkbox {
    display: none;
  }
  &:hover .index {
    display: none;
  }
}
</style>
