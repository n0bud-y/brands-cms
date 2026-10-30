import { Head, Link, router, useForm } from '@inertiajs/react'
import { uid } from '@/lib/utils'
import { useEditorGuard } from '@/lib/useEditorGuard'
import type { Block, Faq, Service, Site } from '@/types'
import { Badge, Button, buttonClass, Card, Field, Input, Switch, Textarea } from '@/components/ui'
import { BlockEditor } from '@/components/BlockEditor'
import { FaqList, PlaceholderHint } from '@/components/FaqList'
import { SlugInput } from '@/components/SlugInput'

type Page = {
  slug: string | null
  is_hidden: boolean
  title: string | null
  blocks: Block[] | null
  faqs: Faq[] | null
  meta_title: string | null
  meta_description: string | null
}

type Form = { slug: string; is_hidden: boolean; title: string; blocks: Block[] | null; faqs: Faq[] | null; meta_title: string; meta_description: string }

type Props = {
  site: Site
  location: { id: number; name: string; slug: string; parent_id: number | null }
  service: Service
  page: Page | null
  path: string | null
  prefix: string
  default_slug: string
  vars: { location: string; state: string; service: string; brand: string }
  fallbacks: { title: string; meta_title: string }
}

const toForm = (p: Page | null): Form => ({
  slug: p?.slug ?? '',
  is_hidden: p?.is_hidden ?? false,
  title: p?.title ?? '',
  blocks: p?.blocks ? p.blocks.map((b) => ({ ...b, id: b.id || uid() })) : null,
  faqs: p?.faqs ?? null,
  meta_title: p?.meta_title ?? '',
  meta_description: p?.meta_description ?? '',
})

const escapeHtml = (s: string) => s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;')

export default function LocationServicePage(props: Props) {
  const { site, location, service, page, path, prefix, default_slug, vars, fallbacks } = props
  const form = useForm<Form>(toForm(page))
  const { data, errors } = form
  const set = <K extends keyof Form>(key: K, value: Form[K]) => form.setData((d) => ({ ...d, [key]: value }))
  const url = `/s/${site.slug}/locations/${location.id}/services/${service.id}`

  const submit = () => {
    if (form.processing) return
    form.put(url, {
      preserveScroll: true,
      onSuccess: (next) => {
        const fresh = toForm((next.props as unknown as Props).page)
        form.setDefaults(fresh)
        form.setData(fresh)
      },
    })
  }
  useEditorGuard(submit, form.isDirty)

  // The template text as it reads here; names are escaped inside rich text, like on the website.
  const tokens: Record<string, string> = {
    '{location}': vars.location,
    '{state}': vars.state,
    '{city}': location.parent_id ? vars.location : '',
    '{service}': vars.service,
    '{brand}': vars.brand,
  }
  const fill = (text: string, html = false) => text.replace(/\{(location|state|city|service|brand)\}/g, (t) => (html ? escapeHtml(tokens[t]) : tokens[t]))
  const fillDeep = <T,>(value: T, key = ''): T => {
    if (typeof value === 'string') return fill(value, ['html', 'left', 'right'].includes(key)) as T
    if (Array.isArray(value)) return value.map((v) => fillDeep(v, key)) as T
    if (value && typeof value === 'object') return Object.fromEntries(Object.entries(value).map(([k, v]) => [k, fillDeep(v, k)])) as T
    return value
  }

  const liveUrl = path ? `${site.url}/${path}` : null

  return (
    <form
      onSubmit={(e) => {
        e.preventDefault()
        submit()
      }}
    >
      <Head title={`${service.name} in ${location.name}`} />

      <div className="sticky top-[58px] z-10 -mx-4 mb-6 flex flex-wrap items-center justify-between gap-3 border-b-2 border-ink/10 bg-paper/85 px-4 py-3 backdrop-blur sm:-mx-6 sm:px-6 lg:top-0 lg:-mx-10 lg:px-10">
        <div className="flex min-w-0 items-center gap-3">
          <Link href={`/s/${site.slug}/locations/${location.id}`} className={buttonClass({ variant: 'secondary', size: 'sm' })}>
            ← {location.name}
          </Link>
          {page ? <Badge tone="pink">✍️ Custom</Badge> : <Badge>📋 Service template</Badge>}
          {form.isDirty && (
            <span className="flex items-center gap-1.5 text-xs font-bold text-pop-orange">
              <span className="size-2 animate-pulse rounded-full bg-pop-orange" /> Unsaved
            </span>
          )}
        </div>
        <div className="flex items-center gap-2">
          {liveUrl && (
            <a href={liveUrl} target="_blank" rel="noreferrer" className={buttonClass({ variant: 'secondary' })}>
              View live ↗
            </a>
          )}
          <Button type="submit" variant="pop" disabled={form.processing || !form.isDirty} title="Ctrl+S">
            {form.processing ? 'Saving…' : '💾 Save'}
          </Button>
        </div>
      </div>

      <div className="grid gap-8 lg:grid-cols-[1fr_21rem]">
        <div className="min-w-0 space-y-8">
          <div>
            <p className="text-sm font-bold text-ink/50">
              🧰{' '}
              <Link href={`/s/${site.slug}/services/${service.id}`} className="underline">
                {service.name}
              </Link>{' '}
              in {location.name}
            </p>
            <h1 className="text-4xl leading-tight font-extrabold tracking-tight">{fill(data.title || service.title || fallbacks.title)}</h1>
            <div className="mt-3">
              <SlugInput prefix={prefix} placeholder={default_slug} value={data.slug} onChange={(v) => set('slug', v)} />
            </div>
            {errors.slug ? (
              <p className="mt-1.5 text-sm font-semibold text-pop-red">⚠ {errors.slug}</p>
            ) : (
              <p className="mt-1.5 text-xs font-semibold text-ink/55">Empty = {prefix + default_slug}. A changed URL redirects (301) from the old one.</p>
            )}
          </div>

          <Card title="Title" icon="🔠" tone="yellow">
            <PlaceholderHint />
            <Field label="Page title" error={errors.title} hint="Empty = the service's title.">
              <Input value={data.title} placeholder={fill(service.title || fallbacks.title)} onChange={(e) => set('title', e.target.value)} />
            </Field>
          </Card>

          <section className="space-y-4">
            <div className="flex flex-wrap items-center justify-between gap-2">
              <h2 className="text-xl font-extrabold tracking-tight">🧱 Page content</h2>
              <Switch
                checked={data.blocks !== null}
                onChange={(own) => set('blocks', own ? fillDeep(service.blocks ?? []).map((b) => ({ ...b, id: uid() })) : null)}
                label="Own content for this page"
              />
            </div>
            {data.blocks === null ? (
              <p className="rounded-xl border-2 border-dashed border-ink/30 bg-white/70 p-4 text-sm text-ink/60">
                Uses the {service.blocks?.length ?? 0} blocks of the{' '}
                <Link href={`/s/${site.slug}/services/${service.id}`} className="font-bold underline">
                  {service.name}
                </Link>{' '}
                template, with {location.name} filled in. Switch on to write this page yourself (it starts from the template).
              </p>
            ) : (
              <BlockEditor blocks={data.blocks} onChange={(blocks) => set('blocks', blocks)} />
            )}
          </section>

          <Card title="FAQs" icon="❓" tone="pink">
            <Switch
              checked={data.faqs !== null}
              onChange={(own) => set('faqs', own ? fillDeep(service.faqs ?? []) : null)}
              label="Own FAQs for this page"
            />
            {data.faqs === null ? (
              <p className="text-sm text-ink/60">Uses the service&apos;s {service.faqs?.length ?? 0} FAQs.</p>
            ) : (
              <FaqList items={data.faqs} onChange={(faqs) => set('faqs', faqs)} />
            )}
          </Card>
        </div>

        <aside className="space-y-6">
          <Card title="Status" icon="🚦" tone="lime">
            <Switch checked={!data.is_hidden} onChange={(show) => set('is_hidden', !show)} label={`Show ${service.name} in ${location.name}`} />
            {data.is_hidden && <p className="text-sm text-ink/60">Hidden: no page and no box for this service here.</p>}
          </Card>

          <Card title="SEO" icon="🔍" tone="cyan">
            <Field label="Meta title" error={errors.meta_title}>
              <Input
                value={data.meta_title}
                placeholder={fill(service.meta_title || fallbacks.meta_title)}
                onChange={(e) => set('meta_title', e.target.value)}
              />
            </Field>
            <Field label="Meta description" error={errors.meta_description}>
              <Textarea
                value={data.meta_description}
                placeholder={fill(service.meta_description || service.summary || '')}
                onChange={(e) => set('meta_description', e.target.value)}
              />
            </Field>
          </Card>

          {page && (
            <Button
              variant="secondary"
              className="w-full"
              onClick={() => confirm('Drop the changes on this page and use the service template again?') && router.delete(url)}
            >
              ↩️ Back to the template
            </Button>
          )}
        </aside>
      </div>
    </form>
  )
}
