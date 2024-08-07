<template>
  <w-select
    ref="select"
    v-model="value"
    :data="searchList"
    multiple
    @change="onChange"
  />
</template>

<script>
export default {
  name: 'searchSelect',
  props: {
    column: { type: Object, default: () => ({}) },
  },
  data() {
    return { value: [], searchList: [], created: false }
  },
  methods: {
    onChange(v) {
      return this.$emit('search', v.length ? v : undefined, this.column)
    },
  },
  mounted() {
    if (!this.created) {
      this.created = true
      if (this.$w_fun.typeOf(this.column.searchList) === 'array') {
        this.searchList = this.column.searchList
      } else {
        let url = '', key = 'list'
        if (this.$w_fun.typeOf(this.column.searchList) === 'string') {
          url = this.column.searchList
        }
        if (this.$w_fun.typeOf(this.column.searchList) === 'object' && this.column.searchList.url && this.column.searchList.key) {
          url = this.column.searchList.url
          key = this.column.searchList.key
        }
        this.$w_fun.post(url).then(res => {
          this.searchList = res.data[key]
        })
      }
    }
  },
}
</script>
