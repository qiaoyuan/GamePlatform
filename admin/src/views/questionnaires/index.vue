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
  </div>
</template>

<script>
import QuestionnairesAddDialog from './dialog/questionnairesAddDialog'

export default {
  name: 'QuestionnairesIndex',
  components: { QuestionnairesAddDialog },
  data() {
    return {
      module: 'questionnaires',
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
      this.$refs.questionnairesAddDialog.open(row)
    },
    onAdd() {
      this.$refs.questionnairesAddDialog.open({})
    }
  }
}
</script>
