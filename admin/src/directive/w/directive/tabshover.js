export default {
  inserted(el, binding, vnode) {
    let timeID = null
    el.addEventListener('mouseenter', () => {
      clearTimeout(timeID)
      timeID = setTimeout(function () {
        const siblings = getSiblings(el)
        const tip = el.getAttribute('tip')
        if (el.innerHTML.indexOf(tip) === -1) {
          el.innerHTML = tip
        }
        for (let i = 0; i < siblings.length; i++) {
          const item = siblings[i]
          const _icon = item.getAttribute('tip-icon')
          if (_icon) {
            item.innerHTML = '<i class="el-icon-' + _icon + '"></i>'
          }
        }
      }, 50)
    })
    el.addEventListener('mouseleave', () => {
      clearTimeout(timeID)
      timeID = setTimeout(function () {
        const icon = el.getAttribute('tip-icon')
        if (icon) {
          el.innerHTML = '<i class="el-icon-' + icon + '"></i>'
        }
        if (el.parentNode.children.length === 1) {
          el.innerHTML = '<i class="el-icon-' + icon + '"></i>'
        } else {
          el.parentNode.children[0].innerHTML = el.parentNode.children[0].getAttribute('tip')
        }
      }, 50)
    })

    function getSiblings(elem) {
      let sibArr = []
      let allChilds = elem.parentNode.children
      for (let i = 0; i < allChilds.length; i++) {
        const item = allChilds[i]
        if (item.nodeType === 1 && item !== elem) {
          sibArr.push(item)
        }
      }
      return sibArr
    }
  },
  update(el) {
    // todo 更新icon 和文字
  }
}
