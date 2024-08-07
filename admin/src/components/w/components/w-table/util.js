// 处理额外常驻按钮数据状态
export const makeOtherOperatesStatus = (v, row, $index) => {
  switch (typeof v) {
    case 'string':
    case 'boolean':
      return v
    case 'function':
      return v(row, $index)
  }
}
export const searchTypeList = {
  like: 'like',
  match: 'match',
  min: 'min',
  max: 'max',
  percentageMax: 'percentageMax',
  percentageMin: 'percentageMin',
  date: 'match',
  month: 'match',
  year: 'match',
  daterange: 'range',
  datebetween: 'between',
  dateint: 'between',
  monthrange: 'range',
  timerange: 'range',
  timeint: 'between',
  datetimerange: 'range',
  tree: 'multiple',
  multiple: 'multiple',
  radio: 'radio',
  status: 'multiple',
  remote: 'multiple',
  find: 'match',
  hselect: 'multiple'
}
