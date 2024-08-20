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
    <QuestionsAddDialog ref="questionsAddDialog" @done="getList" />
  </div>
</template>

<script>
import QuestionsAddDialog from './dialog/questionsAddDialog'

export default {
  name: 'QuestionsIndex',
  components: { QuestionsAddDialog },
  data() {
    return {
      module: 'questions',
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
      this.$refs.questionsAddDialog.open(row)
    },
    onAdd() {
      this.$refs.questionsAddDialog.open({})
    }
  }
}
</script>
