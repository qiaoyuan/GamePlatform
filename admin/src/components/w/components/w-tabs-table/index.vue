<template>
  <div class="flex-1 dp-f flex-d-c">
    <div
      class="dp-f justify-content-between align-items-center pb5"
      :style="isCollection ? 'padding-left: 18px;position: relative;' : ''"
    >
      <!-- 左侧导航按钮 -->
      <div class="dp-f justify-content-between align-items-center">
        <el-button
          type="text"
          icon="el-icon-arrow-left"
          :size="size"
          style="font-size: 20px"
          @click="$router.back()"
          v-if="isRecycle"
        ></el-button>
        <w-tabs v-model="currentTab" :tabsList="tabsList" type="radios" />
      </div>
      <!-- 右侧操作按钮 -->
      <div class="header-operate dp-f align-items-center flex-1 justify-content-between">
        <div>
          <el-tooltip v-for="search in currentSearchList" :key="search.key">
            <el-tag
              :key="search.key"
              closable
              disable-transitions
              class="df-search-list-tag pointer"
              size="small"
              @click="removeSearch(search)"
              @close="removeSearch(search)"
            >
              {{ search.label }}
            </el-tag>
            <template #content> {{ currentSearchLabel(search) }} </template>
          </el-tooltip>
        </div>
        <div>
          <w-input
            v-if="k !== false"
            v-model="kValue"
            :clearable="true"
            :placeholder="kPlaceholder"
            style="width: 200px"
            @keydown.enter.native="onSearchK"
            @clear="onSearchK"
            class="mr20"
          >
            <template #append>
              <i class="el-icon-search cursor" @click="onSearchK" />
            </template>
          </w-input>
          <slot name="headerOperate" />
          <ElButtonGroup>
            <!-- 常规按钮 -->
            <template v-for="(item, key, index) in operates_">
              <template v-if="Object.keys(operates_).length === 1 && !layout.show">
                <ElButton
                  :key="`${key}-`"
                  :disabled="item.disabled"
                  :type="item.type"
                  :size="size"
                  :class="'normal-button-' + size"
                  @click="onClick(key)"
                >
                  {{ item.tip }}
                </ElButton>
              </template>
              <template v-else>
                <ElButton
                  v-tabshover
                  :key="`${key}+`"
                  :disabled="item.disabled"
                  :tip="item.tip"
                  :tip-icon="item.tipIcon"
                  :type="item.type"
                  :size="size"
                  :class="'normal-button-' + size"
                  @click="onClick(key)"
                >
                  <template v-if="index === 0"> {{ item.tip }}</template>
                  <i v-else :class="`el-icon-${item.tipIcon}`" />
                </ElButton>
              </template>
            </template>
            <!-- 排版按钮 -->
            <template v-if="layout.show && !isRecycle">
              <ElButton
                v-if="Object.keys(operates_).length && !isRecycle"
                v-tabshover
                :key="`${currentTab}+`"
                :disabled="layout.disabled"
                :tip="layout.tip"
                :tip-icon="layout.tipIcon"
                :type="layout.type"
                :size="size"
                :class="'normal-button-' + size"
                @click="openLayout"
              >
                <i :class="`el-icon-${layout.tipIcon}`" />
              </ElButton>
              <ElButton
                v-else
                :key="`${currentTab}-`"
                :disabled="layout.disabled"
                :type="layout.type"
                :size="size"
                :class="'normal-button-' + size"
                @click="openLayout"
              >
                {{ layout.tip }}
              </ElButton>
            </template>
          </ElButtonGroup>
        </div>
      </div>
      <i
        v-if="isCollection"
        @click="goBack"
        class="el-icon-arrow-left"
        style="position: absolute; left: 0; cursor: pointer; font-size: 16px"
      ></i>
    </div>
    <!-- 导入 -->
    <w-import :import="operates_.import && operates_.import.url" :form="operates_.import && operates_.import.form || {}" :timeout="operates_.import && operates_.import.timeout || 60000" ref="importDialog" />
    <!-- 导出 -->
    <ColumnConfig
      ref="columnConfig"
      :column="typeof actions_.columns === 'string' ? actions_.columns : ''"
      :query="query_"
      :columnOptionList="columnOptionList"
      @set="onSet"
      @close="onRefresh"
    />
    <slot v-if="!loading">
      <w-table
        ref="table"
        v-bind="{ ...$attrs, ...$props }"
        :is-recycle="isRecycle"
        v-on="$listeners"
        :query="query_"
        @afterRefresh="afterRefresh"
      >
        <template v-for="(_slot, slotName) in $scopedSlots" #[slotName]="props">
          <slot :name="slotName" v-bind="props" />
        </template>
      </w-table>
    </slot>
  </div>
</template>

<script>
import { post } from '@/libs/request'
import { download } from '@/utils/download'
import { queryToObj } from '@/utils/w'
import { hasPermission } from '@/directive/w/directive/p'
import { pList } from '@/utils/wData'
import ColumnConfig from './components/columnConfig/index.vue'
import { typeOf } from "@/libs/libs";

export default {
  name: 'wTabsTable',
  inheritAttrs: false,
  components: { ColumnConfig },
  props: {
    // 判断当前是否是回收站，回收站的话要展示返回按钮
    isCollection: { type: Boolean, default: false },
    query: { type: Object, default: () => ({}) },
    baseUrl: { type: String, default: '' },
    module: { type: String, default: '' },
    actions: { type: Object, default: () => ({}) },
    operates: { type: Object, default: () => ({}) },
    k: { type: [String, Boolean], default: false },
    kPlaceholder: { type: String, default: '请输入关键词' },
    customImport: { type: Boolean, default: false },
    // 可复制的表头
    columnOptionList: { type: Array },
    changeTab: { type: Function },
    tabKey: { type: String, default: 'tab' },
  },
  computed: {
    query_() {
      return {
        ...this.query,
        [this.tabKey]: this.tabKey !== 'tab' && this.currentTab === '-1' ? undefined : this.currentTab,
        [this.k_]: this.kValue
      }
    },
    actions_() {
      if (!this.module && !this.baseUrl) return this.actions
      const actions = { tabs: false, total: false, columns: true, index: true, sort: false }
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
    operates_() {
      // recycle当前列表页是否需要回收站，暂时先放在这里，后期需求变更再做修改
      const operates = { add: true, import: false, export: false, refresh: true, full: true, size: true, recycle: false, }
      const attrs = {
        add: { p: `${this.module}${pList.add}`, tip: '新增', tipIcon: 'plus', type: 'primary' },
        import: { p: `${this.actions_.index}${pList.import}`, tip: '导入', tipIcon: 'upload2' },
        export: { p: `${this.actions_.index}${pList.export}`, tip: '导出', tipIcon: 'download' },
        refresh: { tip: '刷新', tipIcon: 'refresh-right' },
        full: { tip: '全屏', tipIcon: 'full-screen', type: this.full ? 'primary' : '' },
        size: { tip: '尺寸', tipIcon: this.isMini ? 'zoom-in' : 'zoom-out', type: this.isMini ? '' : 'primary' },
        recycle: { p: `${this.actions_.index}${pList.recycle}`, tip: '回收站', tipIcon: 'delete' },
      }
      if (this.isRecycle) {
        delete operates.add
        delete operates.import
        delete operates.export
        delete operates.recycle
      }
      return Object.keys(operates).reduce((pre, k) => {
        switch (this.operates[k]) {
          case false:
            break
          case undefined:
            operates[k] && (!attrs[k].p || hasPermission(attrs[k].p)) && (pre[k] = attrs[k])
            break
          case true:
            (!attrs[k].p || hasPermission(attrs[k].p)) && (pre[k] = attrs[k])
            break
          default:
            const p = this.operates[k].hasOwnProperty('p') ? this.operates[k].p : attrs[k].p
            p
              ? hasPermission(p) && (pre[k] = { ...attrs[k], ...this.operates[k] })
              : (pre[k] = { ...attrs[k], ...this.operates[k] })
            break
        }
        return pre
      }, {})
    },
    layout() {
      const layout = {
        show: true,
        p: `${this.actions_.index}${pList.layout}`,
        tip: '排版',
        tipIcon: 's-operation',
      }
      const layout_ = this.operates.layout
      switch (layout_) {
        case false:
          return { show: false }
        case undefined:
          return layout
        case true:
          return layout
        default:
          return { ...layout, ...layout_ }
      }
    },
    exportQuerys() {
      const [tableUrl, query] = (this.actions_.index || '').split('?')
      const exportBtn = this.operates_.export
      return {
        tableUrl,
        tableQuery: { ...queryToObj(query), ...this.query_ },
        module: exportBtn ? exportBtn.module : undefined,
      }
    },
    full: {
      get() {
        return this.$store.state.app.isTaskListFullScreen
      },
      set(v) {
        this.$store.commit('toggleFullScreenState', v)
      },
    },
    size: {
      get() {
        return this.$store.getters['size']
      },
      set(val) {
        this.$store.dispatch('setSize', val)
      },
    },
    isMini() {
      return this.size === 'mini'
    },
  },
  data() {
    return {
      settingField: false,
      tabsList: [],
      currentTab: '',
      loading: false,
      k_: 'k',
      kValue: undefined,
      isRecycle: false,
      wradio: '',
      currentSearchList: [],
    }
  },
  watch: {
    'actions_.tabs': {
      immediate: true,
      handler(v) {
        v && this.getTabs()
      },
    },
    async currentTab(v) {
      this.$emit('changeTab', v)
      this.changeTab && (await this.changeTab())
      this.$nextTick(() => this.onRefresh())
    },
    k: {
      immediate: true,
      handler(v) {
        if (typeOf(v) === 'string' && v) {
          this.k_ = v
        }
      },
    },
    // 判断当前是不是回收站
    '$route.query.isRecycle': {
      immediate: true,
      handler(v, ov) {
        if (v !== undefined) {
          this.isRecycle = true
          if (ov === undefined) this.onRefresh()
        } else {
          this.isRecycle = false
          if (ov !== undefined) this.onRefresh()
        }
        this.wradio = this.$route.query.wradio
        this.getTabs()
      },
    },
    '$route.query.filter'() {
      this.setCurrentSearchList()
    },
  },
  methods: {
    // 查看当前搜索内容
    currentSearchLabel({ key }) {
      const tableColumn = this.$refs.table.$refs.tableColumn.find(
        ({ column: { search, v } }) => (search || v) === key
      )
      if (!tableColumn.$refs.headerSearch || !tableColumn.$refs.headerSearch.$refs.search) {
        const filter = this.$route.query.filter ? JSON.parse(this.$route.query.filter) : {}
        const searchKey = tableColumn.column.search || tableColumn.column.v
        const filterKey = Object.keys(filter).find(
          k => k.slice(0, k.lastIndexOf('_')) === searchKey
        )
        const value = filterKey ? filter[filterKey] : ''
        if (this.$w_fun.typeOf(value) === 'array') {
          return value.map(v => (['', undefined].includes(v) ? '-' : v)).join(' ~ ')
        }
        return value
      }
      const searchRef = tableColumn.$refs.headerSearch.$refs.search
      const {
        value,
        column: { searchType },
      } = searchRef
      if (searchType === 'tree') {
        return searchRef.$refs.tree
          .getCheckedNodes(false, true)
          .map(({ label }) => label)
          .join(' / ')
      }
      if (['multiple', 'radio'].includes(searchType)) {
        return searchRef.$refs.tree
          .getCheckedNodes(true)
          .map(({ label }) => label)
          .join(' / ')
      }
      if (searchType === 'remote') {
        if (this.$w_fun.typeOf(value) === 'array') {
          return value
            .map(_value => {
              const item = searchRef.searchList.find(({ value: v }) => v === _value)
              return item && item.label
            })
            .filter(i => i)
            .join(' / ')
        }
        const item = searchRef.searchList.find(({ value: _value }) => value === _value)
        return item ? item.label : ''
      }
      if (this.$w_fun.typeOf(value) === 'array') {
        return value.map(v => (['', undefined].includes(v) ? '-' : v)).join(' ~ ')
      }
      return value
    },
    // 设置当前搜索内容
    setCurrentSearchList() {
      const v = this.$route.query.filter
      if (!v) return (this.currentSearchList = [])
      const filterKey = Object.keys(JSON.parse(v)).map(k => k.slice(0, k.lastIndexOf('_')))
      if (!this.$refs.table.$refs.tableColumn) return (this.currentSearchList = [])
      this.currentSearchList = this.$refs.table.$refs.tableColumn.reduce((pre, tableColumn) => {
        const {
          column: { label, search, v },
        } = tableColumn
        if (label === '日期') return pre
        const key = search || v
        if (!filterKey.includes(key)) return pre
        pre.push({ label, key })
        return pre
      }, [])
    },
    // 表格刷新完毕事件
    afterRefresh() {
      this.$nextTick(() => this.setCurrentSearchList())
    },
    // 重新搜索
    removeSearch({ key: currentKey }) {
      const filter = this.$route.query.filter ? JSON.parse(this.$route.query.filter) : {}
      this.$router.replace({
        query: {
          ...this.$route.query,
          filter: JSON.stringify(
            Object.keys(filter).reduce((pre, key) => {
              if (key.slice(0, key.lastIndexOf('_')) === currentKey) return pre
              pre[key] = filter[key]
              return pre
            }, {})
          ),
        },
      })
      this.$refs.table.onRefresh()
    },
    // 对于回收站页面要有返回按钮
    goBack() {
      if (this.$route.query.backUrl) {
        this.$router.push({
          path: this.$route.query.backUrl,
        })
      } else {
        this.$router.back()
      }
    },
    // 获取 radioTab
    async getTabs() {
      let tabs = this.actions_.tabs_ || this.actions_.tabs
      if (this.$w_fun.typeOf(tabs) === 'array') {
        const name = this.wradio || this.currentTab
        const tab = tabs.find(f => f.name + '' === name + '')
        const label = tab && tab.label
        this.tabsList = this.isRecycle ? [{ label: (label === '全部' ? '' : (label + '-')) + '回收站', name: name }] : tabs
        return
      }
      if (!tabs) {
        this.currentTab = '-1'
        return
      }
      const { data } = await post(tabs, this.query, undefined, false)
      this.tabsList = data.list || []
      this.$emit('getTabs', data)
      // 判断是否为回收站
      if (this.isRecycle) {
        const name = this.wradio || this.currentTab
        const tab = data.list.find(f => f.name + '' === name + '')
        const label = tab && tab.label
        this.tabsList = [{ label: label + '-回收站', name: name }]
      } else {
        const total = this.actions_.total_ || this.actions_.total
        if (!total) return
        const totalList = (await post(total, this.query, undefined, false)).data
        this.tabsList = this.tabsList.map((item, index) => ({ ...item, total: totalList[index] }))
      }
    },
    // 更新数据
    getList() {
      this.$refs.table && this.$refs.table.getList()
    },
    // 刷新表格
    onRefresh() {
      this.$refs.table && this.$refs.table.onRefresh()
    },
    // 点击操作按钮事件
    onClick(key) {
      switch (key) {
        case 'add':
          const url = this.operates_[key].url
          url && this.$router.push(url)
          return this.$emit('add')
        case 'import':
          // hzy 这个地方因为新组建没有导入功能，暂时使用老组建的导入，等新的导入功能开发完毕，这一块再进行更换
          return this.customImport ? this.$emit('import') : (this.$refs.importDialog.visible = true)
        case 'export':
          download('export/run', {
            ...this.$refs.table.filter,
            _api: this.$refs.table.actions_.index,
            _export: 1
          })
          return
        case 'recycle':
          if (this.operates['recycle'].autoLink) {
            this.$router.push({
              query: { isRecycle: 1 },
            })
          }
          return this.$emit('recycle')
        case 'refresh':
          this.$store.dispatch('cleanColumnOptions')
          const query = { ...this.$route.query }
          if (query.filter || query.page || query.sort) {
            delete query.sort
            delete query.page
            delete query.filter
            this.$router.replace({ query })
          }
          this.$nextTick(() => this.onRefresh())
          break
        case 'full':
          this.full = !this.full
          break
        case 'size':
          this.size = this.isMini ? 'small' : 'mini'
          this.$nextTick(
            () => this.$refs.table && this.$refs.table.setOperateWidth()
          )
          break

      }
    },
    openLayout() {
      // 获取当前表格列实际渲染宽度 el-table__header-wrapper
      const col = document.querySelectorAll('.el-table__header-wrapper colgroup col')
      const w = []
      col.forEach(item => {
        w.push(item.width)
      })
      w.shift()
      this.$refs.columnConfig.open(w)
    },
    onSet(v) {
      this.$refs.table.columns = v.map(item => ({
        ...this.$refs.table.columns.find(itm => item.v === itm.v),
        ...item,
      }))
    },
    // 全局搜索事件
    onSearchK() {
      this.$emit('searchK', this.kValue)
      this.$nextTick(() => this.getList())
    },
  },
}
</script>

<style lang="less" scoped>
.w-badge > i {
  background-color: #f46d6e;
}

/deep/ .el-input-group__append {
  background: none;
}
</style>

<style lang="less">
.df-search-list-tag {
  margin-left: 5px;
}
</style>
