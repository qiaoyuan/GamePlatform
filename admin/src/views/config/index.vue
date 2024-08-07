<template>
  <div class="app-container">
    <el-tabs v-model="activeName" type="card" @tab-click="handleClick">
      <el-tab-pane v-for="config of configs" :label="config.title" :name="'' + config.group" :key="config.group">
        <el-container style="height: 700px; border: 1px solid #eee">
          <el-aside width="200px" height="100%" style="border-right: 1px solid #eee">
            <el-menu
              @open="handleOpen"
            >
              <el-menu-item
                v-for="item of config.list"
                :ref="'menu' + item.name"
                :key="item.name"
                :index="item.name"
                @click="changeMenu(item)"
              >
                <i class="el-icon-menu"></i>
                <span slot="title">{{ item.title }}</span>
              </el-menu-item>
            </el-menu>
          </el-aside>
          <el-container>
            <div class="w100">
              <p v-if="activeConfig.desc" class="f16 w100 mt10 ml10" v-html=" activeConfig.desc"></p>
              <component
                ref="config"
                :is="activeConfig.type"
                :name="name"
                :group="parseInt(activeName)"
                :title="activeConfig.title"
                :fields="activeConfig.list"
                :attrs="activeConfig.attrs || {}"
              />
            </div>
          </el-container>
        </el-container>
      </el-tab-pane>
    </el-tabs>

  </div>
</template>

<script>
import config from '@/components/Config/config'
import banner from '@/components/Config/banner'
import mInput from '@/components/Config/mInput'
export default {
  name: 'ConfigIndex',
  components: { config, banner, mInput },
  data() {
    return {
      activeName: '2',
      activeConfig: {
        name: undefined,
        type: undefined,
        title: undefined,
        list: []
      },
      name: '',
      configs: [
        {
          group: 2,
          title: '站点',
          list: [
            { type: 'mInput', name: 'exchange_rate', title: '汇率', attrs: {
                type: 'number',
                step: 0.0001
              } },
            { type: 'config', name: 'site_info', title: '站点信息', list: [
                { field: 'name', label: '站点名称' },
                { field: 'icp', label: '备案信息' },
                { field: 'tel', label: '联系电话' },
                { field: 'title', label: '标题', attrs:{ type: 'textarea'} },
                { field: 'keyword', label: '关键词', attrs:{ type: 'textarea'} },
                { field: 'desc', label: '简介', attrs:{ type: 'textarea', autosize: { minRows: 2, maxRows: 4} } },
                { field: 'tj_code', label: '统计代码', attrs:{ type: 'textarea', autosize: { minRows: 4, maxRows: 8} } },
                { field: 'cs_code', label: '客服代码', attrs:{ type: 'textarea', autosize: { minRows: 4, maxRows: 8} } },
              ]
            },
          ]
        },
        {
          group: 3,
          title: '支付',
          list: [
            { type: 'config', name: 'pay_alipay', title: '支付宝', list: [
                { field: 'app_id', label: 'APPID' },
                { field: 'notify_url', label: '通知URL', attrs:{ type: 'textarea', autosize: { minRows: 2, maxRows: 4} } },
                { field: 'return_url', label: '回跳URL', attrs:{ type: 'textarea', autosize: { minRows: 2, maxRows: 4} } },
                { field: 'ali_public_key', label: 'PUBLIC KEY', attrs:{ type: 'textarea', autosize: { minRows: 4, maxRows: 8}} },
                { field: 'private_key', label: 'PRIVATE KEY', attrs:{ type: 'textarea', autosize: { minRows: 4, maxRows: 8}} },
              ]
            },
            { type: 'config', name: 'pay_wechat', title: '微信', list: [
                { field: 'appid', label: 'APPID' },
                { field: 'app_id', label: '公众号ID' },
                { field: 'miniapp_id', label: '小程序ID' },
                { field: 'mch_id', label: '商户ID' },
                { field: 'key', label: '秘钥' },
                { field: 'notify_url', label: '通知URL', attrs:{ type: 'textarea', autosize: { minRows: 2, maxRows: 4} } },
                { field: 'return_url', label: '回跳URL', attrs:{ type: 'textarea', autosize: { minRows: 2, maxRows: 4} } },
                { field: 'cert_client', label: '证书cert', attrs:{ type: 'textarea', autosize: { minRows: 4, maxRows: 8}} },
                { field: 'cert_key', label: '证书key', attrs:{ type: 'textarea', autosize: { minRows: 4, maxRows: 8}} },
              ]
            },
          ]
        },
        {
          group: 4,
          title: '三方登录',
          list: [
            { type: 'config', name: 'third_login_wechat_mini', title: '微信小程序', list: [
                { field: 'app_id', label: 'APPID' },
                { field: 'secret', label: '秘钥' },
              ]
            }
          ]
        },
      ]
    }
  },
  mounted() {
    this.$refs['menu'+this.configs[0].list[0].name][0].$el.click()
  },
  methods: {
    handleOpen(a, b) {
    },
    changeMenu(menu) {
      this.activeConfig = menu
      this.name = this.activeConfig.name
    },
    handleClick() {
      this.$nextTick(_ => {
        for (const item of this.configs) {
          if (item.group.toString() === this.activeName) {
            this.$refs['menu'+item.list[0].name][0].$el.click()
          }
        }
      })
    }
  }
}
</script>
