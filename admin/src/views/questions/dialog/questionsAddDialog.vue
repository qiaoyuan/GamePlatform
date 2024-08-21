<template>
  <w-dialog-form
    ref="wDialogForm"
    title="添加/编辑"
    :form="form"
    :action="formAction"
    :width="$w_fun.isMobile() ? '100%' : '60%'"
    @done="v => $emit('done', v)"
  >
  <template  #questionOptions="{ item, model, options }">
    <div v-for="(item,i) in model.options" :key="i" style="display: flex">
        <el-form-item :label="'选项' + (i*1 + 1)" prop="title">
          <el-input v-model="item.title" clearable placeholder="选项内容"></el-input>
        </el-form-item>
        <el-form-item label="分数" prop="score">
            <el-input v-model="item.score" clearable placeholder="分数"></el-input>
        </el-form-item>
        <el-form-item label="顺序" prop="sort">
            <el-input v-model="item.sort" clearable placeholder="顺序"></el-input>
        </el-form-item>
        <el-button circle icon="el-icon-plus" @click="addList()"></el-button>
        <el-button circle icon="el-icon-minus" @click="subList(i)" v-if="i>0"></el-button>
   </div>
  </template>
  </w-dialog-form>
</template>

<script>
export default {
  name: 'QuestionsAddDialog',
  data() {
    return {
      module: 'questions',
      formAction: '',
      form: {},
      readonly: false,
      studentList: []
    }
  },
  methods: {
    setForm(data) {
      var { id, questionnaire_id, title, sort, questionOptions, options } = data
      console.log(data)
      this.studentList = questionOptions
      this.form = {
        questionnaire_id: {
          label: '问卷id',
          value: questionnaire_id,
          formType: 'select',
          options: '/questionnaires/select'
        },
        title: { label: '问题名称', value: title },
        sort: { label: '排序', value: sort },
        options: {label: '选项', value: questionOptions, formType: 'questionOptions'},
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
    // 加号
     addList() {
      this.studentList.push({title: '', score: '',sort: ''})
    },
    //减号
    subList(index) {
      this.studentList.splice(index, 1)
    }
  }
}
</script>
