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
    <QuestionnairesAddDialog ref="questionnairesAddDialog" @done="getList" />
    <QuestionsAddDialog ref="questionsAddDialog" @done="getList" />
    <QuestionResponseAddDialog ref="questionResponseAddDialog" @done="getList" />
  </div>
</template>

<script>
import QuestionnairesAddDialog from './dialog/questionnairesAddDialog'
import QuestionsAddDialog from '../questions/dialog/questionsAddDialog'
import QuestionResponseAddDialog from '../questionResponse/dialog/questionResponseAddDialog'
export default {
  name: 'QuestionnairesIndex',
  components: { QuestionnairesAddDialog, QuestionsAddDialog, QuestionResponseAddDialog },
  data() {
    return {
      module: 'questionnaires',
      operates: {
        del: true,
        look: false,
        add: true,
        edit: true,
        multiDel: true,
        other: [
          { title: '新增问题', type: 'primary', click: row => this.openDialogQuestion(row) },
          { title: '新增报告', type: 'primary', click: row => this.openDialogReport(row) }
        ]
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
      this.$refs.questionnairesAddDialog.open(row)
    },
    onAdd() {
      this.$refs.questionnairesAddDialog.open({})
    },
    openDialogQuestion(row) {
      this.$refs.questionsAddDialog.open({
        questionOptions:[{
          title: '', score: '',sort: ''
        }],
        questionnaire_id: row.id
      })
    },
    openDialogReport(row) {
      this.$refs.questionResponseAddDialog.open({
        questionnaire_id: row.id
      })
    }
  }
}
</script>
