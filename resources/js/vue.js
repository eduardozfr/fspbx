import { createApp, h } from 'vue'
import { createInertiaApp } from '@inertiajs/vue3'
import Vueform from '@vueform/vueform'
import vueformConfig from './vueform.config.js'
import { mountLocaleRuntime } from './i18n/runtime'

import './bootstrap'

const appName = window.document.getElementsByTagName('title')[0]?.innerText || 'Laravel'

function resolvePage(name) {
  const [page, module] = name.split('::')

  const pagePath = module
    ? `../../Modules/${module}/Resources/assets/js/Pages/${page}.vue`
    : `./Pages/${page}.vue`

  const pages = module
    ? import.meta.glob('../../Modules/**/Resources/assets/js/Pages/**/*.vue')
    : import.meta.glob('./Pages/**/*.vue')

  if (!pages[pagePath]) {
    throw new Error(`Page not found: ${pagePath}`)
  }

  return typeof pages[pagePath] === 'function' ? pages[pagePath]() : pages[pagePath]
}

createInertiaApp({
  title: (title) => `${title} - ${appName}`,
  resolve: async (name) => await resolvePage(name),
  setup({ el, App, props, plugin }) {
    window.__fspbxLocale = props.initialPage.props.locale?.frontend || 'en-US'
    const localeRuntime = mountLocaleRuntime(() => window.__fspbxLocale || 'en-US')
    const vueApp = createApp({ render: () => h(App, props) })

    vueApp.use(plugin)
    vueApp.use(Vueform, vueformConfig)

    vueApp.mixin({
      mounted() {
        if (this.$page?.props?.locale?.frontend) {
          window.__fspbxLocale = this.$page.props.locale.frontend
          localeRuntime.apply()
        }
      },
      updated() {
        if (this.$page?.props?.locale?.frontend) {
          window.__fspbxLocale = this.$page.props.locale.frontend
          localeRuntime.apply()
        }
      },
    })

    vueApp.mount(el)

    axios.defaults.withCredentials = true
    axios.get('/sanctum/csrf-cookie')
  },
})
