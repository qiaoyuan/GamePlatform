<template>
  <div class="w100 p25">
    <el-form ref="dataForm" :model="model" label-suffix="：" label-position="right" label-width="120px">
      <el-form-item :label="title">
        <el-input v-model="model.value" placeholder="请输入" v-bind="attrs" />
      </el-form-item>
    </el-form>
    <el-button
      :loading="loading"
      class="filter-item"
      type="primary"
      icon="el-icon-check"
      @click="handleSave"
    >保存</el-button>
  </div>
</template>

<script>
export default {
  name: 'ConfigInput',
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
    },
    attrs: {
      type: Object,
      default: () => {
        return {}
      }
    }
  },
  data() {
    return {
      model: { value: '' },
      loading: false
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
      this.loading = true
      this.$w_fun.post('config/get', { name: this.name }).then(response => {
        if (response.data) {
          this.model.value = response.data.detail ? response.data.detail.value : ''
        }
        this.loading = false
      }).catch(() => {
        this.loading = false
      })
    },
    updateData() {
      if (this.model) {
        this.loading = true
        this.$w_fun.post('config/save', { value: this.model.value, name: this.name, group: this.group, title: this.title, type: 0 }).then(response => {
          this.getList()
        }).catch(() => {
          this.loading = false
        })
      } else {
        this.$notify({
          message: '没有需要保存的数据',
          type: 'error',
          duration: 2000
        })
      }
    },
    handleSave() {
      this.updateData()
    }
  }
}
</script>
