<template>
  <w-dialog v-model="visible" width="70%" title="导入" class="w-dialog">
    <slot name="importForm" />
    <div  class="steps">
    第一步: <el-button v-if="template" type="text" @click="download" style="text-decoration: underline;">下载模板</el-button>
    <el-button v-else type="text" @click="download" style="color:#606266"> 暂时没有模板</el-button>
    </div>
    <div>第二步: 上传文件</div>
    <w-upload
      v-bind="{ ...$attrs, ...$props }"
      ref="upload"
      v-model="fileList"
      :action="action"
      :timeout="timeout"
      :data="uploadData"
      :drag="true"
      :multiple="true"
      :autoUpload="false"
      :limit="100"
      :on-change="onChange"
      :before-remove="beforeRemove"
      @progress="onProgress"
    >
      <i class="el-icon-upload"></i>
      <div class="el-upload__text">文件大小不超过20M</div>
    </w-upload>
    <w-form
      ref="wForm"
      :form="form"
      labelWidth="180px"
    >
    </w-form>
    <el-progress
      :percentage="percentage"
      :stroke-width="14"
      text-inside
      style="margin-top:10px"
    />
    <span v-html="progressTip" class="progressTip"></span>

      <template >
        <div class="assert">
          <slot name="assert">
          <div>导入声明：</div>
          <div>请下载模板后,按照模板中的单元格备注提示，填写内容，并上传。</div>
      </slot>
        </div>
      </template>
    <template #footer>
      <el-button :size="$store.getters['size']" @click="visible = false">
        取消
      </el-button>
      <el-button
        type="primary"
        :size="$store.getters['size']"
        :disabled="!fileList.length"
        @click="confirm"
      >
        导入
      </el-button>
    </template>
  </w-dialog>
</template>

<script>
import { post } from '@/libs/request'
export default {
  inheritAttrs: false,
  props: {
    // 模板下载地址
    template: { type: String, default: '' },
    // 触发导入地址
    import: { type: String, default: '/system/index/testImport' },
    timeout: { type: Number, default: 60000 },
    // 请求附带参数
    data: { type: Object, default: () => ({ }) },
    form: {}
  },
  computed: {
    uploadData() {
      return { ...this.data, ...this.formData }
    }
  },
  data () {
    return {
      visible: false,
      fileList: [],
      percentage: 0,
      progressTip: '...等待选择文件',
      action: '',
      formData: {}
    }
  },
  methods: {
    async importFile (file, currentPage = 0) {
      try {
        const {
          data: { total, step, page, done }
        } = await post(this.import, { file, page: currentPage })
        if (done === 1) {
          this.progressTip = '<span style="color: green;">导入完成</span>'
          return (this.percentage = 100)
        }
        this.progressTip = `...正在导入 ${step * page} / ${total}`
        this.percentage = parseInt(((step * page) / total) * 100)
        // this.importFile(file, page)
      } catch (e) {
        this.progressTip = '<span style="color: red;">...链接已中断</span>'
      }
    },
    download () {
      window.open(process.env.VUE_APP_PHP_API + this.template)
    },
    onChange (file, filelist) {
      this.progressTip = '...等待上传文件'
    },
    onProgress(file, fileList) {
      this.percentage = Math.floor(100 * fileList.filter(i => i.status === 'success').length / fileList.length)
      this.progressTip = fileList.filter(i => i.status === 'success').length + '/' + fileList.length
    },
    async confirm () {
      try {
        this.progressTip = '...文件上传中'
        this.formData = await this.$refs.wForm.submit()
        await this.$nextTick()
        await this.$refs.upload.submit()
        this.visible = false
        this.percentage = 100
        this.progressTip = '导入完成'
        // await this.importFile(this.fileList[0].url)
      } catch (e) {}
    },
    beforeRemove () {
      this.percentage = 0
      this.progressTip = '...等待选择文件'
    },
    setForm(form) {
      this.form = form || {}
    }
  },
  watch: {
    visible (val) {
      if (val) {
        this.action = this.import
        this.fileList = []
        this.percentage = 0
        this.progressTip = '...等待选择文件'
      }
    }
  }
}
</script>
<style lang="less" scoped>
 .assert{
  margin-top: 30px;
  margin-bottom: 60px;
  font-size: 13px;
 }
  .assert>div{
      line-height: 20px;
  }
.steps{
margin-bottom: 5px;
.el-button{
  padding: 0px;
   font-size: 13px;
}
}

.w-dialog /deep/ .el-dialog__body{
   font-size: 13px;
}
/deep/ .el-upload-dragger{
  height: auto;
}
</style>
