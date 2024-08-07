<template>
  <w-select
    ref="selectRef"
    v-bind="searchOption"
    v-model="value"
    :loading="loading"
    :data="searchList"
    style="width: 250px"
    @change="onSearch"
    @clear="onSearch"
  />
</template>

<script>
import { post } from '@/libs/request'
export default {
  name: 'searchInput',
  props: {
    column: { type: Object, default: () => ({}) },
  },
  computed: {
    searchOption() {
      const option = this.column.searchOption || {}
      if (this.column.searchType === 'remote') {
        option.reserveKeyword === undefined && (option.reserveKeyword = true)
        option.remote = true
        option.remoteMethod = async query => {
          if (!query) {
            this.searchList = []
            return
          }
          this.loading = true
          this.searchList = (
            await post(option.remoteUrl, { [option.remoteKey || 'keyword']: query })
          ).data.list
          this.loading = false
        }
      }
      return option
    },
  },
  data() {
    return {
      value: undefined,
      loading: false,
      searchList: [],
    }
  },
  watch: {
    'column.searchList': {
      immediate: true,
      handler(v) {
        this.$w_fun.typeOf(v) === 'array' && (this.searchList = v)
      },
    },
  },
  methods: {
    onFocus() {
      this.$refs.selectRef.$refs.selectRef.focus()
    },
    onSearch() {
      this.$emit(
        'search',
        (this.searchOption.multiple ? this.value && this.value.length : this.value)
          ? this.value
          : undefined,
        this.column
      )
    },
  },
}
</script>
