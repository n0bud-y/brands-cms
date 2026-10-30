import { cx } from '@/lib/utils'
import { Button, Field } from '@/components/ui'

/**
 * JSON-LD box for the SEO team. Accepts raw JSON or <script type="application/ld+json"> blocks;
 * checks it live in the browser. The panel validates again and stores only the parsed data.
 */
function check(value: string): { ok: boolean; message: string; data?: unknown } {
  const input = value.trim()
  if (!input) return { ok: true, message: '' }

  const chunks = /<script/i.test(input)
    ? [...input.matchAll(/<script\b[^>]*>([\s\S]*?)<\/script>/gi)].map((m) => m[1])
    : [input]
  if (chunks.length === 0) return { ok: false, message: 'Could not find a complete <script>…</script> block' }

  try {
    const items = chunks.map((chunk) => JSON.parse(chunk.trim()))
    if (items.some((item) => typeof item !== 'object' || item === null)) {
      return { ok: false, message: 'Schema must be a JSON object, like {"@context": …}' }
    }
    const types = items.flatMap((item) => (Array.isArray(item) ? item : [item])).map((i) => i?.['@type']).filter(Boolean)
    return { ok: true, message: types.length ? `Valid · ${types.join(', ')}` : 'Valid JSON', data: items.length === 1 ? items[0] : items }
  } catch (error) {
    return { ok: false, message: (error as Error).message }
  }
}

const PLACEHOLDER = `{
  "@context": "https://schema.org",
  "@type": "FAQPage",
  "mainEntity": []
}

…or paste <script type="application/ld+json">…</script> tags`

export function SchemaInput({
  label,
  hint,
  value,
  error,
  onChange,
}: {
  label: string
  hint: string
  value: string
  error?: string
  onChange: (value: string) => void
}) {
  const result = check(value)

  return (
    <Field label={label} hint={hint} error={error} group>
      <textarea
        value={value}
        rows={9}
        spellCheck={false}
        placeholder={PLACEHOLDER}
        onChange={(e) => onChange(e.target.value)}
        className={cx(
          'block w-full resize-y rounded-xl border-2 bg-ink p-3.5 font-mono text-xs leading-relaxed text-pop-lime outline-none placeholder:text-white/30 focus:ring-4',
          result.ok ? 'border-ink focus:ring-pop-violet/25' : 'border-pop-red focus:ring-pop-red/25',
        )}
      />
      {value.trim() && (
        <div className="mt-2 flex flex-wrap items-center gap-2">
          <span
            className={cx(
              'inline-flex items-center gap-1 rounded-full border-2 border-ink px-2.5 py-0.5 text-xs font-bold',
              result.ok ? 'bg-pop-lime' : 'bg-pop-pink',
            )}
          >
            {result.ok ? '✓' : '✗'} {result.message}
          </span>
          {result.ok && result.data !== undefined && (
            <Button size="sm" variant="ghost" onClick={() => onChange(JSON.stringify(result.data, null, 2))}>
              ✨ Format
            </Button>
          )}
        </div>
      )}
    </Field>
  )
}
