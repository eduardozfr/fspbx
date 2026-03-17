import { normalizeLocale, translateText } from './catalogs'

const TRANSLATABLE_ATTRIBUTES = ['placeholder', 'title', 'aria-label', 'alt']
const SKIPPED_TAGS = new Set(['SCRIPT', 'STYLE', 'NOSCRIPT', 'TEXTAREA', 'CODE', 'PRE'])

function translateAttributes(root, locale) {
  if (!(root instanceof Element)) {
    return
  }

  TRANSLATABLE_ATTRIBUTES.forEach((attribute) => {
    if (root.hasAttribute(attribute)) {
      const currentValue = root.getAttribute(attribute)
      const translatedValue = translateText(currentValue, locale)

      if (translatedValue !== currentValue) {
        root.setAttribute(attribute, translatedValue)
      }
    }
  })

  root.querySelectorAll(TRANSLATABLE_ATTRIBUTES.map((attribute) => `[${attribute}]`).join(',')).forEach((node) => {
    TRANSLATABLE_ATTRIBUTES.forEach((attribute) => {
      if (node.hasAttribute(attribute)) {
        const currentValue = node.getAttribute(attribute)
        const translatedValue = translateText(currentValue, locale)

        if (translatedValue !== currentValue) {
          node.setAttribute(attribute, translatedValue)
        }
      }
    })
  })
}

function translateTextNodes(root, locale) {
  const walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT, {
    acceptNode(node) {
      const parent = node.parentElement

      if (!parent || SKIPPED_TAGS.has(parent.tagName)) {
        return NodeFilter.FILTER_REJECT
      }

      if (!node.textContent?.trim()) {
        return NodeFilter.FILTER_REJECT
      }

      return NodeFilter.FILTER_ACCEPT
    },
  })

  let currentNode = walker.nextNode()

  while (currentNode) {
    const translated = translateText(currentNode.textContent, locale)

    if (translated !== currentNode.textContent) {
      currentNode.textContent = translated
    }

    currentNode = walker.nextNode()
  }
}

export function translateDocument(locale) {
  if (typeof document === 'undefined' || normalizeLocale(locale) === 'en-US') {
    return
  }

  if (document.title) {
    document.title = translateText(document.title, locale)
  }

  translateAttributes(document.body, locale)
  translateTextNodes(document.body, locale)
}

export function mountLocaleRuntime(getLocale) {
  if (typeof document === 'undefined') {
    return { apply: () => null }
  }

  const apply = () => {
    window.requestAnimationFrame(() => {
      translateDocument(getLocale())
    })
  }

  const observer = new MutationObserver((mutations) => {
    if (normalizeLocale(getLocale()) === 'en-US') {
      return
    }

    mutations.forEach((mutation) => {
      mutation.addedNodes.forEach((node) => {
        if (node.nodeType === Node.TEXT_NODE) {
          const translated = translateText(node.textContent, getLocale())
          if (translated !== node.textContent) {
            node.textContent = translated
          }
          return
        }

        if (node.nodeType === Node.ELEMENT_NODE) {
          translateAttributes(node, getLocale())
          translateTextNodes(node, getLocale())
        }
      })
    })
  })

  window.addEventListener('load', apply)

  if (document.body) {
    observer.observe(document.body, { childList: true, subtree: true })
    apply()
  }

  return { apply }
}
