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
    <QuestionnairesOrderAddDialog ref="questionnairesOrderAddDialog" @done="getList" />
  </div>
</template>

<script>
import QuestionnairesOrderAddDialog from './dialog/questionnairesOrderAddDialog'

export default {
  name: 'QuestionnairesOrderIndex',
  components: { QuestionnairesOrderAddDialog },
  data() {
    return {
      module: 'questionnairesOrder',
      operates: {
        del: true,
        look: false,
        add: false,
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
      this.$refs.questionnairesOrderAddDialog.open(row)
    },
    onAdd() {
      this.$refs.questionnairesOrderAddDialog.open({})
    }
  }
}
</script>
