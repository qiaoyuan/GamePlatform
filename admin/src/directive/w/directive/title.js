export default {
  inserted (el, binding, vnode) {
    const { value } = binding
    if (value) {
      el.addEventListener('mouseenter', () => {
        if (el.innerHTML.indexOf(value) === -1) {
          el.innerHTML = el.innerHTML + value
        }
      })
      el.addEventListener('mouseleave', () => {
        if (el.innerHTML.indexOf(value) !== -1) {
          el.innerHTML = el.innerHTML.replace(value, '')
        }
      })
    } else {
      throw new Error(`请设置title"`)
    }
  }
}
