<template>
  <el-table-column :prop="prop" align="center" :min-width="minWidth">
    <template #header>
      {{ label }}
      <el-tooltip v-if="content" placement="top">
        <template #content> <div v-html="content" /> </template>
        <el-link type="primary" :underline="false"> <i class="el-icon-info" /> </el-link>
      </el-tooltip>
    </template>
    <template #default="{ row, $index }">
      <el-form-item :prop="`rule.${$index}.${prop}`" :rules="rules">
        <slot :row="row" :$index="$index" />
        <template v-if="rules" #error="{ error }">
          <el-tooltip class="error-tip" :content="error" placement="top">
            <span v-if="error"><i class="el-icon-warning" /></span>
          </el-tooltip>
        </template>
      </el-form-item>
    </template>
  </el-table-column>
</template>

<script>
export default {
  name: 'tableColumn',
  props: {
    prop: { type: String, default: '' },
    label: { type: String, default: '' },
    content: { type: String, default: '' },
    minWidth: { type: [String, Number], default: 0 },
    rules: { type: Array },
  },
}
</script>

<style lang="less" scoped>
.el-form-item {
  .error-tip {
    position: absolute;
    right: 2px;
    top: 0;
  }

  .error-tip {
    color: #f56c6c;
  }
}
</style>
