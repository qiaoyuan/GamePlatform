<template>
  <transition name="el-zoom-in-top" @after-leave="$emit('dodestroy')">
    <div
      v-show="visible"
      class="el-time-range-picker el-picker-panel el-popper"
      :class="popperClass"
    >
      <div class="el-time-range-picker__content">
        <div class="el-time-range-picker__cell">
          <div class="el-time-range-picker__header">开始时间</div>
          <div
            :class="{ 'has-seconds': showSeconds, 'is-arrow': arrowControl }"
            class="el-time-range-picker__body el-time-panel__content"
          >
            <time-spinner
              ref="minSpinner"
              :show-seconds="showSeconds"
              :am-pm-mode="amPmMode"
              @change="handleMinChange"
              :arrow-control="arrowControl"
              @select-range="setMinSelectionRange"
              :date="minDate"
              :start="start_"
              :end="minEnd"
              :step="step"
            />
          </div>
        </div>
        <div class="el-time-range-picker__cell">
          <div class="el-time-range-picker__header">结束时间</div>
          <div
            :class="{ 'has-seconds': showSeconds, 'is-arrow': arrowControl }"
            class="el-time-range-picker__body el-time-panel__content"
          >
            <time-spinner
              ref="maxSpinner"
              :show-seconds="showSeconds"
              :am-pm-mode="amPmMode"
              @change="handleMaxChange"
              :arrow-control="arrowControl"
              @select-range="setMaxSelectionRange"
              :date="maxDate"
              :start="maxStart"
              :end="end_"
              :step="step"
            />
          </div>
        </div>
      </div>
      <div class="el-time-panel__footer">
        <button type="button" class="el-time-panel__btn cancel" @click="handleCancel()">
          取消
        </button>
        <button
          type="button"
          class="el-time-panel__btn confirm"
          @click="handleConfirm()"
          :disabled="btnDisabled"
        >
          确定
        </button>
      </div>
    </div>
  </transition>
</template>

<script type="text/babel">
import { makeTimePoint } from '../../util'
import {
  parseDate,
  limitTimeRange,
  modifyDate,
  clearMilliseconds,
  timeWithinRange,
} from 'element-ui/src/utils/date-util'
import Locale from 'element-ui/src/mixins/locale'
import TimeSpinner from '../basic/time-spinner'
import dayjs from 'dayjs'

const MIN_TIME = parseDate('00:00:00', 'HH:mm:ss')
const MAX_TIME = parseDate('23:59:59', 'HH:mm:ss')

const minTimeOfDay = function (date) {
  return modifyDate(MIN_TIME, date.getFullYear(), date.getMonth(), date.getDate())
}

const maxTimeOfDay = function (date) {
  return modifyDate(MAX_TIME, date.getFullYear(), date.getMonth(), date.getDate())
}

// increase time by amount of milliseconds, but within the range of day
const advanceTime = function (date, amount) {
  return new Date(Math.min(date.getTime() + amount, maxTimeOfDay(date).getTime()))
}

export default {
  mixins: [Locale],

  components: { TimeSpinner },

  props: {
    start: { type: String, default: '' },
    end: { type: String, default: '' },
    step: { type: Object, default: () => ({}) },
  },

  computed: {
    showSeconds() {
      return (this.format || '').indexOf('ss') !== -1
    },

    offset() {
      return this.showSeconds ? 11 : 8
    },

    spinner() {
      return this.selectionRange[0] < this.offset ? this.$refs.minSpinner : this.$refs.maxSpinner
    },

    btnDisabled() {
      return this.minDate.getTime() > this.maxDate.getTime()
    },
    amPmMode() {
      if ((this.format || '').indexOf('A') !== -1) return 'A'
      if ((this.format || '').indexOf('a') !== -1) return 'a'
      return ''
    },
    start_() {
      return makeTimePoint(this.start, this.step, this.showSeconds, 'start')
    },
    // 最大值的起始值，应大于等于最小值
    maxStart() {
      return makeTimePoint(
        (this.minDate && dayjs(this.minDate).format(`HH:mm${this.showSeconds ? ':ss' : ''}`)) ||
          this.start,
        this.step,
        this.showSeconds,
        'start'
      )
    },
    end_() {
      return makeTimePoint(this.end, this.step, this.showSeconds, 'end')
    },
    minEnd() {
      return makeTimePoint(
        (this.maxDate && dayjs(this.maxDate).format(`HH:mm${this.showSeconds ? ':ss' : ''}`)) ||
          this.end,
        this.step,
        this.showSeconds,
        'end'
      )
    },
  },

  data() {
    return {
      popperClass: '',
      minDate: new Date(),
      maxDate: new Date(),
      value: [],
      oldValue: [new Date(), new Date()],
      defaultValue: null,
      format: 'HH:mm:ss',
      visible: false,
      selectionRange: [0, 2],
      arrowControl: false,
    }
  },

  watch: {
    value(value) {
      if (Array.isArray(value)) {
        this.minDate = new Date(value[0])
        this.maxDate = new Date(value[1])
      } else {
        if (Array.isArray(this.defaultValue)) {
          this.minDate = new Date(this.defaultValue[0])
          this.maxDate = new Date(this.defaultValue[1])
        } else if (this.defaultValue) {
          this.minDate = new Date(this.defaultValue)
          this.maxDate = advanceTime(new Date(this.defaultValue), 60 * 60 * 1000)
        } else {
          this.minDate = new Date()
          this.maxDate = advanceTime(new Date(), 60 * 60 * 1000)
        }
      }
    },

    visible(val) {
      if (val) {
        this.oldValue = this.value
        this.$nextTick(() => this.$refs.minSpinner.emitSelectRange('hours'))
      }
    },
  },

  methods: {
    handleClear() {
      this.$emit('pick', null)
    },

    handleCancel() {
      this.$emit('pick', this.oldValue)
    },

    handleMinChange(date, type) {
      this.minDate = clearMilliseconds(date)
      this.handleChange()
      const minSpinnerRef = this.$refs.minSpinner,
        maxSpinnerRef = this.$refs.maxSpinner
      const [maxH, maxM] = dayjs(this.maxDate).format('HH:mm').split(':'),
        [minH, minM] = dayjs(this.minDate).format('HH:mm').split(':')
      switch (type) {
        case 'hours':
          minSpinnerRef.adjustSpinner('minutes', minSpinnerRef.minutes)
          this.showSeconds && minSpinnerRef.adjustSpinner('seconds', minSpinnerRef.seconds)
          if (minH === maxH) {
            maxSpinnerRef.adjustSpinner('minutes', maxSpinnerRef.minutes)
            this.showSeconds && maxSpinnerRef.adjustSpinner('seconds', maxSpinnerRef.seconds)
          }
          break
        case 'minutes':
          this.showSeconds && minSpinnerRef.adjustSpinner('seconds', minSpinnerRef.seconds)
          if (minM === maxM && this.showSeconds) {
            maxSpinnerRef.adjustSpinner('seconds', maxSpinnerRef.seconds)
          }
          break
      }
      maxSpinnerRef.adjustSpinner(type, maxSpinnerRef[type])
    },

    handleMaxChange(date, type) {
      this.maxDate = clearMilliseconds(date)
      this.handleChange()
    },

    handleChange() {
      if (this.isValidValue([this.minDate, this.maxDate])) {
        this.$refs.minSpinner.selectableRange = [[minTimeOfDay(this.minDate), this.maxDate]]
        this.$refs.maxSpinner.selectableRange = [[this.minDate, maxTimeOfDay(this.maxDate)]]
        this.$emit('pick', [this.minDate, this.maxDate], true)
      }
    },

    setMinSelectionRange(start, end) {
      this.$emit('select-range', start, end, 'min')
      this.selectionRange = [start, end]
    },

    setMaxSelectionRange(start, end) {
      this.$emit('select-range', start, end, 'max')
      this.selectionRange = [start + this.offset, end + this.offset]
    },

    handleConfirm(visible = false) {
      const minSelectableRange = this.$refs.minSpinner.selectableRange
      const maxSelectableRange = this.$refs.maxSpinner.selectableRange

      this.minDate = limitTimeRange(this.minDate, minSelectableRange, this.format)
      this.maxDate = limitTimeRange(this.maxDate, maxSelectableRange, this.format)

      this.$emit('pick', [this.minDate, this.maxDate], visible)
    },

    adjustSpinners() {
      this.$refs.minSpinner.adjustSpinners()
      this.$refs.maxSpinner.adjustSpinners()
    },

    changeSelectionRange(step) {
      const list = this.showSeconds ? [0, 3, 6, 11, 14, 17] : [0, 3, 8, 11]
      const mapping = ['hours', 'minutes'].concat(this.showSeconds ? ['seconds'] : [])
      const index = list.indexOf(this.selectionRange[0])
      const next = (index + step + list.length) % list.length
      const half = list.length / 2
      if (next < half) {
        this.$refs.minSpinner.emitSelectRange(mapping[next])
      } else {
        this.$refs.maxSpinner.emitSelectRange(mapping[next - half])
      }
    },

    isValidValue(date) {
      return (
        Array.isArray(date) &&
        timeWithinRange(this.minDate, this.$refs.minSpinner.selectableRange) &&
        timeWithinRange(this.maxDate, this.$refs.maxSpinner.selectableRange)
      )
    },

    handleKeydown(event) {
      const keyCode = event.keyCode
      const mapping = { 38: -1, 40: 1, 37: -1, 39: 1 }

      // Left or Right
      if (keyCode === 37 || keyCode === 39) {
        const step = mapping[keyCode]
        this.changeSelectionRange(step)
        event.preventDefault()
        return
      }

      // Up or Down
      if (keyCode === 38 || keyCode === 40) {
        const step = mapping[keyCode]
        this.spinner.scrollDown(step)
        event.preventDefault()
        return
      }
    },
  },
}
</script>
