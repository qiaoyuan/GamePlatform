<template>
  <w-dialog v-model="visible" :title="title" @cancel="visible = false" :width="width" :fullscreen="fullscreen">
    <w-form v-bind="{ ...$attrs, ...$props, title: undefined }" v-on="$listeners" ref="form" size="small" class="p20">
      <template #[slotName]="props" v-for="(_slot, slotName) in $scopedSlots">
        <slot :name="slotName" v-bind="props" />
      </template>
    </w-form>
    <template #footer>
      <slot name="footer">
        <el-button v-if="!readonly" :loading="loading" v-p="p.submit" :size="size" type="primary" @click="onSubmit">
          {{ submitText }}
        </el-button>
        <!-- <el-button v-if="!readonly" :size="size" type="danger" plain @click="resetFields"> {{ resetText }} </el-button> -->
        <el-button :size="size" @click="visible = false"> {{ cancleText }} </el-button>
      </slot>
    </template>
  </w-dialog>
</template>

<script>
export default {
  name: 'wDialogForm',
  inheritAttrs: false,
  props: {
    // 权限验证地址对象
    p: { type: Object, default: () => ({ submit: false }) },
    title: { type: String, default: '' },
    readonly: { type: Boolean, default: false },
    submitText: { type: String, default: '提交' },
    resetText: { type: String, default: '重置' },
    cancleText: { type: String, default: '取消' },
    width: { type: [String, Number], default: '500px' },
    fullscreen: { type: Boolean, default: false }
  },
  computed: {
    size() {
      return this.$store.getters['size']
    }
  },
  data() {
    return { visible: false, loading: false }
  },
  methods: {
    toggleVisible() {
      this.visible = !this.visible
    },
    resetFields() {
      this.$refs.form.resetFields()
    },
    async onSubmit() {
      try {
        this.loading = true
        const { data } = await this.$refs.form.submit()
        this.loading = false
        this.toggleVisible()
        this.$emit('done', data)
      } catch (e) {
        this.loading = false
      }
    }
  }
}
</script>
