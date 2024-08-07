// 手机号
export const phone = /^1[3-9]\d{9}$/
// 身份证
export const idCard =
  /^[1-9]\d{5}(18|19|([23]\d))\d{2}((0[1-9])|(10|11|12))(([0-2][1-9])|10|20|30|31)\d{3}[0-9Xx]$/
export const disableIdCard =
  /^[1-9]\d{5}(18|19|([23]\d))\d{2}((0[1-9])|(10|11|12))(([0-2][1-9])|10|20|30|31)\d{3}[0-9Xx][1-7][1-4]$/
export const email = /^[a-zA-Z0-9_-]+@[a-zA-Z0-9_-]+(\.[a-zA-Z0-9_-]+)+$/
export const url = /(https?|ftp|file):\/\/[-A-Za-z0-9+&@#/%?=~_|!:,.;]+[-A-Za-z0-9+&@#/%=~_|]/
export const id =
  /^(\d{1,2}|1\d\d|2[0-4]\d|25[0-5])\.(\d{1,2}|1\d\d|2[0-4]\d|25[0-5])\.(\d{1,2}|1\d\d|2[0-4]\d|25[0-5])\.(\d{1,2}|1\d\d|2[0-4]\d|25[0-5])$/
// 车牌号
export const car =
  /^[京津沪渝冀豫云辽黑湘皖鲁新苏浙赣鄂桂甘晋蒙陕吉闽贵粤青藏川宁琼使领A-Z]{1}[A-Z]{1}[A-Z0-9]{4}[A-Z0-9挂学警港澳]{1}$/
// 金融，携带0-2位小数
export const price = {
  reg: /^(-?)(([1-9]{1}\d{0,6})|(0{1}))(\.\d{1,2})?$/,
  msg: '至多为7位整数和2位小数',
}
// 整数
export const int = { reg: /^-?[0-9]*$/, msg: '应为整数' }
// 非负整数
export const intEgt0 = { reg: /^[1-9]\d*|0$/, msg: '应为非负整数' }
// 正整数
export const intGt0 = { reg: /^[1-9]\d*$/, msg: '应为正整数' }
// 非正整数
export const intElt0 = { reg: /^-[1-9]\d*|0$/, msg: '应为非正整数' }
// 负整数
export const intLt0 = { reg: /^-[1-9]\d*$/, msg: '应为负整数' }
// 非负数的正整数
export const positiveInteger = /^[1-9]\d*|0$/
// 座机号
export const landline = /^(((\d{3,4}-)?[0-9]{7,8}))$/
// 手机号加座机号
export const telphone = /^(((\d{3,4}-)?[0-9]{7,8})|(1[3-9]\d{9}))$/
// 字符串是否包含数字
export const containsNumbers = /^((?!(.+)?\d(.+)?).)*$/
export default {
  phone,
  idCard,
  disableIdCard,
  email,
  url,
  id,
  car,
  price,
  int,
  intEgt0,
  intGt0,
  intElt0,
  intLt0,
  positiveInteger,
  landline,
  telphone,
  containsNumbers,
}
