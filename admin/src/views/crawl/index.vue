<template>
  <div class="app-container">
    <w-tabs-table
      ref="wTable"
      :operates="operates"
      :module="module"
      @add="onAdd"
      @edit="onEdit"
      @getList="updateStats"
    >
      <template #headerOperate>
        <div class="crawl-server-stats">
          <el-tag type="success" effect="plain">
            主服务器启用任务：{{ statsLoaded ? formatServerStats(stats.main_server) : '--' }}
          </el-tag>
          <el-tag type="warning" effect="plain">
            爬虫2启用任务：{{ statsLoaded ? formatServerStats(stats.crawler_2) : '--' }}
          </el-tag>
        </div>
      </template>
      <template #other>
        <el-tooltip content="一键爬取" placement="bottom">
          <el-button type="warning" size="mini" icon="el-icon-download" @click="crawlEmpty()">
            一键爬取全部
          </el-button>
        </el-tooltip>
      </template>
      <template #crawl_server_nameSlot="{ row }">
        <el-select
          :value="row.crawl_server"
          :disabled="Boolean(row._crawlServerSaving)"
          size="mini"
          class="crawl-server-select"
          @change="changeCrawlServer(row, $event)"
        >
          <el-option label="主服务器" :value="1" />
          <el-option label="爬虫2" :value="2" />
        </el-select>
      </template>
      <template #crawl_type_nameSlot="{ row }">
        <el-select
          :value="Number(row.crawl_type ?? 0)"
          :disabled="Boolean(row._crawlTypeSaving)"
          size="mini"
          class="crawl-type-select"
          @change="changeCrawlType(row, $event)"
        >
          <el-option label="默认爬虫" :value="0" />
          <el-option label="店铺加强" :value="1" />
        </el-select>
      </template>
      <template #enhance_storesSlot="{ row }">
        <div class="enhance-stores-cell">
          <span :title="row.enhance_stores || ''">{{ row.enhance_stores || '未设置' }}</span>
          <el-button type="text" size="mini" @click="openEnhanceStores(row)">设置</el-button>
        </div>
      </template>
      <template #status="{ row }">
        <el-switch
          :value="Number(row.status)"
          :disabled="Boolean(row._statusSaving)"
          active-color="#13ce66"
          inactive-color="#ff4949"
          :active-value="1"
          :inactive-value="0"
          @change="changeTargetStatus(row, $event)"
        />
      </template>
    </w-tabs-table>

    <crawl-add-dialog ref="crawlAddDialog" @done="getList" />
    <el-dialog
      title="设置加强店铺"
      :visible.sync="enhanceStoresDialog.visible"
      width="480px"
      :close-on-click-modal="false"
      :close-on-press-escape="!enhanceStoresDialog.saving"
      :show-close="!enhanceStoresDialog.saving"
    >
      <el-input
        v-model="enhanceStoresDialog.value"
        type="textarea"
        :rows="5"
        :maxlength="2048"
        show-word-limit
        :disabled="enhanceStoresDialog.saving"
        placeholder="每行一个店铺名，或用逗号分隔"
      />
      <p>仅店铺加强类型生效。有名单按名字加强；清空后按改价策略选择符合条件的Top3加强，本轮列表候选仍全部入库。</p>
      <template #footer>
        <el-button :disabled="enhanceStoresDialog.saving" @click="enhanceStoresDialog.visible = false">取消</el-button>
        <el-button type="primary" :loading="enhanceStoresDialog.saving" @click="saveEnhanceStores">保存</el-button>
      </template>
    </el-dialog>
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
      stats: {
        main_server: { g2g: 0, eld: 0 },
        crawler_2: { g2g: 0, eld: 0 },
      },
      statsLoaded: false,
      enhanceStoresDialog: { visible: false, row: null, value: '', saving: false },
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
    openEnhanceStores(row) {
      if (this.enhanceStoresDialog.saving) return
      this.enhanceStoresDialog = { visible: true, row, value: row.enhance_stores || '', saving: false }
    },
    async saveEnhanceStores() {
      const dialog = this.enhanceStoresDialog
      if (dialog.saving || !dialog.row) return
      dialog.saving = true
      try {
        const res = await this.$w_fun.post(`${this.module}/status`, {
          id: dialog.row.id,
          enhance_stores: dialog.value,
        })
        this.$set(dialog.row, 'enhance_stores', res.data.enhance_stores)
        dialog.visible = false
        this.$refs.wTable.getList()
      } catch (_) {
        // 请求层展示错误，保留输入供重试。
      } finally {
        dialog.saving = false
      }
    },
    formatServerStats(value) {
      const count = raw => {
        if ((typeof raw !== 'number' && typeof raw !== 'string') || raw === '') return '--'
        const n = Number(raw)
        return Number.isSafeInteger(n) && n >= 0 ? n : '--'
      }
      if (value && typeof value === 'object') {
        return `G2G ${count(value.g2g)}　ELD ${count(value.eld)}`
      }
      return `${count(value)} 个`
    },
    getList() {
      this.$store.dispatch('cleanColumnOptions', this.module)
      this.$refs.wTable.getList()
    },
    updateStats(data) {
      if (data?.stats?.main_server != null && data?.stats?.crawler_2 != null) {
        this.stats = data.stats
        this.statsLoaded = true
        return
      }

      // Older API responses can be counted locally when this unfiltered response
      // contains every row (for example, the current 34 rows on a 50-row page).
      const raw = data?.list
      const rows = Array.isArray(raw) ? raw : (raw?.data ?? [])
      const filter = this.$route.query.filter ? JSON.parse(this.$route.query.filter) : {}
      if (Object.keys(filter).length) {
        this.statsLoaded = false
        return
      }
      if (!Array.isArray(raw) && Number(raw?.total) > rows.length) {
        this.statsLoaded = false
        return
      }

      const counts = {
        main_server: { g2g: 0, eld: 0 },
        crawler_2: { g2g: 0, eld: 0 },
      }
      rows.forEach(row => {
        if (Number(row.status) !== 1) return
        const server = Number(row.crawl_server) === 1
          ? 'main_server'
          : (Number(row.crawl_server) === 2 ? 'crawler_2' : null)
        const category = String(row.category || '')
        const platform = category
          ? (category.startsWith('ELD') ? 'eld' : 'g2g')
          : null
        if (server && platform) counts[server][platform]++
      })
      this.stats = counts
      this.statsLoaded = true
    },
    async changeCrawlServer(row, crawlServer) {
      const nextServer = Number(crawlServer)
      if (nextServer === Number(row.crawl_server) || row._crawlServerSaving) {
        return
      }
      this.$set(row, '_crawlServerSaving', true)
      try {
        const res = await this.$w_fun.post(`${this.module}/status`, {
          id: row.id,
          crawl_server: nextServer,
        })
        this.$set(row, 'crawl_server', nextServer)
        this.$set(row, 'crawl_server_name', res?.data?.crawl_server_name || (nextServer === 1 ? '主服务器' : '爬虫2'))
        this.$refs.wTable.getList()
      } catch (_) {
        // 请求层已展示错误；row 保持原值，选择框会自动回退。
      } finally {
        this.$delete(row, '_crawlServerSaving')
      }
    },
    async changeCrawlType(row, crawlType) {
      const nextType = Number(crawlType)
      if (nextType === Number(row.crawl_type ?? 0) || row._crawlTypeSaving) return
      this.$set(row, '_crawlTypeSaving', true)
      try {
        const res = await this.$w_fun.post(`${this.module}/status`, {
          id: row.id,
          crawl_type: nextType,
        })
        this.$set(row, 'crawl_type', nextType)
        this.$set(row, 'crawl_type_name', res?.data?.crawl_type_name || (nextType === 1 ? '店铺加强' : '默认爬虫'))
        this.$refs.wTable.getList()
      } catch (_) {
        // 请求层展示错误，保持原值让选择器回退。
      } finally {
        this.$delete(row, '_crawlTypeSaving')
      }
    },
    async changeTargetStatus(row, status) {
      this.$set(row, '_statusSaving', true)
      try {
        await this.$w_fun.post(`${this.module}/status`, { id: row.id, status: Number(status) })
        this.$refs.wTable.getList()
      } catch (_) {
        // Request layer displays the error; a list refresh restores the saved value.
        this.$refs.wTable.getList()
      } finally {
        this.$delete(row, '_statusSaving')
      }
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

<style scoped>
.crawl-server-select {
  width: 100px;
}

.crawl-type-select {
  width: 110px;
}

.enhance-stores-cell {
  display: flex;
  align-items: center;
  gap: 8px;
}

.enhance-stores-cell span {
  flex: 1;
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.crawl-server-stats {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  margin-right: 16px;
}
</style>
