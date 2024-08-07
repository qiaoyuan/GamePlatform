import Cookies from 'js-cookie'

const TokenKey = 'USER_TOKEN' + location.host

export function getToken() {
  return Cookies.get(TokenKey) || ''
}

export function setToken(token) {
  return Cookies.set(TokenKey, token)
}

export function removeToken() {
  return Cookies.remove(TokenKey)
}

export function getCookie(key) {
  if (key) key += location.host
  return Cookies.get(key) || ''
}

export function setCookie(key, value) {
  if (key) key += location.host
  return Cookies.set(key, value)
}

export function removeCookie(key) {
  if (key) key += location.host
  return Cookies.remove(key)
}
