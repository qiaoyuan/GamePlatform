<template>
  <div>
    <span>&nbsp;&nbsp;</span>
    <el-button
      v-for="(item, index) in iconList"
      :key="index"
      :size="size"
      :type="getType(item.value)"
      class="w5 m0 p0"
      @click="setIcon(item.value)"
      style="font-size: 16px"
    >
      <i :class="item.value" />
    </el-button>
  </div>
</template>
<script>
import { iconList } from '@/utils/wData'
export default {
  name: 'wIcon',
  props: {
    isCheckbox: { type: Boolean, default: false },
    icon: { type: [String, Array], default: '' }
  },
  model: { prop: 'icon', event: 'update:icon' },
  computed: {
    size() {
      return this.$store.getters['size']
    }
  },
  data() {
    return {
      iconList
    }
  },
  methods: {
    getType(v) {
      if (this.isCheckbox) {
        return this.icon.includes(v) ? 'primary' : 'text'
      }
      return v === this.icon ? 'primary' : 'text'
    },
    setIcon(v) {
      if (this.isCheckbox) {
        const icon = [...this.icon]
        const index = this.icon.findIndex(i => i === v)
        index === -1 ? icon.push(v) : icon.splice(index, 1)
        this.$emit('update:icon', icon)
      }
      if (!this.isCheckbox) {
        this.icon === v ? this.$emit('update:icon', '') : this.$emit('update:icon', v)
      }
    }
  }
}
</script>
