<template>
  <div class="multi-operate">
    <template v-for="(item, key, index) in operates_">
      <ElButton
        v-if="item.show"
        :key="key"
        :disabled="item.disabled"
        :icon="`el-icon-${item.tipIcon}`"
        :type="item.type"
        :size="size"
        @click="onClick(key)"
      >
        {{ item.tip }}
      </ElButton>
    </template>
    <slot :selection="selection" />
  </div>
</template>

<script>
import { post } from '@/libs/request'
import { pList } from '@/utils/wData'
import { hasPermission } from '@/directive/w/directive/p'
export default {
  name: 'multiGroup',
  props: {
    primaryKey: { type: [Function, String] },
    selection: { type: Array, default: () => [] },
    operates: { type: Object, default: () => ({}) },
    query: { type: Object, default: () => ({}) },
    module: { type: String, default: '' },
  },
  computed: {
    size() {
      return this.$store.getters['size']
    },
    operates_() {
      const operates = {
        multiDel: { tip: `批量${this.isRecycle ? '永久': ''}删除`, tipIcon: 'delete', type: 'danger', confirm: true },
      }
      const restore = {
        multiRestore: {
          key: 'multiRestore',
          tip: '批量还原',
          tipIcon: 'refresh-left',
          type: 'primary',
          show: hasPermission(`${this.module}${pList.restore}`),
          url: `${this.module}${pList.restore}`,
          disabled: !this.selection.length,
          confirm: true
        }
      }
      const target = this.isRecycle ? restore: {}
      return Object.assign(target, Object.keys(this.operates).reduce((pre, k) => {
        const { show, disabled } = this.operates[k]
        pre[k] = {
          ...operates[k],
          ...this.operates[k],
          show: typeof show === 'function' ? show(this.selection) : show,
          disabled:
            !this.selection.length ||
            (typeof disabled === 'function' ? disabled(this.selection) : disabled),
        }
        return pre
      }, {}))
    },
    isRecycle(){
      return this.$route.query.isRecycle !== undefined
    }
  },
  methods: {
    // 点击操作按钮事件
    async onClick(key) {
      try {
        const primaryKey = this.selection.map(item => item[this.primaryKey])
        switch (key) {
          case 'multiDel':
            const url = this.isRecycle ? `${this.module}${pList.forceDelete}`: this.operates_[key].url
            this.operates_.multiDel.confirm &&
              (await this.$confirm('确认批量删除吗？', '删除提示', {
                confirmButtonText: '确定',
                cancelButtonText: '取消',
                type: 'warning',
              }))
            if (url) {
              await post(url, { ...this.query, [this.primaryKey]: primaryKey })
              this.$emit('done')
            }
            break
          case 'multiRestore':
            const rUrl = this.operates_[key].url
            this.operates_[key].confirm &&
            (await this.$confirm('确认批量还原吗？', '提示', {
              confirmButtonText: '确定',
              cancelButtonText: '取消',
              type: 'warning',
            }))
            if (rUrl) {
              await post(rUrl, { ...this.query, [this.primaryKey]: primaryKey })
              this.$emit('done')
            }
            break
        }
        this.$emit(key)
      } catch (e) {}
    },
  },
}
</script>

<style lang="less" scoped>
.milti-opreate {
  display: flex;
  flex-wrap: nowrap;

  /deep/ .el-button + .el-button {
    margin-left: 5px;
  }
}
</style>
