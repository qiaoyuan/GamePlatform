<template>
  <span class="recycle-group-button">
    <ElButton
      v-for="(item, index) in operates"
      :key="index"
      :disabled="!item.show"
      :type="item.type"
      :size="size"
      @click="onClick(item)"
    >
      {{ item.title }}
    </ElButton>
  </span>
</template>

<script>
import { post } from '@/libs/request'
export default {
  name: 'recycleGroup',
  props: {
    primaryKey: { type: String },
    operates: { type: Array, default: () => [] },
    row: { type: Object, default: () => ({}) },
  },
  computed: {
    size() {
      return this.$store.getters['size']
    },
  },
  methods: {
    onClick(item) {
      if (item.field === 'restore') {
        this.restore(item)
      } else if (item.field === 'forceDelete') {
        this.forceDelete(item)
      }
    },
    // 还原
    restore(item) {
      this.$confirm('是否确认还原？', '提示', { type: 'warning' }).then(async () => {
        await post(item.url, {
          [this.primaryKey]: [this.row[this.primaryKey]],
        })
        this.$emit('done')
      })
    },
    // 永久删除
    forceDelete(item) {
      this.$confirm('是否确认永久删除？', '提示', { type: 'warning' }).then(async () => {
        await post(item.url, {
          [this.primaryKey]: [this.row[this.primaryKey]],
        })
        this.$emit('done')
      })
    },
  },
}
</script>
