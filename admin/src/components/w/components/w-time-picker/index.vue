<script>
import DfTimePicker from './src/picker/time-picker'
export default {
  components: { DfTimePicker },
  props: {
    modelValue: { type: [String, Date, Array] },
    editable: { type: Boolean, default: false },
    placeholder: { type: String, default: '请选择时间' },
    startPlaceholder: { type: String, default: '开始时间' },
    endPlaceholder: { type: String, default: '结束时间' },
    isRange: { type: Boolean, default: false },
    format: { type: String, default: 'HH:mm' },
    valueFormat: { type: String, default: 'HH:mm' },
    pickerOptions: { type: Object, default: () => ({}) },
    appendToBody: { type: Boolean, default: false }
  },
  model: { prop: 'modelValue', event: 'update:modelValue' },
  computed: {
    size() {
      return this.$store.getters['w/size']
    },
    value: {
      get() {
        return this.modelValue
      },
      set(v) {
        this.$emit('update:modelValue', v || undefined)
      },
    },
  },
  render() {
    return (
      <df-time-picker
        ref='timePickerRef'
        v-model={this.value}
        attrs={this.$attrs}
        props={this.$props}
        on={this.$listeners}
        size={this.size}
        class='w100'
        scopedSlots={this.$scopedSlots}
      />
    )
  },
}
</script>
