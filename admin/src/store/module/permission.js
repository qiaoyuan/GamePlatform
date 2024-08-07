import constantRoutes from '@/router/w'
import { post } from '@/libs/request';
import router from "@/router"
import Layout from '@/layout'
import Empty from '@/layout/empty'

const permission = {
  state: {
    routes: [],
    addRoutes: [],
    loadRoutes: false
  },
  mutations: {
    SET_ROUTES: (state, routes) => {
      state.addRoutes = routes
      state.routes = constantRoutes.concat(routes)
      state.loadRoutes = true
    }
  },
  actions: {
    // 生成路由
    GenerateRoutes({ commit }) {
      return new Promise(async (resolve, reject) => {
        // 向后端请求路由数据
        const res = await post('/index/menus')
        const loopMenu = function (items, subKey = 'children') {
          const menus = []
          for (const item of items) {
            let component = 'Layout'
            if (item.level > 1) {
              if (item.url.indexOf('/') > 0) {
                component = item.url
              } else {
                component = 'Empty'
              }
            }
            const menuItem = {
              name: item.url.split(/[\/_]/).map( i => i[0].toUpperCase() + i.substr(1)).join(''),
              path: item.url,
              hidden: !!item.is_hide,
              component: component,
              meta: {title: item.title, icon: item.icon, level: item.level},
            }
            if (item[subKey] && item[subKey].length > 0) {
              menuItem.children = loopMenu(item[subKey], subKey)
            }
            if (item.level === 1) {
              menuItem.redirect = 'noRedirect'
              menuItem.path = '/' + menuItem.path
            }
            menus.push(menuItem)
          }
          return menus
        }
        const menus = loopMenu(res.data.menus)
        const accessedRoutes = filterAsyncRouter(menus)
        for (const item of accessedRoutes) {
          router.addRoute(item)
        }
        commit('SET_ROUTES', accessedRoutes)
        resolve(accessedRoutes)
      })
    }
  },
  getters: {
    permission_routes: state => state.routes
  }
}

// 遍历后台传来的路由字符串，转换为组件对象
function filterAsyncRouter(asyncRouterMap) {
  return asyncRouterMap.filter(route => {
    if (route.component) {
      // Layout组件特殊处理
      // route.name = `${route.component}_${route.name}` // 解决路由名称重名警告
      if (route.component === 'Layout') {
        route.component = Layout
      } else if (route.component === 'Empty') {
        route.component = Empty
      } else {
        route.component = loadView(route.component)
      }
    }
    if (route.children != null && route.children && route.children.length) {
      route.children = filterAsyncRouter(route.children)
    }
    return true
  })
}

export const loadView = (view) => { // 路由懒加载
  return (resolve) =>  require([`@/views/${view}`], resolve)
}

export default permission
