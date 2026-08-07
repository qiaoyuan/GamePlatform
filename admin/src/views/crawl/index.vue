<template>
  <div class="app-container">
    <w-tabs-table
      ref="wTable"
      :operates="operates"
      :module="module"
      @add="onAdd"
      @edit="onEdit"
    >
      <template #other>
        <el-tooltip content="一键爬取" placement="bottom">
          <el-button type="warning" size="mini" icon="el-icon-download" @click="crawlEmpty()">
            一键爬取全部
          </el-button>
        </el-tooltip>
      </template>
    </w-tabs-table>

    <crawl-add-dialog ref="crawlAddDialog" @done="getList" />
  </div>
</template>

<script>
import CrawlAddDialog from './dialog/crawlAddDialog'

export default {
  name: 'CrawlIndex',
  components: { CrawlAddDialog },
  data() {
    return {
      module: 'crawl',
      operates: {
        del: true,
        look: false,
        add: true,
        edit: true,
        multiDel: true,
        other: [
          {
            title: '爬取',
            type: 'primary',
            p: 'crawl/crawl',
            click: row => this.doCrawl(row),
          },
          {
            title: '竞品分析',
            type: 'success',
            p: 'crawl/products',
            click: row => this.showProducts(row),
          },
        ],
      },
    }
  },
  methods: {
    getList() {
      this.$store.dispatch('cleanColumnOptions', this.module)
      this.$refs.wTable.getList()
    },
    onAdd() {
      this.$refs.crawlAddDialog.open({})
    },
    onEdit(row) {
      this.$refs.crawlAddDialog.open(row)
    },
    // 单个爬取
    async doCrawl(row) {
      const loading = this.$loading({
        lock: true,
        text: `正在爬取 ${row.name}...`,
        spinner: 'el-icon-loading',
        background: 'rgba(0, 0, 0, 0.3)',
      })
      try {
        const { data } = await this.$http.post(`${this.module}/crawl`, { id: row.id })
        loading.close()
        this.$message.success(data.msg || `爬取完成！共 ${data.data.count} 条，耗时 ${data.data.elapsed}`)
        this.getList()
      } catch (e) {
        loading.close()
        this.$message.error(e?.response?.data?.msg || '爬取失败')
      }
    },
    // 查看竞品分析
    showProducts(row) {
      this.$router.push({ name: 'CrawlProducts', query: { target_id: row.id, name: row.name } })
    },
    // 一键爬取全部启用的目标
    async crawlEmpty() {
      // 获取当前列表
      this.$refs.wTable.staticQuery = { status: 1 }
      const res = await this.$http.get(this.module + '/index', { params: { page: 1, limit: 999, status: 1 } })
      const list = res?.data?.data?.list ?? res?.data?.list ?? []
      if (!list.length) {
        this.$message.warning('没有启用的爬取目标')
        return
      }
      this.$refs.wTable.staticQuery = {}

      const loading = this.$loading({
        lock: true,
        text: `正在爬取第 1/${list.length} 个...`,
        spinner: 'el-icon-loading',
        background: 'rgba(0, 0, 0, 0.3)',
      })
      let total = 0
      for (let i = 0; i < list.length; i++) {
        loading.setText(`正在爬取第 ${i + 1}/${list.length} 个: ${list[i].name}`)
        try {
          const { data } = await this.$http.post(`${this.module}/crawl`, { id: list[i].id })
          total += data.data.count || 0
        } catch (_) {
          // 单个失败继续
        }
      }
      loading.close()
      this.$message.success(`全部爬取完成！共 ${total} 条数据`)
      this.getList()
    },
  },
}
</script>
