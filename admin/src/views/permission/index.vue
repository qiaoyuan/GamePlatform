<template>
  <div class="app-container">
    <w-tabs-table
      ref="wTable"
      :operates="operates"
      :tree-props="{ children: 'children', hasChildren: 'id' }"
      :lazy="true"
      :load="onLoad"
      :module="module"
      :pagination="false"
      @add="onAdd"
      @edit="onEdit"
    >
      <template #icon="{ row }">
        <i :class="row.icon"></i>
      </template>
    </w-tabs-table>
    <w-dialog-form
      ref="wDialogForm"
      title="权限"
      v-model="dialogFormVisible"
      :form="form"
      :action="formAction"
      :width="$w_fun.isMobile() ? '100%' : '60%'"
      @done="getList(true)"
    >
      <template #iconSlot="{ item, model }">
        <w-icon v-model="model.icon" />
      </template>
    </w-dialog-form>
  </div>
</template>

<script>

export default {
  name: 'PermissionIndex',
  data() {
    return {
      module: 'permission',
      operates: {
        del: { show: true, url: 'permission/delete' },
        look: false,
        add: true,
        edit: true,
        other: [
          { title: '新增', click: (row, index) => this.onAdd({ parent_id:  row.id }) }
        ],
        layout: false,
      },
      maps: {},
      form: {},
      nodeId: 0,
      dialogFormVisible: false,
      formAction: ''
    }
  },
  created() {
  },
  methods: {
    getList(node = false) {
      if (node) {
        const { tree, resolve } = this.maps[this.nodeId] || {}
        if (tree) {
          this.$w_fun.post('permission/index', { parent_id: this.nodeId }).then(
            ({
               data: { list },
             }) =>
              resolve(
                list.map((itm, idx) => ({ ...itm, tableIndex: `${tree.tableIndex}.${idx + 1}` }))
              )
          )
        } else {
          this.$refs.wTable.getList()
        }
      } else {
        this.$refs.wTable.getList()
      }
    },
    onLoad(tree, treeNode, resolve) {
      const pid = tree.id
      this.maps[pid] = { tree, treeNode, resolve }
      this.$w_fun.post(this.module + '/index', { parent_id: pid }).then(
        ({
           data: { list },
         }) =>
          resolve(list.map((itm, idx) => ({...itm, tableIndex: `${tree.tableIndex}.${idx + 1}`})))
      )
    },
    setForm({ id, parent_id, title, url, is_menu, is_hide, is_hide_sub, sort, icon }) {
      this.$refs.wDialogForm.visible = true
      this.form = {
        id: { show: false, value: id },
        parent_id: {
          label: '上级权限',
          value: parent_id,
          formType: 'treeRadioFree',
          options: 'permission/selectTree',
          required: false
        },
        title: { label: '名称', value: title },
        url: { label: 'URL', value: url },
        is_menu: { label: '菜单', value: is_menu, formType: 'boolean' },
        is_hide: { show: v => v.is_menu === 1, label: '隐藏', value: is_hide, formType: 'boolean' },
        is_hide_sub: { show: v => v.is_menu === 1, label: '隐藏子菜单', value: is_hide_sub, formType: 'boolean' },
        sort: {
          label: '排序',
          value: sort,
          formType: 'number',
          required: false,
          attrs: { min: 0 }
        },
        icon: {
          show: v => v.is_menu === 1,
          label: '图标',
          value: icon,
          formType: 'iconSlot',
          required: false
        }
      }
    },
    onAdd(row) {
      this.formAction = `${this.module}/add`
      this.nodeId = row ? row.parent_id : 0
      this.setForm(row || {})
    },
    onEdit(row) {
      this.nodeId = row.parent_id
      this.formAction = `${this.module}/edit`
      this.setForm(row)
    },
  }
}
</script>
