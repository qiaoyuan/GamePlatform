<template>
  <div :class="classObj" class="app-wrapper">
    <div :class="{ 'fixed-header': fixedHeader }">
      <navbar />
    </div>
    <div class="dp-f align-items-content h100 w-content-main">
      <sidebar class="sidebar-container" />
      <div :class="{ hasTagsView: needTagsView }" class="flex-1 main-container">
        <tags-view v-if="needTagsView" />
        <app-main />
      </div>
    </div>
  </div>
</template>

<script>
import { AppMain, Navbar, Sidebar, TagsView } from './components'
import { mapState } from 'vuex'

export default {
  name: 'Layout',
  components: {
    AppMain,
    Navbar,
    Sidebar,
    TagsView
  },
  computed: {
    ...mapState({
      sidebar: state => state.app.sidebar,
      needTagsView: state => true,
      fixedHeader: state => true
    }),
    classObj() {
      return {
        hideSidebar: !this.sidebar.opened,
        openSidebar: this.sidebar.opened,
        withoutAnimation: this.sidebar.withoutAnimation,
      }
    }
  },
  methods: {
  }
}
</script>

<style lang="scss" scoped>
@import "@/assets/styles/mixin.scss";
@import "@/assets/styles/variables.scss";

.w-content-main {
  min-height: calc(100vh - 50px);
  padding-top: 50px;
}

.main-container {
  // min-height: calc(100vh - 50px);
  // padding-top: 50px;
  display: flex;
  flex-direction: column;
}

.app-wrapper {
  @include clearfix;
  position: relative;
  height: 100%;
  width: 100%;

  &.mobile.openSidebar {
    position: fixed;
    top: 0;
  }

  &.openSidebar {
    .main-container {
      width: calc(100% - 200px);
    }
  }
}

.drawer-bg {
  background: #000;
  opacity: 0.3;
  width: 100%;
  top: 0;
  height: 100%;
  position: absolute;
  z-index: 999;
}

.fixed-header {
  position: fixed;
  top: 0;
  right: 0;
  left: 0;
  z-index: 1;
  // width: calc(100% - #{$sideBarWidth});
  transition: width 0.28s;
  background-image: linear-gradient(#ffffff, #ffffff, #ffffff, #f1f1f1);
  // border-bottom: 1px solid #e8e8e8;
  box-shadow: 0 1px 3px 3px rgba(0, 0, 0, .04);
  // &::after{
  //   content: '';
  //   position: absolute;
  //   left: 0;
  //   right: 0;
  //   bottom: 0;
  //   height: 1px;
  //   background-color: #e8e8e8;
  // }
}

.hideSidebar .fixed-header {
  // width: calc(100% - 54px)
}

.mobile .fixed-header {
  width: 100%;
}
</style>

<style lang="scss" scoped>
@media only screen and (max-width: 600px) {
  .app-wrapper.openSidebar .main-container{
    width: 100%;
  }
  .w-content-main{
    flex-direction: column;
    height: auto;
  }
  #app .sidebar-container{
    width: 100% !important;
    height: auto;
    &.has-logo ::v-deep .el-scrollbar{
      height: auto;
      .scrollbar-wrapper{
        overflow-x: auto !important;
      }
      .el-menu{
        display: flex;
      }
    }
  }
}
</style>
