<template>
  <div class="app-container">
    <w-tabs-table
      ref="wTable"
      :module="module"
      :operates="operates"
      @add="onAdd"
    >
      <template #attachment="{ row }">
        <el-image
          v-if="row.type === 'img'"
          :src="row.url"
          :preview-src-list="[row.url]"
          style="width: 70px;height: 70px"
          fit="contain"
        ></el-image>
        <a v-else target="_blank" class="blue" :href="row.url">附件</a>
      </template>
    </w-tabs-table>
    <w-dialog-form
      ref="wDialogForm"
      v-model="dialogVisible"
      :form="form"
      title="上传"
      @done="getList"
    >
    </w-dialog-form>
  </div>
</template>

<script>
import { copyText } from '@/utils/w'

export default {
  name: 'AttachmentIndex',
  data() {
    return {
      module: 'attachment',
      operates: {
        edit: false,
        look: false,
        add: true,
        del: { show: true, url: 'attachment/delete' },
        other: [
          { title: '复制', click: row => this.copyRowInfo(row.url) }
        ],
      },
      dialogVisible: false,
      result: '',
      keep: 1,
      q: 100,
      form: {
        upload: { label: '上传', formType: 'upload', attrs: { imageSetting: true } },
      }
    }
  },
  methods: {
    getList() {
      this.$refs['wTable'].getList()
    },
    size(size) {
      let unit = 'B'
      size > 1024 && ((size = Number(size / 1024).toFixed(2)) && (unit = 'KB'))
      size > 1024 && ((size = Number(size / 1024).toFixed(2)) && (unit = 'MB'))
      return size + unit
    },
    onAdd() {
      this.$refs.wDialogForm.visible = true
    },
    copyRowInfo(item) {
      copyText(item)
      this.$message.success('复制成功')
    }
  }
}
</script>
