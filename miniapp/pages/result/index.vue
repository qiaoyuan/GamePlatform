<template>
	<view class="result">
		<rich-text :nodes="ReportObj"></rich-text>
	</view>
</template>

<script>
	export default {
		onShow() {
			this.ReportObj = uni.getStorageSync('reportContent') 
		},
		data() {
			return {
				ReportObj: {}
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