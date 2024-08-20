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
    <QuestionResponseAddDialog ref="questionResponseAddDialog" @done="getList" />
  </div>
</template>

<script>
import QuestionResponseAddDialog from './dialog/questionResponseAddDialog'

export default {
  name: 'QuestionResponseIndex',
  components: { QuestionResponseAddDialog },
  data() {
    return {
      module: 'questionResponse',
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
      this.$refs.questionResponseAddDialog.open(row)
    },
    onAdd() {
      this.$refs.questionResponseAddDialog.open({})
    }
  }
}
</script>
