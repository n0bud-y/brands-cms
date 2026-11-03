import { useState } from 'react'
import { Head, Link, router, useForm } from '@inertiajs/react'
import { slugify, uid } from '@/lib/utils'
import { useEditorGuard } from '@/lib/useEditorGuard'
import type { Block, Faq, Feature, Service, Site } from '@/types'
import { Badge, Button, buttonClass, Card, Field, Input, Switch, Textarea } from '@/components/ui'
import { BlockEditor } from '@/components/BlockEditor'
import { FaqList, PlaceholderHint } from '@/components/FaqList'
import { emptyFeature, FeatureInput } from '@/components/FeatureInput'
import { ImageInput } from '@/components/ImageInput'
import { SlugInput } from '@/components/SlugInput'
import { ask } from '@/lib/confirm'

type Form = {
  name: string
  slug: string
  icon: string
  summary: string
  title: string
  blocks: Block[]
  feature: Feature
  faqs: Faq[]
  meta_title: string
  meta_description: string
  is_active: boolean
}

type Props = {
  site: Site
  service: Service | null
  /** A real place of this brand, for previews. */
  example: { location: string; state: string; prefix: string }
  fallbacks: { title: string; meta_title: string }
}

const toForm = (s: Service | null): Form => ({
  name: s?.name ?? '',
  slug: s?.slug ?? '',
  icon: s?.icon ?? '',
  summary: s?.summary ?? '',
  title: s?.title ?? '',
  blocks: (s?.blocks ?? []).map((b) => ({ ...b, id: b.id || uid() })),
  feature: s?.feature ?? emptyFeature,
  faqs: s?.faqs ?? [],
  meta_title: s?.meta_title ?? '',
  meta_description: s?.meta_description ?? '',
  is_active: s?.is_active ?? true,
})

export default function ServiceEditorPage(props: Props) {
  return <Editor key={props.service?.id ?? 'new'} {...props} />
}

function Editor({ site, service, example, fallbacks }: Props) {
  const form = useForm<Form>(toForm(service))
  const { data, errors } = form
  const [slugTouched, setSlugTouched] = useState(Boolean(service))
  const set = <K extends keyof Form>(key: K, value: Form[K]) => form.setData((d) => ({ ...d, [key]: value }))

  const submit = () => {
    if (form.processing) return
    // The built-in titles show as real text; saved empty so they keep following the default.
    form.transform((d) => ({
      ...d,
      title: d.title === fallbacks.title ? '' : d.title,
      meta_title: d.meta_title === fallbacks.meta_title ? '' : d.meta_title,
    }))
    if (!service) return form.post(`/s/${site.slug}/services`)
    form.put(`/s/${site.slug}/services/${service.id}`, {
      preserveScroll: true,
      onSuccess: (page) => {
        const next = toForm((page.props as unknown as { service: Service }).service)
        form.setDefaults(next)
        form.setData(next)
      },
    })
  }
  useEditorGuard(submit, form.isDirty)

  // How the texts read in one real place.
  const fill = (text: string) =>
    text
      .replaceAll('{location}', example.location)
      .replaceAll('{state}', example.state)
      .replaceAll('{city}', example.location)
      .replaceAll('{service}', data.name || 'Service')
      .replaceAll('{brand}', site.name)
  const url = `${site.url}/${example.prefix}-${data.slug || 'service'}`

  return (
    <form
      onSubmit={(e) => {
        e.preventDefault()
        submit()
      }}
    >
      <Head title={service ? service.name : 'New service'} />

      <div className="sticky top-[58px] z-10 -mx-4 mb-6 flex flex-wrap items-center justify-between gap-3 border-b-2 border-ink/10 bg-paper/85 px-4 py-3 backdrop-blur sm:-mx-6 sm:px-6 lg:top-0 lg:-mx-10 lg:px-10">
        <div className="flex min-w-0 items-center gap-3">
          <Link href={`/s/${site.slug}/services`} className={buttonClass({ variant: 'secondary', size: 'sm' })}>
            ← Services
          </Link>
          {!service && <Badge tone="pink">✨ New</Badge>}
          {form.isDirty && (
            <span className="flex items-center gap-1.5 text-xs font-bold text-pop-orange">
              <span className="size-2 animate-pulse rounded-full bg-pop-orange" /> Unsaved
            </span>
          )}
        </div>
        <Button type="submit" variant="pop" disabled={form.processing || (!form.isDirty && Boolean(service))} title="Ctrl+S">
          {form.processing ? 'Saving…' : service ? '💾 Save' : '➕ Add service'}
        </Button>
      </div>

      <div className="grid gap-8 lg:grid-cols-[1fr_21rem]">
        <div className="min-w-0 space-y-8">
          <div>
            <input
              required
              placeholder="Service name, e.g. Ghostwriting"
              value={data.name}
              onChange={(e) => {
                const name = e.target.value
                form.setData((d) => ({ ...d, name, slug: slugTouched ? d.slug : slugify(name) }))
              }}
              className="w-full bg-transparent text-4xl leading-tight font-extrabold tracking-tight outline-none placeholder:text-ink/25"
            />
            {errors.name && <p className="text-sm font-semibold text-pop-red">⚠ {errors.name}</p>}
            <div className="mt-3">
              <SlugInput
                prefix={`/${example.prefix}-`}
                placeholder="ghostwriting"
                value={data.slug}
                onChange={(v) => set('slug', v)}
                onTouch={() => setSlugTouched(true)}
              />
            </div>
            {errors.slug ? (
              <p className="mt-1.5 text-sm font-semibold text-pop-red">⚠ {errors.slug}</p>
            ) : (
              <p className="mt-1.5 text-xs font-semibold text-ink/55">
                Every state and city gets this page: /state-{data.slug || 'slug'} and /state/city-{data.slug || 'slug'}.
              </p>
            )}
          </div>

          <Card title="Box on location pages" icon="🧩" tone="yellow">
            <PlaceholderHint />
            <ImageInput label="Icon or image" hint="Shown in the service box. A square image or icon works best." value={data.icon} onChange={(v) => set('icon', v)} />
            <Field label="Short description" error={errors.summary} hint={data.summary && `In ${example.location}: “${fill(data.summary)}”`}>
              <Textarea value={data.summary} placeholder="Professional ghostwriters for authors in {location}." onChange={(e) => set('summary', e.target.value)} />
            </Field>
          </Card>

          <Card title="Service page" icon="📄" tone="cyan">
            <Field label="Page title" error={errors.title} hint={`In ${example.location}: “${fill(data.title || fallbacks.title)}”`}>
              <Input value={data.title || fallbacks.title} onChange={(e) => set('title', e.target.value)} />
            </Field>
          </Card>

          <section>
            <div className="mb-2 flex items-center gap-2">
              <h2 className="text-xl font-extrabold tracking-tight">🧱 Page content</h2>
              <Badge tone="violet">{data.blocks.length} blocks</Badge>
            </div>
            <p className="mb-4 text-sm text-ink/60">
              Used on this service&apos;s page in every location. Write {'{location}'} where the place name goes. One page can get its own
              text from its location.
            </p>
            <BlockEditor blocks={data.blocks} onChange={(blocks) => set('blocks', blocks)} />
          </section>

          <Card title="Text + image section" icon="🖼️" tone="cyan">
            <p className="text-sm text-ink/60">Shown above the FAQs on this service's page in every location: text on the left, image on the right.</p>
            <PlaceholderHint />
            <FeatureInput value={data.feature} onChange={(v) => set('feature', v)} />
          </Card>

          <Card title="FAQs" icon="❓" tone="pink">
            <FaqList items={data.faqs} onChange={(faqs) => set('faqs', faqs)} />
          </Card>
        </div>

        <aside className="space-y-6">
          <Card title="Status" icon="🚦" tone="lime">
            <Switch checked={data.is_active} onChange={(v) => set('is_active', v)} label="Live on every location page" />
            {!data.is_active && <p className="text-sm text-ink/60">Switched off: no pages or boxes for this service anywhere.</p>}
          </Card>

          <Card title="SEO" icon="🔍" tone="cyan">
            <PlaceholderHint />
            <Field label="Meta title" error={errors.meta_title}>
              <Input value={data.meta_title || fallbacks.meta_title} onChange={(e) => set('meta_title', e.target.value)} />
            </Field>
            <Field label="Meta description" error={errors.meta_description}>
              <Textarea value={data.meta_description} placeholder="Defaults to the short description" onChange={(e) => set('meta_description', e.target.value)} />
            </Field>
            <div className="rounded-xl border-2 border-ink bg-paper p-3">
              <p className="mb-1.5 text-[11px] font-bold tracking-widest text-ink/45 uppercase">Google preview · {example.location}</p>
              <p className="truncate text-xs text-emerald-700">{url}</p>
              <p className="truncate text-base font-semibold text-blue-700">{fill(data.meta_title || fallbacks.meta_title)}</p>
              <p className="line-clamp-2 text-sm text-ink/65">{fill(data.meta_description || data.summary) || 'Description…'}</p>
            </div>
          </Card>

          {service && (
            <Button
              variant="secondary"
              className="w-full"
              onClick={async () =>
                (await ask({ title: `Duplicate ${service.name}?`, message: 'A switched-off copy with the same text, FAQs and section, ready to reword.', yes: 'Yes, duplicate', emoji: '📋' })) &&
                router.post(`/s/${site.slug}/services/${service.id}/duplicate`)
              }
            >
              📋 Duplicate service
            </Button>
          )}

          {service && (
            <Button
              variant="danger"
              className="w-full"
              onClick={async () =>
                (await ask({ title: `Delete ${service.name}?`, message: 'Its page disappears from every state and city.', yes: 'Yes, delete', danger: true })) &&
                router.delete(`/s/${site.slug}/services/${service.id}`)
              }
            >
              🗑️ Delete service
            </Button>
          )}
        </aside>
      </div>
    </form>
  )
}
