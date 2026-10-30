import { slugify } from '@/lib/utils'

/** The "/prefix/your-slug" pill under a title. Typing keeps it URL-safe; leaving the field tidies it up. */
export function SlugInput({
  prefix,
  value,
  placeholder,
  onChange,
  onTouch,
}: {
  prefix: string
  value: string
  placeholder: string
  onChange: (slug: string) => void
  onTouch?: () => void
}) {
  return (
    <div className="inline-flex max-w-full items-center rounded-full border-2 border-ink bg-white pl-3 text-sm shadow-hard-sm focus-within:ring-4 focus-within:ring-pop-violet/25">
      <span className="shrink-0 font-semibold text-ink/45" title="Web address">🔗 {prefix}</span>
      <input
        aria-label="Web address"
        placeholder={placeholder}
        className="min-w-0 bg-transparent py-1.5 pr-3 font-bold outline-none"
        size={Math.max(value.length, placeholder.length, 10)}
        value={value}
        onChange={(e) => {
          onTouch?.()
          onChange(e.target.value.toLowerCase().replace(/[^a-z0-9-]/g, '-'))
        }}
        onBlur={() => onChange(slugify(value))}
      />
    </div>
  )
}
