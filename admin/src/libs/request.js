import axios from 'axios'
import { Message, MessageBox} from 'element-ui'
import store from '@/store'
import { getToken, setToken } from '@/utils/auth'
import { showLoading, hideLoading } from '@/utils/loading'

// 创建axios实例
let baseU = process.env.VUE_APP_BASE_API
if (location.host.startsWith('t')) {
  baseU = baseU.replace('aapi.', 'taapi.')
}

const showMessage = (message = '', type = 'error', duration = 5000) =>
  Message({ message, duration, type })


const service = axios.create({
  baseURL: baseU, // api 的 base_url process.env.BASE_API
  timeout: 12000 // 请求超时时间
  ,withCredentials: true
})

// request拦截器
service.interceptors.request.use(
  config => {
    // showLoading()
    if (getToken()) {
      config.headers['Authorization'] = getToken()
    }
    return config
  },
  error => {
    // hideLoading()
    // Do something with request error
    Promise.reject(error)
  }
)

const handleError = (error, reject) => {
  const lowerMessage = (error.message && error.message.toLowerCase()) || ''
  let message = '请求失败 请刷新后重试'
  if (lowerMessage.includes('network error')) {
    message = '连接失败 请检查您的网络设置'
  } else if (lowerMessage.includes('timeout')) {
    message = '请求超时 请检查您的网络设置'
  } else if (lowerMessage.includes('404')) {
    message = '404 资源不存在'
  } else if (lowerMessage.includes('500')) {
    message = '500 服务器错误'
  }
  showMessage(message)
  return reject(error)
}

const request = (config = {}, loading = true, message = true) => {
  loading && showLoading()
  return new Promise((resolve, reject) =>
    service
      .request(config)
      .then(response => {
        /**
         * code为非20000是抛错 可结合自己业务进行修改
         */
        const res = response.data
        if (res.code !== 0) {
          if (res.code === 2) {
            // token 验证失败
            if (!store.state.user.loginExpireNotice) {
              store.dispatch('setLoginExpireNotice', true)
              message && showMessage(res.message)
            }
            setTimeout(() => {
              location.reload()
            }, 1000)
          } else {
            message && showMessage(res.message)
          }
          return reject(res)
        } else {
          if (res.message && res.message !== '') {
            message && showMessage(res.message, 'success', 3000)
          }
          const token = response.headers.authorization
          if (token) {
            store.dispatch('refreshToken', token)
          }
          return resolve(response.data)
        }
      })
      .catch(error => handleError(error, reject))
      .finally(() => loading && hideLoading())
  )
}

export function post(url, data, config={}, loading = false, message = true) {
  return request({
    url: url,
    method: 'post',
    data: data || {},
    ...config
  }, loading, message)
}
export function get(url, data, config={}, loading = true, message = true) {
  return request({
    url: url,
    method: 'get',
    data: data || {},
    ...config
  }, loading, message)
}

