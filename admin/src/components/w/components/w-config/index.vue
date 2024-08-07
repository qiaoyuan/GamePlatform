<template>
  <div class="app-container">
    <el-tabs v-model="activeName" type="card" @tab-click="handleClick">
      <el-tab-pane v-for="(config, index) of configs" :label="config.title" :name="'' + index" :key="index">
        <el-container style="height: 700px; border: 1px solid #eee">
          <el-aside width="200px" height="100%" style="border-right: 1px solid #eee">
            <el-menu
              @open="handleOpen"
            >
              <el-menu-item
                v-for="item of config.list"
                :ref="'menu' + item.key"
                :key="item.key"
                :index="item.key"
                @click="changeMenu(item)"
              >
                <i class="el-icon-menu"></i>
                <span slot="title">{{ item.title }}</span>
              </el-menu-item>
            </el-menu>
          </el-aside>
          <el-container>
            <div class="w100 p10">
              <p v-if="activeConfig.desc" class="f16 w100 mt10 ml10">{{ activeConfig.desc }}</p>
              <component ref="config" :config="activeConfig" :is="activeConfig.type"/>
              <el-button
                class="ml20"
                type="primary"
                icon="el-icon-check"
                @click="save"
              >保存</el-button>
            </div>
          </el-container>
        </el-container>
      </el-tab-pane>
    </el-tabs>
  </div>
</template>

<script>
import kv from './components/kv'
export default {
  name: 'wConfig',
  props: {
    action: { type: String, default: '' },
  },
  components: { kv },
  data() {
    return {
      activeName: '0',
      activeConfig: {
        key: undefined,
        type: 'kv',
        title: undefined
      },
      configs: []
    }
  },
  mounted() {
    this.$w_fun.post(this.action).then(res => {
      this.configs = res.data.list
      this.$nextTick(() => {
        this.$refs['menu'+this.configs[0].list[0].key][0].$el.click()
      })
    })
  },
  methods: {
    handleOpen(a, b) {
    },
    changeMenu(menu) {
      this.activeConfig = menu
    },
    handleClick() {
      this.$nextTick(_ => {
        for (const item in this.configs) {
          if (item === this.activeName) {
            this.$refs['menu'+this.configs[item].list[0].key][0].$el.click()
          }
        }
      })
    },
    save() {
      this.$w_fun.post('/setting/save', { key: this.activeConfig.key, value: this.$refs.config[0].getValue() }).then(res => {
        this.$refs.config[0].getDetail()
      })
    },
  }
}
</script>
<style lang="less" scoped>
/deep/ .el-menu{
  .el-menu-item {
    height: 32px;
    line-height: 32px;
  }
}
</style>
