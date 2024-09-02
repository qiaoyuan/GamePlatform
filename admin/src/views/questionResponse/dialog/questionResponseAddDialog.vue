<template>
  <w-dialog-form
    ref="wDialogForm"
    title="添加/编辑"
    :form="form"
    :action="formAction"
    :width="$w_fun.isMobile() ? '100%' : '60%'"
    @done="v => $emit('done', v)"
  >
    <!-- <template #groupIndex="{item, model, options }">
        <el-tabs v-model="activeName" type="card" @tab-click="handleClick">
        <el-tab-pane v-for="(item, index) in groupIndex" :key="index" :label="item.label" :name="(item.value).toString()">
          配置内容
        </el-tab-pane>
      </el-tabs>

    </template> -->
  </w-dialog-form>
</template>

<script>
export default {
  name: 'QuestionResponseAddDialog',
  data() {
    return {
      module: 'questionResponse',
      formAction: '',
      form: {},
      readonly: false,
      groupIndex: [],
      activeName: '0'
    }
  },
  methods: {
    setForm({ id, lt, start, questionnaire_id, text, group_index }) {
      this.$w_fun.post('questionnaires/groupList', {id: questionnaire_id}).then(response =>{
        this.groupIndex = response.data.list
      })
      this.form = {
        group_index: {
          label: '评分阶段',
          value: group_index,
          formType: 'select',
          options: `/questionnaires/groupList?id=${questionnaire_id}`,

        },
        start: { label: '起始值', value: start, formType: 'number' },
        lt: { label: '小于等于', value: lt, formType: 'number' },
        questionnaire_id: {
          label: '问卷ID',
          value: questionnaire_id,
          formType: 'select',
          options: '/questionnaires/select'
        },
        text: { label: '配置内容', value: text,  formType: 'editor' },
      }
      if (id) {
        this.form.id = { show: false, value: id }
        this.formAction = `${this.module}/edit`
      } else {
        this.formAction = `${this.module}/add`
      }
      this.$refs.wDialogForm.visible = true
    },
    open(data, readonly = false) {
      this.setForm(data)
      this.readonly = readonly
    },
    getForm() {
      return this.$refs.wDialogForm.$refs.form
    },
    handleClick(tab, event) {
        console.log(tab, event);
      }
  }
}
</script>

