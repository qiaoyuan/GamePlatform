<template>
  <div class="w100 p25">
    <el-button type="primary" @click="onAdd">添加</el-button>
    <w-table
      ref="wTable"
      :actions="actions"
      :operates="operates"
      :pagination="false"
      @edit="onEdit"
      @del="onDel"
    >
    </w-table>
    <w-dialog-form
      ref="wDialogForm"
      title="Banner"
      v-model="dialogFormVisible"
      :form="form"
      :width="$w_fun.isMobile() ? '100%' : '60%'"
      @submit="done"
    />
    <el-button
      class="filter-item"
      type="primary"
      icon="el-icon-check"
      @click="handleSave"
    >保存</el-button>
  </div>
</template>

<script>
export default {
  name: 'ConfigBanner',
  props: {
    name: {
      type: String,
      required: true
    },
    group: {
      type: Number,
      default: () => {
        return 2
      }
    },
    title: {
      type: String,
      required: true
    }
  },
  data() {
    return {
      operates: {
        del: true,
        look: false,
        edit: true,
      },
      actions: {
        index: [],
        sort: (row, newIndex, oldIndex) => {
          this.actions.index.splice(newIndex, 0, ...this.actions.index.splice(oldIndex, 1))
          this.actions.index = JSON.parse(JSON.stringify(this.actions.index))
          this.refresh()
        },
        columns: [
          { v: 'image', label: '图片', search: false, sort: false, render: 'image' },
          { v: 'title', label: '标题', search: false, sort: false },
          { v: 'url', label: '跳转地址', search: false, sort: false },
          { v: 'status', label: '状态', render: 'status', searchType: false, sort: false },
        ]
      },
      dialogFormVisible: false,
      form: {},
      index: -1
    }
  },
  watch: {
    name(val, old) {
      this.getList()
    }
  },
  created() {
    this.getList()
  },
  methods: {
    getList() {
      this.listLoading = true
      this.$w_fun.post('config/get', { name: this.name }).then(response => {
        if (response.data) {
          this.actions.index = response.data.detail ? response.data.detail.value : []
        }
        this.listLoading = false
        this.refresh()
      })
    },
    refresh() {
      this.$refs.wTable && this.$refs.wTable.onRefresh()
    },
    handleSave() {
      this.$w_fun.post('config/save', { value: this.actions.index, name: this.name, group: this.group, title: this.title }).then(response => {
        this.getList()
      })
    },
    setForm({ image, title, url, status }) {
      this.form = {
        title: {
          label: '标题',
          value: title,
          max: 60
        },
        image: {
          label: '图片',
          value: [{ path: image, url: image }],
          formType: 'upload',
          attrs: {
            accept: '.jpg,.png,.jpeg',
          },
        },
        url: {
          label: '跳转地址',
          value: url,
        },
        status: {
          label: '状态',
          value: status === undefined ? 1 : status,
          formType: 'status',
        },
      }
    },
    onEdit(row, index) {
      this.index = index
      this.setForm(row)
      this.$refs.wDialogForm.visible = true
    },
    onAdd() {
      this.index = -1
      this.setForm({})
      this.$refs.wDialogForm.visible = true
    },
    onDel(row, index) {
      this.actions.index.splice(index, 1)
      this.refresh()
    },
    done(data) {
      if (this.index === -1) {
        this.actions.index.push({ ...data, id: this.actions.index.length + 1 })
      } else {
        this.actions.index.splice(this.index, 1, { ...data })
      }
      this.refresh()
    }
  }
}
</script>
