import { useState, type ReactNode } from 'react'
import { tones, type Tone } from '@/lib/theme'
import type { Block, BlockType } from '@/types'
import { cx, stripHtml, uid } from '@/lib/utils'
import { Badge, Button, Field, Input, Select, Textarea } from '@/components/ui'
import { ImageInput } from '@/components/ImageInput'
import { RichText } from '@/components/RichText'

// Each block type is described by its fields; one generic form renders them all.
// Keep this list in sync with panel/app/Support/Blocks.php and brand-kit/cms/blocks/*.php.

type FieldDef =
  | { key: string; label: string; type: 'text' | 'textarea' | 'rich' | 'image'; placeholder?: string; hint?: string }
  | { key: string; label: string; type: 'select'; options: [string, string][] }
  | { key: string; label: string; type: 'list'; itemLabel: string; fields: FieldDef[] }

type Values = Record<string, unknown>

type BlockDef = {
  label: string
  emoji: string
  tone: Tone
  fields: FieldDef[]
  defaults: Values
  summary: (b: Values) => string
}

const str = (v: unknown) => (typeof v === 'string' ? v : v == null ? '' : String(v))

const BLOCKS: Record<BlockType, BlockDef> = {
  heading: {
    label: 'Heading',
    emoji: '🔠',
    tone: 'violet',
    fields: [
      { key: 'text', label: 'Text', type: 'text' },
      { key: 'level', label: 'Size', type: 'select', options: [['2', 'Section heading (H2)'], ['3', 'Subheading (H3)']] },
    ],
    defaults: { text: '', level: 2 },
    summary: (b) => str(b.text),
  },
  paragraph: {
    label: 'Paragraph',
    emoji: '✍️',
    tone: 'yellow',
    fields: [{ key: 'html', label: 'Text', type: 'rich' }],
    defaults: { html: '' },
    summary: (b) => stripHtml(str(b.html)),
  },
  image: {
    label: 'Image',
    emoji: '🖼️',
    tone: 'cyan',
    fields: [
      { key: 'url', label: 'Image', type: 'image' },
      { key: 'alt', label: 'Alt text', type: 'text', hint: 'Describes the image for search engines and screen readers.' },
      { key: 'caption', label: 'Caption', type: 'text' },
    ],
    defaults: { url: '', alt: '', caption: '' },
    summary: (b) => str(b.caption) || str(b.alt) || str(b.url),
  },
  image_text: {
    label: 'Image + text',
    emoji: '🧩',
    tone: 'pink',
    fields: [
      { key: 'image', label: 'Image', type: 'image' },
      { key: 'alt', label: 'Alt text', type: 'text' },
      { key: 'side', label: 'Image side', type: 'select', options: [['left', '⬅️ Image left'], ['right', '➡️ Image right']] },
      { key: 'html', label: 'Text', type: 'rich' },
    ],
    defaults: { image: '', alt: '', side: 'left', html: '' },
    summary: (b) => `${b.side === 'right' ? '➡️' : '⬅️'} ${stripHtml(str(b.html))}`,
  },
  quote: {
    label: 'Quote',
    emoji: '💬',
    tone: 'orange',
    fields: [
      { key: 'text', label: 'Quote', type: 'textarea' },
      { key: 'cite', label: 'Who said it', type: 'text' },
    ],
    defaults: { text: '', cite: '' },
    summary: (b) => str(b.text),
  },
  cta: {
    label: 'Call to action',
    emoji: '📣',
    tone: 'lime',
    fields: [
      { key: 'title', label: 'Title', type: 'text' },
      { key: 'text', label: 'Text', type: 'textarea' },
      { key: 'button', label: 'Button label', type: 'text', placeholder: 'Get a quote' },
      { key: 'url', label: 'Button link', type: 'text', placeholder: '/contact or https://…' },
    ],
    defaults: { title: '', text: '', button: '', url: '' },
    summary: (b) => str(b.title),
  },
  two_column: {
    label: 'Two columns',
    emoji: '📰',
    tone: 'cyan',
    fields: [
      { key: 'left', label: 'Left column', type: 'rich' },
      { key: 'right', label: 'Right column', type: 'rich' },
    ],
    defaults: { left: '', right: '' },
    summary: (b) => stripHtml(str(b.left)),
  },
  gallery: {
    label: 'Gallery',
    emoji: '🎞️',
    tone: 'pink',
    fields: [
      {
        key: 'images',
        label: 'Images',
        type: 'list',
        itemLabel: 'image',
        fields: [
          { key: 'url', label: 'Image', type: 'image' },
          { key: 'alt', label: 'Alt text', type: 'text' },
        ],
      },
    ],
    defaults: { images: [] },
    summary: (b) => `${Array.isArray(b.images) ? b.images.length : 0} images`,
  },
  faq: {
    label: 'FAQ',
    emoji: '❓',
    tone: 'violet',
    fields: [
      {
        key: 'items',
        label: 'Questions',
        type: 'list',
        itemLabel: 'question',
        fields: [
          { key: 'q', label: 'Question', type: 'text' },
          { key: 'a', label: 'Answer', type: 'textarea' },
        ],
      },
    ],
    defaults: { items: [{ q: '', a: '' }] },
    summary: (b) => `${Array.isArray(b.items) ? b.items.length : 0} questions`,
  },
}

function FieldInput({ def, value, onChange }: { def: FieldDef; value: unknown; onChange: (v: unknown) => void }) {
  switch (def.type) {
    case 'text':
      return (
        <Field label={def.label} hint={def.hint}>
          <Input value={str(value)} placeholder={def.placeholder} onChange={(e) => onChange(e.target.value)} />
        </Field>
      )
    case 'textarea':
      return (
        <Field label={def.label} hint={def.hint}>
          <Textarea value={str(value)} placeholder={def.placeholder} onChange={(e) => onChange(e.target.value)} />
        </Field>
      )
    case 'rich':
      return (
        <Field label={def.label} group>
          <RichText value={str(value)} onChange={onChange} />
        </Field>
      )
    case 'image':
      return <ImageInput label={def.label} hint={def.hint} value={str(value)} onChange={onChange} />
    case 'select':
      return (
        <Field label={def.label}>
          <Select value={str(value) || def.options[0][0]} onChange={(e) => onChange(e.target.value)}>
            {def.options.map(([v, label]) => (
              <option key={v} value={v}>
                {label}
              </option>
            ))}
          </Select>
        </Field>
      )
    case 'list': {
      const items = (Array.isArray(value) ? value : []) as Values[]
      const empty = Object.fromEntries(def.fields.map((f) => [f.key, '']))
      return (
        <Field label={def.label} group>
          <div className="space-y-3">
            {items.map((item, i) => (
              <div key={i} className="space-y-3 rounded-xl border-2 border-dashed border-ink/30 bg-paper p-3">
                <div className="flex items-center justify-between">
                  <Badge>
                    {def.itemLabel} #{i + 1}
                  </Badge>
                  <Button size="sm" variant="ghost" onClick={() => onChange(items.filter((_, j) => j !== i))}>
                    ✕ Remove
                  </Button>
                </div>
                <Fields
                  defs={def.fields}
                  values={item}
                  onChange={(patch) => onChange(items.map((it, j) => (j === i ? { ...it, ...patch } : it)))}
                />
              </div>
            ))}
            <Button size="sm" variant="secondary" onClick={() => onChange([...items, empty])}>
              ➕ Add {def.itemLabel}
            </Button>
          </div>
        </Field>
      )
    }
  }
}

function Fields({ defs, values, onChange }: { defs: FieldDef[]; values: Values; onChange: (patch: Values) => void }) {
  return (
    <>
      {defs.map((def) => (
        <FieldInput key={def.key} def={def} value={values[def.key]} onChange={(v) => onChange({ [def.key]: v })} />
      ))}
    </>
  )
}

function IconButton({ title, disabled, onClick, children }: { title: string; disabled?: boolean; onClick: () => void; children: ReactNode }) {
  return (
    <button
      type="button"
      title={title}
      aria-label={title}
      disabled={disabled}
      onClick={onClick}
      className="grid size-7 place-items-center rounded-lg border-2 border-ink bg-white text-xs font-bold text-ink transition hover:-translate-y-0.5 hover:shadow-hard-sm disabled:pointer-events-none disabled:opacity-30"
    >
      {children}
    </button>
  )
}

export function BlockEditor({ blocks, onChange }: { blocks: Block[]; onChange: (blocks: Block[]) => void }) {
  const [collapsed, setCollapsed] = useState<Set<string>>(() => new Set())
  const [dragFrom, setDragFrom] = useState<number | null>(null)
  const [dragOver, setDragOver] = useState<number | null>(null)
  const [armed, setArmed] = useState<number | null>(null) // only the drag handle starts a drag

  const update = (i: number, patch: Values) => onChange(blocks.map((b, j) => (j === i ? { ...b, ...patch } : b)))
  const move = (from: number, to: number) => {
    if (to < 0 || to >= blocks.length || from === to) return
    const next = [...blocks]
    next.splice(to, 0, next.splice(from, 1)[0])
    onChange(next)
  }
  const duplicate = (i: number) => {
    const next = [...blocks]
    next.splice(i + 1, 0, { ...structuredClone(blocks[i]), id: uid() })
    onChange(next)
  }
  const toggle = (id: string) =>
    setCollapsed((prev) => {
      const next = new Set(prev)
      if (!next.delete(id)) next.add(id)
      return next
    })
  const add = (type: BlockType) => onChange([...blocks, { id: uid(), type, ...structuredClone(BLOCKS[type].defaults) }])

  // "Auto alternate": 1st image+text left, 2nd right, 3rd left…
  const imageTextCount = blocks.filter((b) => b.type === 'image_text').length
  const alternate = () => {
    let n = 0
    onChange(blocks.map((b) => (b.type === 'image_text' ? { ...b, side: n++ % 2 ? 'right' : 'left' } : b)))
  }

  return (
    <div className="space-y-4">
      {blocks.length === 0 && (
        <div className="rounded-2xl border-2 border-dashed border-ink/40 bg-white/60 px-4 py-10 text-center">
          <div className="mb-2 text-4xl">🧱</div>
          <p className="font-extrabold">Blank canvas</p>
          <p className="text-sm text-ink/55">Stack some blocks below to build the post.</p>
        </div>
      )}

      {blocks.map((block, i) => {
        const def = BLOCKS[block.type]
        if (!def) return null
        const isCollapsed = collapsed.has(block.id)
        return (
          <article
            key={block.id}
            draggable={armed === i}
            onDragStart={(e) => {
              setDragFrom(i)
              e.dataTransfer.effectAllowed = 'move'
            }}
            onDragOver={(e) => {
              if (dragFrom === null) return
              e.preventDefault()
              setDragOver(i)
            }}
            onDrop={(e) => {
              e.preventDefault()
              if (dragFrom !== null) move(dragFrom, i)
            }}
            onDragEnd={() => {
              setDragFrom(null)
              setDragOver(null)
              setArmed(null)
            }}
            className={cx(
              'rounded-2xl border-2 border-ink bg-white shadow-hard transition',
              dragFrom === i && 'rotate-1 opacity-50',
              dragOver === i && dragFrom !== i && 'ring-4 ring-pop-violet/40',
            )}
          >
            <header
              className={cx(
                'flex items-center gap-2 border-ink px-2 py-1.5',
                tones[def.tone],
                isCollapsed ? 'rounded-[14px]' : 'rounded-t-[14px] border-b-2',
              )}
            >
              <span
                className="cursor-grab px-1.5 text-lg leading-none select-none active:cursor-grabbing"
                title="Drag to reorder"
                onMouseDown={() => setArmed(i)}
                onMouseUp={() => setArmed(null)}
              >
                ⠿
              </span>
              <button type="button" className="flex min-w-0 flex-1 items-center gap-2 text-left text-sm" onClick={() => toggle(block.id)}>
                <span className="text-base">{def.emoji}</span>
                <span className="font-extrabold">{def.label}</span>
                {isCollapsed && <span className="truncate opacity-70">{def.summary(block).slice(0, 80)}</span>}
              </button>
              <div className="flex shrink-0 items-center gap-1">
                <IconButton title="Move up" disabled={i === 0} onClick={() => move(i, i - 1)}>
                  ↑
                </IconButton>
                <IconButton title="Move down" disabled={i === blocks.length - 1} onClick={() => move(i, i + 1)}>
                  ↓
                </IconButton>
                <IconButton title="Duplicate" onClick={() => duplicate(i)}>
                  ⧉
                </IconButton>
                <IconButton title="Delete" onClick={() => onChange(blocks.filter((_, j) => j !== i))}>
                  ✕
                </IconButton>
              </div>
            </header>
            {!isCollapsed && (
              <div className="space-y-4 p-4 sm:p-5">
                <Fields defs={def.fields} values={block} onChange={(patch) => update(i, patch)} />
              </div>
            )}
          </article>
        )
      })}

      <div className="rounded-2xl border-2 border-dashed border-ink/40 bg-white/60 p-4">
        <div className="mb-3 flex flex-wrap items-center justify-between gap-2">
          <p className="font-extrabold">➕ Add a block</p>
          {imageTextCount > 1 && (
            <Button size="sm" variant="secondary" onClick={alternate}>
              ⇄ Alternate image sides
            </Button>
          )}
        </div>
        <div className="grid grid-cols-2 gap-2.5 sm:grid-cols-3 xl:grid-cols-5">
          {(Object.keys(BLOCKS) as BlockType[]).map((type) => (
            <button
              key={type}
              type="button"
              onClick={() => add(type)}
              className={cx(
                'flex flex-col items-center gap-1 rounded-xl border-2 border-ink px-2 py-3 text-xs font-bold shadow-hard-sm transition hover:-translate-y-0.5 hover:-rotate-1 hover:shadow-hard',
                tones[BLOCKS[type].tone],
              )}
            >
              <span className="text-xl">{BLOCKS[type].emoji}</span>
              {BLOCKS[type].label}
            </button>
          ))}
        </div>
      </div>
    </div>
  )
}
