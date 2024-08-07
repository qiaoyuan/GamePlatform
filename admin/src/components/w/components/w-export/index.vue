<template>
  <w-dialog v-model="visible" width="800px" title="导出">
  </w-dialog>
</template>

<script>
export default {
  inheritAttrs: false,
  props: {
    // 默认文件名
    downFileName: { type: String, default: '' },
    // 请求条件
    tableQuery: { type: Object, default: () => ({}) },
    // 请求地址
    tableUrl: { type: String, default: '', required: true },
    // 请求模块名
    module: { type: String, default: 'recruit' },
  },
  computed: {
    userId() {
      return this.$store.state.user.userId
    },
    size() {
      return this.$store.getters['size']
    },
  },
  data() {
    return {
      visible: false,
      downFileName_: '',
      tableQuery_: {},
      list: [],
      mylist: [],
      times: 0,
    }
  },
  watch: {
    async visible(val) {
      val && (await this.fetchList())
    },
  },
  methods: {
    open(downFileName, filter) {
      this.visible = true
      this.downFileName_ = downFileName
      this.tableQuery_ = { ...this.tableQuery, ...filter }
    },
    async fetchList() {
    },
    async confirm() {
    },
    download(url) {
      window.open(url)
    },
  },
}
</script>
