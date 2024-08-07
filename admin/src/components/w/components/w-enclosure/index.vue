<template>
  <div v-if="enclosure_.length">
    <el-button :size="wSize" type="text" @click="onClick">
      <i class="el-icon-download" />
    </el-button>
    <w-drawer v-bind="{ ...$attrs, ...$props }" v-model="visible">
      <el-link
        v-for="(item, index) in enclosure_"
        :key="index"
        type="primary"
        :underline="false"
        :href="item.url"
        target="_blank"
        class="enclosure-item"
      >
        {{ index + 1 }}. {{ item.name }}
      </el-link>
    </w-drawer>
  </div>
</template>

<script>
import { dataToFile } from '@/utils/w'
export default {
  name: 'wEnclosure',
  immediate: false,
  props: {
    title: { type: String, default: '下载' },
    enclosure: { type: [String, Array, Object], defualt: '' }
  },
  computed: {
    wSize() {
      return this.$store.getters['size']
    },
    enclosure_() {
      return dataToFile(this.enclosure)
    }
  },
  data() {
    return { visible: false }
  },
  methods: {
    onClick() {
      if (this.enclosure_.length > 1) return (this.visible = true)
      const link = document.createElement('a')
      link.setAttribute('download', this.enclosure_[0].name)
      link.setAttribute('target', '_blank')
      link.href = this.enclosure_[0].url
      link.click()
    }
  }
}
</script>

<style lang="less" scoped>
.enclosure-item {
  display: block;
  margin: 8px 0;
}
</style>
