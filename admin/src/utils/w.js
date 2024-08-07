import dayjs from 'dayjs'
import { hasPermission } from '@/directive/w/directive/p'
import { typeOf } from '@/libs/libs'


export function base64ToFile(dataURL, fileName) {
  return new Promise((resolve, reject) => {
    let arr = dataURL.split(','),
      mime = arr[0].match(/:(.*?);/)[1],
      bstr = atob(arr[1]),
      n = bstr.length,
      u8arr = new Uint8Array(n)
    while (n--) {
      u8arr[n] = bstr.charCodeAt(n)
    }
    resolve(new File([u8arr], fileName, { type: mime }))
  })
}



export const deepCopy = data => {
  const t = typeOf(data)
  let o

  if (t === 'array') {
    o = []
  } else if (t === 'object') {
    o = {}
  } else {
    return data
  }

  if (t === 'array') {
    for (let i = 0; i < data.length; i++) {
      o.push(deepCopy(data[i]))
    }
  } else if (t === 'object') {
    for (let i in data) {
      o[i] = deepCopy(data[i])
    }
  }
  return o
}

export function setObj(to, from) {
  for (const i in to) {
    if (typeof from[i] !== 'undefined') {
      to[i] = from[i]
    }
  }
}

// keyName 代表返回数组中收集的字段名，默认value，有时候需要直接显示，可返回 label
export function findTreePath(tree, value, keyName = 'value') {
  const path = []
  const deepFind = (tree, value) => {
    for (const item of tree) {
      if (item.children) {
        if (deepFind(item.children, value, keyName)) {
          path.unshift(item[keyName])
          return true
        }
      } else {
        if (item.value === value) {
          path.push(item[keyName])
          return true
        }
      }
    }
  }
  deepFind(tree, value)
  return path
}

// 类似于 findTreePath，向下递归查找并返回路径，在找到第一个指定值时停止，不要求必须是最底层的数据
// 第三个参数 keyName，用于指定返回 parents 的字段名，传入 true 则返回所有字段
export const findTreeParents = (data = [], id, keyName = 'value', parents = []) => {
  const cParents = [...parents]
  const result = data.some(item => {
    const currentValue = keyName === true ? item : item[keyName]
    if (item.value === id) {
      parents = [...cParents, currentValue]
      return true
    }
    if (item.children && item.children.length) {
      parents = findTreeParents(item.children, id, keyName, [...cParents, currentValue])
      return parents.length
    }
    return false
  })
  return result ? parents : []
}

export const fill0 = (payload = '', length = 2) => {
  const zeroNum = length - `${parseInt(payload)}`.length
  let result = `${payload}`
  if (zeroNum > 0) {
    for (let i = 0; i < zeroNum; i++) {
      result = `0${result}`
    }
  }
  return result
}

export const dateToString = (format = 'YYYY-MM-DD', date = new Date()) =>
  dayjs(date).format(format.replace('yyyy', 'YYYY').replace('dd', 'DD'))

export const objToQuery = obj => {
  const queryArr = []
  Object.keys(obj).forEach(key => queryArr.push(`${key}=${obj[key]}`))
  const queryStr = queryArr.join('&')
  return queryStr ? `?${queryStr}` : ''
}

export const queryToObj = query => {
  const result = {}
  query &&
    query.split('&').forEach(i => {
      const [k, v] = i.split('=')
      result[k] = v
    })
  return result
}

// 对 query 参数进行排序（主要针对全名报错）
export const sortQuery = href => {
  const [path, query = ''] = href.split('?')
  const queryArr = queryToObj(query)
  const baseQueryArr = {}
  queryArr.tabIndex !== undefined && (baseQueryArr.tabIndex = queryArr.tabIndex)
  queryArr.wradio !== undefined && (baseQueryArr.wradio = queryArr.wradio)
  return (
    path +
    objToQuery(
      Object.keys(queryArr).reduce((pre, k) => {
        pre[k] = queryArr[k]
        return pre
      }, baseQueryArr)
    )
  )
}

export const computedTablePermission = (defaultSetting, props, permissions) => {
  const d = { ...defaultSetting }
  permissions.permissionType === 'disabled'
    ? Object.keys(d).forEach(k => {
        const cP = permissions[k]
        const isP = cP !== false
        typeof props[k] === 'undefined'
          ? // 未设置，为 true 且需要权限验证 则进行权限验证并添加 xxxDisabled 字段
            d[k] && isP && (d[`${k}Disabled`] = !hasPermission(cP))
          : // 自定义，向 d 赋值并同上验证
            (d[k] = props[k]) && props[k] && isP && (d[`${k}Disabled`] = !hasPermission(cP))
      })
    : Object.keys(d).forEach(k => {
        const cP = permissions[k]
        const isP = cP !== false
        d[k] =
          typeof props[k] === 'undefined'
            ? // 未设置，为 true 则进行权限验证
              d[k]
              ? isP
                ? hasPermission(cP)
                : true
              : false
            : // 自定义，向 d 赋值并同上验证
            props[k]
            ? isP
              ? hasPermission(cP)
              : true
            : false
      })
  return d
}

export const ellipsisStr = (str = '', length = 4) => {
  return str.length > length ? `${str.substring(0, length)}...` : str
}

export const dataToNum = data => {
  switch (typeof data) {
    case 'string':
      let newStr = ''
      for (const i in data) {
        if (!isNaN(Number(data[i]))) {
          newStr = data.substring(i)
          break
        }
      }
      const newNum = parseFloat(newStr)
      return isNaN(newNum) ? 0 : newNum
    case 'number':
      return data
    case 'boolean':
      return data ? 1 : 0
    default:
      return 0
  }
}
// 查找字典中的label
export const selectLabel = (dict, value, valueKey = 'value', labelKey = 'label') => {
  if (typeOf(value) === 'array') {
    let arr = dict.filter(f => value.includes(f[valueKey]))
    return arr.map(m => m[labelKey])
  }
  let item = dict.find(f => f[valueKey] === value)
  return item ? item[labelKey] : ''
}

// 类似于 selectLabel，未找到匹配字典的值会将原值返回，而不是舍弃
export const valueToLabel = (dict = [], value, valueKey = 'value', labelKey = 'label') => {
  if (typeOf(value) === 'array') {
    return value.map(i => {
      const item = dict.find(f => f[valueKey] === i)
      return item ? item[labelKey] : i
    })
  }
  const item = dict.find(f => f[valueKey] === value)
  return item ? item[labelKey] : value
}

// 将传入数据转换为 upload 和 enclosure 组件 能识别的格式
export const dataToFile = value => {
  switch (typeOf(value)) {
    case 'string':
      return value
        ? [
            {
              name: value,
              path: value,
              url: value,
              size: 0,
              percentage: 100,
              status: 'success',
              uid: Math.random(),
            },
          ]
        : []
    case 'object':
      return value.path || value.url
        ? [
            {
              name: value.name || value.path || value.url,
              path: value.path || value.url,
              url: value.url || value.path,
              size: 0,
              percentage: 100,
              status: 'success',
              uid: Math.random(),
              verify_status: value.verify_status || 0,
            },
          ]
        : []
    case 'array':
      return value
        .map(i => {
          switch (typeOf(i)) {
            case 'string':
              return i
                ? {
                    name: i,
                    path: i,
                    url: i,
                    size: 0,
                    percentage: 100,
                    status: 'success',
                    uid: Math.random(),
                  }
                : undefined
            case 'object':
              return i.path || i.url
                ? {
                    name: i.name || i.path || i.url,
                    path: i.path || i.url,
                    url: i.url || i.path,
                    size: 0,
                    percentage: 100,
                    status: 'success',
                    uid: Math.random(),
                    verify_status: i.verify_status || 0,
                  }
                : undefined
          }
        })
        .filter(i => i)
    default:
      return []
  }
}

// 将对象转换为 formData
export const toFormData = data => {
  const formData = new FormData()
  Object.keys(data).forEach(i => formData.append(i, data[i]))
  return formData
}

// 判断文件类型
export const getFileType = fileName => {
  const index = fileName.lastIndexOf('.')
  const extension = fileName.substr(index + 1)
  if (['png', 'jpg', 'jpeg', 'bmp', 'gif', 'webp', 'psd', 'svg', 'tiff'].includes(extension)) {
    return 'img'
  }
  if (extension === 'pdf') return 'pdf'
  return 'other'
}

// 根据传入的 key 递归取值
export const keysToValue = (data, keys = '') => {
  if (!data) return
  let result = data
  return keys.split('.').every(k => {
    if (!result || !Object.hasOwnProperty.call(result, k)) return false
    result = result[k]
    return true
  })
    ? result
    : undefined
}

// 树状数据转扁平化
export const toPlain = (list = []) => {
  let result = []
  for (const item of list) {
    const { children, ...menu } = item
    result.push(menu)
    if (children && children.length) {
      result = result.concat(toPlain(children))
    }
  }
  return result
}

export const copyText = text => {
  const textarea = document.createElement('textarea')
  textarea.value = text
  document.body.appendChild(textarea)
  textarea.select()
  document.execCommand('Copy')
  document.body.removeChild(textarea)
}

export function deepClone(obj) {
  // 对常见的“非”值，直接返回原来值
  if ([null, undefined, NaN, false].includes(obj)) return obj
  if (typeof obj !== 'object' && typeof obj !== 'function') {
    // 原始类型直接返回
    return obj
  }
  const o = array(obj) ? [] : {}
  for (const i in obj) {
    if (obj.hasOwnProperty(i)) {
      o[i] = typeof obj[i] === 'object' ? deepClone(obj[i]) : obj[i]
    }
  }

  function array(value) {
    if (typeof Array.isArray === 'function') {
      return Array.isArray(value)
    }
    return Object.prototype.toString.call(value) === '[object Array]'
  }
  return o
}
