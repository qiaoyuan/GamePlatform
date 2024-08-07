<template>
  <div>
    <div v-if="imageSetting" class="w100">
      <div> 类型：
        <el-radio-group v-model="uploadData.type">
          <el-radio label="image">Image</el-radio>
          <el-radio label="video">Video</el-radio>
        </el-radio-group>
      </div>
      <div v-if="uploadData.type === 'image'">
        图片质量：<el-input  v-model="uploadData.q" placeholder="图片质量" class="w50"></el-input>
      </div>
    </div>
    <el-upload
      ref="upload"
      v-bind="{ ...$attrs, ...$props }"
      v-on="$listeners"
      :limit="9999"
      :auto-upload="false"
      :fileList="fileList"
      :on-change="onChange_"
      list-type="picture-card"
      :showFileList="false"
      :multiple="limit > 1"
      class="upload"
    >
      <template #trigger>
        <i v-if="limit === 1 && fileList.length === 0" class="el-icon-plus avatar-uploader-icon" />
        <template v-if="limit !== 1">
          <i class="el-icon-plus avatar-uploader-icon" />
          <div class="limit-tip">{{ fileList.length }} / {{ limit || '∞' }}</div>
        </template>
        <template v-else>
          <div v-for="item in fileList" :key="item.uid" class="file-box">
            <img v-if="getFileType(item.name) === 'img'" :src="item.url" />
            <div v-else class="file-name">{{ item.name }}</div>
            <div class="icon-list">
              <i class="el-icon-zoom-in" @click.stop="onPreview_(item)" />
              <i class="el-icon-edit-outline" />
              <div class="close-box" @click.stop="onRemove_(item)">
                <i class="el-icon-close" />
              </div>
            </div>
          </div>
        </template>
      </template>
      <template v-if="limit !== 1">
        <draggable
          :options="{
            group: 'people',
            animation: 150,
            ghostClass: 'sortable-ghost',
            chosenClass: 'chosenClass',
            scroll: true
          }"
          v-model="fileList"
          class="draggableImg"
        >
          <div v-for="(item, index) in fileList" :key="index" class="file-box multiple-file-box">
            <img v-if="getFileType(item.name) === 'img'" :src="item.url" />
            <div v-else class="file-name">{{ item.name }}</div>
            <div class="icon-list" @click="onPreview_(item)">
              <i class="el-icon-zoom-in" />
              <!-- <i class="el-icon-edit-outline" @click.stop="onSelect(item, index)" /> -->
              <div class="close-box" @click.stop="onRemove_(item)">
                <i class="el-icon-close" />
              </div>
            </div>
          </div>
        </draggable>
      </template>
      <slot name="tip">
        <div class="tip">{{ tip }}</div>
      </slot>
    </el-upload>

    <ImageViewer
      v-if="fileList.length > 0"
      v-show="imageViewer"
      ref="imgViewer"
      class="image-viewer"
      :url-list="imgList"
      :on-close="() => (imageViewer = false)"
    />
    <w-pdf ref="wPdf" v-bind="wPdf" :hasButton="false" />
  </div>
</template>

<script>
import ImageViewer from 'element-ui/packages/image/src/image-viewer'
import draggable from 'vuedraggable'
import { getFileType, toFormData } from '@/utils/w'
export default {
  name: 'wUpload',
  inheritAttrs: false,
  components: { ImageViewer, draggable },
  props: {
    // 请求地址
    action: { type: String, default: '/upload/index' },
    // 请求配置
    config: { type: Object, default: () => ({}) },
    // 多选文件
    multiple: { type: Boolean, default: false },
    // 请求附带参数
    data: { type: Object, default: () => ({ }) },
    // 文件字段名
    name: { type: String, default: 'file' },
    // 自动上传
    autoUpload: { type: Boolean, default: false },
    // 最大允许上传个数
    limit: { type: Number, default: 1 },
    // 提示文本
    tip: { type: String, default: '' },
    // 父组件文件列表，通过 v-model 双向绑定
    modelValue: { type: Array, default: () => [] },
    // 单个文件大小（单位 kb）
    size: { type: Number, default: 0 },
    timeout: { type: Number, default: 60000 },
    // 选择文件时的回调（避免使用者定义的钩子被覆盖）
    onChange: { type: Function, default: () => {} },
    // 点击已选择文件的钩子
    onPreview: { type: Function, default: () => {} },
    // 删除文件之后的钩子
    onRemove: { type: Function, default: () => {} },
    imageSetting: { type: Boolean, default: false }
  },
  data() {
    return {
      // 图片模式的预览
      imageViewer: false,
      // 非图片模式的本地预览
      wPdf: { buttonText: '', title: '', url: '', fileName: '' },
      uploadData: { }
    }
  },
  model: { prop: 'modelValue', event: 'update:fileList' },
  computed: {
    btnSize() {
      return this.$store.getters['size']
    },
    fileList: {
      get() {
        return this.modelValue
      },
      set(v) {
        this.$emit('update:fileList', v)
        this.$emit('change', v)
      }
    },
    imgList() {
      return this.fileList.reduce((pre, item) => {
        getFileType(item.name) === 'img' && pre.push(item.url)
        return pre
      }, [])
    }
  },
  methods: {
    upload(file) {
      return new Promise((resolve, reject) => {
        this.$w_fun.post(
          this.action,
          toFormData({
            ...this.uploadData,
            ...this.data,
            filename: file.name,
            file: file.raw
          }),
          {
            timeout: this.timeout,
            ...this.config
          }
        )
          .then(({ data }) => resolve(data))
          .catch(r => {
            this.$message.error(`上传 ${file.name} 失败`)
            reject(r)
          })
      })
    },
    async clearFiles() {
      this.fileList = []
    },
    // 手动上传
    async submit() {
      // 等待每次文件上传结束再进入下一次遍历
      const needUpload = this.fileList.filter( i => !i.path)
      if (needUpload.length > 0) {
        for (const i in needUpload) {
          const res = await this.upload(needUpload[i])
          for (const j in this.fileList) {
            if (this.fileList[j].uid === needUpload[i].uid) {
              this.fileList[j] = { ...this.fileList[j], ...res, percentage: 100, status: 'success' }
              this.$emit('progress', this.fileList[j], this.fileList)
            }
          }
        }
      }
    },
    async onChange_(file, fileList) {
      if (this.size && file.size > this.size) {
        fileList.splice(
          fileList.findIndex(({ uid }) => uid === file.uid),
          1
        )
        return this.$message.error(`文件大小不可超过 ${this.size / 1024 / 1024} MB`)
      }
      // 当文件数量超出上限时，自动替换多余文件
      if (this.limit === 1) {
        fileList = [file]
      } else if (fileList.length > (this.limit || 9999)) {
        this.$confirm(`超出文件上限，将替换已上传文件？`, '上传提示', {
          confirmButtonText: '确定',
          cancelButtonText: '取消',
          type: 'warning'
        })
          .then(() => {
            fileList.splice(0, fileList.length - this.limit)
          })
          .catch(() => {
            fileList.splice(fileList.length - this.limit, fileList.length - this.limit)
          })
      }
      this.onChange(file, fileList)
      if (this.autoUpload) {
        try {
          const data = await this.upload(file)
          const index = fileList.findIndex(({ uid }) => uid === file.uid)
          fileList[index] = { ...fileList[index], ...data, percentage: 100, status: 'success' }
        } catch (e) {}
      }
      this.fileList = fileList
    },
    onRemove_(file) {
      this.fileList.splice(
        this.fileList.findIndex(item => item.uid === file.uid),
        1
      )
      this.onRemove(file, this.fileList)
    },
    onPreview_(file) {
      if (getFileType(file.name) === 'img') {
        this.$refs.imgViewer.index = this.imgList.findIndex(item => item === file.url)
        this.imageViewer = true
      } else {
        this.wPdf = {
          buttonText: '',
          title: file.name,
          url: file.url,
          fileName: file.name
        }
        this.$nextTick(() => this.$refs.wPdf.open())
      }
      this.onPreview(file)
    },
    onSelect(item, index) {
      this.$refs.upload.$children[0].$el.click()
    },
    getFileType
  }
}
</script>

<style lang="less" scoped>
@width: 100px;
@height: 100px;

.upload {
  display: flex;
  flex-wrap: wrap;

  /deep/ .el-upload--picture-card {
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    margin: 0 10px 10px 0;
    width: @width;
    height: @height;
    overflow: hidden;

    .limit-tip {
      position: absolute;
      top: calc(50% + 24px);
      color: #8c939d;
      line-height: 1em;
    }
  }

  .draggableImg {
    display: contents;
  }

  .multiple-file-box {
    overflow: hidden;
    margin: 0 10px 10px 0;
    background-color: #fbfdff;
    border: 1px dashed #c0ccda;
    border-radius: 6px;
  }

  .file-box {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    width: @width;
    height: @height;

    img {
      height: 100%;
      margin: auto;
    }

    .file-name {
      text-align: center;
      padding: 2px;
      line-height: 1.5em;
    }

    .icon-list {
      position: absolute;
      display: flex;
      align-items: center;
      justify-content: center;
      width: 100%;
      height: 100%;
      opacity: 0;
      background: none;
      cursor: pointer;
      text-align: center;
      transition: opacity 0.4s, background 0.4s;

      &:hover {
        opacity: 1;
        background: rgba(0, 0, 0, 0.7);
      }

      i {
        font-size: 20px;
        color: #fff;
        margin: 6px;
      }

      .close-box {
        position: absolute;
        right: -20px;
        top: -20px;
        width: 40px;
        height: 40px;
        text-align: center;
        line-height: 18px;
        background: #19aa8d;
        transform: rotate(-135deg);

        .el-icon-close {
          transform: rotate(-45deg);
          margin: 0;
          font-size: 14px;
        }
      }
    }
  }

  .tip {
    width: 100%;
    line-height: 1em;
    color: #8c939d;
  }
}

.image-viewer {
  z-index: 3000 !important;
}
</style>
