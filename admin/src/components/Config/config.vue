<template>
  <div class="w100 p25">
    <el-form ref="dataForm" :model="model" label-suffix="：" label-position="right" label-width="180px">
      <el-form-item v-for="item of fields" :key="item.field" :label="item.label">
        <el-switch
          v-if="item.type === 'switch'"
          v-model="model[item.field]"
          :active-value="1"
          :inactive-value="0"
          active-color="#13ce66"
          inactive-color="#ff4949"
        ></el-switch>
        <el-input
          v-else
          v-model="model[item.field]"
          v-bind="item.attrs || {}"
        />
      </el-form-item>
    </el-form>
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
  name: 'ConfigField',
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
    fields: {
      type: Array,
      required: true
    }
  },
  data() {
    return {
      model: {}
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
          this.model = response.data.detail ? response.data.detail.value : {}
          if (this.model.constructor === Array) {
            this.model = {}
          }
        }
        this.listLoading = false
      })
    },
    updateData() {
      if (this.model) {
        this.$w_fun.post('config/save', { value: this.model, name: this.name, group: this.group, title: this.title }).then(response => {
          this.getList()
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
