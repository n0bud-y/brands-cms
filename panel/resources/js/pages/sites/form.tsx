import { useEffect, useState } from 'react'
import { Head, router, useForm, usePage } from '@inertiajs/react'
import { toast } from '@/lib/toast'
import { cx, firstError, slugify } from '@/lib/utils'
import type { Site } from '@/types'
import { BrandAvatar, Button, Card, Field, Input, PageHeader, Switch } from '@/components/ui'
import { ask } from '@/lib/confirm'

const KNOWN_LAYOUTS: { value: string; emoji: string; label: string; description: string }[] = [
  { value: 'classic', emoji: '🖼️', label: 'Classic', description: 'Cover on top, then content' },
  { value: 'hero', emoji: '🌅', label: 'Hero', description: 'Full-width cover, title over it' },
  { value: 'sidebar', emoji: '📑', label: 'Sidebar', description: 'Table of contents + contact box' },
  { value: 'minimal', emoji: '📄', label: 'Minimal', description: 'Just the words' },
]

type Connection = { panel_url: string; cache_secret: string; cache_clear_url: string }

/** Admin only. Create a brand (/sites/new) or edit one (/s/:site/brand). */
export default function SiteForm({ site, connection }: { site: Site | null; connection?: Connection }) {
  const form = useForm({
    name: site?.name ?? '',
    slug: site?.slug ?? '',
    url: site?.url ?? 'https://',
    cache_clear_url: site?.cache_clear_url ?? '',
    available_layouts: site?.available_layouts ?? ['classic'],
    is_active: site?.is_active ?? true,
  })
  const [slugTouched, setSlugTouched] = useState(Boolean(site))

  const known = KNOWN_LAYOUTS.map((l) => l.value)
  const custom = form.data.available_layouts.filter((l) => !known.includes(l))
  const toggleLayout = (value: string) =>
    form.setData(
      'available_layouts',
      form.data.available_layouts.includes(value)
        ? form.data.available_layouts.filter((l) => l !== value)
        : [...form.data.available_layouts, value],
    )

  return (
    <>
      <Head title={site ? `${site.name} · Brand` : 'New brand'} />
      <PageHeader
        title={site ? 'Brand' : 'New brand'}
        emoji={site ? '🏷️' : '✨'}
        eyebrow={
          <>
            <BrandAvatar name={form.data.name || 'New'} slug={form.data.slug || 'new'} size="sm" />
            <span className="font-bold text-ink/60">{form.data.name || 'Untitled brand'}</span>
          </>
        }
      />
      <div className="grid gap-8 lg:grid-cols-[1fr_24rem]">
        <form
          className="space-y-6"
          onSubmit={(e) => {
            e.preventDefault()
            if (site) form.put(`/s/${site.slug}/brand`, { preserveScroll: true })
            else form.post('/sites')
          }}
        >
          <Card title="Details" icon="📋" tone="violet">
            <div className="grid gap-4 sm:grid-cols-2">
              <Field label="Name" error={form.errors.name}>
                <Input
                  required
                  value={form.data.name}
                  placeholder="Brand A Publishing"
                  onChange={(e) => {
                    form.setData((d) => ({
                      ...d,
                      name: e.target.value,
                      slug: slugTouched ? d.slug : slugify(e.target.value).slice(0, 64),
                    }))
                  }}
                />
              </Field>
              <Field
                label="Slug"
                error={form.errors.slug}
                hint={site ? '⚠ Changing it breaks the website until its cms/config.php uses the new slug.' : 'Used in API URLs.'}
              >
                <Input
                  required
                  value={form.data.slug}
                  placeholder="brand-a"
                  onChange={(e) => {
                    setSlugTouched(true)
                    form.setData('slug', e.target.value)
                  }}
                />
              </Field>
            </div>
            <Field label="Website URL" error={form.errors.url} hint="Including https://, e.g. https://brand-a.com">
              <Input required type="url" value={form.data.url} onChange={(e) => form.setData('url', e.target.value)} />
            </Field>
            <Field
              label="Cache clear URL"
              error={form.errors.cache_clear_url}
              hint={`Optional. Defaults to ${(form.data.url || 'https://site').replace(/\/+$/, '')}/cms/clear-cache.php`}
            >
              <Input type="url" value={form.data.cache_clear_url} onChange={(e) => form.setData('cache_clear_url', e.target.value)} />
            </Field>
            <Switch
              checked={form.data.is_active}
              onChange={(v) => form.setData('is_active', v)}
              label={form.data.is_active ? 'Active: the website can load content' : 'Paused: the website gets no content'}
            />
          </Card>

          <Card title="Blog layouts" icon="🎨" tone="pink">
            <p className="text-sm text-ink/60">
              Pick the layouts this website has in <code>cms/layouts/</code>. Writers only see these.
            </p>
            <div className="grid gap-2.5 sm:grid-cols-2">
              {KNOWN_LAYOUTS.map((l) => {
                const on = form.data.available_layouts.includes(l.value)
                return (
                  <button
                    key={l.value}
                    type="button"
                    aria-pressed={on}
                    onClick={() => toggleLayout(l.value)}
                    className={cx(
                      'flex items-center gap-3 rounded-xl border-2 p-3 text-left transition',
                      on ? 'border-ink bg-pop-lime shadow-hard-sm' : 'border-ink/20 bg-white hover:border-ink',
                    )}
                  >
                    <span className="text-2xl">{l.emoji}</span>
                    <span className="min-w-0 flex-1">
                      <span className="block font-bold">{l.label}</span>
                      <span className="block text-xs text-ink/60">{l.description}</span>
                    </span>
                    <span
                      className={cx(
                        'grid size-6 place-items-center rounded-md border-2 border-ink text-xs font-extrabold',
                        on ? 'bg-ink text-pop-lime' : 'bg-white',
                      )}
                    >
                      {on ? '✓' : ''}
                    </span>
                  </button>
                )
              })}
            </div>
            <Field
              label="Custom layouts"
              hint="Comma-separated file names without .php, for layouts you built yourself."
              error={firstError(form.errors, 'available_layouts')}
            >
              <Input
                defaultValue={custom.join(', ')}
                placeholder="e.g. magazine, landing"
                onBlur={(e) => {
                  const extra = e.target.value.split(',').map((s) => s.trim()).filter(Boolean)
                  form.setData('available_layouts', [...form.data.available_layouts.filter((l) => known.includes(l)), ...extra])
                }}
              />
            </Field>
          </Card>

          <div className="flex justify-end">
            <Button type="submit" variant="pop" size="lg" disabled={form.processing}>
              {form.processing ? 'Saving…' : site ? '💾 Save brand' : '✨ Create brand'}
            </Button>
          </div>
        </form>

        {site && connection ? (
          <div className="space-y-6">
            <Integration site={site} connection={connection} />
            <DangerZone site={site} />
          </div>
        ) : (
          <aside className="h-fit rounded-2xl border-2 border-ink bg-pop-yellow p-5 shadow-hard">
            <p className="text-3xl">🔑</p>
            <p className="mt-2 font-extrabold">What happens next</p>
            <p className="mt-1 text-sm text-ink/75">
              After creating the brand you get its API key and a ready-to-paste config for the website. The key is shown once, so
              copy it right away.
            </p>
          </aside>
        )}
      </div>
    </>
  )
}

function Integration({ site, connection }: { site: Site; connection: Connection }) {
  const { flash } = usePage()
  // The API key only exists in the flash of the request that created/rotated it; keep it while this page is open.
  const [apiKey, setApiKey] = useState(flash.api_key)
  const [showSecret, setShowSecret] = useState(false)
  const [busy, setBusy] = useState(false)

  useEffect(() => {
    if (flash.api_key) setApiKey(flash.api_key)
  }, [flash.api_key])

  const post = (path: string) =>
    router.post(`/s/${site.slug}/${path}`, {}, { preserveScroll: true, onStart: () => setBusy(true), onFinish: () => setBusy(false) })

  // The copied text always has the real cache secret; on screen it stays hidden until asked for.
  const configWith = (secret: string) => `<?php
// cms/config.php on ${site.url}
return [
    'panel_url'    => '${connection.panel_url}',
    'site'         => '${site.slug}',
    'api_key'      => '${apiKey ?? 'PASTE_API_KEY_HERE'}',
    'cache_secret' => '${secret}',
    'site_url'     => '${site.url}',
];`
  const config = configWith(connection.cache_secret)
  const shownConfig = configWith(showSecret ? connection.cache_secret : '•'.repeat(24))

  return (
    <Card title="Connect the website" icon="🔌" tone="cyan">
      {apiKey && (
        <div className="rounded-xl border-2 border-ink bg-pop-yellow p-3 text-sm shadow-hard-sm">
          <p className="font-extrabold">🔑 Copy the API key now. It won't be shown again.</p>
          <code className="mt-2 block break-all bg-white/70">{apiKey}</code>
        </div>
      )}
      <p className="text-sm text-ink/60">
        Copy the <code>brand-kit</code> files into the website, then save this as <code>cms/config.php</code>:
      </p>
      <pre className="overflow-x-auto rounded-xl border-2 border-ink bg-ink p-4 font-mono text-[11px] leading-relaxed text-pop-lime">
        {shownConfig}
      </pre>
      <div className="flex flex-wrap gap-2">
        <Button size="sm" variant="primary" onClick={() => navigator.clipboard.writeText(config).then(() => toast('Copied 📋'))}>
          📋 Copy config
        </Button>
        <Button size="sm" variant="secondary" onClick={() => setShowSecret((s) => !s)}>
          {showSecret ? '🙈 Hide cache secret' : '👀 Show cache secret'}
        </Button>
        <Button size="sm" variant="pop" disabled={busy} onClick={() => post('test-connection')}>
          {busy ? 'Working…' : '⚡ Test connection'}
        </Button>
      </div>
      <div className="flex flex-wrap gap-2 border-t-2 border-dashed border-ink/15 pt-4">
        <Button
          size="sm"
          variant="ghost"
          disabled={busy}
          onClick={async () =>
            (await ask({ title: 'Issue a new API key?', message: 'The website stops loading content until its config has the new key.', yes: 'Yes, new key', emoji: '🔑' })) &&
            post('rotate-key')
          }
        >
          ↻ New API key
        </Button>
        <Button
          size="sm"
          variant="ghost"
          disabled={busy}
          onClick={async () =>
            (await ask({ title: 'Issue a new cache secret?', message: 'Instant updates stop until the website config has it.', yes: 'Yes, new secret', emoji: '🔑' })) &&
            post('rotate-secret')
          }
        >
          ↻ New cache secret
        </Button>
      </div>
    </Card>
  )
}

function DangerZone({ site }: { site: Site }) {
  return (
    <Card title="Danger zone" icon="💀" tone="red">
      <p className="text-sm text-ink/65">Deletes this brand with all its posts, settings and uploaded images. No undo.</p>
      <Button
        variant="danger"
        size="sm"
        onClick={() =>
          prompt(`Type "${site.slug}" to delete this brand permanently.`) === site.slug && router.delete(`/s/${site.slug}/brand`)
        }
      >
        🗑️ Delete brand
      </Button>
    </Card>
  )
}
