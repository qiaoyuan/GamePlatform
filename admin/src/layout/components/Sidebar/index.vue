<template>
  <div v-if="currentMenus.length" :class="{'has-logo':showLogo}">
    <!-- <logo v-if="showLogo" :collapse="isCollapse" /> -->
    <el-scrollbar wrap-class="scrollbar-wrapper">
      <el-menu
        :default-active="activeMenu"
        :collapse="isCollapse"
        :background-color="variables.menuBg"
        :text-color="variables.menuText"
        :unique-opened="true"
        :active-text-color="variables.menuActiveText"
        :collapse-transition="false"
        mode="horizontal"
      >
        <sidebar-item v-for="route in currentMenus" :key="route.path" :item="route" :base-path="route.path" />
      </el-menu>
    </el-scrollbar>
  </div>
</template>

<script>
import { mapGetters } from 'vuex'
import Logo from './Logo'
import SidebarItem from './SidebarItem'
import variables from '@/assets/styles/variables.scss'
import { deepClone } from '@/utils/w'

export default {
  components: { SidebarItem, Logo },
  computed: {
    ...mapGetters([
      'permission_routes',
      'sidebar'
    ]),
    currentMenus() {
      const permission_routes = deepClone(this.permission_routes)
      for(const item of permission_routes) {
        if (!item.hidden && item.path) {
          const item_path = item.path
          const current_path = this.$route.path
          if (current_path.substring(0,item_path.length) === item_path) {
            return item.children.map(child => {
              child.path = item_path + '/' + child.path
              return child
            })
          }
        }
      }
      return []
    },
    activeMenu() {
      const route = this.$route
      const { meta, path } = route
      // if set path, the sidebar will highlight the path you set
      if (meta.activeMenu) {
        return meta.activeMenu
      }
      return path
    },
    showLogo() {
      return true
    },
    variables() {
      return variables
    },
    isCollapse() {
      return !this.sidebar.opened
    }
  },
}
</script>
