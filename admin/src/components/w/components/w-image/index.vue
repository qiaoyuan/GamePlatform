<template>
  <el-image
    v-if="src_"
    v-bind="{ ...$attrs, ...$props }"
    v-on="$listeners"
    :src="src_"
    :preview-src-list="previewSrcList_"
  >
    <template v-for="(_slot, slotName) in $slots" #[slotName]>
      <slot :name="slotName" />
    </template>
  </el-image>
</template>

<script>
import { dataToFile } from '@/utils/w'

export default {
  name: 'wImage',
  props: {
    src: { type: String, default: '' },
    fit: { type: String, default: 'cover' },
    alt: { type: String, default: '' },
    previewSrcList: { type: [String, Object, Array], default: '' }
  },
  computed: {
    src_() {
      const src = dataToFile(this.src)[0]
      return src ? src.url : this.previewSrcList_[0]
    },
    previewSrcList_() {
      return dataToFile(this.previewSrcList).map(({ url }) => url)
    }
  }
}
</script>
