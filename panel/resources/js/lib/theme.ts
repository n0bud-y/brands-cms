// Color "tones" used across the UI. Full class names so Tailwind can see them.
export const tones = {
  violet: 'bg-pop-violet text-white',
  pink: 'bg-pop-pink text-ink',
  lime: 'bg-pop-lime text-ink',
  yellow: 'bg-pop-yellow text-ink',
  cyan: 'bg-pop-cyan text-ink',
  orange: 'bg-pop-orange text-ink',
  red: 'bg-pop-red text-white',
  white: 'bg-white text-ink',
  paper: 'bg-paper text-ink',
  ink: 'bg-ink text-white',
}

export type Tone = keyof typeof tones

const BRAND_TONES: Tone[] = ['violet', 'pink', 'lime', 'yellow', 'cyan', 'orange']

/** A stable color per brand (or any string), so each brand has its own identity everywhere. */
export function brandTone(key: string): string {
  let hash = 0
  for (const char of key) hash = (hash * 31 + char.charCodeAt(0)) | 0
  return tones[BRAND_TONES[Math.abs(hash) % BRAND_TONES.length]]
}

export function initials(name: string): string {
  const letters = name
    .split(/\s+/)
    .filter(Boolean)
    .slice(0, 2)
    .map((word) => word[0].toUpperCase())
    .join('')
  return letters || '?'
}
