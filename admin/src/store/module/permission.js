import constantRoutes from '@/router/w'
import { post } from '@/libs/request';
import router from "@/router"
import Layout from '@/layout'
import Empty from '@/layout/empty'

// 必须在动态菜单路由之后注册，否则 Vue Router 3 会先命中通配符 404 路由。
const notFoundRoute = {
  path: '*',
  name: '404',
  component: (resolve) => require(['@/views/error/404'], resolve),
  hidden: true
}

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
        const getFirstRoutePath = (route) => {
          if (route.children && route.children.length > 0) {
            const child = route.children.find(item => !item.hidden) || route.children[0]
            return getFirstRoutePath(child)
          }
          return route.path
        }
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
            const menuPath = `/${String(item.url || '').replace(/^\/+/, '')}`
            const menuItem = {
              name: item.url.split(/[\/_]/).map( i => i[0].toUpperCase() + i.substr(1)).join(''),
              // 后端菜单 URL 是前端绝对页面地址，不能作为父级路由的相对路径。
              path: menuPath,
              hidden: !!item.is_hide,
              component: component,
              meta: {title: item.title, icon: item.icon, level: item.level},
            }
            if (item[subKey] && item[subKey].length > 0) {
              menuItem.children = loopMenu(item[subKey], subKey)
            }
            if (item.level === 1) {
              if (menuItem.children && menuItem.children.length > 0) {
                const firstRoutePath = getFirstRoutePath(menuItem)
                menuItem.redirect = `/${firstRoutePath.replace(/^\/+/, '')}`
              }
            }
            menus.push(menuItem)
          }
          return menus
        }
        const menus = loopMenu(res.data.menus)
        const accessedRoutes = filterAsyncRouter(menus)
        const routesWithNotFound = accessedRoutes.concat(notFoundRoute)
        if (typeof router.addRoutes === 'function') {
          // 当前项目使用 vue-router 3，批量注册动态路由。
          // 404 必须最后注册，否则会拦截所有动态路由。
          router.addRoutes(routesWithNotFound)
        } else {
          // 兼容 vue-router 4。
          accessedRoutes.forEach(route => router.addRoute(route))
          router.addRoute(notFoundRoute)
        }
        commit('SET_ROUTES', routesWithNotFound)
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
