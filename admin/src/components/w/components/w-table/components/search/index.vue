<template>
  <el-popover
    ref="popover"
    v-model="visible"
    :disabled="!column_.search"
    placement="bottom"
    @show="onShow"
  >
    <span slot="reference" :class="column_.search && 'cursor'" @click.stop="onClick">
      <el-tooltip :disabled="!column_.headerTooltip" placement="top">
        <div slot="content" v-html="column_.headerTooltip" style="white-space: pre-wrap"></div>
        <span :style="{ color: column.color || '#19aa8d' }">{{
          isShowIcon ? column_.label + ' ' : column_.label
        }}</span>
      </el-tooltip>
      <i
        v-if="isShowIcon && column_.search"
        :class="this.iconStyle.color ? 'el-icon-search' : 'el-icon-zoom-in'"
        :style="iconStyle"
      />
    </span>
    <component ref="search" :is="column_.searchType" :column="column_" @search="onSearch" />
  </el-popover>
</template>

<script>
import Input from './components/input.vue'
import Number from './components/number.vue'
import Date from './components/date.vue'
import Daterange from './components/daterange.vue'
import Timerange from './components/timerange.vue'
import Datetimerange from './components/datetimerange.vue'
import Tree from './components/tree.vue'
import Select from './components/select.vue'
import HSelect from './components/hSelect.vue'
import { searchTypeList } from '../../util'

export default {
  name: 'headerSearch',
  components: {
    like: Input, // 模糊
    match: Input, // 精确
    find: Input, // 模糊 + 精确
    number: Number, // 数字范围
    percentage: Number, //百分比范围
    date: Date, // 日期
    datetime: Date, // 日期 + 时间
    month: Date, // 月份
    year: Date, // 年份
    daterange: Daterange, // 日期范围
    datebetween: Daterange, // 日期范围
    dateint: Daterange, // 日期范围，后端自己区分，前端没区别
    monthrange: Daterange, // 月份范围
    timerange: Timerange, // 时间范围
    timeint: Timerange, // 时间范围，后端自己区分，前端没区别
    datetimerange: Datetimerange, // 日期时间范围
    tree: Tree, // 树形全选
    multiple: Tree, // 树形多选
    radio: Tree, // 树形单选
    status: Tree,
    remote: Select,
    hselect: HSelect
  },
  props: {
    column: { type: Object, default: () => ({}) },
    defaultValue: { default: undefined },
  },
  computed: {
    column_() {
      let { v, search, searchType, searchList, render } = this.column
      const s = search !== undefined ? search : v
      if (!searchType && (render === 'boolean' || render === 'status')) {
        searchType = 'multiple'
      }
      return {
        ...this.column,
        search: s,
        searchType: searchType || 'like',
        searchList: searchList || (render === 'status' ? [
          { value: 0, label: '禁用' },
          { value: 1, label: '正常' }
        ] : (render === 'boolean' ? [
          { value: 0, label: '否' },
          { value: 1, label: '是' }
        ] : searchList))
      }
    },
    isShowIcon() {
      return false
    },
  },
  data() {
    return {
      visible: false,
      iconStyle: { color: '#c0c4cc' },
    }
  },
  watch: {
    defaultValue: {
      immediate: true,
      handler(val) {
        if (val) {
          this.$nextTick(() => {
            if (this.column.searchType === 'timeint') {
              this.$refs.search.value = this.defaultValue.map(v => {
                v = `${v}`
                const h = v[0] && v[1] && `${v[0]}${v[1]}`,
                  m = v[2] && v[3] && `${v[2]}${v[3]}`,
                  s = v[4] && v[5] && `${v[4]}${v[5]}`
                return [h, m, s].filter(v => v).join(':')
              })
            } else {
              this.$refs.search.value = this.defaultValue
            }
          })
          this.setIcon(true)
        }
      },
    },
  },
  methods: {
    // 弹出时关闭其他搜索弹窗
    onClick() {
      this.visible === false && document.body.click()
    },
    onShow() {
      // 搜索框自动获取焦点
      this.$nextTick(
        () => this.$refs.search && this.$refs.search.onFocus && this.$refs.search.onFocus()
      )
    },
    setIcon(v) {
      this.iconStyle = v ? { fontWeight: 'bold' } : { color: '#c0c4cc' }
    },
    onSearch(v, { search, searchType }, send = true) {
      this.setIcon(v)
      this.$emit('search', `${search}_${searchTypeList[searchType]}`, v, send)
    },
  },
}
</script>
