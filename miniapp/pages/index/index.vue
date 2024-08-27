<template>
	<view class="home">
		<!-- 顶部内容 -->
		<view class="topBar">
			<view class="logo">
				<image style="width: 100%; height: 100%;" src="../../static/logo.png" mode=""></image>
			</view>
			<view class="report">
				<view class="icon" @click="lookReport">
					<image class="reportImg" src="../../static/report.png" mode=""></image>
					<view class="text">
						报告
					</view>
				</view>
			</view>
		</view>
		<!-- 轮播 -->
		<view class="swiper">
			<view class="swiper-box">
				<swiper :autoplay="true" circular :indicator-dots="true">
					<swiper-item v-for="(item, index) in bannerList" :key="index" @click="goto(item.id)">
						<view class="swiper-item" style="width: 100%;height: 100%;">
							<image style="width: 100%; height: 100%;border-radius: 40rpx;" :src="item.img_url" mode="scaleToFill"
								@error="handleImageError(item)"></image>
						</view>
					</swiper-item>
				</swiper>
			</view>
		</view>

		<!-- 列表 -->
		<view class="list">
			<view class="list-item" v-for="(item,index) in listData" :key="index" @click="gotoCategory(item,index)">
				<image style="width: 60rpx;height: 60rpx;margin-left:calc(50% - 30rpx)" :src="item.icon_url"
					mode="scaleToFill" @error="handleImageError(item)">
				</image>
				<view class="list-text">
					{{item.title}}
				</view>
			</view>
		</view>

		<!-- 热点测评 -->
		<view class="hotTest">
			<view class="content" v-for="(item,i) in hotListData" :key="i">
				<view class="top">
					<view class="title">{{item.title}}</view>
					<view class="text">{{item.description}}</view>
				</view>
				<view class="middle" @click="reviewDetail(item.questionnaires[0].id)">
					<view class="middle-left">
						<view class="title">{{item.questionnaires[0].title}}</view>
						<view class="text">{{item.questionnaires[0].description}}</view>
						<view style="width: 120rpx;">
							<button class="btn">去测试</button>
						</view>
					</view>
					<view class="middle-right">
						<image class="middle-img" :src="item.questionnaires[0].img_url" mode="scaleToFill"
							@error="handleImageError(item)"></image>
						<!-- <view class="num">
							1w人已测
						</view> -->
					</view>

				</view>
				<view class="bottom">
					<view class="bottom-left" @click="reviewDetail(item.questionnaires[1].id ? item.questionnaires[1].id : '')">
						<view class="top">
							<view class="title">{{item.questionnaires[1].title}}</view>
							<view class="text">{{ item.questionnaires[1].description }}</view>
						</view>
						<view class="bottom">
							<view class="btn">去测试</view>
							<view class="img">
								<image class="bottom-img" :src="item.questionnaires[1].img_url" mode=""></image>
								<!-- <view class="num">
									1w人已测
								</view> -->
							</view>
						</view>
					</view>
					<view class="bottom-right" @click="reviewDetail(item.questionnaires[2].id ? item.questionnaires[2].id : '')">
						<view class="top">
							<view class="title">{{ item.questionnaires[2].title }}</view>
							<view class="text">{{ item.questionnaires[2].description }}</view>
						</view>
						<view class="bottom">
							<view class="btn">去测试</view>
							<view class="img">
								<image class="bottom-img" :src="item.questionnaires[2].img_url" mode=""></image>
								<!-- <view class="num">
									1w人已测
								</view> -->
							</view>
						</view>
					</view>
				</view>
				<view class="more" @click="lookMore(item.id, i)">
					<span>查看更多测评</span>
				</view>
			</view>
		</view>
	</view>

</template>

<script>
	import {
		getHomeInfo,
		login
	} from '@/api/index.js'
	export default {
		data() {
			return {
				bannerList: [],
				listData: [],
				hotListData: [],
				token: ''
			}
		},
		onShow() {
			this.token = uni.getStorageSync("token")
			if (!this.token) {
				// 获取用户code
				this.getUserCode()
			}
		},
		onLoad() {
			uni.showToast({
				title: "加载中",
				mask: true,
				icon: 'loading'
			})
			getHomeInfo().then(res => {
				uni.hideToast()
				this.bannerList = res.data.recommend
				this.listData = res.data.icon_list
				this.hotListData = res.data.home_list
			})
		},
		methods: {
			async getUserCode() {
				const loginRes = await uni.login({
					provider: 'weixin'
				});
				const userInfoRes = await uni.getUserInfo({
					provider: 'weixin'
				});
				// 获取到用户信息
				const userInfo = userInfoRes.userInfo;
				this.login()
			},
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
			// 点击轮播链接跳转页面
			goto(id) {
				uni.navigateTo({
					url: `/pages/introduce/index?id=${id}`
				})
			},
			// 跳转分类页面
			gotoCategory(item, index) {
				uni.setStorageSync('categoryId', item.id)
				uni.setStorageSync('categoryIndex', index)
				uni.switchTab({
					url: `/pages/category/index`
				})
			},
			// 跳转到详情页
			reviewDetail(id) {
				if(id){
					uni.navigateTo({
						url: `/pages/introduce/index?id=${id}`
					})
				}else {
					uni.showToast({
						title: '暂无当前测评内容',
						icon: 'none'
					})
				}
				
			},
			// 查看更多测评
			lookMore(id, index) {
				uni.setStorageSync('categoryId', id)
				uni.setStorageSync('categoryIndex', index)
				uni.switchTab({
					url: '/pages/category/index'
				})
			},
			// 查看报告
			lookReport() {
				uni.switchTab({
					url: '/pages/my/index'
				})
			},
			handleImageError(item) {
				item.cat_icon = '../../static/defaultImg.png'
			}
		}
	}
</script>

<style lang="scss" scoped>
	.home {
		width: 100%;
		height: auto;

		.swiper {
			padding: 20rpx;
			border-radius: 60rpx;
		}

		.topBar {
			padding: 20rpx 20rpx 0;
			display: flex;
			justify-content: space-between;
			align-items: center;

			.logo {
				width: 40rpx;
				height: 40rpx;
			}

			.text {
				background-color: azure;
				font-size: 0.75rem;
				text-align: center;
			}
		}
	}

	.icon {
		width: 60rpx;
		height: 100%;

	}

	.reportImg {
		width: 40rpx;
		height: 40rpx;
		margin-left: calc(50% - 20rpx);
	}

	.list {
		width: 100%;
		height: 180rpx;
		display: flex;
		justify-content: space-around;
		align-items: center;
	}

	.list-text {
		text-align: center;
		font-size: 0.8rem;
	}

	.hotTest {
		width: 100%;
		height: 600rpx;

		.content {
			margin: 20rpx;
			height: 100%;
			;
			background-color: #e8f5fe;
			border-radius: 20rpx;
			padding: 20rpx;

			.top {
				width: 100%;
				height: 16%;

				.title {
					font-size: 1rem;
					font-weight: 600;
				}

				.text {
					font-size: 0.75rem;
					color: #4b4a4a;
					margin-top: 10rpx;
					width: calc(80% - 20rpx);
					white-space: nowrap;
					/* 强制不换行 */
					text-overflow: ellipsis;
					/* 超过部分省略号代替 */
					overflow: hidden;
					/* 必须同时设置overflow:hidden才能生效 */

				}
			}

			.middle {
				width: 100%;
				height: 39%;
				border-radius: 20rpx;
				background-color: #ffffff;
				display: flex;
				justify-content: space-between;

				.middle-left {
					flex: 1;
					padding: 20rpx;

					.title {
						font-size: 1rem;
						font-weight: 400;
					}

					.text {
						font-size: 0.75rem;
						color: #4b4a4a;
						margin: 10rpx 0;
						width: calc(80% - 20rpx);
						white-space: nowrap;
						/* 强制不换行 */
						text-overflow: ellipsis;
						/* 超过部分省略号代替 */
						overflow: hidden;
						/* 必须同时设置overflow:hidden才能生效 */

					}

					.btn {
						font-size: 0.75rem;
						color: #ffffff;
						background-color: #7c91fd;
						width: 140rpx;
						border-radius: 40rpx;

					}
				}

				.middle-right {
					width: 40%;
					height: 90%;
					padding-top: 3%;
					margin-right: 20rpx;
					position: relative;

					.middle-img {
						width: 100%;
						height: 90%;
						border-radius: 20rpx;
					}

					.num {
						background-color: #524379;
						color: #ffffff;
						font-size: 0.5rem;
						padding: 5rpx;
						border-radius: 20%;
						position: absolute;
						bottom: 40rpx;
						left: 20rpx;
					}
				}
			}

			.bottom {
				width: 100%;
				height: 39%;
				display: flex;
				justify-content: space-between;
				align-items: center;

				.bottom-left,
				.bottom-right {
					width: calc(48% - 20rpx);
					height: 90%;
					background-color: #ffffff;
					border-radius: 20rpx;
					padding-left: 20rpx;

					.top {
						width: 100%;
						height: calc(40% - 10rpx);

						.title {
							font-size: 1rem;
							font-weight: 400;
							margin-top: 10rpx;
						}

						.text {
							font-size: 0.75rem;
							color: #4b4a4a;
							margin: 10rpx 0;
							width: calc(80% - 20rpx);
							white-space: nowrap;
							/* 强制不换行 */
							text-overflow: ellipsis;
							/* 超过部分省略号代替 */
							overflow: hidden;
							/* 必须同时设置overflow:hidden才能生效 */

						}
					}

					.bottom {
						height: 60%;
						display: flex;
						justify-content: space-between;
						align-items: center;

						.img {
							width: 50%;
							height: 90%;
							position: relative;
							margin-bottom: 10rpx;
							margin-right: 10rpx;
							// margin-bottom: 10rpx;
							.bottom-img {
								width: 100%;
								height: 100%;
								border-radius: 20rpx;
							}

							.num {
								background-color: #524379;
								color: #ffffff;
								font-size: 0.5rem;
								padding: 5rpx;
								border-radius: 20%;
								position: absolute;
								bottom: 16rpx;
								left: 16rpx;
							}
						}

						.btn {
							height: 48rpx;
							font-size: 0.75rem;
							color: #ffffff;
							background-color: #7c91fd;
							width: 100rpx;
							border-radius: 40rpx;
							text-align: center;
							line-height: 48rpx;
							margin-top: 60rpx;

						}

					}
				}
			}
		}
	}

	.more {
		width: 200rpx;
		font-size: 0.75rem;
		height: 8%;
		text-align: center;
		color: #7c91fd;
		margin-left: calc(50% - 100rpx);

		>span {
			border-bottom: 2rpx solid #7c91fd;
		}
	}
</style>