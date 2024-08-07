import Vue from 'vue'
import App from './App.vue'
import Cookies from 'js-cookie'
import router from './router'
import  ElementUI from 'element-ui'
import 'element-ui/lib/theme-chalk/index.css'
import SetConfig from '@/set.config'
import store from '@/store'
import '@/assets/styles/index.scss' // global css
import '@/assets/icons' // icon
// 全局注册组件

// 全局挂载方法
import utils from '@/plugins/utils.js'
Vue.use(utils)
import Component from '@/components/w'
Vue.use(Component)
// 挂载echarts方法
import echart from '@/plugins/echart.js'
Vue.use(echart)

import Directive from '@/directive'
Vue.use(Directive)
import DirectiveW from '@/directive/w'
Vue.use(DirectiveW)

Vue.config.productionTip = false
Vue.use(ElementUI, {
  size: Cookies.get(`size${location.host}`) || 'mini'
})
Vue.prototype.SetConfig = SetConfig

new Vue({
  router,
  store,
  render: h => h(App)
}).$mount('#app')
