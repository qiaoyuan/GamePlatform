import Vue from 'vue'
import NProgress from 'nprogress'
import 'nprogress/nprogress.css'
import Router from 'vue-router'
Vue.use(Router)
import WRouter from './w'
import store from '@/store'
import { getToken } from '@/utils/auth'

NProgress.configure({ showSpinner: false })
const routes = [
  ...WRouter
];

const originalPush = Router.prototype.push
Router.prototype.push = function push(location, resolve, reject) {
  if ( resolve || reject ) return originalPush.call(this, location, resolve, reject)
  return originalPush.call(this, location).catch((e)=>{})
}

const router = new Router({
  // mode: 'history', // 去掉url中的#
  scrollBehavior: () => ({ y: 0 }),
  routes
})

const whiteList = ['/login'] // 不重定向白名单
router.beforeEach(async(to, from, next) => {
  NProgress.start()
  if (getToken()) {
    if (whiteList.includes(to.path)) {
      next({ path: '/' })
    } else {
      if (!store.getters.userinfo) {
          try {
            await store.dispatch('getUserInfo')
            await store.dispatch('GenerateRoutes')
            next({ path: to.fullPath, replace: true})
          } catch (error) {
            store.dispatch('logOut').then(() => {
              location.reload()
            })
          }
      } else {
        next()
      }
      next()
    }
  } else {
    if (whiteList.indexOf(to.path) !== -1) {
      next()
    } else {
      next({ path: `/login?redirect=${to.path}`, replace: true }) // 否则全部重定向到登录页
    }
  }
})

router.afterEach(() => {
  NProgress.done()
})

export default router
