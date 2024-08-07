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
      @changeTab="changeTab"
    >
    </w-tabs-table>
    <ArticleCategoryAddDialog ref="articleCategoryAddDialog" @done="getList" />
  </div>
</template>

<script>
import ArticleCategoryAddDialog from './dialog/articleCategoryAddDialog'

export default {
  name: 'ArticleCategoryIndex',
  components: { ArticleCategoryAddDialog },
  data() {
    return {
      module: 'articleCategory',
      operates: {
        del: true,
        look: false,
        add: true,
        edit: true,
        multiDel: true,
        // recycle: { autoLink: true },
      },
      currentTab: ''
    }
  },
  methods: {
    changeTab(v) {
      this.currentTab = v
    },
    getList() {
      this.$store.dispatch('cleanColumnOptions', this.module + '/select')
      this.$refs.wTable.getList()
    },
    onEdit(row) {
      this.$refs.articleCategoryAddDialog.open(row)
    },
    onAdd() {
      this.$refs.articleCategoryAddDialog.open({ module: this.currentTab })
    }
  }
}
</script>
