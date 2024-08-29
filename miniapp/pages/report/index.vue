<template>
	<view class="result">
		<!-- <NavBar
		   :title="navTitle"
		   @back="onClickBack" /> -->
				<rich-text :nodes="ReportObj"></rich-text>
	</view>
</template>

<script>
	import { reportInfo } from '@/api/index.js'
	export default {
		 onUnload() {// 页面销毁返回首页
		  // 如果多端发布的话判断一下当前操作的客户端 
			//#ifdef MP-WEIXIN
			uni.switchTab({
				url: '/pages/index/index'
			})
			//#endif
		},
		onLoad(options) {
			uni.showToast({
				title: '加载中',
				mask:true,
				icon: 'loading'
			})
			reportInfo(options.id).then(res => {
				const htmlString = res.data.info.text
				this.ReportObj = res.data.info.text
				uni.hideToast()
			})
		},
		data() {
			return {
				ReportObj: null,
				navTitle: '报告内容'
			}
		},
		methods: {
			onClickBack() {
				uni.switchTab({
					url: "/pages/index/index"
				})
			}
		},
	}
</script>

<style lang="scss" scoped>
	.result{
		width: calc(100% - 40rpx);
		padding: 20rpx;
		margin-bottom: 240rpx;
	}
</style>