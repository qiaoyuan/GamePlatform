<template>
  <div class="app-container">
    <w-form
      ref="wForm"
      title="添加/编辑"
      :form="form"
      :action="formAction"
      :fullscreen="true"
      @done="getList"
      itemWidth="45%"
      labelWidth="180px"
    >
    </w-form>
    <div class="bottom-operation">
      <el-button :loading="loading" type="primary" @click="onSubmit()">提交</el-button>
      <el-button @click="$router.go(-1)">返回</el-button>
    </div>
  </div>
</template>

<script>
export default {
  title: 'ArticleAdd',
  data() {
    return {
      module: 'article',
      model: { id: undefined },
      formAction: '',
      form: {},
      loading: false,
      tab: ''
    }
  },
  created() {
    this.model.id = this.$route.query.id
    this.tab = this.$route.query.tab || 'article'
    this.getList()
  },
  methods: {
    getList() {
      if (this.model.id) {
        this.$w_fun.post(`${this.module}/get`, { id: this.model.id }).then(res => {
          this.model = res.data.detail
          this.formAction = `${this.module}/edit`
          this.setForm(this.model)
        })
      } else {
        this.formAction = `${this.module}/add`
        this.setForm({ module: this.tab })
      }
    },
    setForm({ id, title, article_category_id, thumb, status, desc, admin_id, sort, is_index, articleContent = {}, module }) {
      this.form = {
        id: { show: false, value: id },
        module: { show: false, value: module },
        title: { label: '标题', value: title },
        article_category_id: {
          label: '分类',
          value: article_category_id,
          formType: 'select',
          options: '/articleCategory/select?module_match=' . module
        },
        thumb: {
          label: '封面图',
          value: [{ path: thumb, url: thumb }],
          formType: 'upload',
          attrs: {
            accept: '.jpg,.png,.jpeg'
          },
          type: 'array'
        },
        status: { label: '状态', value: status, formType: 'status' },
        desc: { label: '简介', value: desc, required: false, formType: 'textarea' },
        sort: { label: '排序', value: sort, formType: 'number', required: false },
        is_index: { label: '是否首页推荐', value: is_index, formType: 'boolean' },
        content: {
          label: '内容',
          value: articleContent && articleContent.content,
          formType: 'editor',
          width: '100%',
          attrs: {
            height: '400px'
          }
        }
      }
    },
    async onSubmit() {
      try {
        this.loading = true
        await this.$refs.wForm.submit()
        this.loading = false
        if (!this.model.id) {
          this.$router.go(-1)
        } else {
          this.getList()
        }
      } catch (e) {
        this.loading = false
      }
    },
    getForm() {
      return this.$refs.wForm
    },
  }
}
</script>
