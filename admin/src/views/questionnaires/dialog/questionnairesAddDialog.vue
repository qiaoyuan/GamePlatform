<template>
  <w-dialog-form
    ref="wDialogForm"
    title="添加/编辑"
    :form="form"
    :action="formAction"
    :width="$w_fun.isMobile() ? '100%' : '60%'"
    @done="v => $emit('done', v)"
  >
    <template #phaseSlot="{item, model, options }">
       <div  v-for="(item,i) in model.group_conf" :key="i" class="selectContent">
        <el-row >
          <el-col :span="2">
            第{{i+1}} 阶段
          </el-col>
          <el-col :span="9">
            <el-form-item :label="'首个题目'" prop="start" label-width="80px">
              <el-input  v-model="item.start" clearable placeholder="输入首个题目" type="number"></el-input>
            </el-form-item>
          </el-col>
          <el-col :span="9">
            <el-form-item label="末尾题目" prop="end" label-width="80px">
                <el-input v-model="item.end" clearable placeholder="输入题目" type="number"></el-input>
            </el-form-item>
          </el-col>
          <el-col :span="1"><el-button circle icon="el-icon-plus" @click="addList()"></el-button></el-col>
          <el-col :span="1"><el-button circle icon="el-icon-minus" @click="subList(i)" v-if="i>0"></el-button></el-col>
        </el-row>
    </div>
    <!-- <div v-else> 全部阶段</div> -->
    </template>
  </w-dialog-form>
</template>

<script>
export default {
  name: 'QuestionnairesAddDialog',
  data() {
    return {
      module: 'questionnaires',
      formAction: '',
      form: {},
      readonly: false,
      studentList: []
    }
  },
  methods: {
    setForm({ id, title, description, status, img_url, article_category_id, easy, exact, utility, sort,price,content, group_index, group_conf }) {
      this.studentList = group_conf
      console.log(group_conf)
      this.form = {
        title: { label: '问卷名称', value: title },
        description: { label: '问卷简述', value: description, required: false },
        img_url: { label: '图片', value: img_url, formType:'upload'},
        article_category_id: {
          label: '类型',
          value: article_category_id,
          formType: 'select',
          options: '/articleCategory/select'
        },
        price: { width: '50%', label: '价格', value: price, formType: 'number'},
        easy: {  width: '50%', label: '题目易懂', value: easy },
        exact: {   width: '50%', label: '结果准确性', value: exact },
        utility: {  width: '50%', label: '建议实用性', value: utility },
        // group_index: {
        //   label: '是否分配阶段',
        //   value: group_index,
        //   formType: 'select',
        //   options: [{
        //     label: '是',value: 1
        //   },{
        //     label: '否',
        //     value: 0
        //   }],
        //   input: (val) => {
        //     console.log(val)
        //   }
        // },
        group_conf: { label: '评分阶段',value: group_conf, formType: 'phaseSlot', required: false },
        sort: { label: '排序', value: sort},
        content: { label: '内容', value: content, formType: 'editor'},
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
