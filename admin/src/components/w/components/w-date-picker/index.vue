<template>
  <el-date-picker
    ref="datePicker"
    v-bind="{ ...$props, ...$attrs }"
    v-on="$listeners"
    v-model="time"
    class="w100"
    :size="$attrs.size || wSize"
    :format="format_"
    :valueFormat="valueFormat_"
    :pickerOptions="pickerOptions_"
  />
</template>

<script>
export default {
  name: 'wDatePicker',
  props: {
    modelValue: { type: [String, Array, Date], default: new Date() },
    editable: { type: Boolean, default: false },
    type: { type: String, default: 'date' },
    format: { type: String },
    valueFormat: { type: String },
    pickerOptions: { type: Object, default: () => ({}) }
  },
  model: { prop: 'modelValue', event: 'update:modelValue' },
  computed: {
    wSize() {
      return this.$store.getters['size']
    },
    time: {
      get() {
        return this.modelValue
      },
      set(v) {
        this.$emit('update:modelValue', v)
      }
    },
    today() {
      return new Date().setHours(0, 0, 0, 0)
    },
    yesterday() {
      return this.today - 86400000
    },
    tomorrow() {
      return this.today + 86400000
    },
    format_() {
      if (this.format) return this.format
      switch (this.type) {
        case 'year':
          return 'yyyy'
        case 'month':
        case 'monthrange':
          return 'yyyy-MM'
        case 'datetime':
        case 'datetimerange':
          return 'yyyy-MM-dd HH:mm:ss'
        case 'week':
          break
        case 'date':
        case 'dates':
        case 'daterange':
        default:
          return 'yyyy-MM-dd'
      }
    },
    valueFormat_() {
      return this.valueFormat ? this.valueFormat : this.format_
    },
    pickerOptions_() {
      const disabledDate = this.pickerOptions.disabledDate
      switch (this.$w_fun.typeOf(disabledDate)) {
        case 'object':
          const { start, end, between = true } = disabledDate
          if (start === undefined && end === undefined) return this.pickerOptions

          const [startTime, endTime] = [this.makeTime(start), this.makeTime(end)]
          // disabledDate 方法会执行很多次，尽可能将判断写到外面，减少消耗
          if (startTime === undefined && endTime !== undefined) {
            return {
              ...this.pickerOptions,
              disabledDate: between ? time => time > endTime : time => time < endTime
            }
          } else if (startTime !== undefined && endTime === undefined) {
            return {
              ...this.pickerOptions,
              disabledDate: between ? time => time < startTime : time => time > startTime
            }
          } else {
            return {
              ...this.pickerOptions,
              disabledDate: between
                ? time => time < startTime || time > endTime
                : time => time > startTime && time < endTime
            }
          }
        default:
          return this.pickerOptions
      }
    }
  },
  methods: {
    makeTime(time) {
      switch (time) {
        case undefined:
          return undefined
        case 'today':
          return this.today
        case 'yesterday':
          return this.yesterday
        case 'tomorrow':
          return this.tomorrow
        default:
          return new Date(time).getTime()
      }
    }
  }
}
</script>
