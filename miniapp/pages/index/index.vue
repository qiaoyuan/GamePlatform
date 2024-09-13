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
				<view class="middle" @click="reviewDetail(item.questionnaires[0].id ? item.questionnaires[0].id : '')" v-if="item.questionnaires[0]">
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
				<view class="bottom"  v-if="item.questionnaires[1]">
					<view class="bottom-left" @click="reviewDetail(item.questionnaires[1].id ? item.questionnaires[1].id : '')">
						<view class="top">
							<view class="title">{{item.questionnaires[1].title}}</view>
							<view class="text">{{ item.questionnaires[1].description? item.questionnaires[1].description : ''}}</view>
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
					<view class="bottom-right" @click="reviewDetail(item.questionnaires[2].id ? item.questionnaires[2].id : '')" v-if="item.questionnaires[2]">
						<view class="top">
							<view class="title">{{ item.questionnaires[2].title }}</view>
							<view class="text">{{ item.questionnaires[2].description ? item.questionnaires[2].description : '' }}</view>
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
				token: '',
				channelId: 0,
				provider: ''
			}
		},
		onShow() {
			this.token = uni.getStorageSync("token")
			var that = this
			if (!that.token) {
				uni.getProvider({
					service: 'oauth',
						success: function (res) {
							that.provider = res.provider[0] // 获取使用该小程序的平台 ["weixin"] 、["toutiao"]
							// 获取用户code
							// #ifdef MP-WEIXIN
							//该代码仅在微信小程序中生效
								 that.getUserCode(that.provider)
							// #endif
							
							// #ifdef MP-TOUTIAO
							 //该代码仅在抖音、头条小程序中生效
								that.getUserTTCode(that.provider)
							// #endif
						}
				})
				
			}
		},
		onLoad(options) {
			if(options.q){
				let scene = decodeURIComponent(options.q)
				this.channelId = scene.split('=')[1]
			}
			
			uni.showToast({
				title: "加载中",
				mask: true,
				icon: 'loading'
			})
			// // #ifdef MP-WEIXIN
			// this.getUserCode(this.provider)
			// // #endif
			
			// // #ifdef MP-TOUTIAO
			//  //该代码仅在抖音、头条小程序中生效
			// 	this.getUserTTCode(this.provider)
			// // #endif
			getHomeInfo().then(res => {
				this.bannerList = res.data.recommend
				this.listData = res.data.icon_list
				this.hotListData = res.data.home_list
			})
		},
		onPullDownRefresh() {
			// #ifdef MP-WEIXIN
			this.getUserCode(this.provider)
			// #endif
			
			// #ifdef MP-TOUTIAO
			 //该代码仅在抖音、头条小程序中生效
				this.getUserTTCode(this.provider)
			// #endif
		},
		methods: {
			async getUserCode(provider) {
				const loginRes = await uni.login({
					provider: provider
				});
				const userInfoRes = await uni.getUserInfo({
					provider: provider
				});
				// 获取到用户信息
				const userInfo = userInfoRes.userInfo;
				this.login()
			},
			login() {
				uni.login({
					provider: this.provider,
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
								code: code,
								channel_id: this.channelId,
								platform: 1, // 微信平台
							},
							success: (res) => {
								if (res.data) {
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
			getUserTTCode(provider){
				var that = this
				// uni.login({
				// 	provider: 'toutiao',
				// 	success: function(res) {
				// 		console.log(res);
				// 		// that.loginTT(res.code)
				// 		},
				// 	fail(res) {
				// 			console.log(`login 调用失败`);
				// 		},
				// })
				tt.login({
				  force: true,
				  success(res) {
				    // console.log(`login 调用成功${res.code} ${res.anonymousCode}`);
						that.loginTT(res.code)
				  },
				  fail(res) {
				    console.log(`login 调用失败`);
				  },
				});

			},
			loginTT(code) {
				// 抖音小程序 获取接口登录
				uni.request({
					url: 'https://psychology.xuanzeti.top/index/user/login', // 你的登录API地址
					method: 'POST',
					data: {
						code: code,
						channel_id: this.channelId,
						platform: 2, // 抖音平台
					},
					success: (res) => {
						if (res.data) {
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
			background-color: #ffffff;
		}

		.topBar {
			padding: 20rpx 20rpx 0;
			display: flex;
			justify-content: space-between;
			align-items: center;
			background-color: #ffffff;
			.logo {
				width: 140rpx;
				height: 60rpx;
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
		background-color: #ffffff;
	}

	.list-text {
		text-align: center;
		font-size: 0.8rem;
	}

	.hotTest {
		width: 100%;
		max-height: 600rpx;

		.content {
			margin: 20rpx;
			height: 100%;
			;
			background-color: #e8f5fe;
			border-radius: 20rpx;
			padding: 20rpx;

			.top {
				width: 100%;
				// height: 16%;
				height: 100rpx;

				.title {
					font-size: 1rem;
					font-weight: 600;
					width: calc(100% - 20rpx);
					white-space: nowrap;
					/* 强制不换行 */
					text-overflow: ellipsis;
					/* 超过部分省略号代替 */
					overflow: hidden;
					/* 必须同时设置overflow:hidden才能生效 */
				}

				.text {
					// #ifdef MP-WEIXIN
					font-size: 0.75rem;
					// #endif
					
					// #ifdef MP-TOUTIAO
					font-size: 0.6rem;
					// #endif
					
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
				// height: 39%;
				height: 243rpx;
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
						width: calc(100% - 20rpx);
						white-space: nowrap;
						/* 强制不换行 */
						text-overflow: ellipsis;
						/* 超过部分省略号代替 */
						overflow: hidden;
						/* 必须同时设置overflow:hidden才能生效 */
					}

					.text {
						// #ifdef MP-WEIXIN
						font-size: 0.75rem;
						// #endif
						
						// #ifdef MP-TOUTIAO
						font-size: 0.6rem;
						// #endif
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
						// #ifdef MP-WEIXIN
						font-size: 0.75rem;
						// #endif
						
						// #ifdef MP-TOUTIAO
						font-size: 0.6rem;
						// #endif
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
				// height: 39%;
				height: 243rpx;
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
							width: calc(100% - 20rpx);
							white-space: nowrap;
							/* 强制不换行 */
							text-overflow: ellipsis;
							/* 超过部分省略号代替 */
							overflow: hidden;
							/* 必须同时设置overflow:hidden才能生效 */
						}

						.text {
							// #ifdef MP-WEIXIN
							font-size: 0.75rem;
							// #endif
							
							// #ifdef MP-TOUTIAO
							font-size: 0.6rem;
							// #endif
							color: #4b4a4a;
							margin: 10rpx 0;
							width: calc(60% - 20rpx);
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
		height: 34rpx;
		text-align: center;
		color: #7c91fd;
		margin-left: calc(50% - 100rpx);
		margin-top: 5px;
		>span {
			border-bottom: 2rpx solid #7c91fd;
		}
	}
</style>