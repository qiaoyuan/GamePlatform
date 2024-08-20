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
    <QuestionsOptionsAddDialog ref="questionsOptionsAddDialog" @done="getList" />
  </div>
</template>

<script>
import QuestionsOptionsAddDialog from './dialog/questionsOptionsAddDialog'

export default {
  name: 'QuestionsOptionsIndex',
  components: { QuestionsOptionsAddDialog },
  data() {
    return {
      module: 'questionsOptions',
      operates: {
        del: true,
        look: false,
        add: true,
        edit: true,
        multiDel: true,
        // recycle: { autoLink: true },
      },
    }
  },
  methods: {
    getList() {
      this.$store.dispatch('cleanColumnOptions', this.module)
      this.$refs.wTable.getList()
    },
    onEdit(row) {
      this.$refs.questionsOptionsAddDialog.open(row)
    },
    onAdd() {
      this.$refs.questionsOptionsAddDialog.open({})
    }
  }
}
</script>
