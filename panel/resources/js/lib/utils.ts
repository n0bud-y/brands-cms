export const cx = (...classes: (string | false | null | undefined)[]) => classes.filter(Boolean).join(' ')

export const uid = () => Math.random().toString(36).slice(2, 10)

export function slugify(text: string): string {
  return text
    .normalize('NFKD')
    .replace(/[̀-ͯ]/g, '')
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '')
    .slice(0, 190)
    .replace(/-+$/, '')
}

/** ISO string -> value for <input type="datetime-local"> in the browser's timezone. */
export function toLocalInput(iso: string | null): string {
  if (!iso) return ''
  const date = new Date(iso)
  return new Date(date.getTime() - date.getTimezoneOffset() * 60_000).toISOString().slice(0, 16)
}

export const fromLocalInput = (value: string) => (value ? new Date(value).toISOString() : null)

export function formatDate(iso: string | null): string {
  return iso ? new Date(iso).toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' }) : '—'
}

export const plural = (count: number, word: string) => `${count} ${word}${count === 1 ? '' : 's'}`

export const stripHtml = (html: string) => html.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim()

/** First validation message for a field or any of its children (e.g. "available_layouts.2"). */
export function firstError(errors: Record<string, string | undefined>, field: string): string | undefined {
  return errors[field] ?? Object.entries(errors).find(([key]) => key.startsWith(`${field}.`))?.[1]
}
