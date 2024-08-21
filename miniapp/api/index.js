
import request from '@/utils/request'

/**
 * 获取首页信息
 */
export function getHomeInfo() {
  return request({
    url: '/column/get',
    method: 'GET',
  })
}