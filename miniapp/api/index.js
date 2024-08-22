
import request from '@/utils/request'

/**
 * 获取首页信息
 */
export function getHomeInfo() {
  return request({
    url: '/index/home/index',
    method: 'GET',
  })
}

/**
 * 分类左侧列表页
 */
export function getCategory() {
	return request({
		url: '/index/questionnaires/catList',
		method: 'GET'
	})
}

/**
 * 问卷列表页面
 */
export function getCategoryList(params) {
	return request({
		url: '/index/questionnaires/list',
		method: 'GET',
		params
	})
}

/**
 * 获取文件详情
 */
export function getQuestionDetail(id) {
	return request({
		url: `/index/questionnaires/get?id=${id}`,
		method: 'GET'
	})
}