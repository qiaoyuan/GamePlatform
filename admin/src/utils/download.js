import mime from './mime.js'
import axios from 'axios'
import { getToken } from './auth'
import { Message } from 'element-ui'

const defaultType = 'excel'
const defaultElement = 'a'

const request = axios.create({
  baseURL: process.env.VUE_APP_BASE_API,
  headers: { 'Authorization': getToken() },
  timeout: 60000,
  withCredentials: true
})

request.interceptors.response.use(
  response => {
    return Promise.resolve(response)
  },
  error => {
    let message = ''
    message = error.toString().indexOf(410) ? '导出超时，可能是数据太多，可筛选后再导出' : '服务器繁忙，请稍后重试'
    Message({
      message: message,
      duration: 5000,
      type: 'error'
    })
    return Promise.reject(message, error)
  }
)

// 下载
const linkResource = (name, data, config, element) => {
  const blob = new Blob([data], config)
  const downloadElement = document.createElement(element)
  const href = window.URL.createObjectURL(blob)
  downloadElement.href = href
  downloadElement.download = name
  document.body.appendChild(downloadElement)
  downloadElement.click()
  document.body.removeChild(downloadElement)
  window.URL.revokeObjectURL(href)
  return '下载成功'
}

const downloadOperation = (options) => {
  var commonConfig = {
    url: options.url,
    method: options.method,
    responseType: 'blob'
  }
  const typeValue = mime.types[options['type']] || mime.types[defaultType]
  const charset = options['charset'] || mime.charset
  const blobConfig = { type: typeValue + ';' + charset }
  const appendDataConfig = options.method === 'get' ? { params: options.params } : { query: options.params }
  const config = Object.assign(commonConfig, appendDataConfig)
  const ele = options['element'] || defaultElement
  return request(config).then(response => {
    const attachFileName = response.headers['content-disposition']
    if (attachFileName) {
      const fileName = decodeURI(attachFileName.substring(attachFileName.indexOf('"') + 1, (attachFileName.length - 1)))
      return Promise.resolve(linkResource(fileName, response.data, blobConfig, ele))
    } else {
      const fileReader = new FileReader()
      fileReader.onload = function() {
        try {
          const jsonData = JSON.parse(this.result)
          if (jsonData.code === 0) {
            Message({
              message: jsonData.msg,
              type: 'error',
              duration: 5 * 1000
            })
          }
        } catch (err) { // 解析成对象失败，说明是正常的文件流
          console.log(err)
        }
      }
      fileReader.readAsText(response.data)
    }
  })
}

export function download(url, data) {
  return downloadOperation({
    url: `${url}`,
    method: 'get',
    params: data
  })
}
