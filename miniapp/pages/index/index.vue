<template>
	<view class="home">
		<!-- 顶部内容 -->
		<view class="topBar">
			<view class="logo">
				<image style="width: 100%; height: 100%;" src="../../static/logo.png" mode=""></image>
			</view>
			<view class="report">
				<view class="icon">
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
				<swiper :autoplay="true" circular  :indicator-dots="true">
					<swiper-item v-for="(item, index) in bannerList" :key="index"
						@click="goto(item.actionUrl,item.outSide,item.data)"
					>
						<view class="swiper-item" style="width: 100%;height: 100%;">
							<image style="width: 100%; height: 100%;" src="../../static/logo.png" mode="scaleToFill"></image>
						</view>
					</swiper-item>
				</swiper>
			</view>	
		</view>
		
		<!-- 列表 -->
		<view class="list">
			<view class="list-item" v-for="(item,index) in listData" :key="index">
				<image style="width: 60rpx;height: 60rpx;margin-left:calc(50% - 30rpx)" :src="item.url" mode=""></image>
				<view class="list-text">
					{{item.text}}
				</view>
			</view>
		</view>
		
		<!-- 热点测评 -->
		<view class="hotTest">
			<view class="content" v-for="(item,i) in 2" :key="i">
				<view class="top">
					<view class="title">性格类型</view>
					<view class="text">性格决定命运？————揭开你隐藏性格秘密</view>
				</view>
				<view class="middle">
					<view class="middle-left">
						<view class="title">潜意识投射测试</view>
						<view class="text">14张图片，揭露你的潜意识</view>
						<view style="width: 120rpx;">
							<button class="btn" >去测试</button>
						</view>
					</view>
					<view class="middle-right">
						<image class="middle-img" src="../../static/logo.png" mode=""></image>
						<view class="num">
							1w人已测
						</view>
					</view>
					
				</view>
				<view class="bottom">
					<view class="bottom-left">
						<view class="top">
							<view class="title">九型人格测试</view>
							<view class="text">你性格的秘密，都在这</view>
						</view>
						<view class="bottom">
							<view class="btn">去测试</view>
							<view class="img">
								<image class="bottom-img" src="../../static/logo.png" mode=""></image>
								<view class="num">
									1w人已测
								</view>
							</view>
						</view>
					</view>
					<view class="bottom-right">
						<view class="top">
							<view class="title">九型人格测试</view>
							<view class="text">你性格的秘密，都在这</view>
						</view>
						<view class="bottom">
							<view class="btn">去测试</view>
							<view class="img">
								<image class="bottom-img" src="../../static/logo.png" mode=""></image>
								<view class="num">
									1w人已测
								</view>
							</view>
						</view>
					</view>
				</view>
				<view class="more">
					<span>查看更多测评</span>
				</view>
			</view>
		</view>
	</view>
	
</template>

<script>
	import {  getHomeInfo } from '@/api/index.js'
	export default {
		data() {
			return {
				bannerList: [
					{
						id: '1'
					},
					{
						id: '2'
					},
					{
						id: '3'
					}
				],
				listData: [
					{
						id: '1',
						text: '性格类型',
						url: '../../static/logo.png'
					},{
						id: '2',
						text: '爱情检测',
						url: '../../static/logo.png'
					},{
						id: '3',
						text: '心理健康',
						url: '../../static/logo.png'
					},{
						id: '4',
						text: '职场能力',
						url: '../../static/logo.png'
					},{
						id: '5',
						text: '全部测评',
						url: '../../static/logo.png'
					}
				]
			}
		},
		onLoad() {
			getHomeInfo().then(res => {
				console.log(res)
			})
		},
		methods: {
			// 点击轮播链接跳转页面
			goto(url, outside, data) {
				if(!url) {
					return
				}
				if(outside) {
					uni.navigateTo({
						url,
						data
					})
				}
			}
		}
	}
</script>

<style>
	.home {
		width: 100%;
		height: 100%;
		flex: auto;
		overflow: auto;
		.swiper{
			padding: 20rpx;
			border-radius: 60rpx;
		}
		.topBar{
			padding: 20rpx 20rpx 0;
			display: flex;
			justify-content: space-between;
			align-items: center;
			.logo{
				width: 40rpx;
				height: 40rpx;
			}
			.text{
				background-color: azure;
				font-size: 0.75rem;
				text-align: center;
			}
		}
	}
	
	.icon{
		width: 60rpx;
		height: 100%;
		
	}
	.reportImg{
		width: 40rpx;
		height: 40rpx;
		margin-left: calc(50% - 20rpx);
	}
	
	.list{
		width: 100%;
		height: 180rpx;
		display: flex;
		justify-content: space-around;
		align-items: center;
	}
	.list-text{
		text-align: center;
		font-size: 1rem;
	}
	
	.hotTest{
		width: 100%;
		height: 600rpx;
		.content{
			margin: 20rpx;
			height: 100%;;
			background-color: #e8f5fe;
			border-radius: 20rpx;
			padding: 20rpx;
			.top{
				width: 100%;
				height: 16%;
				.title{
					font-size: 1rem;
					font-weight: 600;
				}
				.text{
					font-size: 0.75rem;
					color: #d8d8d8;
					margin-top: 10rpx;
				}
			}
			.middle{
				width: 100%;
				height: 39%;
				border-radius: 20rpx;
				background-color: #ffffff;
				display: flex;
				justify-content: space-between;
				.middle-left{
					flex: 1;
					padding: 20rpx;
					.title{
						font-size: 1rem;
						font-weight: 600;
					}
					.text{
						font-size: 0.75rem;
						color: #d8d8d8;
						margin: 10rpx 0;
					}
					.btn{
						font-size: 0.75rem;
						color: #ffffff;
						background-color: #7c91fd;
						width: 120rpx;
						border-radius: 40rpx;
						
					}
				}
				.middle-right{
					width: 40%;
					height: 90%;
					padding-top: 3%;
					margin-right: 20rpx;
					position: relative;
					.middle-img{
						width: 100%;
						height: 90%;
					}
					.num{
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
			.bottom{
				width: 100%;
				height: 39%;
				display: flex;
				justify-content: space-between;
				align-items: center;
				.bottom-left,.bottom-right{
					width: calc(48% - 20rpx);
					height: 90%;
					background-color: #ffffff;
					border-radius: 20rpx;
					padding-left: 20rpx;
					.top{
						width: 100%;
						height: calc(40% - 10rpx);
						.title{
							font-size: 1rem;
							font-weight: 600;
							margin-top: 10rpx;
						}
						.text{
							font-size: 0.75rem;
							color: #d8d8d8;
							margin: 10rpx 0;
						}
					}
					.bottom{
						height: 60%;
						display: flex;
						justify-content: space-around;
						align-items: center;
						.img{
							width: 50%;
							height: 90%;
							position: relative;
							margin-bottom: 10rpx;
							.bottom-img{
								width: 90%;
								height: 100%;
								margin-right: 20rpx;
							}
							.num{
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
						.btn{
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
	.more{
		width: 180rpx;
		height: 8%;
		text-align: center;
		color: #7c91fd;
		margin-left: calc(50% - 100rpx);
		>span{
			border-bottom: 2rpx solid #7c91fd;
		}
	}
</style>
