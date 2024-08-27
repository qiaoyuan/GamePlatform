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
				<view v-if="current === 0" style="width: 100%;">
					<view v-if="allTest.length>0" style="width: 100%;height: 100%;">
						<view class="item" v-for="(all,k) in allTest" :key="k" @click="lookReport(all)">
							<view class="main">
								{{all.questionnaire.title}}
							</view>
							<view class="text">
								{{all.questionnaire.description}}
							</view>
							<view class="price">
								￥{{all.questionnaire.price}}
							</view>
							<view class="img" >
								<image style="width: 100%; height: 100%; border-radius: 10rpx;" :src="all.questionnaire.img_url" alt="" />
							</view>
						</view>
					</view>
					<text v-else style="height: 80px;">暂无数据</text>
				</view>
				<view v-if="current === 1" style="width: 100%; height: 100%;">
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
				<view v-else-if="current === 2" style="width: 100%;height: 100%;">
					<view v-if="inFinishTest.length>0" style="width: 100%;height: 100%;">
						<view class="item" v-for="(item,index) in inFinishTest" :key="index">
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

		<!-- <uni-popup style="width: 100%;height: 500px" ref="popup" type="center" :animation="false" :maskClick='false'>
			<button class="loginBtn" @click="getUserInfo">一键登录</button>
		</uni-popup> -->
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
				category: [
					{
						id: '3',
						text: '全部测评'
					},{
						id: '2',
						text: '已完成'
					},
					{
						id: '1',
						text: '未完成'
					}
				],
				inFinishTest: [],
				finishTest: [],
				allTest: [],
			}
		},
		onShow() {
			const token = uni.getStorageSync("token")
			this.getTestData(this.current)
			// if (!token) {
			// 	this.$refs.popup.open('top')
			// }
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
				if (type == 0) {
					reportList('').then(res => {
						this.allTest = res.data.list
					})
				} else if (type == 1) {
					reportList(type).then(res => {
						this.finishTest = res.data.list
					})
				} else if(type == 2){
					reportList(type).then(res => {
						this.inFinishTest = res.data.list
					})
				}
			},
			// 查看报告
			lookReport(data) {
				console.log(data)
				uni.setStorageSync('reportContent', data.questionnaire.content)
				uni.navigateTo({
					url: '/pages/result/index'
				})
			},
			// 登录
			async getUserInfo() {
				uni.showToast({
					title: '登陆中',
					mask: true,
					icon: 'loading'
				})
				 const loginRes = await uni.login({ provider: 'weixin' });
				const userInfoRes = await uni.getUserInfo({ provider: 'weixin' });
				// 获取到用户信息
				const userInfo = userInfoRes.userInfo;
				this.login()
			},
			// 登录并获取用户信息
			login() {
				uni.login({
					provider: 'weixin',
					success: (loginRes) => {
						// 登录成功，获取用户code
						const {
							code
						} = loginRes;
						// 发送code到后台换取openId, sessionKey, unionId
						uni.request({
							url: 'https://psychology.xuanzeti.top/index/user/login', // 你的登录API地址
							method: 'POST',
							data: {
								code
							},
							success: (res) => {
								if (res.data) {
									uni.hideToast()
									uni.setStorageSync('openId', res.data.data.open_id);
									uni.setStorageSync('token', res.data.data.token);
									this.$refs.popup.close()
								} else {
									uni.showToast({
										title: '授权失败',
										icon: 'none'
									});
								}
							},
							fail: () => {
								uni.showToast({
									title: '请求失败',
									icon: 'none'
								});
							}
						});
					},
					fail: (err) => {
						console.log('uni.login 接口调用失败，将无法正常使用开放接口等服务', err);
						uni.showToast({
							title: '登录失败',
							icon: 'none'
						});
					}
				});
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
			font-size: 16rpx;
			color: gray;
			margin: 12rpx 0;
			text-align: left;
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

		.img {
			width: 6rem;
			height: 4rem;
			position: absolute;
			right: 20rpx;
			bottom: 8rpx;
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