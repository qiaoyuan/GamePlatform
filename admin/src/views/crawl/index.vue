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
            title: '竞品数据',
            type: 'success',
            p: 'competitorProduct/index',
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
        const res = await this.$w_fun.post(`${this.module}/crawl`, { id: row.id }, {}, false, false)
        loading.close()
        const d = res.data || {}
        this.$message.success(res.message || `爬取完成！共 ${d.count} 条，耗时 ${d.elapsed}`)
        this.getList()
      } catch (e) {
        loading.close()
        this.$message.error(e?.message || '爬取失败')
      }
    },
    // 查看该目标的竞品数据（按爬取目标预筛选，跳转到竞品数据列表页）
    // crawl_data 用 target_id 关联爬取目标，故过滤字段为 target_id
    showProducts(row) {
      this.$router.push({
        name: 'CompetitorProductIndex',
        query: { filter: JSON.stringify({ target_id_multiple: [row.id] }) },
      })
    },
    // 一键爬取全部启用的目标
    async crawlEmpty() {
      // 获取当前启用的爬取目标列表（status_match=1 走后端精确匹配搜索）
      const res = await this.$w_fun.post(this.module + '/index', { page: 1, limit: 999, status_match: 1 }, {}, false, false)
      const raw = res?.data?.list
      const list = Array.isArray(raw) ? raw : (raw?.data ?? [])
      if (!list.length) {
        this.$message.warning('没有启用的爬取目标')
        return
      }

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
          const res = await this.$w_fun.post(`${this.module}/crawl`, { id: list[i].id }, {}, false, false)
          total += (res.data && res.data.count) || 0
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
