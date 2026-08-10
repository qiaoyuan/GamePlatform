<template>
  <div class="app-container">
    <w-tabs-table
      ref="wTable"
      :module="module"
      :operates="operates"
    />
  </div>
</template>

<script>
export default {
  name: 'CompetitorProductIndex',
  data() {
    return {
      module: 'competitorProduct',
      operates: {
        // 竞品数据由爬虫写入，后台只做查看与清理，不提供新增/编辑
        add: false,
        edit: false,
        look: false,
        del: true,
        multiDel: true,
      },
    }
  },
  methods: {
    getList() {
      this.$store.dispatch('cleanColumnOptions', this.module)
      this.$refs.wTable.getList()
    },
  },
}
</script>
