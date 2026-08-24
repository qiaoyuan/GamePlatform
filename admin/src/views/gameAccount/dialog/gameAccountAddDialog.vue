<template>
  <el-dialog
    :title="isEdit ? '编辑账号' : '添加账号'"
    :visible.sync="visible"
    :width="$w_fun.isMobile() ? '100%' : '560px'"
    @close="visible = false"
  >
    <el-form ref="form" :model="form" :rules="currentRules" label-width="130px" size="small">

      <!-- 平台：新增时第一项，编辑时禁用 -->
      <el-form-item label="平台" prop="platform">
        <el-select
          v-model="form.platform"
          style="width:100%"
          :disabled="isEdit"
          @change="onPlatformChange"
        >
          <el-option :value="1" label="G2G" />
          <el-option :value="2" label="Eldorado" />
        </el-select>
      </el-form-item>

      <!-- 未选平台前不显示其他字段 -->
      <template v-if="form.platform !== null">

        <!-- 用户ID：仅 G2G 需要手填 -->
        <el-form-item v-if="form.platform === 1" label="用户ID" prop="user_id">
          <el-input v-model="form.user_id" placeholder="请输入用户ID" />
        </el-form-item>

        <el-form-item label="账号名称">
          <el-input v-model="form.account_name" placeholder="请输入账号名称" />
        </el-form-item>

        <!-- G2G 专用：三令牌 -->
        <template v-if="form.platform === 1">
          <el-form-item label="设备活跃令牌">
            <el-input v-model="form.active_device_token" placeholder="请输入设备活跃令牌" />
          </el-form-item>
          <el-form-item label="长期访问令牌">
            <el-input v-model="form.long_lived_token" placeholder="请输入长期访问令牌" />
          </el-form-item>
          <el-form-item label="刷新令牌">
            <el-input v-model="form.refresh_token" placeholder="请输入刷新令牌" />
          </el-form-item>
        </template>

        <!-- Eldorado 专用：OAuth2 凭证 -->
        <template v-if="form.platform === 2">
          <el-form-item label="Client ID" prop="client_id">
            <el-input v-model="form.client_id" placeholder="请输入 Client ID" />
          </el-form-item>
          <el-form-item label="Client Secret" prop="client_secret">
            <el-input v-model="form.client_secret" placeholder="请输入 Client Secret" show-password />
          </el-form-item>
        </template>

        <el-form-item label="状态" prop="status">
          <el-radio-group v-model="form.status">
            <el-radio :label="1">正常</el-radio>
            <el-radio :label="0">禁用</el-radio>
          </el-radio-group>
        </el-form-item>

      </template>
    </el-form>

    <span slot="footer">
      <el-button @click="visible = false">取消</el-button>
      <el-button
        v-if="form.platform !== null"
        type="primary"
        :loading="loading"
        @click="onSubmit"
      >
        提交
      </el-button>
    </span>
  </el-dialog>
</template>

<script>
import { post } from '@/libs/request'

const defaultForm = () => ({
  id: null,
  user_id: '',
  account_name: '',
  platform: null,          // null = 未选，新增流程先选平台
  active_device_token: '',
  long_lived_token: '',
  refresh_token: '',
  client_id: '',
  client_secret: '',
  status: 1,
})

/** 生成 10 位随机数字，位数与 G2G 的 user_id（如 1001793103）相仿 */
function randomUserId() {
  return String(Math.floor(1000000000 + Math.random() * 9000000000))
}

export default {
  name: 'GameAccountAddDialog',
  data() {
    return {
      visible: false,
      loading: false,
      isEdit: false,
      module: 'gameAccount',
      form: defaultForm(),
    }
  },
  computed: {
    currentRules() {
      const base = {
        platform: [{ required: true, message: '请选择平台', trigger: 'change' }],
        status:   [{ required: true, message: '请选择状态', trigger: 'change' }],
      }
      if (this.form.platform === 1) {
        base.user_id = [{ required: true, message: '请输入用户ID', trigger: 'blur' }]
      }
      if (this.form.platform === 2) {
        base.client_id     = [{ required: true, message: '请输入 Client ID',     trigger: 'blur' }]
        base.client_secret = [{ required: true, message: '请输入 Client Secret', trigger: 'blur' }]
      }
      return base
    },
  },
  methods: {
    open(data = {}) {
      this.form   = Object.assign(defaultForm(), data)
      this.isEdit = !!(data && data.id)
      // 编辑时 platform 已有值；新增时保持 null，让用户先选
      if (!this.isEdit) {
        this.form.platform = null
      }
      this.$nextTick(() => this.$refs.form && this.$refs.form.clearValidate())
      this.visible = true
    },

    onPlatformChange(val) {
      // 清空另一平台的专属字段
      if (val === 1) {
        this.form.client_id     = ''
        this.form.client_secret = ''
        this.form.user_id       = ''
      } else {
        this.form.active_device_token = ''
        this.form.long_lived_token    = ''
        this.form.refresh_token       = ''
        // Eldorado 不需要真实 user_id，自动填随机值
        this.form.user_id = randomUserId()
      }
      this.$nextTick(() => this.$refs.form && this.$refs.form.clearValidate())
    },

    onSubmit() {
      this.$refs.form.validate(async valid => {
        if (!valid || this.loading) return
        this.loading = true
        const url = this.isEdit ? `${this.module}/edit` : `${this.module}/add`
        try {
          await post(url, this.form, {}, false, true)
          this.visible = false
          this.$emit('done')
        } catch (_) {
          // 错误已由请求库统一提示
        } finally {
          this.loading = false
        }
      })
    },

    getForm() {
      return this.$refs.form
    },
  },
}
</script>
