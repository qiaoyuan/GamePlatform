<template>
  <component
    :is="type === 'radios' ? 'Radios' : 'Tabs'"
    v-bind="{ ...$props, tabsList: tabsList_ }"
    v-model="currentTab"
  />
</template>

<script>
import Tabs from './tabs.vue'
import Radios from './radios.vue'
export default {
  components: { Tabs, Radios },
  props: {
    type: { type: String, default: 'tabs' },
    tabsList: { type: Array, default: () => [] },
    modelValue: { type: String, default: '' },
  },
  model: { prop: 'modelValue', event: 'update:modelValue' },
  computed: {
    currentTab: {
      get() {
        return this.modelValue
      },
      set(v) {
        this.$emit('update:modelValue', v)
      },
    },
    tabsList_() {
      return this.tabsList.map(item => ({
        ...item,
        label: `${item.label}`,
        name: item.hasOwnProperty('name') ? `${item.name}` : `${item.value}`,
      }))
    },
  },
}
</script>
