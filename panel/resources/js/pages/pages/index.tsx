import { useState } from 'react'
import { Head, router, useForm } from '@inertiajs/react'
import { cx, plural } from '@/lib/utils'
import type { Site, SitePage } from '@/types'
import { Badge, BrandAvatar, Button, Card, EmptyState, Field, Input, PageHeader, Switch, Textarea } from '@/components/ui'
import { ImageInput } from '@/components/ImageInput'

export default function Pages({ site, pages }: { site: Site; pages: SitePage[] }) {
  const [open, setOpen] = useState<number | null>(null)
  const [finding, setFinding] = useState(false)
  const add = useForm({ path: '' })
  const base = `/s/${site.slug}/pages`

  return (
    <>
      <Head title={`Pages · ${site.name}`} />
      <PageHeader
        title="Pages"
        emoji="📄"
        eyebrow={
          <>
            <BrandAvatar name={site.name} slug={site.slug} size="sm" />
            <span className="font-bold text-ink/60">{site.name}</span>
          </>
        }
      >
        <Button
          variant="pop"
          disabled={finding}
          onClick={() => router.post(`${base}/discover`, {}, { preserveScroll: true, onStart: () => setFinding(true), onFinish: () => setFinding(false) })}
        >
          {finding ? 'Looking…' : '🔍 Find pages'}
        </Button>
      </PageHeader>

      <p className="mb-6 max-w-2xl text-sm text-ink/65">
        The Google title and description of each page on your website. Leave a field empty and the page keeps its own text. <b>Find pages</b>{' '}
        asks the website for its pages, so a new page file shows up here by itself.
      </p>

      <form
        className="mb-6 flex max-w-xl gap-2"
        onSubmit={(e) => {
          e.preventDefault()
          add.post(base, { preserveScroll: true, onSuccess: () => add.reset() })
        }}
      >
        <Input value={add.data.path} placeholder="/blogs or /about" onChange={(e) => add.setData('path', e.target.value)} />
        <Button type="submit" variant="secondary" disabled={add.processing || !add.data.path.trim()}>
          ➕ Add page
        </Button>
      </form>
      {add.errors.path && <p className="-mt-4 mb-6 text-sm font-semibold text-pop-red">⚠ {add.errors.path}</p>}

      {pages.length === 0 ? (
        <EmptyState emoji="📄" title="No pages yet">
          Click <b>Find pages</b> to list your website&apos;s pages.
        </EmptyState>
      ) : (
        <div className="space-y-2">
          <p className="text-xs font-bold text-ink/50">{plural(pages.length, 'page')}</p>
          {pages.map((p) =>
            open === p.id ? (
              <PageForm key={p.id} site={site} page={p} onDone={() => setOpen(null)} />
            ) : (
              <button
                key={p.id}
                type="button"
                onClick={() => setOpen(p.id)}
                className="flex w-full items-center gap-3 rounded-xl border-2 border-ink bg-white px-4 py-3 text-left shadow-hard-sm transition hover:-translate-y-0.5"
              >
                <span className="min-w-0 flex-1">
                  <span className="block truncate font-extrabold">{p.path}</span>
                  <span className="block truncate text-sm text-ink/60">{p.meta_title || p.found_title || 'No title yet'}</span>
                </span>
                {p.noindex && <Badge tone="yellow">🙈 Hidden from Google</Badge>}
                {(p.meta_title || p.meta_description) && <Badge tone="pink">✍️ Edited</Badge>}
                <span className="text-sm">✏️</span>
              </button>
            ),
          )}
        </div>
      )}
    </>
  )
}

function PageForm({ site, page, onDone }: { site: Site; page: SitePage; onDone: () => void }) {
  const form = useForm({
    // The page's own text shows as real, editable text; saved only when changed.
    meta_title: page.meta_title || page.found_title || '',
    meta_description: page.meta_description || page.found_description || '',
    og_image: page.og_image ?? '',
    noindex: page.noindex,
  })
  const { data, errors } = form
  const url = `/s/${site.slug}/pages/${page.id}`
  const title = data.meta_title
  const description = data.meta_description

  return (
    <form
      onSubmit={(e) => {
        e.preventDefault()
        form.transform((d) => ({
          ...d,
          meta_title: d.meta_title === (page.found_title ?? '') ? '' : d.meta_title,
          meta_description: d.meta_description === (page.found_description ?? '') ? '' : d.meta_description,
        }))
        form.put(url, { preserveScroll: true, onSuccess: onDone })
      }}
    >
      <Card
        title={page.path}
        icon="✏️"
        tone="cyan"
        actions={
          <a href={`${site.url}${page.path}`} target="_blank" rel="noreferrer" className="text-sm font-bold underline">
            Open ↗
          </a>
        }
      >
        <div className="grid gap-6 lg:grid-cols-[1fr_20rem]">
          <div className="space-y-4">
            <Field label="Google title" error={errors.meta_title} hint={page.found_title ? "Starts as the page file's own title. Change it here, no file editing needed." : undefined}>
              <Input value={data.meta_title} onChange={(e) => form.setData('meta_title', e.target.value)} />
            </Field>
            <Field label="Google description" error={errors.meta_description}>
              <Textarea value={data.meta_description} onChange={(e) => form.setData('meta_description', e.target.value)} />
            </Field>
            <ImageInput label="Share image" hint="Shown when the page is shared on Facebook, WhatsApp… 1200×630 looks best." value={data.og_image} onChange={(v) => form.setData('og_image', v)} />
            <Switch checked={data.noindex} onChange={(v) => form.setData('noindex', v)} label="🙈 Hide from Google (e.g. a thank-you page). It also leaves the sitemap." />
          </div>
          <div className={cx('h-fit rounded-xl border-2 border-ink bg-paper p-3', data.noindex && 'opacity-40')}>
            <p className="mb-1.5 text-[11px] font-bold tracking-widest text-ink/45 uppercase">Google preview</p>
            <p className="truncate text-xs text-emerald-700">
              {site.url}
              {page.path === '/' ? '' : page.path}
            </p>
            <p className="truncate text-base font-semibold text-blue-700">{title || 'Page title'}</p>
            <p className="line-clamp-2 text-sm text-ink/65">{description || 'Description…'}</p>
          </div>
        </div>
        <div className="flex flex-wrap gap-2">
          <Button type="submit" variant="pop" disabled={form.processing}>
            {form.processing ? 'Saving…' : '💾 Save'}
          </Button>
          <Button variant="ghost" onClick={onDone}>
            Cancel
          </Button>
          <Button
            variant="ghost"
            className="ml-auto"
            onClick={() => confirm(`Remove ${page.path} from this list? The page on the website stays.`) && router.delete(url, { preserveScroll: true })}
          >
            🗑️ Remove from list
          </Button>
        </div>
      </Card>
    </form>
  )
}
