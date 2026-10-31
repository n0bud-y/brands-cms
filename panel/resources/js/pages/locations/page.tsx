import { useMemo } from 'react'
import { Head, Link, router, useForm } from '@inertiajs/react'
import { uid } from '@/lib/utils'
import { useEditorGuard } from '@/lib/useEditorGuard'
import type { Block, Faq, Feature, Service, Site } from '@/types'
import { Badge, Button, buttonClass, Card, Field, Input, Switch, Textarea } from '@/components/ui'
import { BlockEditor } from '@/components/BlockEditor'
import { FaqList } from '@/components/FaqList'
import { emptyFeature, FeatureInput } from '@/components/FeatureInput'
import { SlugInput } from '@/components/SlugInput'

type Page = {
  slug: string | null
  is_hidden: boolean
  title: string | null
  blocks: Block[] | null
  feature: Feature | null
  faqs: Faq[] | null
  meta_title: string | null
  meta_description: string | null
}

/** null in blocks/feature/faqs = still the service template. */
type Form = {
  slug: string
  is_hidden: boolean
  title: string
  blocks: Block[] | null
  feature: Feature | null
  faqs: Faq[] | null
  meta_title: string
  meta_description: string
}

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
  feature: p?.feature ?? null,
  faqs: p?.faqs ?? null,
  meta_title: p?.meta_title ?? '',
  meta_description: p?.meta_description ?? '',
})

const escapeHtml = (s: string) => s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;')
/** Compare content without the editor's random block ids. */
const same = (a: unknown, b: unknown) => JSON.stringify(a, (k, v) => (k === 'id' ? undefined : v)) === JSON.stringify(b, (k, v) => (k === 'id' ? undefined : v))

export default function LocationServicePage(props: Props) {
  const { site, location, service, page, path, prefix, default_slug, vars, fallbacks } = props
  const form = useForm<Form>(toForm(page))
  const { data, errors } = form
  const set = <K extends keyof Form>(key: K, value: Form[K]) => form.setData((d) => ({ ...d, [key]: value }))
  const url = `/s/${site.slug}/locations/${location.id}/services/${service.id}`

  // The service template as it reads here; names are escaped inside rich text, like on the website.
  const template = useMemo(() => {
    const tokens: Record<string, string> = {
      '{location}': vars.location,
      '{state}': vars.state,
      '{city}': location.parent_id ? vars.location : '',
      '{service}': vars.service,
      '{brand}': vars.brand,
    }
    const fill = (text: string, html = false) =>
      text.replace(/\{(location|state|city|service|brand)\}/g, (t) => (html ? escapeHtml(tokens[t]) : tokens[t]))
    const deep = <T,>(value: T, key = ''): T => {
      if (typeof value === 'string') return fill(value, ['html', 'left', 'right'].includes(key)) as T
      if (Array.isArray(value)) return value.map((v) => deep(v, key)) as T
      if (value && typeof value === 'object') return Object.fromEntries(Object.entries(value).map(([k, v]) => [k, deep(v, k)])) as T
      return value
    }
    return {
      title: fill(service.title || fallbacks.title),
      meta_title: fill(service.meta_title || fallbacks.meta_title),
      meta_description: fill(service.meta_description || service.summary || ''),
      blocks: deep(service.blocks ?? []).map((b) => ({ ...b, id: uid() })),
      feature: service.feature ? deep(service.feature) : emptyFeature,
      faqs: deep(service.faqs ?? []),
    }
  }, [service, vars, location.parent_id, fallbacks])

  const submit = () => {
    if (form.processing) return
    // Whatever still matches the template is not saved, so it keeps following the service.
    form.transform((d) => ({
      ...d,
      title: d.title === template.title ? '' : d.title,
      meta_title: d.meta_title === template.meta_title ? '' : d.meta_title,
      meta_description: d.meta_description === template.meta_description ? '' : d.meta_description,
      blocks: d.blocks && !same(d.blocks, template.blocks) ? d.blocks : null,
      feature: d.feature && !same(d.feature, template.feature) ? d.feature : null,
      faqs: d.faqs && !same(d.faqs, template.faqs) ? d.faqs : null,
    }))
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

  const liveUrl = path ? `${site.url}/${path}` : null
  const reset = (key: 'blocks' | 'feature' | 'faqs') =>
    data[key] !== null && (
      <Button size="sm" variant="ghost" onClick={() => set(key, null)}>
        ↺ Back to the template
      </Button>
    )
  const fromTemplate = (what: string) => (
    <p className="text-xs text-ink/55">
      ✍️ This is the {what} from the{' '}
      <Link href={`/s/${site.slug}/services/${service.id}`} className="font-bold underline">
        {service.name}
      </Link>{' '}
      service, filled in for {location.name}. Edit it to give this page its own.
    </p>
  )

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
          {page ? <Badge tone="pink">✍️ Edited</Badge> : <Badge>📋 Service template</Badge>}
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
            <textarea
              rows={1}
              aria-label="Page title"
              value={data.title || template.title}
              onChange={(e) => set('title', e.target.value.replace(/\n/g, ' '))}
              className="w-full resize-none bg-transparent text-4xl leading-tight font-extrabold tracking-tight outline-none [field-sizing:content]"
            />
            {errors.title && <p className="text-sm font-semibold text-pop-red">⚠ {errors.title}</p>}
            <div className="mt-3">
              <SlugInput prefix={prefix} placeholder={default_slug} value={data.slug} onChange={(v) => set('slug', v)} />
            </div>
            {errors.slug ? (
              <p className="mt-1.5 text-sm font-semibold text-pop-red">⚠ {errors.slug}</p>
            ) : (
              <p className="mt-1.5 text-xs font-semibold text-ink/55">Empty = {prefix + default_slug}. A changed address redirects (301) from the old one.</p>
            )}
          </div>

          <section className="space-y-3">
            <div className="flex flex-wrap items-center justify-between gap-2">
              <h2 className="text-xl font-extrabold tracking-tight">🧱 Page content</h2>
              {reset('blocks')}
            </div>
            {data.blocks === null && fromTemplate('text')}
            <BlockEditor blocks={data.blocks ?? template.blocks} onChange={(blocks) => set('blocks', blocks)} />
          </section>

          <Card title="Text + image section" icon="🖼️" tone="cyan" actions={reset('feature')}>
            {data.feature === null && fromTemplate('section')}
            <FeatureInput value={data.feature ?? template.feature} onChange={(v) => set('feature', v)} />
          </Card>

          <Card title="FAQs" icon="❓" tone="pink" actions={reset('faqs')}>
            {data.faqs === null && fromTemplate('FAQs')}
            <FaqList items={data.faqs ?? template.faqs} onChange={(faqs) => set('faqs', faqs)} />
          </Card>
        </div>

        <aside className="space-y-6">
          <Card title="Status" icon="🚦" tone="lime">
            <Switch checked={!data.is_hidden} onChange={(show) => set('is_hidden', !show)} label={`Show ${service.name} in ${location.name}`} />
            {data.is_hidden && <p className="text-sm text-ink/60">Hidden: no page and no box for this service here.</p>}
          </Card>

          <Card title="SEO" icon="🔍" tone="cyan">
            <Field label="Google title" error={errors.meta_title}>
              <Input value={data.meta_title || template.meta_title} onChange={(e) => set('meta_title', e.target.value)} />
            </Field>
            <Field label="Google description" error={errors.meta_description}>
              <Textarea value={data.meta_description || template.meta_description} onChange={(e) => set('meta_description', e.target.value)} />
            </Field>
          </Card>

          {page && (
            <Button
              variant="secondary"
              className="w-full"
              onClick={() => confirm('Drop every change on this page and use the service template again?') && router.delete(url)}
            >
              ↩️ Back to the template
            </Button>
          )}
        </aside>
      </div>
    </form>
  )
}
