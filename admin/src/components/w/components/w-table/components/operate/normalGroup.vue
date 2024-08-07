<template>
  <ElButtonGroup class="dp-i-f">
    <template v-for="(item, key, index) in operates_">
      <template v-if="Object.keys(operates_).length === 1">
        <ElButton
          v-if="item.show"
          :key="key"
          :disabled="item.disabled"
          :type="item.type"
          :size="size"
          @click="onClick(key)"
        >
          {{ item.tip }}
        </ElButton>
      </template>
      <template v-else>
        <ElButton
          v-if="item.show"
          v-tabshover
          :disabled="item.disabled"
          :tip="item.tip"
          :tip-icon="item.tipIcon"
          :type="item.type"
          :size="size"
          @click="onClick(key)"
        >
          <template v-if="index === 0"> {{ item.tip }} </template>
          <i v-else :class="`el-icon-${item.tipIcon}`" />
        </ElButton>
      </template>
    </template>
    <ElButton
      v-if="$scopedSlots.default"
      :size="size"
      class="setting-button"
      :disabled="moreOperate"
    >
      <ElDropdown placement="bottom-end" :disabled="moreOperate">
        <i class="el-icon-more-outline setting-icon" />
        <ElDropdownMenu class="operate-dropdown" slot="dropdown">
          <div ref="dropdown"><slot :row="row" :$index="$index" /></div>
        </ElDropdownMenu>
      </ElDropdown>
    </ElButton>
  </ElButtonGroup>
</template>

<script>
import { post } from '@/libs/request'
export default {
  name: 'normalGroup',
  props: {
    primaryKey: { type: String },
    row: { type: Object, default: () => ({}) },
    $index: { type: Number },
    operates: { type: Object, default: () => ({}) },
    query: { type: Object, default: () => ({}) },
  },
  computed: {
    size() {
      return this.$store.getters['size']
    },
    operates_() {
      const operates = {
        edit: { tip: '编辑', tipIcon: 'edit', type: 'primary' },
        del: { tip: '删除', tipIcon: 'delete', type: 'danger', confirm: true },
        look: { tip: '详情', tipIcon: 'view' },
      }
      return Object.keys(this.operates).reduce((pre, k) => {
        const { show, disabled } = this.operates[k]
        pre[k] = {
          ...operates[k],
          ...this.operates[k],
          show: typeof show === 'function' ? show(this.row, this.$index) : show,
          disabled: typeof disabled === 'function' ? disabled(this.row, this.$index) : disabled,
        }
        return pre
      }, {})
    },
  },
  data() {
    return {
      moreOperate: false,
    }
  },
  mounted() {
    this.$scopedSlots.default &&
      this.$refs.dropdown.children.length === 0 &&
      (this.moreOperate = true)
  },
  methods: {
    // 点击操作按钮事件
    async onClick(key) {
      try {
        const url = this.operates_[key].url
        const primaryKey = this.row[this.primaryKey]
        switch (key) {
          case 'edit':
          case 'look':
            url && this.$router.push(`${url}?${this.primaryKey}=${primaryKey}`)
            break
          case 'del':
            // 根据配置决定是否需要删除提示
            this.operates_.del.confirm &&
              (await this.$confirm('确认删除吗？', '删除提示', {
                confirmButtonText: '确定',
                cancelButtonText: '取消',
                type: 'warning',
              }))
            if (url) {
              await post(url, { ...this.query, [this.primaryKey]: primaryKey })
              this.$emit('done')
            }
            break
          case 'status':
            if (url) {
              await post(url, {
                ...this.query,
                [this.primaryKey]: primaryKey,
                status: 1 - this.row.status,
              })
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
.setting-button {
  padding: 0 !important;
}

.setting-icon {
  font-size: 12px;
  line-height: 16px;
  width: 22px;
}

.operate-dropdown div {
  display: flex;
  flex-direction: column;
  max-width: 120px;
  padding: 0 10px;

  /deep/ .el-button {
    margin: 0;
    margin: 5px 0;
  }
}
</style>
