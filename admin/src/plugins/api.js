const install = Vue => {
	const files = require.context('../api/', true, /\.js$/)
	let modules = {}
	files.keys().forEach(key => {
	  if (key === './index.js') return
		Object.assign(modules, files(key))
	})
	Vue.prototype.$w_api = modules
}


export default {
	install
}
