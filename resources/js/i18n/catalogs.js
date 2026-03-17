import en from '../../lang/en.json'
import ptBR from '../../lang/pt_BR.json'

const catalogs = {
  en,
  'en-US': en,
  pt: ptBR,
  'pt-BR': ptBR,
  pt_BR: ptBR,
}

const dynamicPatterns = [
  { pattern: /^Extensions:\s*(.+)$/i, replacement: 'Ramais: $1' },
  { pattern: /^Phone Numbers:\s*(.+)$/i, replacement: 'Numeros de telefone: $1' },
  { pattern: /^Virtual Faxes:\s*(.+)$/i, replacement: 'Faxes virtuais: $1' },
  { pattern: /^Time Zone:\s*(.+)$/i, replacement: 'Fuso horario: $1' },
  { pattern: /^Version:\s*(.+)$/i, replacement: 'Versao: $1' },
  { pattern: /^Disk:\s*(.+)$/i, replacement: 'Disco: $1' },
  { pattern: /^Memory:\s*(.+)$/i, replacement: 'Memoria: $1' },
]

export function normalizeLocale(locale) {
  if (!locale) {
    return 'en-US'
  }

  return String(locale).toLowerCase().startsWith('pt') ? 'pt-BR' : 'en-US'
}

export function getCatalog(locale) {
  return catalogs[normalizeLocale(locale)] || catalogs['en-US']
}

export function applyReplacements(text, replacements = {}) {
  if (typeof text !== 'string') {
    return text
  }

  return Object.entries(replacements).reduce((translated, [key, value]) => {
    return translated.replaceAll(`:${key}`, value)
  }, text)
}

function translateCoreText(text, locale) {
  if (typeof text !== 'string' || !text.trim()) {
    return text
  }

  const normalizedLocale = normalizeLocale(locale)
  if (normalizedLocale === 'en-US') {
    return text
  }

  const catalog = getCatalog(normalizedLocale)
  const normalizedText = text.replace(/\s+/g, ' ').trim()

  if (catalog[normalizedText]) {
    return catalog[normalizedText]
  }

  for (const rule of dynamicPatterns) {
    if (rule.pattern.test(normalizedText)) {
      return normalizedText.replace(rule.pattern, rule.replacement)
    }
  }

  return text
}

export function translateText(text, locale, replacements = {}) {
  if (typeof text !== 'string') {
    return text
  }

  const leadingWhitespace = text.match(/^\s*/)?.[0] ?? ''
  const trailingWhitespace = text.match(/\s*$/)?.[0] ?? ''
  const core = text.trim()

  if (!core) {
    return text
  }

  const translated = translateCoreText(core, locale)

  return `${leadingWhitespace}${applyReplacements(translated, replacements)}${trailingWhitespace}`
}
