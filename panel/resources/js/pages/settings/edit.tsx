import { useState } from 'react'
import { Head, useForm } from '@inertiajs/react'
import type { Tone } from '@/lib/theme'
import { uid } from '@/lib/utils'
import type { Site } from '@/types'
import { BrandAvatar, Button, Card, Field, Input, PageHeader, Textarea } from '@/components/ui'
import { ImageInput } from '@/components/ImageInput'
import { SchemaInput } from '@/components/SchemaInput'

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

const KNOWN = new Set([...GROUPS.flatMap((g) => g.fields.map((f) => f.key)), 'schema'])

export default function SettingsPage({ site, settings }: { site: Site; settings: Settings }) {
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

        <Card title="Brand schema" icon="🧬" tone="violet" className="lg:col-span-2">
          <SchemaInput
            label="Site-wide schema (JSON-LD)"
            hint="Added to the <head> of every blog page on this website, e.g. Organization, LocalBusiness or WebSite."
            value={values.schema ?? ''}
            error={errors['settings.schema']}
            onChange={(v) => set('schema', v)}
          />
        </Card>

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
