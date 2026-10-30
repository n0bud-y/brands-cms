import type { Faq } from '@/types'
import { Button, Input, Textarea } from '@/components/ui'

/** Questions and answers, added, edited, moved and removed in place. */
export function FaqList({ items, onChange }: { items: Faq[]; onChange: (items: Faq[]) => void }) {
  const update = (i: number, patch: Partial<Faq>) => onChange(items.map((item, j) => (j === i ? { ...item, ...patch } : item)))
  const move = (i: number, by: number) => {
    const next = [...items]
    ;[next[i], next[i + by]] = [next[i + by], next[i]]
    onChange(next)
  }

  return (
    <div className="space-y-3">
      {items.map((item, i) => (
        <div key={i} className="space-y-2 rounded-xl border-2 border-ink/15 bg-paper p-3">
          <div className="flex items-center gap-2">
            <span className="text-xs font-extrabold text-ink/40">Q{i + 1}</span>
            <Input value={item.q} placeholder="Question…" onChange={(e) => update(i, { q: e.target.value })} />
            <Button size="sm" variant="ghost" disabled={i === 0} title="Move up" onClick={() => move(i, -1)}>
              ↑
            </Button>
            <Button size="sm" variant="ghost" disabled={i === items.length - 1} title="Move down" onClick={() => move(i, 1)}>
              ↓
            </Button>
            <Button size="sm" variant="ghost" title="Remove" onClick={() => onChange(items.filter((_, j) => j !== i))}>
              ✕
            </Button>
          </div>
          <Textarea value={item.a} placeholder="Answer…" onChange={(e) => update(i, { a: e.target.value })} />
        </div>
      ))}
      <Button size="sm" variant="secondary" onClick={() => onChange([...items, { q: '', a: '' }])}>
        ➕ Add a question
      </Button>
    </div>
  )
}

/** Which words get swapped for the real names on the website. */
export function PlaceholderHint() {
  return (
    <p className="text-xs text-ink/60">
      Placeholders: {['{location}', '{state}', '{city}', '{service}', '{brand}'].map((p) => (
        <code key={p} className="mr-1 rounded bg-pop-yellow/50 px-1 font-bold text-ink">
          {p}
        </code>
      ))}
      become the real names on each page.
    </p>
  )
}
