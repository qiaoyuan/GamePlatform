<template>
	<view class="my">
		<!-- 顶部内容 -->
		<view class="topRole">
			<view class="avatar">
				<img class="img" src="../../static/logo.png" alt="" />
			</view>
			<view class="description">
				<view class="top">
					Hi!
				</view>
				<view class="bottom">
					欢迎回来，微信用户
				</view>
			</view>
		</view>
		<!-- tab测评历史情况 -->
		<view class="testLog">
			<view class="uni-padding-wrap uni-common-mt">
				<uni-segmented-control :current="current" :values="items" style-type="text" active-color="#007aff"
					@clickItem="onClickItem" />
			</view>
			<view class="content">
				<view v-if="current === 0"><text class="content-text">
					<view v-if="false" class="">
						测试数据
					</view>
					<text v-else style="height: 80px;">暂无数据</text>
				</text></view>
				<view v-if="current === 1"><text class="content-text">选项卡2的内容</text></view>
				<view v-if="current === 2"><text class="content-text">选项卡3的内容</text></view>
			</view>
		</view>

		<!-- 更多 -->
		<view class="more">
			<view class="tab">
				<button style="background: rgb(248, 229, 140);" @click="goHome">查看更多精彩测评</button>
			</view>
		</view>
	</view>
</template>

<script>
	export default {
		data() {
			return {
				current: 0,
				items: ['未完成', '已完成', '全部测评'],
				category: [{
						id: '1',
						text: '未完成'
					},
					{
						id: '2',
						text: '已完成'
					},
					{
						id: '3',
						text: '全部测评'
					}
				]
			}
		},
		methods: {
			onClickItem(e) {
				if (this.current !== e.currentIndex) {
					this.current = e.currentIndex
				}
			},
			// 跳转到测评页
			goHome() {
				uni.switchTab({
					url: '/pages/index/index'
				})
			}
		}
	}
</script>

<style lang="scss" scoped>
	.my {
		width: 100%;
		height: 100%;

		.topRole {
			width: 100%;
			height: 8rem;
			display: flex;
			justify-content: flex-start;
			align-items: center;

			.avatar {
				margin-left: 50rpx;
				margin-right: 50rpx;

				.img {
					width: 100%;
					height: 100%;
					border-radius: 50%;
				}
			}
		}

		.testLog {
			width: 100%;
			height: auto;

			.tab-scroll {
				flex: 1;
				overflow: hidden;
				box-sizing: border-box;
				padding-left: 30rpx;
				padding-right: 30rpx;

				.tab-scroll_box {
					display: flex;
					align-items: center;
					flex-wrap: nowrap;
					box-sizing: border-box;

					.tab_scroll-item {
						line-height: 60rpx;
						margin-right: 35rpx;
						flex-shrink: 0;
						padding-bottom: 10px;
						display: flex;
						justify-content: center;
						font-size: 16px;
						padding-top: 10px;
					}
				}
			}

			.active {
				position: relative;
				color: #ff0000;
				font-weight: 600;
			}

			.active::after {
				content: "";
				position: absolute;
				width: 130rpx;
				height: 4rpx;
				background-color: #ff0000;
				left: 0px;
				right: 0px;
				bottom: 0px;
				margin: auto;
			}
		}
		.uni-common-mt {
				margin-top: 30px;
			}
		
			.uni-padding-wrap {
				// width: 750rpx;
				padding: 0px 30px;
			}

		.content {
				/* #ifndef APP-NVUE */
				display: flex;
				/* #endif */
				justify-content: center;
				align-items: center;
				height: 150px;
				text-align: center;
			}
		.content-text {
				font-size: 14px;
				color: #666;
			}

		.more {
			width: 80%;
			height: 4rem;
			margin-left: 10%;
		}
	}
</style>