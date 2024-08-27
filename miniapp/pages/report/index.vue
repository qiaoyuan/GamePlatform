<template>
	<view class="result">
		<rich-text :nodes="ReportObj"></rich-text>
	</view>
</template>

<script>
	import { reportInfo } from '@/api/index.js'
	export default {
		onShow() {
			// uni.showToast({
			// 	title: '加载中',
			// 	mask:true,
			// 	icon: 'loading'
			// })
			// this.ReportObj = uni.getStorageSync('reportContent')
			// if(this.ReportObj) {
			// 	uni.hideToast()
			// }
		},
		onLoad(options) {
			uni.showToast({
				title: '加载中',
				mask:true,
				icon: 'loading'
			})
			reportInfo(options.id).then(res => {
				const htmlString = res.data.info.text
				console.log(typeof(htmlString))
				this.ReportObj = res.data.info.text
				uni.hideToast()
			})
			
		},
		data() {
			return {
				ReportObj: null
			}
		}
	}
</script>

<style lang="scss" scoped>
	.result{
		width: calc(100% - 40rpx);
		padding: 20rpx;
		margin-bottom: 240rpx;
	}
</style>