/***
 * 防抖 单位时间只触发最后一次
 * @param {?Number|300} type - 当前元素类型
 *  @param {Function} fn - 执行事件
 *  @param {?String|"click"} event - 事件类型 例："keydown"
 *  @param {?Number|300} time - 间隔时间
 *  @param {Array} binding.value - [fn,event,time]
 *  例：<el-input v-debounce="[queryList,`keydown`,300]" />
 *  也可简写成：<el-input v-debounce="[queryList]" />
 *  queryList 函数中 可以拿到输入的值，进行接口请求
 *  例：async queryList(Val) { await xxx(val) }
 */
export default {
  inserted (el, binding, vnode) {
    let [type = "input", fn, event = "input", time = 1000] = binding.value
    const targetEl = el.getElementsByTagName(type)[0];
    let timer
    targetEl.addEventListener(event, () => {
      timer && clearTimeout(timer)
      timer = setTimeout(() => fn(targetEl?.value), time)
    })
  }
}
