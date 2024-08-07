<template>
  <el-select
    v-bind="{ ...$attrs, ...$props }"
    v-on="$listeners"
    v-model="selectValue"
    class="w100"
    :size="$attrs.size || size"
  >
    <template #prefix="props"> <slot v-bind="props" name="prefix" /> </template>
    <template #empty="props"> <slot v-bind="props" name="empty" /> </template>
    <slot>
      <el-option
        v-for="(item, index) in data"
        v-bind="item"
        :key="index"
        :class="{ red: item.plain }"
        :title="item.label"
      >
        {{ ellipsisStr(item.label, 40) }}
      </el-option>
    </slot>
  </el-select>
</template>

<script>
import { ellipsisStr } from '@/utils/w'

export default {
  name: 'wSelect',
  props: {
    modelValue: { type: [String, Number, Array], default: '' },
    data: { type: Array, default: () => [] },
    clearable: { type: Boolean, default: true },
    filterable: { type: Boolean, default: true }
  },
  model: { prop: 'modelValue', event: 'update:modelValue' },
  computed: {
    size() {
      return this.$store.getters['size']
    },
    selectValue: {
      get() {
        return this.modelValue
      },
      set(v) {
        this.$emit('update:modelValue', v)
      }
    }
  },
  methods: {
    ellipsisStr
  }
}
</script>
