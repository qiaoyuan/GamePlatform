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
				<view v-if="current === 0" style="width: 100%;">
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
								<img style="width: 100%; height: 100%;" :src="item.questionnaire.img_url" alt="" />
							</view>
						</view>
					</view>
					<text v-else style="height: 80px;">暂无数据</text>
				</view>
				<view v-if="current === 1" style="width: 100%; height: 100%;">
					<view v-if="finishTest.length>0" style="width: 100%;height: 100%;">
					<view class="item" v-for="(fin,j) in finishTest" :key="j"  @click="lookReport(fin)">
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
								<img style="width: 100%; height: 100%;" :src="fin.questionnaire.img_url" alt="" />
							</view>
						</view>
					</view>
					<text v-else style="height: 80px;">暂无数据</text>
				</view>
				<view v-else-if="current === 2" style="width: 100%;height: 100%;">
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
							<view class="img">
								<img style="width: 100%; height: 100%;" :src="all.questionnaire.img_url" alt="" />
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
	import { reportInfo,  reportList } from '@/api/index.js'
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
				],
				inFinishTest: [],
				finishTest: [],
				allTest: [],
			}
		},
		onLoad() {
			this.getTestData(this.current)
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
				if(type == 0){
					reportList(type).then(res => {
						this.inFinishTest = res.data.list
					})
				}else if(type == 1) {
					reportList(type).then(res => {
						this.finishTest = res.data.list
					})
				}else {
					reportList().then(res => {
						this.allTest = res.data.list
					})
				}
			},
			// 查看报告
			lookReport(data) {
				uni.setStorageSync('reportContent', data.questionnaire.content)
				uni.navigateTo({
					url: '/pages/result/index'
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
	.item{
		position: relative;
		width: 100%;
		height: 140rpx;
		border-bottom: 1px solid #C0C0C0;
		.main{
			font-size: 0.75rem;
			font-weight: 600;
			text-align: left;
			
		}
		.text{
			font-size: 16rpx;
			color: gray;
			margin: 12rpx 0;
			text-align: left;
		}
		.price{
			color: #f4ea2a;
			position: absolute;
			bottom: 20rpx;
			left: 0rpx;
		}
		.total{
			font-size: 16rpx;
			position: absolute;
			bottom: 20rpx;
			right: 180rpx;
			color: gray;
		}
		.img{
			width: 3rem;
			height: 3rem;
			position: absolute;
			right: 100rpx;
			bottom: 10rpx;
		}
	}
	
</style>