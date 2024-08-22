<template>
	<view class="category">
		<!-- 左侧标签区域 -->
		<scroll-view class="left" scroll-y>
			<!-- 定义一个acitves为0，与index的索引绑定,若相等赋予类名，不相等为空 -->
			<view :class="actives === index ? 'active' : ''"  
			v-for="(item, index) in catagoryList" 
			:key="item.id"
			@click="leftClickHandle(item.id, index)"
			>
				{{ item.title }}
			</view>
		</scroll-view>
		<!-- 右侧图片区域 -->
		<scroll-view class="right" scroll-y>
			<template v-if="catagoryList.length>0">
				<view class="item" v-for="item in secondData" :key="item.id" @click="clickTest(item)">
					<view class="main">
						{{item.title}}
					</view>
					<view class="text">
						{{item.text}}
					</view>
					<view class="price">
						￥{{item.price}}
					</view>
					<!-- <view class="total">
						{{item.total}}人已测
					</view> -->
					<view class="img">
						<img src="../../static/logo.png" alt="" />
					</view>
				</view>
			</template>
			<template v-else>
				<view>暂无数据</view>
			</template>
			
			<text class="none" v-if="secondData.length === 0">暂无数据,请浏览其他页面!</text>
		</scroll-view>
	</view>
</template>

<script>
	import { getCategory, getCategoryList } from '@/api/index.js'
	export default {
		data() {
			return {
				actives: '0',
				cates: [
					{
						id: '0',
						classifyName: '全部',
						children: [
							{
								id: '1',
								// img_url: require('../../statiic/logo.png'),
								title: '潜意识投射测试',
								text: '你的魅力值有多高？',
								price: '19.9',
								total: '23.5w'
							},
							{
								id: '2',
								// img_url: require('../../statiic/logo.png'),
								title: '潜意识投射测试',
								text: '你的魅力值有多高？',
								price: '19.9',
								total: '23.5w'
							}
						]
					},
					{
						id: '1',
						classifyName: '性格',
						children: []
					},
					{
						id: '2',
						classifyName: '情感',
						children: []
					},
					{
						id: '3',
						classifyName: '职场',
						children: []
					},
					{
						id: '4',
						classifyName: '健康',
						children: []
					},
					{
						id: '5',
						classifyName: '人际',
						children: []
					},
					{
						id: '6',
						classifyName: '亲子',
						children: []
					},
					{
						id: '7',
						classifyName: '能力',
						children: []
					}
				],
				secondData: [
					{
						id: '1',
						// img_url: require('../../statiic/logo.png'),
						title: '潜意识投射测试',
						text: '你的魅力值有多高？',
						price: '19.9',
						total: '23.5w'
					},
					{
						id: '2',
						// img_url: require('../../statiic/logo.png'),
						title: '潜意识投射测试',
						text: '你的魅力值有多高？',
						price: '19.9',
						total: '23.5w'
					}
				],
				catagoryList: []
			
			}
		},
		onShow(){
			const id = uni.getStorageSync('categoryId')
			const index = uni.getStorageSync('categoryIndex')
			this.leftClickHandle(id, index)
		
		},
		onLoad() {
			getCategory().then(res => {
				this.catagoryList = res.data.list
			})
		},
		methods: {
			leftClickHandle(id, index) {
				this.actives = index
				this.getList(id)
			},
			// 获取列表
			getList(id) {
				// 获取当前选项的列表页
				let params = {
					article_category_id: id
				}
				getCategoryList(params).then(res => {
					this.secondData = res.data.list.data
				})
			},
			// 点击进入测评页面
			clickTest(data) {
				uni.navigateTo({
					url: `/pages/introduce/index?id=${data.id}`,
				})
			}
		}
	}
</script>

<style lang="scss" scoped>
	.category{
		width: 100%;
		height: 100%;
		display: flex;
		.left{
			width: 200rpx;
			height: 100%;
			border-right: 1px solid #eee;
			view {
				height: 60px;
				line-height: 60px;
				color: #333;
				text-align: center;
				font-size: 30rpx;
				border-top: 1px solid #eee;
			}
			.active {
				/* background: $shop-color; */
				color: deepskyblue;
			}
		}
		.right{
			height: 100%;
			width: 520rpx;
			margin: 10rpx auto;
			.item{
				position: relative;
				margin-top: 30rpx;
				border-bottom: 1px solid #C0C0C0;
				height: 140rpx;
				.main{
					font-size: 0.75rem;
					font-weight: 600;
					
				}
				.text{
					font-size: 16rpx;
					color: gray;
					margin: 12rpx 0;
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
					width: 2rem;
					height: 4rem;
					position: absolute;
					right: 100rpx;
					bottom: 20rpx;
				}
			}
			.none {
				color: #eeeeee;
				
			}
		}
	}
</style>