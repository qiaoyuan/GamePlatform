import { getToken, setToken, removeToken, getCookie } from '@/utils/auth';
import { post } from '@/libs/request';
export default {
  state: {
    size: getCookie("size") || "mini",
    token: getToken(),
    userinfo: undefined,
    avatar: require('@/assets/image/user.png'),
    loginExpireNotice: false,
    columnOptions: {},
  },
  mutations: {
    SET_AVATAR: (state, avatar) => {
      state.avatar = avatar
    },
    SET_SIZE: (state, size) => {
      state.size = size;
    },
    SET_TOKEN: (state, token) => {
      setToken(token);
      state.token = token;
    },
    SET_USERINFO: (state, userinfo) => {
      state.userinfo = userinfo;
    },
    setLoginExpireNotice(state, status) {
      state.loginExpireNotice = status
    },
    SET_COLUMN_OPTIONS: (state, value) => {
      state.columnOptions = {...state.columnOptions, ...value}
    },
    CLEAR_COLUMN_OPTIONS: (state, value) => {
      if (value) {
        for( const key in state.columnOptions) {
          if (key.startsWith(value)) {
            state.columnOptions[value] = null
          }
        }
      } else {
        state.columnOptions = {}
      }
    }
  },
  actions: {
    setSize({ commit }, size) {
      commit("SET_SIZE", size);
    },
    // 登录
    login({ commit, dispatch, getters }, data) {
      return new Promise((resolve, reject) => {
        post('/account/login', data).then(async res => {
          commit('SET_TOKEN', res.data.token);
          commit('setLoginExpireNotice', false)
          dispatch('getUserInfo');
          resolve();
        }).catch(error => reject(error));
      });
    },
    // 获取用户信息
    getUserInfo({ commit, state }, userInfo) {
      return new Promise((resolve, reject) => {
        if (userInfo) {
          commit('SET_USERINFO', userInfo)
          resolve(userInfo);
        } else {
          post('/index/index').then(res => {
            commit('SET_USERINFO', res.data.admin)
            resolve();
          }).catch(error => reject(error));
        }
      });
    },
    // 前端登出
    logOut({ commit }) {
      return new Promise((resolve) => {
        commit('SET_TOKEN', '');
        commit('setLoginExpireNotice', false)
        removeToken();
        location.reload();
        resolve();
      });
    },
    // 更新token
    refreshToken({ commit }, token) {
      return new Promise((resolve, reject) => {
        commit('SET_TOKEN', token)
        setToken(token)
        resolve()
      })
    },
    setLoginExpireNotice({ commit }, status) {
      commit('setLoginExpireNotice', status)
    },
    getColumnOptions({ commit, state }, key) {
      return new Promise((resolve, reject) => {
        if (state.columnOptions[key]) {
          resolve(state.columnOptions[key])
          return
        }
        post(key, {}, {}, false).then(res => {
          const data = {}
          data[key] = res.data.list
          commit('SET_COLUMN_OPTIONS', data)
          resolve(res.data.list)
        }).catch(() => {
          resolve([])
        })
      })
    },
    cleanColumnOptions({ commit}, key) {
      commit('CLEAR_COLUMN_OPTIONS', key)
    },
  },
  getters: {
    size: (state) => state.size,
    token: (state) => state.token,
    userinfo: (state) => state.userinfo,
    menus: (state) => state.menus,
    avatar: (state) => state.avatar,
  },
};
