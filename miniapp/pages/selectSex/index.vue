<template>
	<view class="selectSex">
		<view class="sex">
			<view class="title">
				360全面检测（综合方案）
			</view>
			<view class="text">
				为确保测试结果的可信度，<br />
				请选择性别！
			</view>
			<view class="selectContent">
				<view class="boy" @click="goAnswer('boy')">
					<image style="width: 120rpx;height: 120rpx;margin-left:calc(50% - 60rpx)" src="../../static/boy.png" mode=""></image>
					<view class="list-text">
						男生
					</view>
				</view>
				<view class="girl" @click="goAnswer('girl')">
					<image style="width: 120rpx;height: 120rpx;margin-left:calc(50% - 60rpx)" src="../../static/girl.png" mode=""></image>
					<view class="list-text">
						女生
					</view>
				</view>
			</view>
		</view>
	</view>
</template>

<script>
	export default{
		onLoad(option) {
			console.log(option)
		},
		methods: {
			goAnswer(sex) {
				uni.navigateTo({
					url: `/pages/answer/index?id=${123}&sex=${sex}`
				})
			}
		}
	}
</script>

<style>
	.selectSex{
		width: 100%;
		height: 100%;
		.sex{
			width: 90%;
			height: 500rpx;
			position: absolute;
			left: 50%;
			top: 50%;
			transform: translate(-50%, -50%);
			background-color: #f6f6f6;
			.title{
				font-size: 40rpx;
				font-size: 600;
				text-align: center;
				margin: 40rpx 0;
			}
			.text{
				text-align: center;
				font-size: 20rpx;
				
			}
			.selectContent{
				display: flex;
				justify-content: space-evenly;
				align-items: center;
				margin-top: 30rpx;
				.boy,.girl{
					width: 180rpx;
					height: 200rpx;
					>view{
						text-align: center;
					}
				}
			}
		}
	}
</style>