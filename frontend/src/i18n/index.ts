import { useEffect } from 'react'
import { en } from './en'
import { pl } from './pl'

export type Language = 'en' | 'pl'

export const languages: Record<Language, { languageName: string; phrases: Record<string, string> }> = {
  en,
  pl,
}

const textOriginals = new WeakMap<Text, string>()
const translatedAttributes = ['aria-label', 'placeholder', 'title'] as const
const polishPartialPhrases: Array<[string, string]> = [
  ['Welcome back,', 'Witaj ponownie,'],
  ['is set as the household currency.', 'jest ustawione jako waluta gospodarstwa.'],
  ['Home task overdue:', 'Zalegle zadanie domowe:'],
  ['Reminder overdue:', 'Zalegle przypomnienie:'],
  ['health markers are out of range', 'markery zdrowia sa poza zakresem'],
]

function phraseFor(language: Language, value: string): string {
  const phrases = languages[language].phrases
  const normalized = value.replace(/\s+/g, ' ').trim()

  if (language === 'en' || normalized === '') {
    return normalized
  }

  const exact = phrases[normalized]

  if (exact) {
    return exact
  }

  return polishPartialPhrases.reduce(
    (text, [from, to]) => text.replaceAll(from, to),
    normalized,
  )
}

function applyTextTranslation(root: ParentNode, language: Language): void {
  const walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT)
  let node = walker.nextNode()

  while (node) {
    const text = node as Text
    const parent = text.parentElement

    if (parent && !['SCRIPT', 'STYLE', 'TEXTAREA'].includes(parent.tagName)) {
      if (!textOriginals.has(text)) {
        textOriginals.set(text, text.textContent ?? '')
      }

      const original = textOriginals.get(text) ?? ''
      const translated = phraseFor(language, original)
      const leading = original.match(/^\s*/)?.[0] ?? ''
      const trailing = original.match(/\s*$/)?.[0] ?? ''

      const nextText = translated === original.trim() ? original : `${leading}${translated}${trailing}`

      if (text.textContent !== nextText) {
        text.textContent = nextText
      }
    }

    node = walker.nextNode()
  }
}

function applyAttributeTranslation(root: ParentNode, language: Language): void {
  const elements = root.querySelectorAll<HTMLElement>(translatedAttributes.map((attribute) => `[${attribute}]`).join(','))

  elements.forEach((element) => {
    translatedAttributes.forEach((attribute) => {
      if (!element.hasAttribute(attribute)) {
        return
      }

      const originalKey = `i18nOriginal${attribute.replace(/[^a-z]/gi, '')}`
      const dataset = element.dataset as Record<string, string | undefined>

      if (!dataset[originalKey]) {
        dataset[originalKey] = element.getAttribute(attribute) ?? ''
      }

      const original = dataset[originalKey] ?? ''
      const translated = phraseFor(language, original)
      const nextValue = translated === original.trim() ? original : translated

      if (element.getAttribute(attribute) !== nextValue) {
        element.setAttribute(attribute, nextValue)
      }
    })
  })
}

export function translatePhrase(language: Language, value: string): string {
  return phraseFor(language, value)
}

export function translatedPhraseCount(language: Language): number {
  return Object.keys(languages[language].phrases).length
}

export function useDomTranslations(language: Language): void {
  useEffect(() => {
    const translate = () => {
      const root = document.querySelector('.app-shell, .auth-page')

      if (!root) {
        return
      }

      applyTextTranslation(root, language)
      applyAttributeTranslation(root, language)
    }

    translate()

    const observer = new MutationObserver(() => translate())
    observer.observe(document.body, {
      subtree: true,
      childList: true,
      characterData: true,
      attributes: true,
      attributeFilter: [...translatedAttributes],
    })

    return () => observer.disconnect()
  }, [language])
}
