<template>
  <div class="app-container">
    <w-tabs-table
      ref="wTable"
      :operates="operates"
      :module="module"
      :actions="{ tabs: module + '/tabs' }"
      k
      @add="onAdd"
      @edit="onEdit"
    >
    </w-tabs-table>
  </div>
</template>

<script>
export default {
  title: 'ArticleIndex',
  data() {
    return {
      module: 'article',
      operates: {
        del: true,
        look: false,
        add: true,
        edit: true,
        multiDel: true,
        recycle: { autoLink: true },
      },
      currentTab: ''
    }
  },
  methods: {
    changeTab(v) {
      this.currentTab = v
    },
    getList() {
      this.$refs['wTable'].getList()
    },
    onEdit(row) {
      this.$router.push({ name: 'ArticleAdd', query: { id: row.id } })
    },
    onAdd() {
      this.$router.push({ name: 'ArticleAdd', query: { tag: this.currentTab } })
    },
  }
}
</script>
