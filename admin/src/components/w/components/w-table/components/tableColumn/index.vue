<template>
  <ElTableColumn
    v-if="!column.disabled"
    :prop="column.sort || column.v"
    :sortable="column.sort ? 'custom' : false"
    :show-overflow-tooltip="column.render !== 'tooltip'"
    :align="column.align || 'center'"
    :min-width="minWidth"
    :width="column.width || undefined"
    header-align="center"
    :fixed="column.fixed || undefined"
    :class-name="column.className"
    :label-class-name="column.labelClassName"
  >
    <template #header>
      <slot name="header" :column="column" :defaultValue="makeDefaultValue(column)">
        <HeaderSearch
          ref="headerSearch"
          :column="column"
          :defaultValue="makeDefaultValue(column)"
          @search="onSearch"
        />
      </slot>
    </template>
    <template v-if="column.children && column.children.length" #default>
      <template v-for="(item, index) in column.children">
        <TableColumn
          v-if="!item.disabled"
          :key="`${index}${item.v}${item.label}${item.headerTooltip}`"
          :column="item"
          :defaultFilter="defaultFilter"
          @search="onSearch"
        />
      </template>
    </template>
    <template v-else #default="{ row, $index, column: _column }">
      <slot v-if="$scopedSlots.default" :row="row" :$index="$index" :column="_column">
        {{ column.replace ? row[`${column.v}_replace`] : keysToValue(row, column.v) }}
      </slot>
      <span
        v-else-if="
          ['text', '', undefined].includes(column.render) ||
          (column.render === 'tooltip' && !keysToValue(row, column.value)) ||
          (['link', 'router'].includes(column.render) && !keysToValue(row, column.value))
        "
        :style="row[`${column.v}_color`] ? `color: ${row[`${column.v}_color`]}` : ''"
        :class="column.clickSearch || column.clickCopy ? 'cursor' : ''"
        @click="clickColumn(row, column)"
      >
        {{ column.replace ? row[`${column.v}_replace`] : keysToValue(row, column.v) }}
      </span>
      <span v-else-if="column.render === 'html'" v-html="keysToValue(row, column.v)" />
      <template v-else-if="column.render === 'boolean'">
        <span
          v-if="keysToValue(row, column.v) === 1"
          class="green"
          :class="column.clickSearch || column.clickCopy ? 'cursor' : ''"
          @click="clickColumn(row, column)"
        > 是 </span>
        <span
          v-else
          class="red"
          :class="column.clickSearch || column.clickCopy ? 'cursor' : ''"
          @click="clickColumn(row, column)"
        > 否 </span>
      </template>
      <template v-else-if="column.render === 'status'">
        <el-switch
          v-model="row[column.v]"
          active-color="#13ce66"
          inactive-color="#ff4949"
          :active-value="1"
          :inactive-value="0"
          @change="$emit('changeStatus', row, column)"
        />
      </template>
      <template v-else-if="column.render === 'pdf'">
        <w-pdf
          :buttonText="keysToValue(row, column.v) || undefined"
          :url="keysToValue(row, column.value)"
        />
      </template>
      <el-link
        v-else-if="['link', 'router'].includes(column.render)"
        :href="keysToValue(row, column.value)"
        :target="column.render === 'link' ? '_blank' : '_self'"
        style="max-width: 100%"
        :style="row[`${column.v}_color`] ? `color: ${row[`${column.v}_color`]}` : ''"
        class="omit dp-i-b"
      >
        {{ column.replace ? row[`${column.v}_replace`] : keysToValue(row, column.v) }}
      </el-link>
      <w-enclosure
        v-else-if="column.render === 'enclosure'"
        :enclosure="keysToValue(row, column.v)"
      />
      <w-image
        v-else-if="column.render === 'image'"
        :preview-src-list="keysToValue(row, column.v)"
        style="height: 40px; width: auto"
      />
      <ElTooltip v-else-if="column.render === 'tooltip'" placement="top">
        <template #content>
          <div v-html="keysToValue(row, column.value)" />
        </template>
        <span :style="row[`${column.v}_color`] ? `color: ${row[`${column.v}_color`]}` : ''">
          {{ column.replace ? row[`${column.v}_replace`] : keysToValue(row, column.v) }}
        </span>
      </ElTooltip>
    </template>
  </ElTableColumn>
</template>

<script>
import { copyText, keysToValue } from '@/utils/w'
import HeaderSearch from '../search/index.vue'
import { searchTypeList } from '../../util'
export default {
  name: 'TableColumn',
  components: { HeaderSearch },
  props: {
    column: { type: Object, default: () => ({}) },
    defaultFilter: { type: Object, default: () => ({}) },
  },
  computed: {
    minWidth() {
      const { label, search, sort } = this.column
      const labelW = label.length * 14
      const w =
        (this.labelWidth || (search !== false ? labelW + 18 : labelW)) +
        (sort !== false ? 14 : 0) +
        8
      const w_ = this.column.minWidth || this.column.width || 0
      return w > w_ ? w : w_
    },
  },
  data() {
    return { labelWidth: 0 }
  },
  mounted() {
    this.$nextTick(() => {
      const titleCell = this.$refs.headerSearch && this.$refs.headerSearch.$el
      if (titleCell) {
        const range = document.createRange()
        range.setStart(titleCell, 0)
        range.setEnd(titleCell, titleCell.childNodes.length)
        this.labelWidth = Math.ceil(range.getBoundingClientRect().width)
      }
    })
  },
  methods: {
    keysToValue,
    onSearch(...args) {
      this.$emit('search', ...args)
    },
    makeDefaultValue({ search, v, searchType }) {
      if (search === false) return undefined
      const key = search || v
      const result = Object.keys(this.defaultFilter).filter(
        k => k.slice(0, k.lastIndexOf('_')) === key
      )
      if (result.length === 0) return undefined
      if (['number', 'percentage'].includes(searchType)) {
        const num = [undefined, undefined]
        result.forEach(k => {
          if (k.endsWith('in')) {
            num[0] = this.defaultFilter[k]
          } else if (k.endsWith('ax')) {
            num[1] = this.defaultFilter[k]
          }
        })
        return num[0] === undefined && num[1] === undefined ? undefined : num
      }
      return this.defaultFilter[result[0]]
    },
    clickColumn(row, column) {
      if (column.clickSearch) {
        column.search = column.search || column.v
        const search = typeof column.clickSearch === 'string' ? column.clickSearch : column.search
        const searchType = searchTypeList[column.searchType || 'like']
        this.$emit(
          'search',
          column.search + '_' + searchType,
          searchType === 'multiple' ? [keysToValue(row, search)] : keysToValue(row, search)
        )
      }
      if (column.clickCopy) {
        copyText(keysToValue(row, column.v))
        this.$message.success('复制成功')
      }
    },
  },
}
</script>
