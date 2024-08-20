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
    <QuestionAnswersAddDialog ref="questionAnswersAddDialog" @done="getList" />
  </div>
</template>

<script>
import QuestionAnswersAddDialog from './dialog/questionAnswersAddDialog'

export default {
  name: 'QuestionAnswersIndex',
  components: { QuestionAnswersAddDialog },
  data() {
    return {
      module: 'questionAnswers',
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
      this.$refs.questionAnswersAddDialog.open(row)
    },
    onAdd() {
      this.$refs.questionAnswersAddDialog.open({})
    }
  }
}
</script>
