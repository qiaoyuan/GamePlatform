import Vue from 'vue'
import Vuex from 'vuex'
import user from './module/user'
import app from './module/app'
import permission from './module/permission'
import tagsView from './module/tagsView'

Vue.use(Vuex)
export default new Vuex.Store({
    modules: {
      user,
      app,
      permission,
      tagsView
    }
})