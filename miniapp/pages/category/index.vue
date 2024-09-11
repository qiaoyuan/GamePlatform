<template>
	<view class="category">
		<!-- 左侧标签区域 -->
		<scroll-view class="left" scroll-y>
			<!-- 定义一个acitves为0，与index的索引绑定,若相等赋予类名，不相等为空 -->
			<view :class="actives === index ? 'active' : ''" v-for="(item, index) in catagoryList" :key="item.id"
				@click="leftClickHandle(item.id, index)">
				{{ item.title }}
			</view>
		</scroll-view>
		<!-- 右侧图片区域 -->
		<!-- -->
		<scroll-view class="right" scroll-y @scrolltolower="lower" @refresherrefresh="refresherrefresh"  @refresherrestore="onRestore"
			refresher-enabled="true" :refresher-triggered="triggered" >
			<template v-if="secondData.length>0">
				<view class="item" v-for="item in secondData" :key="item.id" @click="clickTest(item)">
					<view class="main">
						{{item.title}}
					</view>
					<view class="text">
						{{item.description? item.description : ''}}
					</view>
					<view class="price">
						￥{{item.price}}
					</view>
					<!-- <view class="total">
						{{item.total}}人已测
					</view> -->
					<view class="img">
						<image style="width: 100%; height: 100%; border-radius: 10rpx;" :src="item.img_url" alt="" />
					</view>
				</view>
				<!-- 加载更多 -->
				<view style="padding: 0rpx 20rpx 20rpx;">
					<u-loadmore :status="status" :iconSize="28" :fontSize="28" />
				</view>
			</template>
			<template v-else>
				<view style="width: 100%; height: auto;">
					<image style=" padding: 20rpx;width: calc(100% - 40px); height: 140px" src="../../static/empty.png"
						mode=""></image>
					<view class="none">
						暂无数据
					</view>
				</view>
			</template>

		</scroll-view>
	</view>
</template>

<script>
	import {
		getCategory,
		getCategoryList
	} from '@/api/index.js'
	export default {
		data() {
			return {
				actives: 0,
				secondData: [],
				catagoryList: [],
				page: {
					pageNum: 1,
					limit: 10,
					total: ''
				},
				categoryId: '',
				categoryIndex: '',
				//设置当前下拉刷新状态，true 表示下拉刷新已经被触发，false 表示下拉刷新未被触发
				triggered: true,
				//加载更多样式
				status: "loadmore",
				//下拉刷新
				refreshing: false,
			}
		},
		watch: {
			categoryId: {
				handler(n) {
					uni.showToast({
						title: '加载中',
						mask: true,
						icon: 'loading'
					})
					this.leftClickHandle(this.categoryId, this.categoryIndex)
				}
			}
		},
		onShow() {
			this.categoryId = uni.getStorageSync('categoryId')
			this.categoryIndex = uni.getStorageSync('categoryIndex')
		},
		onLoad() {
			uni.showToast({
				title: "加载中",
				mask: true,
				icon: 'loading'
			})
			getCategory().then(res => {
				this.catagoryList = res.data.list
				if (this.categoryId) {
					this.leftClickHandle(this.categoryId, this.categoryIndex)
				} else {
					this.leftClickHandle(this.catagoryList[0].id, 0)
				}
			})
		},
		methods: {
			leftClickHandle(id, index) {
				this.categoryId = id
				this.categoryIndex = index
				this.page.pageNum = 1
				this.actives = index
				uni.setStorageSync('categoryId', id)
				uni.setStorageSync('categoryIndex', index)
				this.getList(id)
			},
			// 获取列表
			getList(id) {
				// 获取当前选项的列表页
				let params = {
					article_category_id: id,
					page: this.page.pageNum,
					limit: this.page.limit
				}
				getCategoryList(params).then(res => {
					uni.hideToast()
					this.secondData = res.data.list.data
					this.page.totloadingal = res.data.list.last_page
				})
			},
			// 下拉触底加载数据
			async lower() {
				if (this.page.pageNum <= this.page.total) {
					this.page.pageNum += 1
					let res = await getCategoryList({
						article_category_id: this.categoryId,
						page: this.page.pageNum,
						limit: this.page.limit
					})
					const lowerData = res.data.list.data
					this.secondData.push(...lowerData)
					if (Math.ceil(this.page.total / 10) == this.page.pageNum) {
						this.status = 'nomore'
					}
				}else {
					uni.showToast({
						title: '暂无更多数据',
						icon:"none"
					})
				}

			},
			//下拉刷新
			refresherrefresh() {
				this.page.pageNum = 1
				// this.getList(this.categoryId)
				if (this.refreshing) return;
				this.refreshing = true
				if (!this.triggered) {
					this.triggered = true
				}
				//下拉刷新逻辑
				this.getList(this.categoryId)
				//结束下拉刷新状态
				setTimeout(() => {
					this.triggered = false;
					this.refreshing = false;
				}, 500)
			},
			//自定义下拉刷新被复位
			onRestore() {
				this.triggered = "restore"
			},
			// 点击进入测评页面
			clickTest(data) {
				var questionId = data.id
				uni.navigateTo({
					url: `/pages/introduce/index?id=${questionId}`,
				})
			}
		}
	}
</script>

<style lang="scss" scoped>
	.category {
		width: 100%;
		height: 100%;
		display: flex;

		.left {
			width: 200rpx;
			height: 100vh;
			border-right: 1px solid #eee;
			background-color: #eeeeee;
			box-sizing: border-box;

			view {
				height: 60px;
				line-height: 60px;
				color: #333;
				text-align: center;
				font-size: 30rpx;
				border-top: 1px solid #eee;
			}

			.active {
				color: deepskyblue;
				border-left: 4px solid deepskyblue;
				background-color: #ffffff;
			}
		}

		.right {
			height: 100vh;
			flex: 1;

			.item {
				position: relative;
				padding-top: 30rpx;
				padding-left: 20rpx;
				border-bottom: 1px solid #eeeeee;
				height: 140rpx;

				.main {
					font-size: 0.75rem;
					font-weight: 600;
					width: calc(100% - 6rem);
					white-space: nowrap;
					/* 强制不换行 */
					text-overflow: ellipsis;
					/* 超过部分省略号代替 */
					overflow: hidden;
					/* 必须同时设置overflow:hidden才能生效 */
				}

				.text {
					font-size: 20rpx;
					color: gray;
					margin: 12rpx 0;
					width: calc(100% - 7rem);
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
					left: 20rpx;
				}

				.total {
					font-size: 16rpx;
					position: absolute;
					bottom: 20rpx;
					right: 180rpx;
					color: gray;
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
					bottom: 20rpx;
				}
			}

			.none {
				color: #4b4a4a;
				text-align: center;
				margin-top: 20rpx;
				;
			}
		}
	}
</style>