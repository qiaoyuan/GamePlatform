<template>
	<view class="introduce">
		<view class="introduce-detail">
			<!-- 封面图片 -->
			<view class="cover">
			<image style="width: 100%;height: 100%;border-radius: 20rpx;" :src="introduceInfo.img_url" mode=""></image>
			</view>

			<!-- 标题、价格 -->
			<view class="title-price">
				<view class="title">{{introduceInfo.title}}</view>
				<view class="text">{{introduceInfo.description}}？</view>
				<view class="price"> ￥{{introduceInfo.price}} </view>
				<view class="list">
					<ul style="display: flex;justify-content: space-around;align-items: center;color: gray;">
						<li style="font-size: .75rem;">题目易懂： {{introduceInfo.easy }}</li>
						<li style="font-size: .75rem;">结果准确性：{{introduceInfo.exact}}</li>
						<li style="font-size: .75rem;">建议实用性：{{introduceInfo.utility}}</li>
					</ul>
				</view>
			</view>

			<!-- 详情介绍 -->
			<view class="detail">
				<view class="detail-name">
					<view class="name">
						测评介绍
					</view>
					<view class="index"></view>
				</view>
					<!-- <image style="width: calc(100% - 40rpx); height: 7200px;padding: 0 20rpx;;" src="../../static/coverImg.png" mode=""></image> -->
					<rich-text :nodes="introduceInfo.content"></rich-text>
			</view>

			<!-- 用户评分 -->
			<!-- <view class="score">
				<view class="detail-name">
					<view class="name">
						用户评价
					</view>
					<view class="index"></view>
				</view>
				<view class="score-content">
					<view class="score1">
						
					</view>
					<view class="score2">
						
					</view>
					<view class="score3">
						
					</view>
				</view>
			</view> -->

			<!-- 相关推荐 -->
			<view class="share">

			</view>

			<view class="goods-carts">
				<view class="bottom">
					<view class="goHome" @click="goHome">
						<button > 测试大厅 </button>
					</view>
					<view class="test" >
						<button class="btn-pay" @click="goSelectSex" :loading="loading" :disabled="disabled">立即测试</button>
					</view>
				</view>
			</view>
		</view>
	</view>
</template>

<script>
	import { getQuestionDetail } from '@/api/index.js'
	export default {
		onLoad: function(option) {
			uni.showToast({
				title: "加载中",
				mask: true,
				icon: 'loading'
			})
			this.currentId = option.id
			getQuestionDetail(option.id).then(res => {
				uni.hideToast()
				this.introduceInfo = res.data.info
			})
		},
		data() {
			return {
				introduceInfo: {},
				currentId: '',
				disabled: false,
				loading: false
			}
		},
		methods: {
			// 回到测试大厅
			goHome() {
				uni.switchTab({
					url:"/pages/index/index"
				})
			},
			async payment() {
				if(this.loading) {
					return;
				}
				this.loading = true
				uni.showLoading({
					title: '支付处理中'
				})
				try{
					// 调取订单
				} catch(e) {
					uni.showModal({
						content: e.message,
						showCancel: false
					})
				}finally{
					this.loading = false,
					uni.hideLoading()
				}
			},
			goSelectSex(currentId) {
				uni.navigateTo({
					url: `/pages/selectSex/index?id=${this.currentId}`
				})
			},
			onClick(e) {
				uni.showToast({
					title: `点击${e.content.text}`,
					icon: 'none'
				})
			},
		}
	}
</script>

<style lang="scss" scoped>
	.introduce {
		width: calc(100% - 40rpx);
		height: 100%;
		flex: auto;
		overflow: auto;
		background-color: #f6f6f6;
		padding: 20rpx;
	}
	.cover{
		width: 100%;
		height: 450rpx;
	}
	.title-price{
		width: 100%;
		height: 360rpx;
		background-color: #ffffff;
		.title{
			text-align: center;
			font-size: 40rpx;
			font-weight: 600;
			margin-top: 10rpx;
		}
		.text{
			text-align: center;
			font-size: 28rpx;
			color: gray;
			padding: 30rpx 0;
		}
		.price{
			text-align: center;
			color: #ffa852;
			font-size: 40rpx;
			margin-bottom: 100rpx;
		}
		.list{
			margin-top: 20rpx;
		}
	}
	.detail{
		width: 100%;
		min-height: 800px;
		margin-top: 20rpx;
		background-color: #ffffff;
		margin-bottom: 120rpx;
		.detail-name{
			width: 100%;
			height: 140rpx;
			text-align: center;
			.name{
				height: 80rpx;
				font-size: 30rpx;
				font-weight: 600;
				line-height: 100rpx;
			}
			.index{
				width: 60rpx;
				height: 1px;
				border-bottom: 8rpx solid #f6f6f6;
				margin-left: calc(50% - 30rpx);
			}
		}
		.cover-img{
			width: 100%;
			height: auto;
		}
	}
	.score{
		width: 100%;
		height: 600rpx;
		background-color: #ffffff;
		.detail-name{
			width: 100%;
			height: 140rpx;
			text-align: center;
			.name{
				height: 80rpx;
				font-size: 30rpx;
				font-weight: 600;
				line-height: 100rpx;
			}
			.index{
				width: 60rpx;
				height: 1px;
				border-bottom: 8rpx solid #f6f6f6;
				margin-left: calc(50% - 30rpx);
			}
		}
		.score-content{
			width: 100%;
			height: 60%;
			display: flex;
			justify-content: space-evenly;
			align-items: center;
			>view{
				width: 30%;
				height: 90%;border: 1px solid #f6f6f6;
			}
		}
	}

	.goods-carts {
		/* #ifndef APP-NVUE */
		display: flex;
		/* #endif */
		flex-direction: column;
		position: fixed;
		left: 0;
		right: 0;
		/* #ifdef H5 */
		left: var(--window-left);
		right: var(--window-right);
		/* #endif */
		bottom: 0;

		.bottom {
			width: 100%;
			height: 100%;
			display: flex;
			justify-content: space-between;
			align-items: center;

			.goHome {
				width: 25%;
				height: 100%;
				position: relative;
				>button {
					height: 100rpx;
					font-size: 20rpx;
					border: none;
					background: #ffffff;
					line-height: 100rpx;
					border-radius: unset;
				}
			}

			.test {
				width: 75%;
				height: 100%;

				>button {
					height: 100rpx;
					border: none;
					background: #ffe371;
					line-height: 100rpx;
					border-radius: unset;
				}
			}
		}
	}
</style>