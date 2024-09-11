<template>
	<view class="my">
		<!-- 顶部内容 -->
		<view class="topRole">
			<view class="avatar">
				<img class="img" src="../../static/boy.png" alt="" />
			</view>
			<view class="description">
				<view class="top">
					Hi!
				</view>
				<view class="bottom">
					欢迎回来
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
				<view v-if="current === 0" style="width: 100%;height: 100%;min-height: 600rpx;">
					<view v-if="allTest.length>0" style="width: 100%;height: 100%;">
						<view class="item" v-for="(all,k) in allTest" :key="k" @click="goAllTest(all)">
							<view class="main">
								{{all.questionnaire.title}}
							</view>
							<view class="text">
								{{all.questionnaire.description}}
							</view>
							<view class="price">
								￥{{all.questionnaire.price}}
							</view>
							<view class="test readyTest" v-if="all.response_id">
								已测
							</view>
							<view class="test noTest" v-else>
								未测
							</view>
							<view class="img" >
								<image style="width: 100%; height: 100%; border-radius: 10rpx;" :src="all.questionnaire.img_url" alt="" />
							</view>
						</view>
					</view>
					<text v-else style="height: 80px;">暂无数据</text>
				</view>
				<view v-if="current === 1" style="width: 100%; height: 100%; min-height: 600rpx;">
					<view v-if="finishTest.length>0" style="width: 100%;height: 100%;">
						<view class="item" v-for="(fin,j) in finishTest" :key="j" @click="lookReport(fin)">
							<view class="main">
								{{fin.questionnaire.title}}
							</view>
							<view class="text">
								{{fin.questionnaire.description}}
							</view>
							<view class="price">
								￥{{fin.questionnaire.price}}
							</view>
							<view class="img">
								<img style="width: 100%; height: 100%; border-radius: 10rpx;"" :src="fin.questionnaire.img_url" alt="" />
							</view>
						</view>
					</view>
					<text v-else style="height: 80px;">暂无数据</text>
				</view>
				<view v-else-if="current === 2" style="width: 100%;height: 100%; min-height: 600rpx;">
					<view v-if='inFinishTest.length>0' style="width: 100%;height: 100%;">
						<view class="item" v-for="(item,index) in inFinishTest" :key="index" @click="toAnswer(item)">
							<view class="main">
								{{item.questionnaire.title}}
							</view>
							<view class="text">
								{{item.questionnaire.description}}
							</view>
							<view class="price">
								￥{{item.questionnaire.price}}
							</view>
							<!-- <view class="total">
								{{item.total}}人已测
							</view> -->
							<view class="img">
								<img style="width: 100%; height: 100%; border-radius: 10rpx;"" :src="item.questionnaire.img_url" alt="" />
							</view>
						</view>
					</view>
					<text v-else style="height: 80px;">暂无数据</text>
				</view>
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
	import {
		reportInfo,
		reportList,
		login
	} from '@/api/index.js'
	export default {
		data() {
			return {
				current: 0,
				items: ['全部测评', '已完成','未完成'],
				inFinishTest: [], // 未完成数据
				finishTest: [], // 已完成数据
				allTest: [], // 全部数据
			}
		},
		onShow() {
			const token = uni.getStorageSync("token")
			this.getTestData(this.current)
			// if (!token) {
			// 	this.$refs.popup.open('top')
			// }
		},
		onLoad() {
			uni.showToast({
				title: '加载中',
				icon: 'loading',
				mask:true
			})
		},
		methods: {
			onClickItem(e) {
				if (this.current !== e.currentIndex) {
					this.current = e.currentIndex
				}
				this.getTestData(this.current)
			},
			// 跳转到测评页
			goHome() {
				uni.switchTab({
					url: '/pages/index/index'
				})
			},
			getTestData(type) {
				if (type == 0) { // 全部测评
					reportList('').then(res => {
						this.allTest = res.data.list
					})
				} else if (type == 1) { // 已完成测评
					reportList(type).then(res => {
						this.finishTest = res.data.list
					})
				} else if(type == 2){ // 未完成测评
					reportList(type).then(res => {
						this.inFinishTest = res.data.list
					})
				}
			},
			goAllTest(all) {
					if(!all.response_id) { // 前往答题
						this.toAnswer(all)
					}else { // 查看报告
						this.lookReport(all)
					}
			},
			// 查看报告
			lookReport(data) {
				uni.navigateTo({
					url: `/pages/report/index?surveryId=${data.questionnaire_id}`
				})
			},
			//未完成进入答题
			toAnswer(data) {
				console.log(data)
					uni.navigateTo({
						url: `/pages/selectSex/index?id=${data.questionnaire_id}&title=${data.questionnaire.title}`
					})
			},
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
				width: 80rpx;
				height: 80rpx;
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
			min-height: 120rpx;
			height: auto;
			width: calc(100% - 40rpx);
			text-align: center;
			margin-top: 20rpx;
			padding: 20rpx;
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

	.item {
		position: relative;
		width: 100%;
		height: 140rpx;
		border-bottom: 1px solid #eeeeee;

		.main {
			font-size: 0.75rem;
			font-weight: 600;
			text-align: left;
		}

		.text {
			font-size: 20rpx;
			color: gray;
			margin: 12rpx 0;
			text-align: left;
			width: calc(100% - 9rem);
			white-space: nowrap;
			/* 强制不换行 */
			text-overflow: ellipsis;
			/* 超过部分省略号代替 */
			overflow: hidden;
			/* 必须同时设置overflow:hidden才能生效 */
		}

		.price {
			color: #ffa852;
			position: absolute;
			bottom: 20rpx;
			left: 0rpx;
		}

		.total {
			font-size: 16rpx;
			position: absolute;
			bottom: 20rpx;
			right: 180rpx;
			color: gray;
		}
		.test{
			font-size: 0.5rem;
			position: absolute;
			top: 20rpx;
			right: 240rpx;
		}

		.img {
			// #ifdef MP-WEIXIN
			width: 6rem;
			height: 4rem;
			// #endif
			// #ifdef MP-TOUTIAO
			width: 5rem;
			height: 3.5rem;
			// #endif
			position: absolute;
			right: 20rpx;
			bottom: 8rpx;
		}
		.readyTest{
			color: springgreen;
			border: 1px solid springgreen;
		}
		.noTest{
			color: #ff0000;
			border: 1px solid #ff0000;
		}
	}

	.loginBtn {
		width: 200rpx;
		height: 100rpx;
		line-height: 100rpx;
		position: absolute;
		left: 50%;
		top: 50%;
		transform: translate(-50%, 400%);
	}
</style>