<template>
	<view class="result">
		<rich-text :nodes="ReportObj"></rich-text>
	</view>
</template>

<script>
	export default {
		onShow() {
			uni.showToast({
				title: '加载中',
				mask:true,
				icon: 'loading'
			})
			this.ReportObj = uni.getStorageSync('reportContent')
			if(this.ReportObj) {
				uni.hideToast()
			}
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