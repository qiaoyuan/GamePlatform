<template>
  <div class="app-container">
    <w-tabs-table
      ref="wTable"
      :module="module"
      :operates="operates"
    />
  </div>
</template>

<script>
export default {
  name: 'PriceStrategyLogIndex',
  data() {
    return {
      module: 'priceStrategyLog',
      operates: {
        // 执行日志由系统写入，只做查看与清理
        add: false,
        edit: false,
        look: false,
        del: true,
        multiDel: true,
      },
    }
  },
}
</script>
