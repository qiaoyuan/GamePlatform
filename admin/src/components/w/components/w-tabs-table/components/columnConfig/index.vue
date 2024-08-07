<template>
  <w-dialog
    ref="dialog"
    v-model="visible"
    v-dialogDrag
    title="表头排版"
    width="1100px"
    :before-close="onClose"
  >
    <el-form ref="form" :model="model" :size="size" class="dialog-form" :disabled="!edit">
      <el-table
        ref="table"
        :data="model.rule"
        row-key="v"
        :stripe="true"
        :border="true"
        :size="size"
      >
        <el-table-column v-show="edit" align="center" min-width="60">
          <template #header>
            排序
            <el-tooltip content="用户自行拖动对应列的图标进行排序" placement="top">
              <el-link type="primary" :underline="false"> <i class="el-icon-info" /> </el-link>
            </el-tooltip>
          </template>
          <template #default="{ row, $index }">
            <i v-if="!row.fixed" :class="[getIcon($index), 'drag-btn', edit ? 'cursor' : '']" />
          </template>
        </el-table-column>
        <TableColumn
          prop="label"
          label="表头标题"
          content="表头名称"
          minWidth="100"
          :rules="rules.label"
        >
          <template #default="{ row, $index }">
            <w-input v-model="row.label" />
          </template>
        </TableColumn>
        <TableColumn
          prop="headerTooltip"
          label="注释"
          content="表头标题的简要说明，填写则显示，不填写则不显示"
          minWidth="240"
        >
          <template #default="{ row, $index }">
            <el-input
              :ref="`headerTooltipRef-${$index}`"
              v-model="row.headerTooltip"
              type="textarea"
              :size="size"
              :autosize="{ minRows: 1 }"
            />
          </template>
        </TableColumn>
        <TableColumn
          prop="minWidth"
          label="列最小宽度"
          content="默认为自适应调节列宽，用户可按需自行调节列宽值，若不填写，则使用默认值"
          minWidth="100"
        >
          <template #default="{ row }">
            <w-input v-model.number="row.minWidth" type="number" min="0" />
          </template>
        </TableColumn>
        <TableColumn
          prop="minWidth"
          label="默认宽度"
          content="当前表格默认列宽，供参考，不可编辑"
          minWidth="100"
        >
          <template #default="{ row }">
            <span :class="edit ? '' : 'grey'">{{ row.factWidth }}</span>
          </template>
        </TableColumn>
        <TableColumn prop="color" label="标题颜色" minWidth="80">
          <template #default="{ row, $index }">
            <el-color-picker v-model="row.color" :predefine="['#19aa8d', '#f44336', '#e6a23c']" />
          </template>
        </TableColumn>
        <TableColumn
          prop="align"
          label="内容对齐方式"
          content="用户可自行选择对齐方式，不选择时则使用系统默认方式"
          minWidth="120"
        >
          <template #default="{ row, $index }">
            <RadioGroup
              v-model="row.align"
              :isCancel="true"
              :disabled="!edit"
              :data="[
                { label: '左', value: 'left', name: 'align' + $index },
                { label: '中', value: 'center', name: 'align' + $index },
                { label: '右', value: 'right', name: 'align' + $index },
              ]"
            />
          </template>
        </TableColumn>
        <TableColumn
          prop="fixed"
          label="冻结方式"
          content="默认不固定，为保持良好的阅读体验，建议列固定总数不要超过五列"
          minWidth="100"
        >
          <template #default="{ row, $index }">
            <RadioGroup
              v-model="row.fixed"
              :data="[
                { label: '左', value: 'left', name: 'fixed' + $index },
                { label: '右', value: 'right', name: 'fixed' + $index },
              ]"
              :disabled="!edit || row.label === '备注'"
              @change="model.rule = sortRule(model.rule)"
            />
          </template>
        </TableColumn>
        <TableColumn
          prop="disabled"
          label="是否显示"
          content="开关开启时则显示对应列，开关关闭时则不显示对应列"
          minWidth="100"
        >
          <template #default="{ row }">
            <el-switch v-model="row.disabled" :active-value="false" :inactive-value="true" />
          </template>
        </TableColumn>
      </el-table>
    </el-form>
    <template #footer>
      <el-button
        v-show="edit === 0"
        :size="size"
        type="primary"
        @click="onEdit(true)"
      >
        编辑
      </el-button>
      <el-button v-if="edit" :size="size" type="primary" @click="onSave">
        保存
      </el-button>
      <el-tooltip
        content="重置除“表头标题”和“注释”以外的数据为初始值，且重置后列排序为初始排序方式"
        placement="top"
      >
        <el-button v-show="edit" v-p="p" :size="size" type="danger" @click="onReset">
          重置
        </el-button>
      </el-tooltip>
      <el-button v-if="edit" :size="size" @click="onQuit"> 退出编辑 </el-button>
      <el-button :size="size" @click="onClose"> 关闭 </el-button>
      <el-button :size="size" @click="onClear"> 清除缓存 </el-button>
    </template>
  </w-dialog>
</template>

<script>
import { post } from '@/libs/request'
import { hasPermission } from '@/directive/w/directive/p'
import { queryToObj } from '@/utils/w'
import { debounce, cloneDeep } from 'lodash'
import Sortable from 'sortablejs'
import TableColumn from './components/tableColumn.vue'
import RadioGroup from './components/radioGroup.vue'
export default {
  name: 'columnsConfig',
  components: { TableColumn, RadioGroup },
  props: {
    column: { type: String, default: '' },
    query: { type: Object, default: () => ({}) },
    p: { type: [Boolean, String], default: '' },
    columnOptionList: { type: Array },
  },
  computed: {
    size() {
      return this.$store.getters['size']
    },
    column_() {
      return this.column.split('?')
    },
    tabName() {
      return this.column_[0]
    },
    tab() {
      return { ...queryToObj(this.column_[1]), ...this.query }
    },
  },
  data() {
    return {
      visible: false,
      sortable: undefined,
      isCancel: false,
      edit: 0, // 0 只读 1 普通 2 通用模板
      defaultRule: '',
      isRelated: true, // 若关联其他模板，则切换回自己模板时显示【使用】按钮
      model: { rule: [] },
      rules: {
        label: [
          { required: true, message: '名称不能为空' },
          { max: 12, message: '名称不能超过12个字符' },
        ],
        minWidth: [
          {
            validator: (r, v) =>
              v === '' || /^\d+$/.test(v)
                ? Promise.resolve()
                : Promise.reject('列最小宽度应为非负整数'),
          },
        ],
      },
      isFirst: true,
      factWidths: [], //默认实际渲染宽度
      radioTemplate: undefined,
    }
  },
  watch: {
    edit(v) {
      v ? this.rowDrop() : this.sortable.destroy()
    },
    'model.rule': {
      deep: true,
      handler: debounce(function (v) {
        !this.isFirst && this.$emit('set', v)
        this.isFirst = false
      }, 800),
    },
    async radioTemplate(v) {
      if (!v) {
        const { rule } = await this.getColumns({ is_edit: 0 })
        this.model.rule = rule
        return
      }
      const { tab_name, module } = this.columnOptionList.find(({ value }) => value === v)
      const {
        data: { list },
      } = await post('/column/get', { tab_name, module, tab: v }, undefined, false)
      const list_ = this.makeRule(list)
      this.model.rule = this.model.rule.map(rule => {
        const rule_ = list_.find(({ v }) => v === rule.v)
        if (rule_) rule_.disabled = rule.disabled
        return rule_ || rule
      })
    },
  },
  methods: {
    hasPermission,
    // 拖拽排序
    rowDrop() {
      const el = this.$refs.dialog.$el.querySelectorAll(
        '.el-table__body-wrapper > table > tbody'
      )[0]
      this.sortable = Sortable.create(el, {
        ghostClass: 'sortable-ghost',
        handle: '.drag-btn',
        setData: dataTransfer => dataTransfer.setData('Text', ''),
        onEnd: e => {
          const current = this.model.rule.find((item, index) => index === e.newIndex)
          if (current && current.fixed) {
            const items = e.from.getElementsByTagName(e.item.tagName)
            e.oldIndex > e.newIndex
              ? e.from.insertBefore(e.item, items[e.oldIndex + 1])
              : e.from.insertBefore(e.item, items[e.oldIndex])
            return
          }
          const targetRow = this.model.rule.splice(e.oldIndex, 1)[0]
          this.model.rule.splice(e.newIndex, 0, targetRow)
        },
      })
    },
    // 判断拖拽图标
    getIcon(i) {
      const prev = (this.model.rule[i - 1] || {}).fixed || i === 0
      // 若为备注则不可拖拽
      const lastIndex =
        this.model.rule[this.model.rule.length - 1].label === '备注'
          ? this.model.rule.length - 2
          : this.model.rule.length - 1
      const next = (this.model.rule[i + 1] || {}).fixed || i === lastIndex
      if (this.model.rule[i].label === '备注') return ''
      if (!prev && !next) return 'el-icon-sort'
      if (!prev && next) return 'el-icon-sort-up'
      if (prev && !next) return 'el-icon-sort-down'
      return 'el-icon-sort'
    },
    // 排序
    sortRule(data) {
      const sortNum = ({ fixed, label }) =>
        label === '备注' ? 3 : fixed === 'left' ? 0 : fixed === 'right' ? 2 : 1
      data.sort((a, b) => sortNum(a) - sortNum(b))
      return data
    },
    // 将请求数据 筛选 + 排序 至表格所需格式
    makeRule(data) {
      const { left, center, right, remark } = data.reduce(
        (
          pre,
          {
            v,
            headerTooltip = '',
            align = 'center',
            label = '',
            minWidth = '',
            disabled = false,
            fixed = undefined,
            factWidth = '',
            color,
          }
        ) => {
          pre[label === '备注' ? 'remark' : fixed || 'center'].push({
            v,
            headerTooltip,
            align,
            label,
            minWidth: minWidth === '' ? '' : Number(minWidth),
            disabled,
            fixed,
            factWidth,
            color,
          })
          return pre
        },
        { left: [], center: [], right: [], remark: [] }
      )
      return [...left, ...center, ...right, ...remark]
    },
    // 获取表头设置 和 模板id
    async getColumns(query = {}) {
      const {
        data: { list },
      } = await post(`/column/get`, {
        tab_name: this.tabName,
        ...this.tab,
        ...query,
      })
      list.forEach((v, i) => {
        if (v.disabled) {
          this.factWidths.splice(i, 0, '')
        }
        v.factWidth = this.factWidths[i]
      })
      return { rule: this.makeRule(list) }
    },
    // 打开弹窗
    async open(w) {
      this.radioTemplate = ''
      this.factWidths = w
      this.isFirst = true
      this.edit = 0
      const { rule } = await this.getColumns()
      this.model.rule = rule
      this.visible = true
      this.$nextTick(() =>
        Object.keys(this.$refs).forEach(
          ref => ref.startsWith('headerTooltipRef') && this.$refs[ref].resizeTextarea()
        )
      )
    },
    // 重置初始模板
    async onReset() {
      const { rule } = await this.getColumns({ is_code: 1 })
      this.model.rule = rule.map(item => {
        const { label, headerTooltip } = this.model.rule.find(({ v }) => v === item.v)
        return { ...item, label, headerTooltip }
      })
    },
    // 进入编辑模式
    async onEdit() {
      this.edit = 1
      this.defaultRule = JSON.stringify(this.model.rule)
    },
    // 判断表单是否保存
    async checkSave() {
      if (JSON.stringify(this.model.rule) === this.defaultRule) return
      try {
        await this.$confirm('还有未保存的内容，确认离开吗？', '提示', {
          type: 'warning',
          closeOnClickModal: false,
        })
        this.isCancel = false
      } catch (e) {
        this.isCancel = true
        this.template = this.lastTemplate
      }
    },
    // 保存模板
    async onSave() {
      const query = {
        rule: this.model.rule,
        tab_name: this.tabName,
        ...this.tab,
      }
      await post('/column/saveCommon', query)
      if (this.edit === 0) return
      this.isCancel = false
      this.edit = 0
    },
    // 退出编辑模式
    async onQuit() {
      try {
        await this.checkSave()
        if (this.isCancel) return
        this.model.rule = JSON.parse(this.defaultRule)
        this.edit = 0
      } catch (e) {}
    },
    // 关闭表格
    async onClose() {
      try {
        this.edit !== 0 && (await this.checkSave())
        if (this.isCancel) return
        this.$emit('close')
        this.visible = false
      } catch (e) {}
    },
    async onClear() {
      try {
        await post(`/column/refresh`, { tab_name: this.tabName, ...this.tab })
      } catch (e) {}
    },
  },
}
</script>

<style lang="less" scoped>
.dialog-form {
  margin: 0 -19px -19px;
}

.drag-btn {
  width: 100%;
  line-height: 24px;
}

/deep/ .el-form-item {
  margin: 0;

  .el-input__inner,
  .el-textarea__inner,
  .el-color-picker__trigger {
    border-radius: 0;
    &:not(:focus) {
      border: none;
      background-color: transparent !important;
    }

    &[type='number'] {
      padding-right: 0;
    }
  }
}

/deep/ .el-table {
  thead .el-table__cell {
    padding: 5px 0 !important;
    background-color: #f8f8f8;
  }

  tbody .el-table__cell {
    padding: 0 !important;
    .cell {
      padding: 0 !important;
    }
  }
}
.grey {
  color: #c0c4cc;
}

/deep/ .el-form-item__content {
  display: flex;
  align-items: center;
  justify-content: center;
}
</style>
