import { useState } from 'react'
import { Head, useForm } from '@inertiajs/react'
import type { Tone } from '@/lib/theme'
import { uid } from '@/lib/utils'
import type { Site } from '@/types'
import { BrandAvatar, Button, Card, Field, Input, PageHeader, Switch, Textarea } from '@/components/ui'
import { ImageInput } from '@/components/ImageInput'

type Settings = Record<string, string>

type FieldDef = { key: string; label: string; type?: 'textarea' | 'image' | 'email' | 'url'; hint?: string; placeholder?: string }

// Keys the brand-kit templates already use. Anything else is a "custom" setting.
const GROUPS: { title: string; icon: string; tone: Tone; fields: FieldDef[] }[] = [
  {
    title: 'Contact',
    icon: '📞',
    tone: 'yellow',
    fields: [
      { key: 'email', label: 'Email', type: 'email', placeholder: 'hello@brand.com' },
      { key: 'phone', label: 'Phone', placeholder: '+1 555 0100' },
      { key: 'whatsapp', label: 'WhatsApp number', hint: 'With country code, e.g. +15550100', placeholder: '+15550100' },
      { key: 'address', label: 'Address', type: 'textarea' },
    ],
  },
  {
    title: 'Branding',
    icon: '🎨',
    tone: 'pink',
    fields: [
      { key: 'logo', label: 'Logo', type: 'image' },
      { key: 'favicon', label: 'Favicon', type: 'image' },
      { key: 'footer_text', label: 'Footer text', type: 'textarea', placeholder: '© Brand. All rights reserved.' },
    ],
  },
  {
    title: 'Social links',
    icon: '🌐',
    tone: 'cyan',
    fields: [
      { key: 'facebook', label: 'Facebook', type: 'url', placeholder: 'https://facebook.com/…' },
      { key: 'instagram', label: 'Instagram', type: 'url', placeholder: 'https://instagram.com/…' },
      { key: 'linkedin', label: 'LinkedIn', type: 'url', placeholder: 'https://linkedin.com/company/…' },
      { key: 'twitter', label: 'X / Twitter', type: 'url', placeholder: 'https://x.com/…' },
      { key: 'youtube', label: 'YouTube', type: 'url', placeholder: 'https://youtube.com/@…' },
      { key: 'tiktok', label: 'TikTok', type: 'url', placeholder: 'https://tiktok.com/@…' },
    ],
  },
  {
    title: 'Blog page',
    icon: '📝',
    tone: 'lime',
    fields: [
      { key: 'blog_title', label: 'Blog title', hint: 'Heading and <title> of /blogs', placeholder: 'Our blog' },
      { key: 'blog_description', label: 'Blog description', type: 'textarea', hint: 'Meta description of /blogs' },
    ],
  },
]

// The website's blog and location pages use these; empty = the built-in look.
const COLORS: { key: string; label: string; hint: string; fallback: string }[] = [
  { key: 'theme_primary', label: 'Main colour', hint: 'Headings, buttons, dark sections', fallback: '#12284c' },
  { key: 'theme_accent', label: 'Accent colour', hint: 'Highlights, icons, button hover', fallback: '#c9a45c' },
  { key: 'theme_text', label: 'Text colour', hint: 'Paragraphs', fallback: '#2b3445' },
  { key: 'theme_background', label: 'Light background', hint: 'Soft grey/cream sections', fallback: '#f7f5f0' },
]
const FONTS = ['font_heading', 'font_body', 'font_google']
const CODE: { key: string; label: string; hint: string }[] = [
  { key: 'code_head', label: 'Inside <head>', hint: 'Google Tag Manager / Analytics, verification meta tags, pixels.' },
  { key: 'code_body', label: 'Right after <body>', hint: 'The <noscript> part of Google Tag Manager.' },
  { key: 'code_footer', label: 'Before </body>', hint: 'Chat widgets and other scripts that can load last.' },
]

const KNOWN = new Set([
  ...GROUPS.flatMap((g) => g.fields.map((f) => f.key)),
  ...COLORS.map((c) => c.key),
  ...FONTS,
  ...CODE.map((c) => c.key),
])

/** Colour swatch + hex box; empty means "use the built-in colour". */
function ColorField({ label, hint, value, fallback, error, onChange }: { label: string; hint: string; value: string; fallback: string; error?: string; onChange: (v: string) => void }) {
  return (
    <Field label={label} hint={hint} error={error} group>
      <div className="flex items-center gap-2">
        <input
          type="color"
          aria-label={label}
          value={value || fallback}
          onChange={(e) => onChange(e.target.value)}
          className="h-10 w-14 shrink-0 cursor-pointer rounded-xl border-2 border-ink bg-white p-1"
        />
        <Input value={value} placeholder={fallback} className="font-mono" onChange={(e) => onChange(e.target.value.trim())} />
        {value && (
          <Button size="sm" variant="ghost" title="Back to the built-in colour" onClick={() => onChange('')}>
            ↺
          </Button>
        )}
      </div>
    </Field>
  )
}

export default function SettingsPage({ site, settings, can_edit_code }: { site: Site; settings: Settings; can_edit_code: boolean }) {
  const form = useForm({
    settings: Object.fromEntries(Object.entries(settings).filter(([k]) => KNOWN.has(k))) as Settings,
  })
  const [custom, setCustom] = useState(() =>
    Object.entries(settings)
      .filter(([k]) => !KNOWN.has(k))
      .map(([key, value]) => ({ id: uid(), key, value })),
  )

  const values = form.data.settings
  const set = (key: string, value: string) => form.setData('settings', { ...values, [key]: value })
  const errors = form.errors as Record<string, string | undefined>

  return (
    <form
      onSubmit={(e) => {
        e.preventDefault()
        form.transform((data) => {
          const all: Settings = { ...data.settings }
          for (const row of custom) if (row.key.trim()) all[row.key.trim()] = row.value
          return { settings: all }
        })
        form.put(`/s/${site.slug}/settings`, { preserveScroll: true })
      }}
    >
      <Head title={`Settings · ${site.name}`} />
      <PageHeader
        title="Settings"
        emoji="🎛️"
        eyebrow={
          <>
            <BrandAvatar name={site.name} slug={site.slug} size="sm" />
            <span className="font-bold text-ink/60">{site.name}</span>
          </>
        }
      >
        <Button type="submit" variant="pop" disabled={form.processing}>
          {form.processing ? 'Saving…' : '💾 Save settings'}
        </Button>
      </PageHeader>

      {errors.settings && (
        <p className="mb-6 rounded-xl border-2 border-ink bg-pop-pink/30 px-4 py-3 text-sm font-semibold">⚠️ {errors.settings}</p>
      )}

      <div className="grid gap-6 lg:grid-cols-2">
        {GROUPS.map((group) => (
          <Card key={group.title} title={group.title} icon={group.icon} tone={group.tone}>
            {group.fields.map((f) => {
              const value = values[f.key] ?? ''
              if (f.type === 'image') return <ImageInput key={f.key} label={f.label} value={value} onChange={(v) => set(f.key, v)} />
              return (
                <Field key={f.key} label={f.label} hint={f.hint ?? <code>setting('{f.key}')</code>} error={errors[`settings.${f.key}`]}>
                  {f.type === 'textarea' ? (
                    <Textarea value={value} placeholder={f.placeholder} onChange={(e) => set(f.key, e.target.value)} />
                  ) : (
                    <Input type={f.type ?? 'text'} value={value} placeholder={f.placeholder} onChange={(e) => set(f.key, e.target.value)} />
                  )}
                </Field>
              )
            })}
          </Card>
        ))}

        <Card title="Theme" icon="🎨" tone="violet" className="lg:col-span-2">
          <p className="text-sm text-ink/60">The blog and location pages use these colours and fonts, so they match the rest of the website.</p>
          <div className="grid gap-6 lg:grid-cols-[1fr_18rem]">
            <div className="space-y-4">
              <div className="grid gap-4 sm:grid-cols-2">
                {COLORS.map((c) => (
                  <ColorField
                    key={c.key}
                    label={c.label}
                    hint={c.hint}
                    fallback={c.fallback}
                    value={values[c.key] ?? ''}
                    error={errors[`settings.${c.key}`]}
                    onChange={(v) => set(c.key, v)}
                  />
                ))}
              </div>
              <div className="grid gap-4 sm:grid-cols-2">
                <Field label="Heading font" hint="Font name, e.g. Playfair Display" error={errors['settings.font_heading']}>
                  <Input value={values.font_heading ?? ''} placeholder="Same as the website" onChange={(e) => set('font_heading', e.target.value)} />
                </Field>
                <Field label="Text font" hint="Font name, e.g. Poppins" error={errors['settings.font_body']}>
                  <Input value={values.font_body ?? ''} placeholder="Same as the website" onChange={(e) => set('font_body', e.target.value)} />
                </Field>
              </div>
              <Switch
                checked={values.font_google === '1'}
                onChange={(v) => set('font_google', v ? '1' : '')}
                label="Load these fonts from Google Fonts (leave off if the website already has them)"
              />
            </div>

            {/* Roughly how a blog card looks with these choices. */}
            <div
              className="rounded-2xl border-2 border-dashed border-ink/30 p-4"
              style={{ background: values.theme_background || COLORS[3].fallback, color: values.theme_text || COLORS[2].fallback, fontFamily: values.font_body || undefined }}
            >
              <p className="mb-3 text-[11px] font-bold tracking-widest uppercase opacity-50">Preview</p>
              <span
                className="inline-block rounded-full px-3 py-1 text-[11px] font-bold tracking-wider uppercase"
                style={{ background: values.theme_accent || COLORS[1].fallback, color: values.theme_primary || COLORS[0].fallback }}
              >
                Category
              </span>
              <p
                className="mt-2 text-xl leading-tight font-bold"
                style={{ color: values.theme_primary || COLORS[0].fallback, fontFamily: values.font_heading || undefined }}
              >
                Ghostwriting in California
              </p>
              <p className="mt-1 text-sm">Expert writers for authors across the state.</p>
              <span
                className="mt-3 inline-block rounded-full px-4 py-2 text-sm font-bold text-white"
                style={{ background: values.theme_primary || COLORS[0].fallback }}
              >
                Get a free quote
              </span>
            </div>
          </div>
        </Card>

        {can_edit_code && (
          <Card title="Site code" icon="🧩" tone="orange" className="lg:col-span-2">
            <p className="text-sm text-ink/60">
              Added to every page of the website, so you never edit files for a new tag. 👑 Only admins see this, because code here can change
              anything on the site.
            </p>
            {CODE.map((c) => (
              <Field key={c.key} label={c.label} hint={c.hint} error={errors[`settings.${c.key}`]}>
                <Textarea
                  rows={5}
                  spellCheck={false}
                  className="font-mono text-xs"
                  placeholder="<script>…</script>"
                  value={values[c.key] ?? ''}
                  onChange={(e) => set(c.key, e.target.value)}
                />
              </Field>
            ))}
          </Card>
        )}

        <Card title="Custom settings" icon="🧪" tone="orange" className="lg:col-span-2">
          <p className="text-sm text-ink/60">
            Any extra value the website needs. Use it in PHP with <code>{"<?= setting('your_key') ?>"}</code>.
          </p>
          {custom.map((row) => (
            <div key={row.id} className="flex flex-wrap items-start gap-2 sm:flex-nowrap">
              <Input
                className="font-mono sm:w-56"
                placeholder="key_name"
                value={row.key}
                onChange={(e) => setCustom((rows) => rows.map((r) => (r.id === row.id ? { ...r, key: e.target.value.toLowerCase() } : r)))}
              />
              <Input
                placeholder="Value"
                value={row.value}
                onChange={(e) => setCustom((rows) => rows.map((r) => (r.id === row.id ? { ...r, value: e.target.value } : r)))}
              />
              <Button variant="ghost" title="Remove" onClick={() => setCustom((rows) => rows.filter((r) => r.id !== row.id))}>
                ✕
              </Button>
            </div>
          ))}
          <Button size="sm" variant="secondary" onClick={() => setCustom((rows) => [...rows, { id: uid(), key: '', value: '' }])}>
            ➕ Add setting
          </Button>
        </Card>
      </div>
    </form>
  )
}
