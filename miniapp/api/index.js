
import request from '@/utils/request'
/**
 * 登录
 */
export function login(data) {
	return request({
		url: 'index/user/login',
		data,
		method: 'POST'
	})
}

/**
 * 获取首页信息
 */
export function getHomeInfo() {
  return request({
    url: '/index/home/index',
    method: 'POST',
  })
}

/**
 * 分类左侧列表页
 */
export function getCategory() {
	return request({
		url: '/index/questionnaires/catList',
		method: 'POST'
	})
}

/**
 * 问卷列表页面
 */
export function getCategoryList(data) {
	return request({
		url: '/index/questionnaires/list',
		method: 'POST',
		data
	})
}

/**
 * 获取文件详情
 */
export function getQuestionDetail(id) {
	return request({
		url: `/index/questionnaires/get?id=${id}`,
		method: 'POST'
	})
}

/**
 * 很具id获取问题列表
 */
export function getQusetionList(id) {
	return request({
		url: `/index/questions/list?questionnaire_id=${id}`,
		method: 'POST'
	})
}

/**
 * 提交答案
 */
export function submitAnswer(data) {
	return request({
		url: '/index/user/createRes',
		method: 'POST',
		data
	})
}

/**
 * 报告页
 */
export function reportInfo(id){
	return request({
		url: `/index/user/report?questionnaire_id=${id}`,
		method: 'POST'
	})
}


/**
 * 历史报告is_ok'=1：完成的报告  is_ok=0: 未完成报告
 */
export function reportList(data) {
	return request({
		url: `/index/user/reportList`,
		method: 'POST',
		data: {
			is_ok: data
		}
	})
}

/**
 * 获取订单详情
 */
export function getOrderInfo(data) {
	return request({
		url: '/index/order/create',
		method: 'POST',
		data
	})
}