<template>
  <div>
    <w-date-picker
      ref="dateRef"
      v-bind="{ size: wSize, ...dateAttrs }"
      v-model="dateValue"
      style="width: 60%"
      :size="dateAttrs.size || wSize"
      :placeholder="dateAttrs.placeholder || '日期'"
    />
    <el-time-select
      ref="timeRef"
      v-bind="{
        pickerOptions: { start: '08:00', step: timeAttrs['step'] || '00:30', end: '22:00' },
        size: wSize,
        placeholder: '时间',
        ...timeAttrs,
      }"
      v-model="timeValue"
      style="width: 40%"
    />
  </div>
</template>
<script>
import { dateToString } from '@/utils/w'

export default {
  name: 'wDateTimeSelect',
  model: { prop: 'modelValue', event: 'update:modelValue' },
  props: {
    modelValue: { type: String, default: dateToString('yyyy-MM-dd HH:mm') },
    size: { type: String },
    dateAttrs: { type: Object, default: () => ({}) },
    timeAttrs: { type: Object, default: () => ({}) },
  },
  computed: {
    wSize() {
      return this.$store.getters['w/size']
    },
    modelValue_() {
      return this.modelValue.split(' ')
    },
    dateValue: {
      get() {
        return this.modelValue_[0]
      },
      set(v) {
        v = [v, this.modelValue_[1]].join(' ')
        this.$emit('update:modelValue', v)
        this.$emit('change', v)
      },
    },
    timeValue: {
      get() {
        return this.modelValue_[1]
      },
      set(v) {
        v = [this.modelValue_[0], v].join(' ')
        this.$emit('update:modelValue', v)
        this.$emit('change', v)
      },
    },
  },
}
</script>
