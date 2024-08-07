<template>
  <div class="w-editor">
    <Toolbar class="header" :editor="editor" :defaultConfig="toolbarConfig_" />
    <Editor
      ref="editor"
      class="body"
      v-model="content"
      :defaultConfig="editorConfig"
      :style="{ height: height }"
      @onCreated="onCreated"
    />
    <w-dialog-form
      ref="wDialogForm"
      title="插入图片"
      v-model="dialogFormVisible"
      :form="form"
      :width="$w_fun.isMobile() ? '100%' : '60%'"
      @submit="insertImage"
    />
  </div>
</template>

<script>
import '@wangeditor/editor/dist/css/style.css'
import { Editor, Toolbar } from '@wangeditor/editor-for-vue'
import { toFormData } from '@/utils/w'

export default {
  name: 'wEditor',
  components: { Editor, Toolbar },
  props: {
    modelValue: { type: String, default: '' },
    toolbarConfig: { type: [String, Object], default: () => { return {} } },
    height: { type: String, default: '300px' },
  },
  model: { prop: 'modelValue', event: 'update:modelValue' },
  computed: {
    content: {
      get() {
        return this.modelValue
      },
      set(v) {
        this.$emit('update:modelValue', v)
      }
    },
    toolbarConfig_() {
      if (typeof this.toolbarConfig !== 'string') return this.toolbarConfig
      if (this.toolbarConfig === 'simple')
        return {
          toolbarKeys: [
            'headerSelect', // 文本标题
            'bold', // 粗体
            'italic', // 斜体
            'underline', // 下划线
            {
              title: '...',
              key: 'group-more-style',
              menuKeys: [
                'through', // 删除线
                'code', // 行内代码
                'sup', // 上标
                'sub', // 下标
                'blockquote' // 引用
              ]
            },
            {
              title: '字体',
              key: 'group-more-style',
              menuKeys: [
                'fontSize', // 字体大小
                'fontFamily', // 字体样式
                'lineHeight' // 行高
              ]
            },
            'color', // 字体颜色
            'bgColor', // 背景颜色,
            'clearStyle', // 清除格式
            '|',
            {
              title: '列表',
              key: 'group-more-style',
              menuKeys: [
                'bulletedList', // 无序列表
                'numberedList', // 有序列表
                'todo' // 待办列表
              ]
            },
            {
              title: '对齐',
              key: 'group-more-style',
              menuKeys: [
                'justifyLeft', // 左对齐
                'justifyRight', // 右对齐
                'justifyCenter', // 居中对齐
                'justifyJustify' // 两端对齐
              ]
            },
            '|',
            'insertLink', // 插入链接
            'insertTable', // 插入表格
            'insertImage', // 插入图片
            'uploadImage', // 上传图片
            // 'insertVideo', // 插入视频
            // 'uploadVideo', // 上传视频
            'codeBlock', // 代码块
            'divider', // 分割线
            '|',
            'undo',
            'redo',
            '|',
            'fullScreen',
          ]
        }
      return {}
    }
  },
  data() {
    return {
      editor: null,
      insertFn: null,
      form: {},
      dialogFormVisible: false,
      editorConfig: {
        MENU_CONF: {
          uploadImage: {
            customBrowseAndUpload: insertFn => {
              this.insertFn = insertFn
              this.form = {
                images: {
                  label: '详细介绍图片',
                  value: [],
                  formType: 'upload',
                  attrs: {
                    accept: '.jpg,.png,.jpeg',
                    limit: 20
                  },
                  type: 'array',
                  required: false
                }
              }
              this.$refs.wDialogForm.visible = true
            }
          }
        }
      }
    }
  },
  methods: {
    onCreated(editor) {
      this.editor = Object.seal(editor)
    },
    getText() {
      return this.editor.getText()
    },
    insertImage(res) {
      for (const src of res.images) {
        this.insertFn(src)
      }
    },
    uploadImg(file, insertFn) {
      this.$w_fun.post(
          '/upload/index',
          toFormData({
            filename: file.name,
            file: file
          }),
          {
            timeout: 60000
          }
        )
        .then(({ data }) => {
          insertFn(data.url)
        })
        .catch(r => {
          this.$message.error(`上传 ${file.name} 失败`)
        })
    }
  },
  // 组件销毁时，及时销毁编辑器
  beforeDestroy() {
    if (this.editor !== null) return this.editor.destroy()
  }
}
</script>

<style lang="less" scoped>
.w-e-full-screen-container {
  z-index: 3;
}
.w-editor {
  border: 1px solid #dcdfe6;
  border-radius: 4px;

  // 工具栏
  .header {
    border-bottom: 1px solid #dcdfe6;
    border-radius: 4px 4px 0 0;

    /deep/ .w-e-bar-item {
      padding: 0;
    }
  }

  .body {
    overflow-y: hidden;

    /deep/ .w-e-text-container {
      border-radius: 0 0 4px 4px;
    }

    // 内容滚动条
    /deep/ .w-e-scroll {
      &::-webkit-scrollbar {
        width: 5px;
        height: 5px;
      }

      &::-webkit-scrollbar-thumb {
        border-radius: 5px;
        background: #666666;
      }

      &::-webkit-scrollbar-track {
        border-radius: 5px;
        background: #dedede;
      }

      & > div[role='textarea'] {
        padding: 10px 15px;
        p {
          margin: 0;
        }
      }
    }
  }
}
</style>
