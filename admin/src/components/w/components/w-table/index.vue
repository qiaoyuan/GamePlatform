<template>
  <div class="dp-f flex-d-c flex-1">
    <div ref="tableBody" class="flex-1 relative o-h">
      <ETable
        ref="table"
        v-if="!loading"
        v-bind="{ ...$props, ...$attrs }"
        v-on="$listeners"
        :data="data"
        :maxHeight="maxSize.height"
        :size="size_"
        :empty-text="emptyText"
        :default-sort="defaultSort_"
        :show-summary="showSummary"
        :summary-method="summaryMethod"
        @sort-change="onSortChange"
      >
        <!-- 序号、选择、拖拽 -->
        <IndexColumn
          :width="indexWidth"
          :selectable="selectable_"
          :sortable="!!actions_.sort"
          :primaryKey="primaryKey"
          :data="data"
          @selected="v => (selection = v)"
        />
        <template v-for="(item, index) in columns">
          <TableColumn
            v-if="!item.disabled"
            ref="tableColumn"
            :key="`${index}${item.v}${item.label}${item.headerTooltip}`"
            :column="item"
            :defaultFilter="allFilter"
            @search="onSearch"
            @changeStatus="changeStatus"
          >
            <template #header="slotProps">
              <slot v-bind="slotProps" :name="`${item.search || item.v}HeaderSlot`" />
            </template>
            <template
              v-if="$scopedSlots[`${item.v}Slot`] || (item.render && $scopedSlots[item.render])"
              #default="slotProps"
            >
              <slot
                v-bind="slotProps"
                :name="$scopedSlots[`${item.v}Slot`] ? `${item.v}Slot` : item.render"
              />
            </template>
          </TableColumn>
        </template>
        <ElTableColumn
          v-if="operateWidth"
          label="操作"
          prop="tableOperate"
          header-align="center"
          :width="operateWidth"
          :resizable="false"
          fixed="right"
          class-name="last-column"
        >
          <template #default="{ row, $index }">
            <div
              class="operates dp-f align-items-center"
              :style="{ justifyContent: `flex-center` }"
            >
              <RecycleGroup
                v-if="isRecycle"
                :primaryKey="primaryKey"
                :operates="recycleOperates"
                :row="row"
                @done="getList"
              />
              <template v-else>
                <template v-for="(item, index) in otherOperates">
                  <el-button
                    v-if="makeOtherOperatesStatus(item.show, row, $index)"
                    v-p="item.p ? makeOtherOperatesStatus(item.p, row, $index) : ''"
                    :key="index"
                    :disabled="makeOtherOperatesStatus(item.disabled, row, $index)"
                    :type="makeOtherOperatesStatus(item.type, row, $index)"
                    :size="size_"
                    @click="item.click(row, $index)"
                  >
                    {{ makeOtherOperatesStatus(item.title, row, $index) }}
                  </el-button>
                </template>
                <NormalGroup
                  :primaryKey="primaryKey"
                  :row="row"
                  :$index="$index"
                  :operates="normalOperate"
                  :query="query"
                  @edit="$emit('edit', row, $index)"
                  @del="$emit('del', row, $index)"
                  @look="$emit('look', row, $index)"
                  @done="getList"
                >
                  <template v-if="$scopedSlots.moreOperate" #default="props">
                    <slot name="moreOperate" v-bind="props" />
                  </template>
                </NormalGroup>
              </template>
            </div>
          </template>
        </ElTableColumn>
      </ETable>
    </div>
    <div
      v-if="pagination || $scopedSlots.multiOperate"
      ref="tableFooter"
      class="dp-f justify-content-between border-f1f1f1-top pt5"
    >
      <MultiGroup
        :primaryKey="primaryKey"
        :selection="selection"
        :operates="multiOperate"
        :query="query"
        :module="module"
        @multiDel="$emit('multiDel', selection)"
        @done="getList"
      >
        <template #default="props"> <slot name="multiOperate" v-bind="props" /> </template>
      </MultiGroup>
      <w-pagination
        v-if="pagination"
        :pageSize.sync="page.limit"
        :currentPage.sync="page.page"
        :total="page.total"
        @pagination="onPagination"
      />
    </div>
    <OperateWidth
      ref="operateWidth"
      :data="data"
      :otherOperates="otherOperates"
      :recycleOperates="recycleOperates"
      :normalOperate="normalOperate"
      :moreOperate="!!$scopedSlots.moreOperate"
      :isRecycle="isRecycle"
    />
  </div>
</template>

<script>
import { post } from '@/libs/request'
import { keysToValue, findTreeParents } from '@/utils/w'
import { makeOtherOperatesStatus } from './util'
import { pList } from '@/utils/wData'
import { hasPermission } from '@/directive/w/directive/p'
import { debounce } from 'lodash'
import TableColumn from './components/tableColumn/index.vue'
import NormalGroup from './components/operate/normalGroup.vue'
import MultiGroup from './components/operate/multiGroup.vue'
import RecycleGroup from './components/operate/recycleGroup.vue'
import IndexColumn from './components/indexColumn/index.vue'
import OperateWidth from './components/operate/operateWidth.vue'
import Sortable from 'sortablejs'
import ETable from './src/table.vue'
export default {
  name: 'wTable',
  components: {
    ETable,
    TableColumn,
    NormalGroup,
    RecycleGroup,
    MultiGroup,
    IndexColumn,
    OperateWidth,
  },
  props: {
    // 斑马纹
    stripe: { type: Boolean, default: true },
    // 边框
    border: { type: Boolean, default: true },
    // 高亮行
    highlightCurrentRow: { type: Boolean, default: true },
    // 用于渲染的唯一标识符
    rowKey: { type: String, default: 'id' },
    // 用于发起请求的唯一标识符
    primaryKey: { type: String, default: 'id' },
    // 选择框
    selectable: { type: [Boolean, Function], default: false },
    // 分页
    pagination: { type: Boolean, default: true },
    // 默认过滤
    defaultFilter: { type: Object, default: () => ({}) },
    // 默认排序
    defaultSort: { type: String, default: '' },
    // 默认分页
    defaultPage: { type: Object, default: () => ({}) },
    // 承载表格发送请求的附带参数
    query: { type: Object, default: () => ({}) },
    // 请求根路径（有时候可能不是相对地址而是绝对地址，需要拼接根路径，在有些地方需要使用请求地址名时把根路径去掉）
    baseUrl: { type: String, default: '' },
    // 当前表格所在模块名
    module: { type: String, default: '' },
    // 承载基础请求地址
    actions: { type: Object, default: () => ({}) },
    // 承载对各个默认按钮的配置
    operates: { type: Object, default: () => ({}) },
    // 是否异步请求分页和求和
    asyncSummary: { type: Boolean, default: true },
  },
  computed: {
    selectable_() {
      return this.selectable || this.isRecycle || this.operates.multiDel
    },
    size_() {
      return this.$store.getters['size']
    },
    // 默认筛选
    defaultFilter_() {
      const { filter } = this.$route.query
      if (filter) return JSON.parse(filter)
      return Object.keys(this.defaultFilter).reduce((pre, k) => {
        const { type, value } = this.defaultFilter[k]
        pre[`${k}_${type}`] = value
        return pre
      }, {})
    },
    allFilter() {
      return Object.assign({}, this.defaultFilter_, this.filter)
    },
    // 默认排序
    defaultSort_() {
      const defaultSort = this.$route.query.sort || this.defaultSort
      const sort = defaultSort.slice(0, 1)
      const key = defaultSort.slice(1)
      if (sort === '+') return { prop: key, order: 'ascending' }
      if (sort === '-') return { prop: key, order: 'descending' }
      return undefined
    },
    // 默认分页
    defaultPage_() {
      const { page } = this.$route.query
      if (page) {
        const pageArr = page.split(',')
        const pageNum = parseInt(pageArr[0])
        const limitNum = parseInt(pageArr[1])
        const totalNum = parseInt(pageArr[2])
        return {
          page: pageNum > 0 ? pageNum : 1,
          limit: [50, 100, 150].includes(limitNum) ? limitNum : 50,
          total: totalNum > 0 ? totalNum : 0,
        }
      } else {
        return { page: 1, limit: 50, total: 0 }
      }
    },
    // 请求地址
    actions_() {
      if (!this.module && !this.baseUrl) return this.actions
      const actions = { columns: true, index: true, sort: false, status: true }
      return Object.keys(actions).reduce((pre, k) => {
        switch (this.actions[k]) {
          case false:
          case '':
            break
          case undefined:
            if (actions[k] === false) break
          case true:
            pre[k] = `${this.module}/${k}`
            pre[`${k}_`] = `${this.baseUrl}${this.module}/${k}`
            break
          default:
            pre[k] =
              this.baseUrl && this.$w_fun.typeOf(this.actions[k]) === 'string'
                ? this.actions[k].replace(this.baseUrl, '')
                : this.actions[k]
            pre[`${k}_`] = this.actions[k]
            break
        }
        return pre
      }, {})
    },
    // 其他常驻按钮配置
    otherOperates() {
      const other = this.operates.other
      if (this.$w_fun.typeOf(other) !== 'array') return []
      return other.reduce(
        (
          pre,
          {
            p = false,
            show = true,
            disabled = false,
            title = '',
            type = 'primary',
            click = () => {},
          }
        ) => {
          // title 不存在，过滤
          if (!title) return pre
          // 权限存在，处理权限
          if (p) {
            switch (this.$w_fun.typeOf(p)) {
              case 'function':
                pre.push({ p, show, disabled, title, type, click })
                break
              default:
                // 权限验证不通过，过滤
                if (!hasPermission(p)) return pre
                pre.push({ show, disabled, title, type, click })
                break
            }
          } else {
            pre.push({ show, disabled, title, type, click })
          }
          return pre
        },
        []
      )
    },
    // 回收站按钮配置
    recycleOperates() {
      return [
        {
          field: 'restore',
          title: '还原',
          show: true,
          p: hasPermission(`${this.module}${pList.restore}`),
          type: 'primary',
          url: `${this.module}/${pList.restore}`,
        },
        {
          field: 'forceDelete',
          title: '永久删除',
          show: true,
          p: hasPermission(`${this.module}${pList.forceDelete}`),
          type: 'danger',
          url: `${this.module}/${pList.forceDelete}`,
        },
      ]
    },
    // 固定常驻按钮配置
    normalOperate() {
      const normalOperate = {
        edit: { show: true, p: `${this.module}${pList.edit}` },
        del: { show: true, p: `${this.module}${pList.del}`, url: `${this.module}${pList.del}` },
        status: { show: false, p: `${this.module}${pList.status}` },
        look: { show: true, p: `${this.module}${pList.look}` },
      }
      return this.makeOperates(normalOperate)
    },
    // 批量操作按钮配置
    multiOperate() {
      const multiOperate = {
        multiDel: { show: false, p: `${this.module}${pList.edit}` },
      }
      return this.makeOperates(multiOperate)
    },

    // showSummary() {
    //   return this.asyncSummary ? true : this.columns.some(({ v, disabled }) => !disabled && this.summary[v] !== undefined)
    // },
    // 是否为回收站
    isRecycle() {
      return this.$route.query.isRecycle !== undefined
    },
  },
  data() {
    return {
      loading: false, // 控制表格重新渲染（这样重新渲染无需处理表格逻辑）
      emptyText: '数据加载中...',
      columns: [],
      downFileName: '',
      data: [],
      summary: {}, // 合计
      filter: {}, // 搜索
      sort: this.defaultSort, // 排序
      page: {}, // 分页
      indexWidth: 0, // 序号列宽度
      maxSize: { height: undefined, width: undefined }, // 表格显示区域的最大尺寸
      selection: [], // 多选项
      operateWidth: 0,
      showSummary: true, // 是否展示合计
    }
  },
  methods: {
    keysToValue,
    makeOtherOperatesStatus,
    makeSortValue({ fixed }) {
      switch (fixed) {
        case 'left':
          return 0
        case 'right':
          return 2
        default:
          return 1
      }
    },
    // 获取表头
    async makeColumns() {
      const columns = this.actions_.columns_ || this.actions_.columns
      const result = { columns: [], downFileName: '' }
      switch (this.$w_fun.typeOf(columns)) {
        case 'object':
          result.columns = columns.list
          columns.downFileName && (result.downFileName = columns.downFileName)
          break
        case 'array':
          result.columns = columns
          break
        case 'string':
          const { data } = await post(
            '/column/get',
            {
              tab_name: columns,
              ...this.query,
              _recycle: this.isRecycle ? 1 : undefined,
            },
            undefined,
            false
          )
          result.columns = data.list
          data.downFileName && (result.downFileName = data.downFileName)
          this.$emit('getColumns', data)
          break
      }
      return result
    },
    // 设置表头
    setColumns({ columns, downFileName }) {
      this.columns = columns
      this.downFileName = downFileName
    },
    // 更新表头
    async getColumns() {
      this.setColumns(await this.makeColumns())
    },
    // 收集 columns 的 searchList
    makeOptionsMap(list = [], parentIndex = '', result = []) {
      return list.reduce((pre, { search, searchList, children = [] }, index) => {
        const newIndex = `${parentIndex}${index}`
        if (search !== false && searchList && this.$w_fun.typeOf(searchList) !== 'array') {
          const idx = pre.findIndex(
            ({ option }) => JSON.stringify(option) === JSON.stringify(searchList)
          )
          idx === -1
            ? pre.push({ key: [newIndex], option: searchList })
            : pre[idx].key.push(newIndex)
        }
        children.length && (pre = this.makeOptionsMap(children, newIndex + '.children.', pre))
        return pre
      }, result)
    },
    // 处理 columns 的 searchList
    setColumnsOptions() {
      this.makeOptionsMap(this.columns).forEach(async ({ key, option }) => {
        let list = []
        switch (this.$w_fun.typeOf(option)) {
          case 'string':
            list = await this.$store.dispatch('getColumnOptions', option)
            break
          case 'object':
            const { url = '', method = 'post', data = {}, key = 'list' } = option
            list = keysToValue((await post(url, data, {}, false, false)).data, key)
            break
        }
        key.forEach(k => {
          const currentRow = keysToValue(this.columns, k)
          this.$set(currentRow, 'searchList', list)
        })
        // 替换列数据
        this.replaceIndex(key)
      })
    },
    makeReplaceIndex(list = this.columns, parentIndex = '') {
      return list.reduce((pre, { search, searchList, replace, children = [] }, index) => {
        const newIndex = `${parentIndex}${index}`
        search !== false &&
          this.$w_fun.typeOf(searchList) === 'array' &&
          searchList.length &&
          replace &&
          pre.push(newIndex)
        children.length &&
          (pre = [...pre, ...this.makeReplaceIndex(children, newIndex + '.children.')])
        return pre
      }, [])
    },
    // 根据表头搜索数据替换对应列的数据
    replaceIndex(columnIndexs = this.makeReplaceIndex(), list = this.data) {
      list.forEach((row, index) => {
        columnIndexs.forEach(columnIndex => {
          const column = keysToValue(this.columns, columnIndex)
          if (
            column.search === false ||
            !column.replace ||
            this.$w_fun.typeOf(column.searchList) !== 'array' ||
            column.searchList.length === 0
          ) {
            this.$set(list[index], `${column.v}_replace`, '\\')
            return
          }
          const value = keysToValue(row, column.v)
          switch (this.$w_fun.typeOf(value)) {
            case 'array':
              this.$set(
                list[index],
                `${column.v}_replace`,
                value
                  .map(v => findTreeParents(column.searchList, v, 'label').pop())
                  .filter(i => i)
                  .join(' ') || '\\'
              )
              break
            default:
              this.$set(
                list[index],
                `${column.v}_replace`,
                findTreeParents(column.searchList, value, 'label').pop() || '\\'
              )
              break
          }
          // 如果是树状数据，向下递归替换
          row.children && row.children.length && this.replaceIndex([columnIndex], row.children)
        })
      })
    },
    // 递归设置序号 tableIndex，后端返回数据中应避免使用此字段
    setIndex(data = [], index = '', level = 0, length = 0) {
      const list = data.map((item, idx) => {
        const newIndex = `${index}${idx + 1}`
        const newLength = newIndex.length * 8 - level * 4
        newLength > length && (length = newLength)
        const { list, length: cLength } = this.setIndex(
          item.children,
          `${newIndex}.`,
          level + 1,
          length
        )
        cLength > length && (length = cLength)
        return { ...item, tableIndex: newIndex, children: list }
      })
      return { list, length }
    },
    // 获取数据
    async makeList(loading = false) {
      const index = this.actions_.index_ || this.actions_.index
      const result = {
        list: [],
        summary: false,
        indexWidth: this.selectable ? 40 : this.actions_.sort ? 50 : 40,
      }
      switch (this.$w_fun.typeOf(index)) {
        case 'object':
          result.list = index.list
          index.summary && (result.summary = index.summary)
          break
        case 'array':
          result.list = index
          break
        case 'string':
          const { data } = await post(
            index,
            {
              ...this.page,
              ...this.filter,
              sort: this.sort,
              ...this.query,
              _recycle: this.isRecycle ? 1 : undefined,
              _summary: this.asyncSummary ? 0 : 1
            },
            undefined,
            loading
          )
          result.list = data.list
          if (data.summary) {
            result.summary = data.summary
          } else {
            if (!this.asyncSummary) {
              this.showSummary = false
            }
          }
          this.$emit('getList', data)
          break
      }
      return result
    },
    makeSummary() {
      const index = this.actions_.index_ || this.actions_.index
      switch (this.$w_fun.typeOf(index)) {
        case 'object':
          index.summary && (this.summary = index.summary)
          if (index.total) {
            this.$set(this.page, 'total', index.total)
          }
          break
        case 'string':
          post(
            index,
            {
              ...this.page,
              ...this.filter,
              ...this.query,
              _recycle: this.isRecycle ? 1 : undefined,
              _summary: 1,
            },
            undefined,
            false
          ).then(res => {
            if (res.data.summary) {
              this.summary = res.data.summary
            } else {
              this.showSummary = false
            }
            if (res.data.list) {
              this.$set(this.page, 'total', res.data.list)
            }
          })
          break
      }
    },
    // 设置数据
    async setList({ list, summary, indexWidth }) {
      const { list: newData, length } = this.setIndex(this.pagination ? list.data : list)
      this.indexWidth = length > indexWidth - 4 ? length + 4 : indexWidth
      this.page = this.pagination
        ? { total: list.total, limit: list.per_page, page: list.current_page }
        : {}
      if(summary) {
        this.summary = summary
      } else {
        if (this.asyncSummary) {
          this.makeSummary()
        }
      }
      this.data = newData
      this.setRouteQuery()
    },
    // 更新数据
    async getList(loading) {
      this.setList(await this.makeList(loading))
      this.replaceIndex()
      this.$nextTick(
        () => (this.operateWidth = Math.ceil(this.$refs.operateWidth.getOperateWidth()))
      )
    },
    setOperateWidth() {
      this.$nextTick(
        () => (this.operateWidth = Math.ceil(this.$refs.operateWidth.getOperateWidth()))
      )
    },
    // 刷新表格
    async onRefresh() {
      this.emptyText = '数据加载中...'
      this.data = []
      const { sort } = this.$route.query
      this.sort = sort || this.defaultSort
      this.page = { ...this.defaultPage_ }
      this.filter = { ...this.defaultFilter_ }
      this.getMaxSize()
      const [columns, list] = await Promise.all([this.makeColumns(), this.makeList()])
      this.loading = true
      await this.$nextTick()
      this.setColumns(columns)
      this.setList(list)
      this.replaceIndex()
      this.setColumnsOptions()
      await this.$nextTick()
      this.operateWidth = this.$refs.operateWidth.getOperateWidth()
      this.loading = false
      this.emptyText = '暂无数据'
      this.$emit('afterRefresh')
      await this.$nextTick()
      this.actions_.sort && this.rowDrop()
    },
    // 修改路由
    setRouteQuery() {
      const filter = JSON.stringify(this.filter)
      const { page = 1, limit = 50, total = 0 } = this.page
      const obj = {
        sort: this.sort,
        page: `${page},${limit},${total}`,
        filter: filter,
      }
      if (obj.sort !== this.$route.query.sort || obj.page !== this.$route.query.page || obj.filter !== this.$route.query.filter) {
        this.$router.replace({
          query: {
            ...this.$route.query,
            ...obj
          },
        })
      }
    },
    // 排序
    onSortChange({ prop, order }) {
      switch (order) {
        case 'descending':
          this.sort = `-${prop}`
          break
        case 'ascending':
          this.sort = `+${prop}`
          break
        default:
          this.sort = undefined
          break
      }
      this.getList()
    },
    // 搜索
    onSearch(k, v, send = true, loading = false) {
      this.filter[k] = v
      this.$emit('onSearch', k, v)
      if (!send) return
      this.page.page = 1
      this.$nextTick(() => {
        this.getList(loading)
      })
    },
    // 分页
    onPagination(page) {
      this.$emit('pagination', page)
      this.getList()
    },
    // 获取表格可占用区域尺寸
    getMaxSize(other = 25) {
      const windowHeight = window.innerHeight // 窗口高度
      if (!this.$refs.tableBody) return
      this.$nextTick(() => {
        const tableTop = this.$refs.tableBody.getBoundingClientRect().top // 表格最高位置
        const footerHeight = this.$refs.tableFooter ? this.$refs.tableFooter.clientHeight : 0
        this.maxSize = {
          height: Math.floor(windowHeight - tableTop - footerHeight - other),
          width: Math.floor(this.$refs.tableBody.clientWidth),
        }
      })
    },
    // 合计
    summaryMethod() {
      if (!this.columns.some(({ v, disabled }) => !disabled && this.summary[v] !== undefined)) {
        return false
      }
      const makeColumns = (list = []) =>
        list.reduce((pre, { v, disabled, children = [] }) => {
          if (disabled) return pre
          children.length
            ? (pre = pre.concat(makeColumns(children)))
            : pre.push({
                label: this.summary.hasOwnProperty(v) ? this.summary[v] : '',
                color: this.summary.hasOwnProperty(v + '_color') ? this.summary[v + '_color'] : '',
              })
          return pre
        }, [])
      return [{ label: '合计', color: '' }].concat(makeColumns(this.columns))
    },
    // 行拖拽
    rowDrop() {
      const table = this.$refs.table.$el.querySelector('.el-table__body-wrapper tbody')
      Sortable.create(table, {
        ghostClass: 'sortable-ghost',
        handle: '.table-index-sort div',
        animation: 300,
        onEnd: async ({ newIndex, oldIndex }) => {
          if (newIndex !== oldIndex) {
            await post(
              this.actions_.sort_ || this.actions_.sort,
              {
                ...this.query,
                [this.primaryKey]: this.data[oldIndex][this.primaryKey],
                up: newIndex < oldIndex,
                step: Math.abs(newIndex - oldIndex),
              },
              undefined,
              false
            )
            this.getList()
          }
        },
      })
    },
    // 处理操作按钮配置
    makeOperates(operates) {
      return Object.keys(operates).reduce((pre, k) => {
        switch (this.operates[k]) {
          case false:
            break
          case undefined:
            operates[k].show && hasPermission(operates[k].p) && (pre[k] = { show: true })
            break
          case true:
            hasPermission(operates[k].p) && (pre[k] = { show: true, url: operates[k].url || '' })
            break
          default:
            const p = this.operates[k].hasOwnProperty('p') ? this.operates[k].p : operates[k].p
            p
              ? hasPermission(p) && (pre[k] = { show: true, ...this.operates[k] })
              : (pre[k] = { show: true, ...this.operates[k] })
            break
        }
        return pre
      }, {})
    },
    changeStatus(row, column) {
      const action = `${this.module}/${column.v}`
      if (this.module) {
        post(action, {
          ...this.query,
          [this.primaryKey]: row[this.primaryKey],
          [column.v]: row[column.v],
        })
      }
    }
  },
  mounted() {
    window.addEventListener(
      'resize',
      debounce(() => this.getMaxSize(), 500)
    )
  },
  beforeDestroy() {
    this.observer && this.observer.disconnect()
  },
  destroyed() {
    window.removeEventListener(
      'resize',
      debounce(() => this.getMaxSize(), 500)
    )
  }
}
</script>

<style lang="less" scoped>
/deep/ .el-table {
  .el-table__body-wrapper {
    background-color: #fff;
    .el-table__body {
      padding-bottom: 0.1px;
    }
    .el-table__empty-block {
      width: 100% !important;
      position: sticky;
      left: 0;
    }
  }
  .current-row > td,
  .hover-row > td {
    background-color: #f0faff !important;
  }
  .el-table__cell .cell {
    padding: 0 2px;
  }
  .cell.el-tooltip {
    min-width: auto;
  }
  // 排序按钮
  .caret-wrapper {
    height: 1.2em;
    width: 0.9em;

    .sort-caret {
      left: auto;
      right: 0;
    }

    .ascending {
      top: -0.4em;
    }

    .descending {
      bottom: -0.15em;
    }
  }
  // 展开/折叠 按钮
  .el-table__expand-icon {
    width: auto;
    margin-right: 0;
  }

  .operates {
    display: flex;
    align-items: center;
    > :not(:first-child) {
      margin-left: 4px;
    }
  }

  th {
    overflow: visible;
  }

  // 为常规单元格添加背景色
  .el-table__row:not(.el-table__row--striped) td {
    background-color: #fff;
  }

  // 固定列悬浮时的阴影
  .is-edge-left-column::after,
  .is-edge-right-column::after {
    position: absolute;
    top: 0;
    bottom: -1px;
    width: 10px;
    content: '';
    pointer-events: none;
  }
  .is-scrolling-middle,
  .is-scrolling-right {
    .is-edge-left-column::after {
      box-shadow: inset 10px 0 10px -10px rgba(0, 0, 0, 0.3);
      right: -10px;
    }
  }
  .is-scrolling-middle,
  .is-scrolling-left {
    .is-edge-right-column::after {
      box-shadow: inset -10px 0 10px -10px rgba(0, 0, 0, 0.3);
      left: -10px;
    }
  }

  .el-table__row.current-row,
  .el-table__row.hover-row {
    td {
      background-color: #ddf5f0 !important;
    }
  }
}
</style>
