import store from '@/store'
// 判断是否拥有权限
export const hasPermission = value => {
  const allPermissions = ['*']
  // 超级管理员 或 未传递权限 验证通过
  if (allPermissions[0] === '*' || !value) return true
  // 逻辑或 找到任意对应权限 返回 true
  const permissionOr = value.split('||')
  if (permissionOr.some(i => allPermissions.includes(i))) return true
  // 逻辑与 找到全部对应权限 返回 true
  const permissionAnd = value.split('&&')
  if (permissionAnd.every(i => allPermissions.includes(i))) return true
  return false
}

// 处理 node
const setNode = (el, type) => {
  if (!el.parentNode) return
  switch (type) {
    case 'disabled':
      el.setAttribute('disabled', 'disabled')
      break
    case 'remove':
      el.parentNode.removeChild(el)
      break
  }
}

// disabled 暂时待定
// v-p[.disabled]="permissionName"
export default {
  inserted(el, { value, modifiers }) {
    !hasPermission(value) && setNode(el, modifiers.disabled ? 'disabled' : 'remove')
  }
}
