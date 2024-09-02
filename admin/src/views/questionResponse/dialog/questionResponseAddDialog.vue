<template>
  <w-dialog-form
    ref="wDialogForm"
    title="添加/编辑"
    :form="form"
    :action="formAction"
    :width="$w_fun.isMobile() ? '100%' : '60%'"
    @done="v => $emit('done', v)"
  >
    <template #groupIndex="{item, model, options}">
       <el-form-item  :prop="item.group_index">
        <el-select v-model="item.group_index" placeholder="请选择"  style="width: 100%" @change="changeIndex">
          <el-option
            style="width: 100%"
            v-for="item in groupIndex"
            :key="item.value"
            :label="item.label"
            :value="item.value">
          </el-option>
        </el-select>
      </el-form-item>
    </template>
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
      questionData: [],
      activeName: '0',
      questionnaire_id: '',
      group_index: ''
    }
  },
  mounted() {
     this.$w_fun.post('/questionnaires/select').then(res => {
       this.questionData = res.data.list
     })
  },
  methods: {
    async setForm({ id, lt, start, questionnaire_id, text, group_index }) {
      var that = this
      that.questionnaire_id = questionnaire_id
      that.group_index = group_index
      if(questionnaire_id){
        const a =  await that.$w_fun.post('questionnaires/groupList', {id: questionnaire_id})
        that.groupIndex = a.data.list
      }
      that.form = {
        questionnaire_id: {
          label: '问卷ID',
          value: that.questionnaire_id,
          formType: 'select',
          options: '/questionnaires/select',
          methods: {
          async change(v){
            const a = await that.$w_fun.post('questionnaires/groupList', {id: v})
            that.groupIndex = a.data.list
            that.$set(that.form, 'group_index', that.groupIndex)
            console.log(that.groupIndex)
          }
        }
        },
        group_index: {
          label: '评分阶段',
          value: that.group_index,
          formType: 'groupIndex',
          options: that.groupIndex,
          required: false
        },
        start: { label: '起始值', value: start, formType: 'number' },
        lt: { label: '小于等于', value: lt, formType: 'number' },
        
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
    changeIndex(tab,event) {
      this.$refs.wDialogForm.$refs.form.model.group_index = tab
    },
  }
}
</script>

