<template>
  <div class="operates-box">
    <span ref="operatesBoxRef">
      <span v-if="isRecycle">
        <RecycleGroup primaryKey="id" :operates="recycleOperates" :row="{}" />
      </span>
      <template v-else>
        <span v-for="(row, $index) in data" :key="$index">
          <template v-for="(item, index) in otherOperates">
            <el-button
              v-if="makeOtherOperatesStatus(item.show, row, $index)"
              v-p="item.p ? makeOtherOperatesStatus(item.p, row, $index) : ''"
              :key="index"
              :size="size"
            >
              {{ makeOtherOperatesStatus(item.title, row, $index) }}
            </el-button>
          </template>
          <NormalGroup primaryKey="id" :row="row" :$index="$index" :operates="normalOperate">
            <span v-if="moreOperate" />
          </NormalGroup>
          <br />
        </span>
      </template>
    </span>
  </div>
</template>

<script>
import { makeOtherOperatesStatus } from '../../util'
import NormalGroup from './normalGroup.vue'
import RecycleGroup from './recycleGroup.vue'
export default {
  components: { NormalGroup, RecycleGroup },
  props: {
    data: { type: Array, default: () => [] },
    otherOperates: { type: Array, default: () => [] },
    recycleOperates: { type: Array, default: () => ({}) },
    normalOperate: { type: Object, default: () => ({}) },
    moreOperate: { type: Boolean, default: false },
    isRecycle: { type: Boolean, default: false },
  },
  computed: {
    size() {
      return this.$store.getters['size']
    },
  },
  methods: {
    makeOtherOperatesStatus,
    getOperateWidth() {
      const width = Math.ceil(this.$refs.operatesBoxRef.getBoundingClientRect().width * (this.size === 'mini' ? 1 : 1.45))
      if (width > 34) return width + 10
      if (width > 0) return 45
      return 0
    },
  },
}
</script>

<style lang="less" scoped>
.operates-box {
  height: 0;
  overflow: hidden;
  /deep/.el-button {
    padding: 2px 5px;
    font-size: 12px;
    height: 18px;
  }
  > span > span > :not(:first-child) {
    margin-left: 4px;
  }
}
</style>
