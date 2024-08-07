<script>
import { typeOf } from '@/utils/w'
import dayjs from 'dayjs'
import DfDateTimePicker from '@/components/w/components/w-time-picker/src/picker/date-picker'
export default {
  name: 'WDateTimePicker',
  components: { DfDateTimePicker },
  props: {
    modelValue: { type: [String, Date, Array] },
    editable: { type: Boolean, default: false },
    placeholder: { type: String, default: '请选择日期时间' },
    startPlaceholder: { type: String, default: '开始日期时间' },
    endPlaceholder: { type: String, default: '结束日期时间' },
    isRange: { type: Boolean, default: false },
    format: { type: String, default: 'yyyy-MM-dd HH:mm' },
    valueFormat: { type: String, default: 'yyyy-MM-dd HH:mm' },
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
    today() {
      return new Date().setHours(23, 59, 59, 999)
    },
    yesterday() {
      return this.today - 86400000
    },
    tomorrow() {
      return this.today + 86400000
    },
    pickerOptions_() {
      const disabledDate = this.pickerOptions.disabledDate
      switch (this.$w_fun.typeOf(disabledDate)) {
        case 'object':
          const { start, end, between = true } = disabledDate
          if ([null, undefined].includes(start) && [null, undefined].includes(end))
            return this.pickerOptions

          const [startTime, endTime] = [this.makeTime(start), this.makeTime(end)]
          // disabledDate 方法会执行很多次，尽可能将判断写到外面，减少消耗
          if ([null, undefined].includes(startTime) && ![null, undefined].includes(endTime)) {
            return {
              ...this.pickerOptions,
              disabledDate: between ? time => time > endTime : time => time < endTime,
            }
          } else if (
            ![null, undefined].includes(startTime) &&
            [null, undefined].includes(endTime)
          ) {
            return {
              ...this.pickerOptions,
              disabledDate: between ? time => time < startTime : time => time > startTime,
            }
          } else {
            return {
              ...this.pickerOptions,
              disabledDate: between
                ? time => time < startTime || time > endTime
                : time => time > startTime && time < endTime,
            }
          }
        default:
          return this.pickerOptions
      }
    },
  },
  data() {
    return {
      holiday: {},
    }
  },
  methods: {
    async getHoliday(ym) {
      this.holiday = { ...this.holiday, [ym]: [] }
    },
    cellClassName(date) {
      const ym = dayjs(date).format('YYYY-MM')
      if (!this.holiday[ym]) {
        this.holiday[ym] = []
        this.getHoliday(ym)
        return ''
      }
      return this.holiday[ym].includes(dayjs(date).format('YYYY-MM-DD')) ? 'y-holiday' : ''
    },
    makeTime(time) {
      switch (time) {
        case '-':
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
    },
  },
  render() {
    return (
      <df-date-time-picker
        ref='dateTimePickerRef'
        v-model={this.value}
        attrs={this.$attrs}
        props={{
          ...this.$props,
          pickerOptions: {
            ...this.pickerOptions_,
            cellClassName: this.cellClassName,
          },
        }}
        on={this.$listeners}
        type={this.isRange ? 'datetimerange' : 'datetime'}
        size={this.size}
        class='w100'
        scopedSlots={this.$scopedSlots}
      />
    )
  },
}
</script>
