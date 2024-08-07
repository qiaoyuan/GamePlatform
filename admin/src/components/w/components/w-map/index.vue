<template>
  <div>
    <el-input v-if="inputValue.showContent !== false" id="map-input" v-model="inputValue.content"></el-input>
    <div id="map-container"></div>
  </div>
</template>

<script>
import AMapLoader from "@amap/amap-jsapi-loader";//按需引入依赖
window._AMapSecurityConfig = {
  securityJsCode: 'c8330ce1eaf819e1254b41471e15fc0e',
};
export default {
  name: 'wMap',
  props: {
    modelValue: { type: Object, default: () => ({ content: '', point: [] }) },
  },
  model: { prop: 'modelValue', event: 'update:modelValue' },
  computed: {
    wSize() {
      return this.$store.getters['size']
    },
    inputValue: {
      get() {
        return this.modelValue
      },
      set(v) {
        this.$emit('update:modelValue', v)
      }
    },
  },
  data() {
    return {
      map: undefined,
      marker: undefined,
      AMap: undefined,
      Autocomplete: undefined
    }
  },
  mounted () {
    this.loadMap()
  },
  methods: {
    loadMap() {
      AMapLoader.load({
          key: "ae1e31552a7cc61eb45435522b97d64b", //key值是key值 和安全密钥不同
          version: "2.0", // 指定要加载的 JSAPI 的版本，缺省时默认为 1.4.15
          plugins: ['AMap.ToolBar', 'AMap.AutoComplete'], // 需要使用的的插件列表，如比例尺'AMap.Scale'等
        })
        .then((AMap) => {
          this.AMap = AMap
          this.map = new AMap.Map('map-container', {
            viewMode: "2D", //  是否为3D地图模式
            zoom: 12, // 初始化地图级别
            resizeEnable: true,
          });
          this.map.addControl(new AMap.ToolBar());
          if (this.inputValue.point.length > 0 && this.inputValue.point[0]) {
            this.setPoint(this.inputValue.point)
          }
          this.map.on('click', this.click)
          this.Autocomplete = new AMap.AutoComplete({
            input: 'map-input'
          })
          this.Autocomplete.on('select', ({ poi }) => {
            this.inputValue.point = [poi.location.lng, poi.location.lat]
            this.inputValue.content = poi.name
            this.setPoint(poi.location)
          })
        })
        .catch((e) => {
          console.log(e);
        });
    },
    click({ lnglat }) {
      this.inputValue.point = [lnglat.getLng(), lnglat.getLat()]
      this.setPoint([lnglat.getLng(), lnglat.getLat()])
    },
    setPoint(point) {
      if (this.marker) {
        this.marker.setPosition(point)
      } else {
        this.marker = new this.AMap.Marker({
          icon: "https://webapi.amap.com/theme/v1.3/markers/n/mark_b.png",
          position: point,
          anchor: 'bottom-center'
        });
        this.marker.setDraggable(true)
        this.map.add(this.marker)
      }
      this.map.setCenter(point)
    }
  }
}
</script>

<style lang="less" scoped>
#map-container {
  width: 100%;
  height: 400px;
}
.amap-copyright {
  display: none !important;
}
.amap-logo {
  display: none !important;
}
</style>
