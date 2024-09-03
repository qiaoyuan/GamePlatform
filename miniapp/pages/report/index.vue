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
			uni.switchTab({
				url: '/pages/index/index'
			})
		},
		onLoad(options) {
			uni.showToast({
				title: '加载中',
				mask:true,
				icon: 'loading'
			})
			console.log(options)
			this.surveryId = options.surveryId
			reportInfo(this.surveryId).then(res => {
				const htmlStringArray = res.data.info
				this.ReportObj = ''
				for(let i = 0; i < htmlStringArray.length; i++) {
					this.ReportObj += htmlStringArray[i].text
				}
				// this.ReportObj = res.data.info.text || null
				uni.hideToast()
				
			})
		},
		data() {
			return {
				ReportObj: null,
				navTitle: '报告内容',
				surveryId: '', // 问卷id
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