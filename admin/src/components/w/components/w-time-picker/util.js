import dayjs from 'dayjs'
import isSameOrAfter from 'dayjs/plugin/isSameOrAfter.js'
import isSameOrBefore from 'dayjs/plugin/isSameOrBefore.js'
import objectSupport from 'dayjs/plugin/objectSupport.js'
dayjs.extend(isSameOrAfter)
dayjs.extend(isSameOrBefore)
dayjs.extend(objectSupport)

// 处理时间字符串为对象
const timeToObj = (time = '') => {
  const [hour, minute, second] = time.split(':').map(v => {
    const intV = parseInt(v)
    return isNaN(intV) ? undefined : intV
  })
  return { hour, minute, second }
}

// 处理实际有效时间
const makeRealTime = (step = 0, time, type) => {
  if (step <= 1) return time
  const diff = time % step
  if (!diff) return time
  switch (type) {
    case 'start':
      return step - diff + time
    case 'end':
      return time - diff
  }
}

// 制作有效时间点
export const makeTimePoint = (
  time = '',
  { h, hour, m, minute, s, second } = {},
  showSeconds = false,
  type
) => {
  switch (type) {
    case 'start': {
      const { hour: h_ = 0, minute: m_ = 0, second: s_ = 0 } = timeToObj(time)
      return dayjs({
        h: makeRealTime(h || hour, h_, 'start'),
        m: makeRealTime(m || minute, m_, 'start'),
        s: showSeconds ? makeRealTime(s || second, s_, 'start') : 0,
      })
    }
    case 'end': {
      const { hour: h_ = 23, minute: m_ = 59, second: s_ = 59 } = timeToObj(time)
      return dayjs({
        h: makeRealTime(h || hour, h_, 'end'),
        m: makeRealTime(m || minute, m_, 'end'),
        s: showSeconds ? makeRealTime(s || second, s_, 'end') : 0,
      })
    }
  }
}

// 制作有效可选的时间
export const makeTimeList = (start, end, step = 0, type) => {
  const timeNum = ['minute', 'second'].includes(type) ? 60 : 24
  const timeList = []
  for (let i = 0; i < timeNum; i++) {
    ;(!(start !== undefined) || i >= start) &&
      (!(end !== undefined) || i <= end) &&
      (!(step > 1) || !(i % step)) &&
      timeList.push(i)
  }
  return timeList
}

// 将时间转换为有效时间
export const toValidTime = (v, start, end, step = {}, showSeconds) => {
  const [year, _month, day, hour, minute, _second] = dayjs(v)
    .format('YYYY MM dd HH mm ss')
    .split(' ')
    .map(v => parseInt(v))
  const month = _month - 1
  const second = showSeconds ? _second : 0
  // 转换为今天的时间，用于比较
  const _v = dayjs({ hour, minute, second })
  // 早于最早时间，返回该天最早时间
  if (_v.isSameOrBefore(start))
    return dayjs({
      year,
      month,
      day,
      hour: start.hour(),
      minute: start.minute(),
      second: start.second(),
    })
  // 晚于最晚时间，返回该天最晚时间
  if (_v.isSameOrAfter(end))
    return dayjs({
      year,
      month,
      day,
      hour: end.hour(),
      minute: end.minute(),
      second: end.second(),
    })
  // 处于有效时间内 取往后最近的一个有效时间
  return dayjs({
    year,
    month,
    day,
    hour: makeRealTime(step.h || step.hour, hour, 'start'),
    minute: makeRealTime(step.m || step.minute, minute, 'start'),
    second: showSeconds ? makeRealTime(step.s || step.second, second, 'start') : 0,
  })
}
