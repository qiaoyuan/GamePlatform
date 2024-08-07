<template>
  <div class="navbar">
    <logo v-if="true" :collapse="isCollapse" />
    <hamburger id="hamburger-container" :is-active="sidebar.opened" class="hamburger-container" @toggleClick="toggleSideBar" />

    <!-- <breadcrumb id="breadcrumb-container" class="breadcrumb-container" /> -->
    <!-- 一级菜单 -->
    <div class="w-first-menu">
      <template v-for="(item, index) in permission_routes">
        <div
          v-if="item.name === 'www_index' && item.children[0]"
          class="w-item"
          :class="{'w-active':$route.path === '/index'}"
          @click="routerLinkGoto(item.redirect)"
        >{{ item.children[0].meta.title  }}</div>
        <template v-else>
          <div
            v-if="!item.hidden"
            :key="index"
            class="w-item"
            :class="{'w-active':$route.path.includes(item.path + '/')}"
            @click="routerLinkGoto(backRouteFirstChildPath(item))"
          >{{ item.meta && item.meta.title }}</div>
        </template>
      </template>
    </div>
    <!-- #一级菜单 -->
    <div class="right-menu ml-auto">

      <el-dropdown class="avatar-container right-menu-item hover-effect" trigger="click" size="medium">
        <div class="avatar-wrapper">
          <img :src="avatar" class="user-avatar">
          <i class="el-icon-caret-bottom gray2" />
        </div>
        <el-dropdown-menu slot="dropdown" size="medium">
          <el-dropdown-item size="medium" @click.native="changePassword">修改密码</el-dropdown-item>
          <el-dropdown-item size="medium" divided @click.native="logout">
            <span>退出登录</span>
          </el-dropdown-item>
        </el-dropdown-menu>
      </el-dropdown>
    </div>
    <w-dialog-form
      ref="wDialogForm"
      title="修改密码"
      :form="form"
      action="index/info"
      :width="$w_fun.isMobile() ? '100%' : '60%'"
      @done="reLogin"
    />
  </div>
</template>

<script>
import { mapGetters } from 'vuex'
// import Breadcrumb from '@/components/Breadcrumb'
import Logo from './Sidebar/Logo'
import Hamburger from '@/components/Hamburger'
import router from '@/router'

export default {
  components: {
    // Breadcrumb,
    Hamburger,
    Logo,
  },
  computed: {
    ...mapGetters([
      'permission_routes',
      'sidebar',
      'avatar',
    ]),
    isCollapse() {
      return this.$w_fun.isMobile() ? true : !this.sidebar.opened
    }
  },
  data() {
    return {
      form: {}
    }
  },
  methods: {
    backRouteFirstChildPath(currentRoute) {
      const getPath = (children, parentPath) => {
        for (const item of children) {
          if (item.children && item.children.length > 0 && item.meta.level < 4) {
            return getPath(item.children, parentPath + '/' + item.path)
          } else {
            return (parentPath + '/' + item.path) || ''
          }
        }
      }

      if (currentRoute.children && currentRoute.children.length > 0 && currentRoute.redirect === 'noRedirect') {
        return getPath(currentRoute.children, currentRoute.path)
      } else {
        return currentRoute.path
      }
    },
    toggleSideBar() {
      this.$store.dispatch('toggleSideBar')
    },
    async logout() {
      this.$confirm('确定注销并退出系统吗？', '提示', {
        confirmButtonText: '确定',
        cancelButtonText: '取消',
        type: 'warning'
      }).then(() => {
        this.$store.dispatch('logOut').then(() => {
          location.reload()
        })
      })
    },
    routerLinkGoto(to, type = 'push') {
      router[type](to)
    },
    changePassword() {
      this.form = {
        opassword: { label: '原密码', value: '', attrs: { showPassword: true } },
        password: { label: '新密码', value: '', attrs: { showPassword: true } },
        rpassword: { label: '确认密码', value: '', attrs: { showPassword: true } },
      }
      this.$refs.wDialogForm.visible = true
    },
    reLogin() {
      this.$alert('修改成功，请重新登录', '重新登陆', {
        confirmButtonText: '确定',
        callback: action => {
          this.$store.dispatch('logOut').then(() => {
            location.reload()
          })
        }
      }).catch(() => {
        this.$store.dispatch('logOut').then(() => {
          location.reload()
        })
      })
    }
  }
}
</script>

<style lang="scss" scoped>
.w-first-menu{
  display: flex;
  align-items: center;
  flex: 1;
  .w-item{
    border-radius: 5px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 15px;
    cursor: pointer;
    color: #cccccc;
    padding: 0 10px;
    margin: 0 1px;
    height: 50px;
    position: relative;
    &.w-active{
      font-weight: bold;
      color: #ffffff;
      &::after{
        position: absolute;
        left: 0;
        right: 0;
        top: 0;
        height: 4px;
        background-color: rgba(64,158,255,1);
        content: '';
      }
      &::before{
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        bottom: 0;
        left: 0;
      }
    }
  }
}
.navbar {
  height: 50px;
  overflow: hidden;
  position: relative;
  display: flex;
  align-items: center;
  background: #2b2f3a;
  color: #ffffff;
  // background: #fff;
  // box-shadow: 0 1px 4px rgba(0,21,41,.08);

  .hamburger-container {
    line-height: 46px;
    height: 100%;
    float: left;
    cursor: pointer;
    transition: background .3s;
    -webkit-tap-highlight-color:transparent;

    &:hover {
      background: rgba(0, 0, 0, .025)
    }
  }

  .breadcrumb-container {
    float: left;
  }

  .errLog-container {
    display: inline-block;
    vertical-align: top;
  }

  .right-menu {
    float: right;
    height: 100%;
    line-height: 50px;

    &:focus {
      outline: none;
    }

    .right-menu-item {
      display: inline-block;
      padding: 0 8px;
      height: 100%;
      font-size: 18px;
      color: #5a5e66;
      vertical-align: text-bottom;

      &.hover-effect {
        cursor: pointer;
        transition: background .3s;

        &:hover {
          background: rgba(0, 0, 0, .025)
        }
      }
      &.none{display: none;}
    }

    .avatar-container {
      margin-right: 30px;

      .avatar-wrapper {
        margin-top: 5px;
        position: relative;

        .user-avatar {
          cursor: pointer;
          width: 20px;
          height: 20px;
          border-radius: 10px;
        }

        .el-icon-caret-bottom {
          cursor: pointer;
          position: absolute;
          right: -20px;
          top: 15px;
          font-size: 12px;
        }
      }
    }
  }
}
</style>
