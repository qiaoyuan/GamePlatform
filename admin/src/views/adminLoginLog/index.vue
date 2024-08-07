<template>
  <div class="app-container">
    <w-tabs-table
      ref="wTable"
      :operates="operates"
      :module="module"
      k
      @add="onAdd"
      @edit="onEdit"
    >
    </w-tabs-table>
  </div>
</template>

<script>

export default {
  title: 'AdminLoginLogIndex',
  data() {
    return {
      module: 'adminLoginLog',
      operates: {
        del: false,
        look: false,
        add: false,
        edit: false,
        multiDel: false,
        // recycle: { autoLink: true },
      },
      formAction: '',
      form: {}
    }
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
    setForm({ id, ip, admin_id }) {
      this.form = {
        ip: { label: 'IP', value: ip },
        admin_id: {
          label: '人员',
          value: admin_id,
          formType: 'select',
          options: '/admin/select'
        },
      }
      if (id) {
        this.form.id = { show: false, value: id }
      }
      this.$refs.wDialogForm.visible = true
    },
    getForm() {
      return this.$refs.wDialogForm.$refs.form
    },
  }
}
</script>
