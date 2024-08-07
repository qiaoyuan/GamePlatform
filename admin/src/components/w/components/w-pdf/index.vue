<template>
  <div v-if="url">
    <!-- <el-tag v-if="title" :size="size" @click="open"> 预览 </el-tag> -->
    <el-button
      v-if="buttonText"
      :size="size"
      type="primary"
      :plain="true"
      class="label"
      @click="open"
    >
      {{ buttonText }}
    </el-button>
    <w-dialog
      v-model="visible"
      :title="title"
      width="800px"
      style="text-align: center"
      @onscroll="handleScroll"
      id="printTest"
    >
      <img v-if="fileType === 'img'" :src="url" class="imageBox" />
      <pdf
        v-bind="$attrs"
        v-on="$listeners"
        v-else-if="fileType === 'pdf'"
        ref="pdfComp"
        :src="pdfSrc"
        :page="i"
        v-for="i in pdfTotalPages"
        :key="i"
      ></pdf>
      <template #footer>
        <el-button :size="size" @click="visible = false">关闭</el-button>
        <el-button v-if="hasDown" :size="size" @click="handlePrintOrDownload">
          下载/打印
        </el-button>
      </template>
    </w-dialog>
  </div>
</template>

<script>
import pdf from 'vue-pdf'
import { ellipsisStr, getFileType } from '@/utils/w'

export default {
  inheritAttrs: false,
  components: { pdf },
  props: {
    title: { type: String, default: '预览' },
    buttonText: { type: String, default: '预览' },
    url: { type: String, default: '', required: true },
    // 文件名 按需传入，有时候需要预览本地文件，必须传入文件名，否则无法判断文件格式，会直接进入下载
    fileName: { type: String, default: '' },
    errMsg: { type: String, default: '缺少文件路径' },
    hasDown: { type: Boolean, default: false }
  },
  data() {
    return {
      visible: false,
      pdfSrc: '',
      pdfTotalPages: 1,
      isReachBottom: false
    }
  },
  computed: {
    size() {
      return this.$store.getters['size']
    },
    fileType() {
      return getFileType(this.fileName || this.url)
    }
  },
  watch: {
    url: {
      immediate: true,
      handler(val) {
        this.pdfSrc = val
      }
    },
    visible: {
      immediate: true,
      handler(v) {
        v && this.getPdfTotalPages()
      }
    }
  },
  methods: {
    ellipsisStr(str = '', length = 4) {
      return ellipsisStr(str, length)
    },
    getPdfTotalPages() {
      let loadingTask = pdf.createLoadingTask(this.pdfSrc)
      loadingTask.promise
        .then(pdf_res => {
          this.pdfSrc = loadingTask
          this.pdfTotalPages = pdf_res.numPages
        })
        .catch(err => {
          // err
          // console.log(err)
        })
    },
    handleScroll(content) {
      let scrollH = content.scrollHeight
      let height = content.clientHeight
      let scrollTop = content.scrollTop
      if (scrollTop + height + 10 >= scrollH) {
        this.$emit('onReachBottom', this.isReachBottom)
        this.isReachBottom = true
      }
    },
    open() {
      if (!this.url) return this.$message.warning(this.errMsg)
      if (this.fileType === 'other') {
        const link = document.createElement('a')
        link.setAttribute('download', this.fileName || this.url)
        link.setAttribute('target', '_blank')
        link.href = this.url
        return link.click()
      }
      if (this.fileType === 'img') {
        this.$emit('num-pages', 1)
      }
      this.visible = true
    },

    handlePrintOrDownload() {
      window.open(this.url)
    }
  }
}
</script>

<style lang="less" scoped>
.label {
  max-width: 100%;
  overflow: hidden;
  white-space: nowrap;
  text-overflow: ellipsis;
}

.iframe {
  width: 100%;
  height: calc(80vh - 130px);
  overflow: auto;
}

.imageBox {
  text-align: center;
  max-width: 100%;
  height: auto;
}
</style>
