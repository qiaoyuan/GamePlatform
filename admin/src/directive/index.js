import DirectiveW from '@/directive/w'
const install = Vue => {
  Vue.use(DirectiveW)
  const files = require.context('./js/', true, /\S+\.js$/)
  files.keys().forEach(key => {
    const arr = key.split('/')
    const componentName = arr[arr.length - 1].split('.')[0]
    const componentConfig = files(key)
    Vue.directive(componentName, componentConfig.default || componentConfig)
  })
}
export default {
  install
}
