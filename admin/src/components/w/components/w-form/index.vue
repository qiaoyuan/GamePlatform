<template>
  <el-form
    v-bind="{ ...$attrs, ...$props, title: undefined }"
    v-on="$listeners"
    ref="form"
    :model="model"
    :rules="rules"
    class="dp-f flex-wrap justify-content-between"
    @submit.native.prevent
  >
    <template v-for="(item, index) in formItems">
      <el-form-item
        v-if="typeof item.show === 'function' ? item.show(model) : item.show"
        :key="item.key"
        :prop="item.prop"
        style="word-wrap: break-word; word-break: break-all"
        :style="{ width: item.width || itemWidth, marginBottom: readonly ? '0' : '18px' }"
      >
        <template #label>
          <slot :name="`${item.prop}Label`" :options="options" :model="model" :item="item">
            {{ item.label }}
            <template v-if="item.label">
              <el-tooltip v-if="item.tooltip" :content="item.tooltip">
                <el-button type="text" :size="size"><i class="el-icon-info" /></el-button>
              </el-tooltip>
              {{ labelSuffix }}
            </template>
          </slot>
        </template>
        <!-- 默认组件 -->
        <template v-if="formTypeList.includes(item.formType)">
          <!-- 展示 -->
          <template v-if="readonly || item.readonly">
            <template
              v-if="
                [
                  'checkbox',
                  'tree',
                  'treeRadio',
                  'treeRadioFree',
                  'treeSelect',
                  'treeSelectFree',
                ].includes(item.formType)
              "
            >
              <div v-html="model[item.prop].join('；') || '-'" />
            </template>
            <w-enclosure v-else-if="item.formType === 'upload'" :enclosure="model[item.prop]" />
            <template v-else-if="item.formType === 'inputArr'">
              <template v-if="model[item.prop].length">
                <div v-for="(itm, idx) in model[item.prop]" :key="idx">
                  {{ idx + 1 }}. {{ itm }}
                </div>
              </template>
              <span v-else>-</span>
            </template>
            <template v-else-if="item.formType === 'editor'">
              <pre v-html="model[item.prop]" />
            </template>
            <template v-else>
              <div v-html="model[item.prop] || '-'" />
            </template>
          </template>
          <template v-else>
            <!-- 文本框 text / 文本域 textarea -->
            <w-input
              v-if="['text', 'textarea'].includes(item.formType)"
              v-model="model[item.prop]"
              v-bind="item.attrs"
              v-on="item.methods"
              :type="item.formType"
            />
            <w-code v-else-if="item.formType === 'code'" v-model="model[item.prop]" v-bind="item.attrs" v-on="item.methods"></w-code>
            <w-code-js v-else-if="item.formType === 'codeJs'" v-model="model[item.prop]" v-bind="item.attrs" v-on="item.methods"></w-code-js>
            <!-- 带建议的文本框 autocomplete -->
            <el-autocomplete
              v-else-if="item.formType === 'autocomplete'"
              v-model="model[item.prop]"
              v-bind="item.attrs"
              v-on="item.methods"
              :size="item.attrs.size || size"
              class="w100"
              :debounce="500"
              :fetch-suggestions="(queryString, cb) => querySearch(queryString, cb, item.prop)"
              @select="validateField(item.prop)"
            />
            <el-cascader
              v-else-if="item.formType === 'cascader'"
              v-model="model[item.prop]"
              v-bind="item.attrs"
              v-on="item.methods"
            ></el-cascader>
            <w-map
              v-else-if="item.formType === 'map'"
              v-model="model[item.prop]"
              v-bind="item.attrs"
              v-on="item.methods"
            ></w-map>
            <!-- 数字框 number -->
            <w-input
              v-else-if="item.formType === 'number'"
              v-model="model[item.prop]"
              v-bind="item.attrs"
              v-on="item.methods"
              type="number"
            />
            <!-- 单选框 radio -->
            <el-radio-group
              v-else-if="item.formType === 'radio'"
              v-model="model[item.prop]"
              v-bind="item.attrs"
              v-on="item.methods"
            >
              <el-radio
                v-for="(itm, idx) in options[item.prop]"
                v-bind="itm"
                :key="idx"
                :label="itm.value"
              >
                {{ itm.label }}
              </el-radio>
            </el-radio-group>
            <!-- 多选框 checkbox -->
            <el-checkbox-group
              v-else-if="item.formType === 'checkbox'"
              v-model="model[item.prop]"
              v-bind="item.attrs"
              v-on="item.methods"
            >
              <el-checkbox
                v-for="(itm, idx) in options[item.prop]"
                v-bind="itm"
                :key="idx"
                :label="itm.value"
              >
                {{ itm.label }}
              </el-checkbox>
            </el-checkbox-group>
            <!-- 下拉框 select -->
            <w-select
              v-else-if="item.formType === 'select'"
              v-model="model[item.prop]"
              v-bind="item.attrs"
              v-on="item.methods"
              :data="options[item.prop]"
            />
            <w-select
              v-else-if="item.formType === 'remote'"
              v-model="model[item.prop]"
              v-bind="item.attrs"
              v-on="item.methods"
              :data="options[item.prop]"
              :remote="true"
              :reserveKeyword="true"
              :remoteMethod="k => remoteMethod(item, k)"
            />
            <el-radio-group
              v-else-if="item.formType === 'status' || item.formType === 'boolean'"
              v-model="model[item.prop]"
              v-bind="item.attrs"
              v-on="item.methods"
            >
              <el-radio :label="1">{{ item.formType === 'status' ? '正常' : '是' }}</el-radio>
              <el-radio :label="0">{{ item.formType === 'status' ? '禁用' : '否' }}</el-radio>
            </el-radio-group>
            <!-- 树状选择器 tree treeRadio treeRadioFree treeSelect treeSelectFree -->
            <component
              v-else-if="
                ['tree', 'treeRadio', 'treeRadioFree', 'treeSelect', 'treeSelectFree'].includes(
                  item.formType
                )
              "
              :is="item.formType === 'tree' ? 'w-tree' : 'w-input-tree'"
              :ref="`${item.prop}Tree`"
              v-model="model[item.prop]"
              v-bind="item.attrs"
              v-on="item.methods"
              :data="options[item.prop]"
              :treeType="item.formType"
              @change="(v, nodes, isFirst) => !isFirst && validateField(item.prop)"
            />
            <!-- 开关 switch -->
            <el-switch
              v-else-if="item.formType === 'switch'"
              v-model="model[item.prop]"
              v-bind="item.attrs"
              v-on="item.methods"
              :active-text="options[item.prop][1].label"
              :active-value="options[item.prop][1].value"
              :inactive-text="options[item.prop][0].label"
              :inactive-value="options[item.prop][0].value"
            />
            <!-- 时间选择器 timeSelect -->
            <el-time-select
              v-else-if="item.formType === 'timeSelect'"
              v-model="model[item.prop]"
              v-bind="item.attrs"
              v-on="item.methods"
              class="w100"
              :picker-options="item.attrs.pickerOptions || pickerOptions"
              :value-format="item.attrs.valueFormat || 'HH:mm'"
              :format="item.attrs.format || 'HH:mm'"
              :size="item.attrs.size || size"
            />
            <!-- 时间选择器 timePicker  -->
            <el-time-picker
              v-else-if="item.formType === 'timePicker'"
              v-model="model[item.prop]"
              v-bind="item.attrs"
              v-on="item.methods"
              class="w100"
              :value-format="item.attrs.valueFormat || 'HH:mm'"
              :format="item.attrs.format || 'HH:mm'"
              :size="item.attrs.size || size"
            />
            <!-- 日期选择器 datePicker -->
            <w-date-picker
              v-else-if="item.formType === 'datePicker'"
              v-model="model[item.prop]"
              v-bind="item.attrs"
              v-on="item.methods"
              :size="item.attrs.size || size"
            />
            <!-- 上传文件 upload -->
            <w-upload
              v-else-if="item.formType === 'upload'"
              :ref="`${item.prop}Upload`"
              v-model="model[item.prop]"
              v-bind="item.attrs"
              v-on="item.methods"
              @change="validateField(item.prop)"
            />
            <!-- 可增删输入框，需自写表单验证 inputArr -->
            <template v-else-if="item.formType === 'inputArr'" class="input-arr">
              <div v-for="(itm, idx) in model[item.prop]" :key="idx" class="mb5 input-arr-box">
                <w-input
                  ref="inputArr"
                  v-model="model[item.prop][idx]"
                  v-bind="item.attrs"
                  v-on="item.methods"
                  type="textarea"
                  :autosize="{ minRows: 1 }"
                  style="width: calc(100% - 30px)"
                  class="mr10"
                  :placeholder="item.attrs.placeholder || `${idx + 1}.请输入${item.label}`"
                />
                <el-button
                  :size="item.attrs.size || size"
                  type="danger"
                  class="oprBtn"
                  style="color: #ffffff"
                  @click="delValidate(item.prop, idx, item.methods.del)"
                  v-if="model[item.prop].length != 1"
                >
                  <i class="el-icon-minus"></i>
                </el-button>
              </div>
              <div
                :size="size"
                type="text"
                class="point"
                @click="addValidate(item.prop, item.methods.add)"
              >
                添加
              </div>
            </template>
            <w-editor
              v-if="item.formType === 'editor'"
              :ref="`${item.prop}Editor`"
              v-model="model[item.prop]"
              v-bind="item.attrs"
              v-on="item.methods"
            />
          </template>
        </template>
        <slot v-else :name="item.formType" :options="options" :model="model" :item="item" />
      </el-form-item>
    </template>
  </el-form>
</template>

<script>
import { cloneDeep } from 'lodash'
import { formTypeList } from '@/utils/wData'
import patterns from './patterns'
import { dataToFile, findTreeParents, valueToLabel } from '@/utils/w'
import { typeOf } from "@/libs/libs";
export default {
  name: 'wForm',
  props: {
    // 整个表单的配置对象
    form: { type: Object, default: () => ({}) },
    // 用作展示页，渲染为文本样式
    readonly: { type: Boolean, default: false },
    // 表单元素宽度，用于控制多列表单
    itemWidth: { type: String, default: '100%' },
    // action 提交表单的地址
    action: { type: String, default: '' },
    // beforeSubmit 表单验证、上传文件之后，提交表单之前，对表单数据进行最后的处理，返回处理好的新表单，接收参数 model
    beforeSubmit: { type: Function },
    labelSuffix: { type: String, default: ':' },
    labelPosition: { type: String, default: 'right' },
    labelWidth: { type: String, default: '120px' },
  },
  data() {
    return {
      times: 0,
      pickerOptions: { start: '08:00', step: '00:15', end: '22:00' },
      formTypeList,
      model: {},
      rules: {},
      formItems: [],
      options: {},
      plainOptions: {},
    }
  },
  computed: {
    size() {
      return this.$store.getters['size']
    },
  },
  methods: {
    // 带建议的输入框筛选方法
    querySearch(v = '', cb, prop) {
      v
        ? cb(
          this.options[prop]
            .filter(({ label }) => label.indexOf(v) !== -1)
            .map(({ label }) => ({ value: label }))
        )
        : cb(this.options[prop].map(({ label }) => ({ value: label })))
    },
    // 添加可增删文本，并触发验证
    addValidate(prop, add) {
      this.model[prop].push('')
      // this.validateField(prop)
      this.$nextTick(() => {
        const refs = this.$refs.inputArr
        refs[refs.length - 1].$refs.input.focus()
      })
      typeof add === 'function' && add()
    },
    // 删除可增删文本，并触发验证
    delValidate(prop, index, del) {
      this.model[prop].splice(index, 1)
      // this.validateField(prop)
      typeof del === 'function' && del(index)
    },
    // 上传需要手动上传的文件
    upload() {
      return Promise.all(
        this.formItems
          .filter(
            ({ readonly, formType, show, attrs = {} }) =>
              !this.readonly &&
              !readonly &&
              formType === 'upload' &&
              (typeof show === 'function' ? show(this.model) : show) &&
              attrs.autoUpload !== true
          )
          .map(({ prop }) => this.$refs[`${prop}Upload`][0].submit())
      )
    },
    // 将传入的 value 进行处理
    makeValue(prop, formType, value, oldValueToShow) {
      switch (formType) {
        case 'select':
          if (
            typeof oldValueToShow === 'string' &&
            oldValueToShow &&
            this.options[prop] &&
            this.options[prop].every(item => item.value !== value)
          ) {
            this.options[prop].push({ label: oldValueToShow, value, disabled: true, plain: true })
          }
          return value
        case 'tree':
        case 'treeSelect':
        case 'treeSelectFree':
          if (oldValueToShow !== undefined && this.options[prop]) {
            value.forEach((item, index) => {
              findTreeParents(this.options[prop], item.value).length === 0 &&
              this.options[prop].push({
                label: oldValueToShow[index],
                value: item.value,
                disabled: true,
                plain: true,
              })
            })
          }
          return value
        case 'treeRadio':
        case 'treeRadioFree':
          if (
            oldValueToShow !== undefined &&
            this.options[prop] &&
            findTreeParents(this.options[prop], value).length === 0
          ) {
            this.options[prop].push({
              label: oldValueToShow,
              value: value,
              disabled: true,
              plain: true,
            })
          }
          return value ? [value] : []
        case 'upload':
          return dataToFile(value)
        default:
          return value
      }
    },
    // 将传入的 readonlyValue 进行处理
    makeReadonlyValue(prop, formType, value, readonlyValue) {
      switch (formType) {
        case 'radio':
        case 'checkbox':
        case 'switch':
          return readonlyValue !== undefined
            ? readonlyValue
            : valueToLabel(this.options[prop], value)
        case 'select':
          return readonlyValue !== undefined
            ? readonlyValue
            : valueToLabel(this.options[prop], value)
        case 'tree':
        case 'treeSelect':
        case 'treeSelectFree':
          if (readonlyValue !== undefined) return [readonlyValue]
          return value
            .map(item => findTreeParents(this.options[prop], item, 'label').pop())
            .filter(i => i)
        case 'treeRadio':
        case 'treeRadioFree':
          if (readonlyValue !== undefined) return [readonlyValue]
          const labels = findTreeParents(this.options[prop], value, 'label')
          return [labels.pop()]
        case 'upload':
          return dataToFile(readonlyValue !== undefined ? readonlyValue : value)
        default:
          return readonlyValue !== undefined ? readonlyValue : value
      }
    },
    // 将传入的 options 进行处理
    makeOptions(optionsList = []) {
      optionsList.forEach(async ({ prop, options }) => {
        let list = []
        switch (this.$w_fun.typeOf(options)) {
          case 'function':
            list = await options()
            break
          case 'string':
            list = await this.$store.dispatch('getColumnOptions', options)
            break
          case 'object':
            const { url = '', method = 'post', data = {}, key = 'list' } = options
            list = await (await this.$w_fun.post(url, data)).data[key]
            break
          case 'array':
            list = options
            break
        }
        prop.forEach(k => {
          this.$set(this.options, k, cloneDeep(list))
          this.plainOptions[k] = this.options[k].length
          const { readonly, formType = 'text', value, readonlyValue, oldValueToShow } = this.form[k]
          this.model[k] =
            this.readonly || readonly
              ? this.makeReadonlyValue(k, formType, value, readonlyValue)
              : this.makeValue(k, formType, value, oldValueToShow)
        })
        this.clearValidate(undefined, 2)
      })
    },
    // 验证某个字段
    validateField(prop) {
      return this.$refs.form.validateField(prop)
    },
    // 验证表单
    validate() {
      return this.$refs.form.validate()
    },
    // 移除表单项的验证结果（times用于递归多次等待视图更新后执行，部分组件加载实际会晚于表单渲染）
    clearValidate(prop, times = 0) {
      this.$refs.form && this.$refs.form.clearValidate(prop)
      times > 0 && this.$nextTick(() => this.clearValidate(prop, times - 1))
    },
    // 重置表单
    resetFields() {
      const model = {}
      Object.keys(this.form).forEach(prop => {
        const { formType = 'text', value, oldValueToShow } = this.form[prop]
        model[prop] = this.makeValue(prop, formType, value, oldValueToShow)
      })
      this.model = model
      this.clearValidate(undefined, 2)
    },
    // 提交表单
    async submit() {
      await this.validate()
      await this.upload()
      // 深克隆 this.model，防止直接修改 model 导致数据类型等报错
      let data = cloneDeep(this.model)
      // 只读表单数据为空
      this.readonly
        ? (data = {})
        : this.formItems.forEach(({ prop, readonly, formType, attrs = {} }) => {
          // 只读元素不提交
          if (readonly) return delete data[prop]
          switch (formType) {
            // 默认单文件传 path 字符串，多文件传 path 数组
            case 'upload':
              const urlArr = this.model[prop].filter(({ path }) => path).map(({ path }) => path)
              data[prop] = attrs.limit > 1 ? urlArr : urlArr[0] ? urlArr[0] : ''
              break
            case 'treeRadio':
            case 'treeRadioFree':
              data[prop] = data[prop][0] || ''
              break
            case 'number':
              data[prop] = data[prop] ? parseFloat(data[prop]) : 0
              break
          }
        })
      // 自定义数据处理方法
      typeof this.beforeSubmit === 'function' && (data = await this.beforeSubmit(data))
      if (this.action) {
        const result = await this.$w_fun.post(this.action, data, true)
        this.$emit('submit', result)
        return result
      }
      this.$emit('submit', data)
      return data
    },
    remoteMethod(formItem, k) {
      let url = ''
      let key = 'k'
      let data = {}
      if (typeOf(formItem.options) === 'string') {
        url = formItem.options
      } else {
        url = formItem.options.url
        key = formItem.options.key || 'k'
        data = formItem.options.data || {}
      }
      this.$w_fun.post(url, { ...data, [key]: k }).then(res => {
        this.$set(this.options, formItem.prop, res.data.list)
      })
    },
    refreshOptions(prop) {
      this.$store.dispatch('cleanColumnOptions', this.form[prop].options)
      this.makeOptions([{ prop: [prop], options: this.form[prop].options }])
    }
  },
  watch: {
    form: {
      immediate: true,
      handler(v, ov = {}) {
        this.times += 1
        this.formItems = []
        const model = {}
        const optionsList = []
        const rules = {}
        this.rules = {}
        Object.keys(this.options).forEach(k => this.options[k].splice(this.plainOptions[k]))
        Object.keys(v).forEach((prop, index) => {
          let {
            show = true,
            readonly = false,
            width,
            label = '',
            value = undefined,
            readonlyValue,
            oldValueToShow,
            formType = 'text',
            attrs = {},
            methods = {},
            options,
            required = true,
            type,
            max,
            min,
            len,
            pattern,
            validator,
            detailDisplay,
            tooltip,
            ellipsis,
            lazy,
          } = v[prop]

          // 兼容老版
          formType === 'input' && (formType = 'text')

          // 隐藏元素 不再继续处理
          if (show === false) return (model[prop] = value)

          // 处理 formItems
          switch (formType) {
            case 'text':
            case 'textarea':
            case 'autocomplete':
              !(attrs.maxlength > 0) && (attrs.maxlength = max)
            case 'number':
              !attrs.placeholder && (attrs.placeholder = `请输入${label}`)
              break
            case 'inputArr':
              break
            default:
              !attrs.placeholder && (attrs.placeholder = `请选择${label}`)
              break
          }
          let makeOptions = true
          if (formType === 'cascader' && lazy && typeOf(options) === 'object') {
            const _this = this
            attrs.props = {
              lazy: true,
              lazyLoad (node, resolve) {
                const { value } = node;
                _this.$w_fun.post(options.url, { ...(options.data || {}), [options.nodeKey || 'parent_id_match']: value || 0 }).then(res => {
                  resolve(res.data.list)
                })
              }
            }
            makeOptions = false
          }
          this.formItems.push({
            key: `${this.times}${index}`,
            show,
            readonly,
            width,
            label,
            prop,
            formType,
            attrs,
            methods,
            // 详情展示的文本
            detailDisplay,
            tooltip,
            ellipsis,
            options
          })

          // 将 options 全部收集至一个数组中，合并相同请求配置的字段，避免重复请求
          if (
            makeOptions &&
            options &&
            (!this.options[prop] ||
              !this.options[prop].length ||
              !ov[prop] ||
              JSON.stringify(options) !== JSON.stringify(ov[prop].options))
          ) {
            const index = optionsList.findIndex(i => i.options === options)
            index === -1
              ? optionsList.push({ prop: [prop], options })
              : optionsList[index].prop.push(prop)
          }

          // 只读表单 不再继续处理
          if (this.readonly || readonly) {
            model[prop] = this.makeReadonlyValue(prop, formType, value, readonlyValue)
            return
          }

          // 处理 value
          model[prop] = this.makeValue(prop, formType, value, oldValueToShow)

          // 处理表单验证
          const rule = []
          // 验证触发时机
          let trigger = 'blur'
          switch (formType) {
            case 'number':
              type = 'number'
              break
            case 'checkbox':
            case 'address':
            case 'tree':
            case 'treeRadio':
            case 'treeRadioFree':
            case 'treeSelect':
            case 'treeSelectFree':
            case 'upload':
              type = 'array'
            case 'autocomplete':
            case 'radio':
            case 'select':
            case 'switch':
            case 'timeSelect':
            case 'timePicker':
            case 'datePicker':
              trigger = 'change'
              break
            case 'inputArr':
              type = 'array'
              break
          }

          if (required !== false) {
            switch (formType) {
              case 'inputArr':
                rule.push({
                  required: true,
                  validator: (r, v) => {
                    if (!v.length) return Promise.reject(`${label}不能为空`)
                    const emptyIndex = v.findIndex(i => !i)
                    if (emptyIndex > -1) return Promise.reject(`第 ${emptyIndex + 1}项不能为空`)
                    return Promise.resolve()
                  },
                  trigger,
                })
                break
              default:
                rule.push({ required: true, message: `${label}不能为空`, trigger })
                break
            }
          }

          switch (type) {
            case 'number':
              if (![max, min].includes(undefined)) {
                rule.push({
                  type,
                  validator: (r, v) =>
                    typeof v === 'number' && (v > max || v < min)
                      ? Promise.reject(`${label}应在${min}-${max}之间`)
                      : Promise.resolve(),
                  trigger,
                })
              } else if (max !== undefined) {
                rule.push({
                  type,
                  validator: (r, v) =>
                    typeof v === 'number' && v > max
                      ? Promise.reject(`${label}不得大于${max}`)
                      : Promise.resolve(),
                  trigger,
                })
              } else if (min !== undefined) {
                rule.push({
                  type,
                  validator: (r, v) =>
                    typeof v === 'number' && v < min
                      ? Promise.reject(`${label}不得小于${min}`)
                      : Promise.resolve(),
                  trigger,
                })
              }
              break
            case 'array':
              len > 0 && rule.push({ type, len, message: `${label}应选择${len}项`, trigger })
              if (max > 0 && min > 0) {
                rule.push({ type, max, min, message: `${label}应选择${min}-${max}项`, trigger })
              } else if (max > 0) {
                rule.push({ type, max, message: `${label}不得超过${max}项`, trigger })
              } else if (min > 0) {
                rule.push({ type, min, message: `${label}不得少于${min}项`, trigger })
              }
              break
            default:
              len > 0 && rule.push({ len, message: `${label}应为${len}个字符`, trigger })
              if (max > 0 && min > 0) {
                rule.push({ max, min, message: `${label}应为${min}-${max}个字符`, trigger })
              } else if (max > 0) {
                rule.push({ max, message: `${label}不得超过${max}个字符`, trigger })
              } else if (min > 0) {
                rule.push({ min, message: `${label}不得少于${min}个字符`, trigger })
              }
              break
          }
          if (pattern) {
            if (patterns[pattern]) {
              const { reg, msg = '格式错误' } = patterns[pattern]
              rule.push({ pattern: reg || patterns[pattern], message: `${label}${msg}`, trigger })
            } else {
              rule.push({ pattern, message: `${label}格式错误`, trigger })
            }
          }
          switch (this.$w_fun.typeOf(validator)) {
            case 'function':
              rule.push({ validator: (r, v) => validator(r, v, this.model, this.options), trigger })
              break
            case 'object':
              rule.push({
                ...validator,
                validator: (r, v) => validator.validator(r, v, this.model, this.options),
              })
              break
          }
          rules[prop] = rule
        })
        this.model = model
        this.rules = rules
        // 处理 options
        this.makeOptions(optionsList)
      },
    },
  },
}
</script>
<style lang="less" scoped>
.oprBtn {
  width: 1.25rem;
  height: 1.25rem;
  padding: 0;
  color: #c0c4cc;
  text-align: center;
}
/deep/ .oprAdd.el-button--text {
  margin-top: 0px;
}
.point {
  cursor: pointer;
  height: 20px;
  line-height: 20px;
  font-size: 13px;
  padding-left: 16px;
  color: #bcbcbc;
}
.input-arr > .input-arr-box:last-of-type(1) {
  margin-bottom: 0px !important;
}
</style>
