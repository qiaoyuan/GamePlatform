<template>
  <div class="app-container">
    <w-tabs-table
      ref="wTable"
      :module="module"
      :operates="operates"
      @add="onAdd"
      @edit="onEdit"
    />
    <w-dialog-form
      ref="wDialogForm"
      title="角色"
      :form="form"
      :action="formAction"
      :width="$w_fun.isMobile() ? '100%' : '60%'"
      @done="getList"
    />

    <el-dialog
      :visible.sync="dialogAdminVisible"
      :title="dialogTitle"
      center
      width="640px"
    >
      <el-transfer
        v-model="roleAdmins.admin_id"
        :filter-method="filterAdmins"
        :titles="['全部', '已选择']"
        :data="admins"
        :render-content="renderFunc"
        filter-placeholder="请输入ID或名字"
        filterable
      />
      <span slot="footer" class="dialog-footer">
        <el-button @click="dialogAdminVisible=false">取 消</el-button>
        <el-button type="primary" @click="updateAdmin">确 定</el-button>
      </span>
    </el-dialog>

    <permission ref="permissionDialog" />
  </div>
</template>

<script>
import Permission from './dialog/permission'

export default {
  name: 'RoleIndex',
  components: { Permission },
  data() {
    return {
      module: 'role',
      operates: {
        del: false,
        look: false,
        add: true,
        other: [
          { title: '权限管理', type: 'success', click: row => this.openDialog(row.id, 'permissionDialog') },
          { title: '成员管理', type: 'danger', click: row => this.openDialog(row.id) }
        ]
      },
      formAction: '',
      form: {},
      admins: [],
      dialogTitle: '成员管理',
      dialogAdminVisible: false,
      roleAdmins: {
        id: undefined,
        admin_id: []
      },
      renderFunc(h, option) {
        return <span>{ option.key } - { option.label }[{ option.status ? '正常' : '禁用' }]</span>
      }
    }
  },
  created() {
    this.$w_fun.post('admin/select').then(response => {
      this.admins = response.data.list.map( item => { return { label: item.label, key: item.value, status: item.status }})
    })
  },
  methods: {
    getList() {
      this.$refs['wTable'].getList()
    },
    onEdit(row) {
      this.setForm(row)
      this.formAction = `${this.module}/edit`
    },
    onAdd() {
      this.setForm({})
      this.formAction = `${this.module}/add`
    },
    setForm({ id, title, description }) {
      this.form = {
        id: { show: false, value: id },
        title: { label: '名称', value: title },
        description: { label: '描述', value: description, required: false },
      }
      this.$refs.wDialogForm.visible = true
    },
    // 弹出层
    openDialog(id, refName) {
      if (refName) {
        this.$refs[refName].getRoleAdminList(id)
      } else {
        this.dialogAdminVisible = true
        this.roleAdmins.id = id
        this.$w_fun.post('role/admin', { id: id }).then(response => {
          this.roleAdmins.admin_id = response.data.role.admins
          this.dialogTitle = '《' + response.data.role.title + '》成员管理'
        })
      }
    },
    filterAdmins(query, item) {
      return item.label.indexOf(query) > -1 || String(item.key).indexOf(query) > -1
    },
    updateAdmin() {
      this.$w_fun.post('role/updateAdmin', this.roleAdmins).then(response => {
        this.$message({
          message: '操作成功',
          type: 'success'
        })
        this.dialogAdminVisible = false
      })
    }
  }
}
</script>
