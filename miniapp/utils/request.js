// import store from '@/store'
import config from '@/config'
import { getAccessToken, getRefreshToken } from '@/utils/auth'
import errorCode from '@/utils/errorCode'
import { toast, showConfirm, tansParams } from '@/utils/common'

let timeout = 10000
const baseUrl = config.baseUrl ;

const request = config => {
  // 是否需要设置 token
  const isToken = (config.headers || {}).isToken === false
  config.header = config.header || {}
  if (getAccessToken() && !isToken) {
    config.header['Authorization'] = 'Bearer ' + getAccessToken()
  }
  // get请求映射params参数
  if (config.params) {
    let url = config.url + '?' + tansParams(config.params)
    url = url.slice(0, -1)
    config.url = url
  }
  return new Promise((resolve, reject) => {
    uni.request({
        method: config.method || 'get',
        timeout: config.timeout ||  timeout,
        url: baseUrl + config.url,
        data: config.data,
        // header: config.header,
        header: config.header,
        dataType: 'json'
      }).then(response => {
        // let [err, res] = response
        // if (err) {
        //   toast('后端接口连接异常')
        //   reject('后端接口连接异常')
        //   return
        // }
		let res = response
        const code = res.data.code || 0
        const msg = errorCode[code] || res.data.msg || errorCode['default']
        if (code === 401) {
			//未登录可能是没有刷新令牌，这里刷新一次然后再去请求
			let refreshToken =  getRefreshToken()
			if(refreshToken){
				//没有刷新令牌
				logOut()
			}else {
				//刷新令牌
			}

          reject('无效的会话，或者会话已过期，请重新登录。')
        } else if (code === 500) {
          toast(msg)
          reject('500')
        } else if (code !== 0) {
          toast(msg)
          reject(code)
        }
        resolve(res.data)
      })
      .catch(error => {
		  console.log(error)
        let { message } = error
        if (message === 'Network Error') {
          message = '后端接口连接异常'
        } else if (message.includes('timeout')) {
          message = '系统接口请求超时'
        } else if (message.includes('Request failed with status code')) {
          message = '系统接口' + message.substr(message.length - 3) + '异常'
        }
        toast(message)
        reject(error)
      })
  })
}
// function logOut(){
// 	showConfirm('登录状态已过期，您可以继续留在该页面，或者重新登录?').then(res => {
// 	  if (res.confirm) {
// 	    store.dispatch('LogOut').then(res => {
// 	      uni.reLaunch({ url: '/pages/login' })
// 	    })
// 	  }
// 	})
// }


export default request
