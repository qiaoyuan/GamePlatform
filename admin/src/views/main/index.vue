<template>
  <div class="p15 w-main-content">
    <div class="dp-f align-items-center w-data-statist">
      <div class="w-item">
        <div class="w-l">
          <div class="w-icon"><i class="iconfont icon-tongji"></i></div>
        </div>
        <div class="w-r">
          <i class="f30 b">{{ sum }}</i>
          <span class="f14">总订单量</span>
        </div>
      </div>
      <div class="w-item">
        <div class="w-l">
          <div class="w-icon"><i class="iconfont icon-zuorigaikuang"></i></div>
        </div>
        <div class="w-r">
          <i class="f30 b">{{ yesterday }}</i>
          <span class="f14">昨日订单量</span>
        </div>
      </div>
      <div class="w-item">
        <div class="w-l">
          <div class="w-icon"><i class="iconfont icon-xiaofangzhanzongliangbeijing"></i></div>
        </div>
        <div class="w-r">
          <i class="f30 b">{{ today }}</i>
          <span class="f14">今日订单量</span>
        </div>
      </div>
    </div>
  </div>
</template>
<script>
import { dateToString } from '@/utils/w'

export default {
  data() {
    return {
      listQuery: {
        sort: '+date',
        date_between: undefined,
        limit: 30,
        page: 1,
        channel_num: undefined
      },
      pickerOptions: {
        disabledDate: time => time > new Date().setHours(0, 0, 0, 0)
      },
      channelList: [],
      total: 0,
      tableData: [],
      dayList: [],
      yesterday: 0,
      today:0,
      sum: 0,
      install_total: 0
    }
  },
  computed: {
    is_admin() {
      return this.$store.getters.userinfo.admin_type < 2
    }
  },
  mounted() {
    const today = new Date().setHours(0, 0, 0, 0)
    let duration = 15
    if (this.is_admin) {
      duration = 6
    }
    this.listQuery.date_between = [
      dateToString('yyyyMMdd', new Date(today - duration * 86400000)).substr(2),
      dateToString('yyyyMMdd', new Date()).substr(2),
    ]
    // if (this.is_admin) {
    //   request.post('channel/select').then(res => {
    //     this.channelList = res.data.list
    //   })
    // }
    // this.getList()
	},
  methods: {
    getList() {
      this.$w_fun.post('channel_data/channel', this.listQuery).then(res => {
        this.sum = res.data.sum || 0
        this.today = res.data.todayCount || 0
        this.yesterday = res.data.yesterdayCount || 0
        this.tableData = res.data.list
        this.install_total = res.data.installTotal || 0
        this.dayList = res.data.dayList || []
      })
    },
    changeSort(sort) {
      this.listQuery.sort = sort
      this.getList()
    },
  },
};
</script>
<style lang="scss" scoped>
.caret-wrapper-jz {
  display: inline-flex;
  flex-direction: column;
  align-items: center;
  height: 34px;
  width: 12px;
  vertical-align: middle;
  cursor: pointer;
  overflow: initial;
  position: relative;
}
.sort-asc-selected {
  border-bottom-color: #409eff !important;
}
.sort-desc-selected {
  border-top-color: #409eff !important;
}
.w-echart{
  background-color: rgba(255,255,255,.06);
  border: 1px solid rgba(255,255,255,.2);
  border-radius: 20px;
  padding: 20px;
    box-shadow: 0 2px 2px 1px rgba(0,0,0,.2);
}
.w-main-content{
  display: flex;
  flex-direction: column;
}
.w-search{
  :deep .el-input__inner{
    background-color: rgba(255,255,255,.06);
    border: 1px solid rgba(255,255,255,.2);
    color: #f1f1f1;
    border-radius: 20px;
  }
  :deep .el-date-editor .el-range-input{
    background-color: transparent;
    color: #f1f1f1;
  }
}
.w-data-statist{
  .w-item{
    border-radius: 20px;
    display: flex;
    justify-content: center;
    align-items: center;
    flex: 1;
    margin:15px 25px 25px 0;
    box-shadow: 0 0px 5px 5px rgba(0,0,0,.08);
    border: 1px solid rgba(255,255,255,.6);
    cursor: pointer;
    position: relative;
    overflow: hidden;
    transition: all .4s ease-in-out .1s;
    text-shadow: 0 2px 2px rgba(0,0,0,.3);
    color: #ffffff;
    &:last-child{margin-right: 0;}
    &::before{
      content: '';
      position: absolute;
      left: -40px;
      top: -40px;
      width: 110px;
      height: 110px;
      background-image: linear-gradient(-145deg, rgba(255,255,255,.2), transparent);
      border-radius: 100%;
      transition: all .4s ease-in-out .1s;
    }
    &::after{
      content: '';
      position: absolute;
      right: -60px;
      bottom: -60px;
      width: 150px;
      height: 150px;
      background-image: linear-gradient(45deg, rgba(255,255,255,.3), transparent);
      border-radius: 100%;
      transition: all .4s ease-in-out .1s;
    }
    .w-l{
      flex: 1;
      display: flex;
      align-items: center;
      justify-content: flex-end;
      padding: 20px 20px 20px 20px;
      .w-icon{
        width: 60px;
        height: 60px;
        border: 3px solid rgba(255,255,255,.5);
        border-radius: 20%;
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
        z-index: 1;
        transition: all .4s ease-in-out .1s;
        box-shadow: 0 1px 2px 1px rgba(0,0,0,.1);
        .iconfont{
          font-size: 40px;
        }
      }
    }
    .w-r{
      display: flex;
      flex-direction: column;
      align-items: flex-start;
      position: relative;
      z-index: 1;
      flex: 1;
      padding: 20px 20px 20px 0;
      box-sizing: border-box;
      transition: all .4s ease-in-out .1s;
      &::after{
        content: '';
        position: absolute;
        right: -70px;
        top: -70px;
        width: 120px;
        height: 120px;
        background-image: linear-gradient(45deg, rgba(255,255,255,.1), transparent);
        border-radius: 100%;
        transition: all .4s ease-in-out .1s;
      }
    }
    &:nth-child(1){
      background-image: linear-gradient(90deg, #1e84f2, #4ab1ed);
      &:hover{
        box-shadow: 
        0 0 30px 20px rgba(0, 169, 255,.05),
        0 0 20px 15px rgba(0, 169, 255,.1),
        0 0 15px 10px rgba(0, 216, 255,.15),
        0 0 10px 5px rgba(0, 246, 255,.3);
        border-color: rgba(255,255,255,.6);
        border-radius: 30px;
        &::before,&::after{
          transform: scale(1.5);
        }
      }
    }
    &:nth-child(2){
      background-image: linear-gradient(90deg, #536be7, #9392f0);
      &:hover{
        box-shadow: 
        0 0 30px 20px rgba(127, 58, 255,.05),
        0 0 20px 15px rgba(127, 58, 255,.1),
        0 0 15px 10px rgba(127, 58, 255,.15),
        0 0 10px 5px rgba(201, 122, 255,.3);
        border-color: rgba(255,255,255,.6);
        border-radius: 30px;
        &::before,&::after{
          transform: scale(1.5);
        }
      }
    }
    &:nth-child(3){
      background-image: linear-gradient(90deg, #44c97a, #7dd171);
      &:hover{
        box-shadow: 
        0 0 30px 20px rgba(191, 255, 0,.05),
        0 0 20px 15px rgba(191, 255, 0,.1),
        0 0 15px 10px rgba(191, 255, 0,.15),
        0 0 10px 5px rgba(191, 255, 0,.3);
        border-color: rgba(255,255,255,.6);
        border-radius: 30px;
        &::before,&::after{
          transform: scale(1.5);
        }
      }
    }
    &:nth-child(4){
      background-image: linear-gradient(90deg, #f97413, #ff9c54);
      &:hover{
        box-shadow: 
        0 0 30px 20px rgba(255, 212, 0,.05),
        0 0 20px 15px rgba(255, 212, 0,.1),
        0 0 15px 10px rgba(255, 212, 0,.15),
        0 0 10px 5px rgba(255, 212, 0,.3);
        border-color: rgba(255,255,255,.6);
        border-radius: 30px;
        &::before,&::after{
          transform: scale(1.5);
        }
      }
    }
    &:hover{
      border: 1px solid rgba(255,255,255,1);
      .w-l{
        .w-icon{
          border-radius: 100%;
        }
      }
    }
  }
}
</style>