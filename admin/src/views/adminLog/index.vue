<template>
  <div class="app-container">
    <w-tabs-table
      ref="wTable"
      :operates="operates"
      :module="module"
    >
    </w-tabs-table>
  </div>
</template>

<script>

import { copyText } from '@/utils/w'

export default {
  name: 'AdminLogIndex',
  data() {
    return {
      module: 'adminLog',
      operates: {
        del: false,
        look: false,
        edit: false,
        add: false,
        other: [
          { title: '复制', click: (row, $index) => this.copyRowInfo(row) }
        ],
      }
    }
  },
  methods: {
    getList() {
      this.$refs['wTable'].getList()
    },
    copyRowInfo(item) {
      copyText(item)
      this.$message.success('复制成功')
    }
  }
}
</script>

