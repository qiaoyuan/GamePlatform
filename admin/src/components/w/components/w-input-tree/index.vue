<template>
  <el-popover ref="popover" :width="width" :disabled="disabled" @show="onShow" @hide="onHide">
    <div ref="treeTag" slot="reference" class="input-box">
      <div ref="tagsBox" class="tags-box component-scrollbar">
        <el-tag
          v-for="(item, index) in tagsList"
          :key="index"
          :size="size"
          :closable="!disabled"
          :disable-transitions="true"
          :title="item.label"
          :type="disabled ? 'info' : ''"
          :class="{ tag: true, 'disabled-tag': disabled }"
          @close="toClose(item.value)"
        >
          {{ ellipsisStr(item.label, tagNum) }}
        </el-tag>
      </div>
      <w-input
        v-model="filterText"
        :disabled="disabled"
        :placeholder="placeholder_"
        class="input"
        :style="jsStyle"
        @blur="onBlur"
      >
        <template #suffix>
          <i
            v-show="!isHover"
            class="el-icon-arrow-down"
            :style="reicon"
            @mouseenter="toggleIcon(true)"
          />
          <i
            v-show="isHover"
            class="el-icon-circle-close"
            @mouseleave="toggleIcon(false)"
            @click="value = []"
          />
        </template>
      </w-input>
    </div>
    <w-tree
      ref="tree"
      v-bind="{ ...$attrs, ...$props }"
      v-on="$listeners"
      v-model="value"
      :isTags="false"
      :isFilter="false"
      class="input-tree component-scrollbar"
      @check="onCheck"
      @nodeChange="onNodeChange"
    />
  </el-popover>
</template>

<script>
import { debounce } from 'lodash'
import { ellipsisStr } from '@/utils/w'
export default {
  name: 'wInputTree',
  inheritAttrs: false,
  props: {
    modelValue: { type: Array, default: () => [] },
    treeType: { type: String, default: 'treeRadio' },
    data: { type: Array, default: () => [] },
    placeholder: { type: String, default: '请选择' },
    disabled: { type: Boolean, default: false },
    tagNum: { type: Number, default: 15 },
  },
  model: { prop: 'modelValue', event: 'update:modelValue' },
  computed: {
    size() {
      return this.$store.getters['size']
    },
    value: {
      get() {
        return this.modelValue
      },
      set(v) {
        this.$emit('update:modelValue', v)
      },
    },
    isRadio() {
      return ['treeRadio', 'treeRadioFree'].includes(this.treeType)
    },
    placeholder_() {
      return this.value.length ? '' : this.placeholder
    },
    jsStyle() {
      return { '--paddingLeft': `${this.tagsWidth > 8 ? this.tagsWidth + 10 : 14}px` }
    },
  },
  data() {
    return {
      width: 0,
      filterText: '',
      isHover: false,
      reicon: {},
      tagsWidth: '12px',
      tagsList: [],
      hideFilter: false,
    }
  },
  watch: {
    filterText: debounce(function (v) {
      this.$refs.tree.$refs.tree.filter(v)
    }, 300),
  },
  methods: {
    ellipsisStr(str = '', length = 4) {
      return ellipsisStr(str, length)
    },
    // 关闭单个标签
    toClose(value) {
      this.$refs.tree.onClose(value)
    },
    // 切换输入框行末图标
    toggleIcon(hover) {
      if (!hover) return (this.isHover = false)
      this.tagsList.length && !this.disabled && (this.isHover = true)
    },
    onBlur() {
      this.hideFilter = true
    },
    onShow() {
      this.$refs.treeTag && (this.width = this.$refs.treeTag.clientWidth)
      this.reicon = { transform: 'rotate(180deg)' }
    },
    onHide() {
      this.reicon = {}
      if (this.hideFilter) {
        this.filterText = ''
      }
    },
    onCheck(node, { checkedKeys }) {
      this.isRadio && checkedKeys.includes(node.value) && (this.$refs.popover.showPopper = false)
    },
    onNodeChange(nodes) {
      this.tagsList = nodes.children
      this.$nextTick(() => (this.tagsWidth = this.$refs.tagsBox.clientWidth))
    },
  },
}
</script>

<style lang="less" scoped>
.input-box {
  position: relative;
  width: 100%;
  height: 100%;

  .input {
    display: block;
    position: relative;
    overflow: hidden;

    /deep/ input {
      padding-left: var(--paddingLeft);
    }

    /deep/ .el-input__suffix {
      display: flex;
      align-items: center;
      font-size: 14px;
      margin-right: 5px;
      cursor: pointer;
    }
  }

  .tags-box {
    position: absolute;
    z-index: 1;
    left: 1px;
    top: 1px;
    align-items: center;
    border-radius: 4px;
    max-width: 75%;
    height: 28px;
    line-height: 28px;
    padding: 0 4px;
    overflow: hidden auto;

    .tag {
      margin: 0;
      margin-left: 6px;

      &:first {
        margin-left: 0;
      }
    }

    .disabled-tag {
      cursor: not-allowed;
    }
  }
}

.input-tree {
  width: 100%;
  max-height: 400px;
  overflow-y: auto;
}
</style>
