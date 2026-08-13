<template>
  <w-dialog
    v-model="visible"
    :title="form.id ? '编辑策略' : '新增策略'"
    :width="620"
    @confirm="onConfirm"
    @cancel="visible = false"
  >
    <el-form ref="form" :model="form" :rules="rules" label-width="120px" size="mini">
      <el-form-item label="策略名称" prop="name">
        <el-input v-model="form.name" placeholder="如：WOW金币-EU-跟最低价" />
      </el-form-item>
      <el-form-item label="对标竞品池" prop="crawl_target_id">
        <el-select v-model="form.crawl_target_id" filterable placeholder="选择爬取目标" style="width: 100%">
          <el-option v-for="o in targetOptions" :key="o.value" :label="o.label" :value="o.value" />
        </el-select>
        <div class="tip">该策略以此爬取目标的竞品数据作为竞价参考池</div>
      </el-form-item>

      <el-divider content-position="left">一、目标店铺过滤</el-divider>
      <el-form-item label="黑名单店铺">
        <el-input
          v-model="form.blacklist_text"
          type="textarea"
          :rows="2"
          placeholder="每行一个店铺标识，黑名单任何时候都不竞价（不填则不限）"
        />
      </el-form-item>
      <el-form-item label="白名单店铺">
        <el-input
          v-model="form.whitelist_text"
          type="textarea"
          :rows="2"
          placeholder="每行一个店铺标识，白名单任何时候都竞价（跳过库存/好评过滤）"
        />
      </el-form-item>
      <el-form-item label="最低库存">
        <el-input-number v-model="form.min_stock" :min="0" :step="1" />
        <span class="tip">低于此库存的店铺不竞价，0=不限</span>
      </el-form-item>
      <el-form-item label="最低好评率(%)">
        <el-input-number v-model="form.min_rating" :min="0" :max="100" :step="1" />
        <span class="tip">低于此好评率的店铺不竞价，0=不限</span>
      </el-form-item>

      <el-divider content-position="left">二、竞品参考价过滤</el-divider>
      <el-form-item label="最低竞品价">
        <el-input-number v-model="form.minimum_price" :min="0" :precision="6" :step="0.0001" placeholder="不填=不限" />
        <span class="tip">价格小于等于此值的竞品不参与最低价计算</span>
      </el-form-item>

      <el-divider content-position="left">三、保底出价</el-divider>
      <el-form-item label="最低出价">
        <el-input-number v-model="form.floor_price" :min="0" :precision="6" :step="0.0001" placeholder="不填=无底线" />
        <span class="tip">出价低于此价则不再竞价（跳过）；不填表示无底线</span>
      </el-form-item>

      <el-divider content-position="left">四、竞价幅度</el-divider>
      <el-form-item label="竞价方式">
        <el-radio-group v-model="form.bid_mode">
          <el-radio label="amount">幅度值</el-radio>
          <el-radio label="equal">等值</el-radio>
        </el-radio-group>
      </el-form-item>
      <el-form-item v-if="form.bid_mode === 'amount'" label="幅度值">
        <el-input-number v-model="form.amplitude" :precision="6" :step="0.0001" />
        <span class="tip">出价 = 目标价 − 幅度值（如目标100、幅度1→出价99；幅度-1→出价101）</span>
      </el-form-item>
      <el-form-item v-else label="说明">
        <span class="tip">等值：与目标店铺同价（目标100→出价100）</span>
      </el-form-item>
      <el-form-item label="价格小数位">
        <el-input-number v-model="form.round_precision" :min="0" :max="8" :step="1" />
      </el-form-item>

      <el-divider content-position="left">五、改价频率与状态</el-divider>
      <el-form-item label="改价频率(分钟)">
        <el-input-number v-model="form.interval_minutes" :min="0" :step="1" />
        <span class="tip">0=不定时；&gt;0 由定时任务按此频率执行</span>
      </el-form-item>
      <el-form-item label="爬后自动执行">
        <el-switch v-model="form.auto_run" :active-value="1" :inactive-value="0" />
        <span class="tip">爬取该竞品池完成后立即执行一次</span>
      </el-form-item>
      <el-form-item label="状态">
        <el-switch v-model="form.status" :active-value="1" :inactive-value="0" active-text="启用" inactive-text="停用" />
      </el-form-item>
    </el-form>
  </w-dialog>
</template>

<script>
import { post } from '@/libs/request'

const defaultForm = () => ({
  id: null,
  name: '',
  crawl_target_id: '',
  auto_run: 1,
  interval_minutes: 0,
  status: 1,
  blacklist_text: '',
  whitelist_text: '',
  min_stock: 0,
  min_rating: 0,
  minimum_price: undefined,
  floor_price: undefined,
  bid_mode: 'amount',
  amplitude: 1,
  round_precision: 6,
})

export default {
  name: 'PriceStrategyFormDialog',
  data() {
    return {
      visible: false,
      loading: false,
      targetOptions: [],
      form: defaultForm(),
      rules: {
        name: [{ required: true, message: '请输入策略名称', trigger: 'blur' }],
        crawl_target_id: [{ required: true, message: '请选择对标竞品池', trigger: 'change' }],
      },
    }
  },
  methods: {
    async open(row) {
      this.form = defaultForm()
      this.$refs.form && this.$refs.form.clearValidate()
      await this.loadTargets()
      if (row && row.id) {
        await this.loadDetail(row.id)
      }
      this.visible = true
    },
    async loadTargets() {
      try {
        const res = await post('crawl/select', {}, {}, false, false)
        this.targetOptions = res.data.list || []
      } catch (_) {
        this.targetOptions = []
      }
    },
    async loadDetail(id) {
      const res = await post('priceStrategy/get', { id }, {}, false, false)
      const info = res.data.info
      if (!info) return
      const config = typeof info.config === 'string'
        ? (() => {
          try { return JSON.parse(info.config) } catch (_) { return {} }
        })()
        : (info.config || {})
      const dim = (config.dimensions && config.dimensions[0])
        || (config.blacklist_stores || config.minimum_price !== undefined ? config : {})
      this.form = {
        id: info.id,
        name: info.name,
        crawl_target_id: info.crawl_target_id,
        auto_run: info.auto_run,
        interval_minutes: info.interval_minutes,
        status: info.status,
        blacklist_text: (dim.blacklist_stores || []).join('\n'),
        whitelist_text: (dim.whitelist_stores || []).join('\n'),
        min_stock: dim.min_stock || 0,
        min_rating: dim.min_rating || 0,
        minimum_price: dim.minimum_price === null || dim.minimum_price === undefined || dim.minimum_price === ''
          ? undefined
          : Number(dim.minimum_price),
        floor_price: dim.floor_price === null || dim.floor_price === undefined ? undefined : Number(dim.floor_price),
        bid_mode: dim.bid_mode || 'amount',
        amplitude: dim.amplitude === undefined ? 1 : Number(dim.amplitude),
        round_precision: dim.round_precision === undefined ? 4 : Number(dim.round_precision),
      }
    },
    splitLines(text) {
      return (text || '')
        .split('\n')
        .map(s => s.trim())
        .filter(s => s)
    },
    buildPayload() {
      const dimension = {
        type: 'lowest',
        blacklist_stores: this.splitLines(this.form.blacklist_text),
        whitelist_stores: this.splitLines(this.form.whitelist_text),
        min_stock: Number(this.form.min_stock) || 0,
        min_rating: Number(this.form.min_rating) || 0,
        minimum_price: this.form.minimum_price === undefined || this.form.minimum_price === null || this.form.minimum_price === ''
          ? null
          : Number(this.form.minimum_price),
        floor_price: this.form.floor_price === undefined || this.form.floor_price === null || this.form.floor_price === ''
          ? null
          : Number(this.form.floor_price),
        bid_mode: this.form.bid_mode,
        amplitude: Number(this.form.amplitude) || 0,
        round_precision: Number(this.form.round_precision) || 4,
      }
      const payload = {
        name: this.form.name,
        crawl_target_id: this.form.crawl_target_id,
        auto_run: this.form.auto_run,
        interval_minutes: Number(this.form.interval_minutes) || 0,
        status: this.form.status,
        config: { dimensions: [dimension] },
      }
      if (this.form.id) payload.id = this.form.id
      return payload
    },
    onConfirm() {
      this.$refs.form.validate(async valid => {
        if (!valid || this.loading) return
        this.loading = true
        try {
          const url = this.form.id ? 'priceStrategy/edit' : 'priceStrategy/add'
          await post(url, this.buildPayload(), {}, false, true)
          this.visible = false
          this.$emit('done')
        } catch (_) {
          // 错误已由请求库统一提示
        } finally {
          this.loading = false
        }
      })
    },
  },
}
</script>

<style lang="less" scoped>
.tip {
  margin-left: 8px;
  font-size: 12px;
  color: #909399;
}
/deep/ .el-divider__text {
  color: #409eff;
  font-weight: 600;
}
</style>
