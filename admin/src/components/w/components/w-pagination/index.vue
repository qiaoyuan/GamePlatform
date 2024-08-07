<template>
  <el-pagination
    v-bind="{ ...$attrs, ...$props }"
    v-on="$listeners"
    :page-size.sync="pageSize_"
    :current-page.sync="currentPage_"
    @size-change="onSizeChange"
    @current-change="onCurrentChange"
  />
</template>

<script>
export default {
  name: 'wPagination',
  props: {
    // 每页条数
    pageSize: { type: Number, default: 50 },
    // 总条数
    total: { type: Number, default: 0 },
    // 当前页数
    currentPage: { type: Number, default: 1 },
    layout: { type: String, default: 'total, sizes, prev, pager, next, jumper' },
    pageSizes: { type: Array, default: () => [20, 30, 50, 100] },
    hideOnSinglePage: { type: Boolean, default: false }
  },
  computed: {
    pageSize_: {
      get() {
        return this.pageSize
      },
      set(v) {
        this.$emit('update:pageSize', v)
      }
    },
    currentPage_: {
      get() {
        return this.currentPage
      },
      set(v) {
        this.$emit('update:currentPage', v)
      }
    }
  },
  mounted() {
    !this.pageSizes.includes(this.pageSize) && this.pageSizes.push(this.pageSize)
  },
  methods: {
    onSizeChange(v) {
      this.$emit('pagination', { pageSize: v, currentPage: this.currentPage_ })
    },
    onCurrentChange(v) {
      this.$emit('pagination', { pageSize: this.pageSize_, currentPage: v })
    }
  }
}
</script>
