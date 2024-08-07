<template>
  <el-dialog
    :visible.sync="dialogVisible"
    title="权限管理"
    width="600"
  >
    <div class="permission-wrap">
      <el-tree ref="tree" :data="allPermission" :props="defaultProps" :default-checked-keys="permission" node-key="id" show-checkbox />
    </div>
    <span slot="footer" class="dialog-footer">
      <el-button @click="dialogVisible = false">取 消</el-button>
      <el-button type="primary" @click="updatepermission">确 定</el-button>
    </span>
  </el-dialog>
</template>

<script>
export default {
  name: 'Permission',
  data() {
    return {
      dialogVisible: false,
      allPermission: [],
      permission: [],
      defaultProps: {
        children: 'children',
        label: 'title'
      },
      roleId: undefined
    }
  },
  methods: {
    getRoleAdminList(roleId) {
      this.dialogVisible = true
      this.roleId = roleId
      this.$w_fun.post('role/permission', { role_id: roleId }).then(response => {
        this.allPermission = response.data.permissions
        this.permission = response.data.role.permissions
      })
    },
    updatepermission() {
      this.permission = this.$refs.tree.getCheckedKeys()
      this.$w_fun.post('role/updatePermission', { role_id: this.roleId, admin_permission_id: this.permission }).then(response => {
        this.dialogVisible = false
        this.$message({
          type: 'success',
          message: '更新成功!'
        })
      })
    }
  }
}
</script>

<style scoped>
  .permission-wrap{max-height: 500px;overflow-y: auto;}
</style>
