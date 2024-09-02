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
    <template #questionId="{item, model, options}">
      <el-form-item prop="questionnaire_id">
        <el-select v-model="questionnaire_id" placeholder="请选择"  style="width: 100%" @change="changeQuestion">
          <el-option
            style="width: 100%"
            v-for="item in questionData"
            :key="item.value"
            :label="item.label"
            :value="item.value">
          </el-option>
        </el-select>
      </el-form-item>
    </template>
    <template #groupIndex="{item, model, options}">
       <el-form-item  prop="group_index">
        <el-select v-model="group_index" placeholder="请选择"  style="width: 100%" >
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
          value: questionnaire_id,
          formType: 'questionId',
        //   options: '/questionnaires/select',
        //   methods: {
        //   async change(v){
        //     const a = await that.$w_fun.post('questionnaires/groupList', {id: v})
        //     that.groupIndex = a.data.list
        //   }
        // }
        },
        group_index: {
          label: '评分阶段',
          value: group_index,
          formType: 'groupIndex',
          options: that.groupIndex,
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
      console.log(data)
      this.setForm(data)
      this.readonly = readonly
    },
    getForm() {
      return this.$refs.wDialogForm.$refs.form
    },
    async changeQuestion(tab, event) {
        this.questionnaire_id = tab
        const a = await this.$w_fun.post('questionnaires/groupList', {id: this.questionnaire_id})
        this.groupIndex = a.data.list
      }
  }
}
</script>

