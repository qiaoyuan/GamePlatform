const install = Vue => {
	const files = require.context('../libs/', true, /\.js$/)
	let modules = {}
	files.keys().forEach(key => {
	  if (key === './index.js') return
		if (files(key).default) {
			const _c = { [key.substring(2,key.length-3)]: files(key).default }
			Object.assign(modules, _c)
		} else {
			Object.assign(modules, files(key))
		}
	})
	Vue.prototype.$w_fun = modules
}


export default {
	install
}
