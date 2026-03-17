import { computed, watchEffect } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { normalizeLocale, translateText } from '../i18n/catalogs'

export function useLocale() {
  const page = usePage()

  const currentLocale = computed(() => normalizeLocale(page.props.locale?.frontend || 'en-US'))
  const availableLocales = computed(() => page.props.locale?.available || [])

  watchEffect(() => {
    if (typeof window !== 'undefined') {
      window.__fspbxLocale = currentLocale.value
    }
  })

  const t = (text, replacements = {}) => translateText(text, currentLocale.value, replacements)

  const switchLocale = (locale) => {
    const switchRoute = page.props.locale?.switch_route || '/locale'
    const payload = {
      locale,
      _token: page.props.csrf_token,
    }
    const isPublicAuthScreen = String(page.component || '').startsWith('Auth/')

    if (isPublicAuthScreen) {
      router.get(switchRoute, { locale }, {
        preserveScroll: true,
        preserveState: false,
        replace: true,
      })

      return
    }

    router.post(
      switchRoute,
      payload,
      {
        preserveScroll: true,
        preserveState: false,
      }
    )
  }

  const formatDate = (value, options = {}) => {
    if (!value) {
      return ''
    }

    const date = value instanceof Date ? value : new Date(value)

    return new Intl.DateTimeFormat(currentLocale.value, {
      dateStyle: 'short',
      ...options,
    }).format(date)
  }

  const formatNumber = (value, options = {}) => {
    return new Intl.NumberFormat(currentLocale.value, options).format(value ?? 0)
  }

  const formatCurrency = (value, currency = 'BRL', options = {}) => {
    return new Intl.NumberFormat(currentLocale.value, {
      style: 'currency',
      currency,
      ...options,
    }).format(value ?? 0)
  }

  return {
    currentLocale,
    availableLocales,
    t,
    switchLocale,
    formatDate,
    formatNumber,
    formatCurrency,
  }
}
