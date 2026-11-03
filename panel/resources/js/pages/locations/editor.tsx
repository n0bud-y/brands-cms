import { useState } from 'react'
import { Head, Link, router, useForm } from '@inertiajs/react'
import { slugify, uid } from '@/lib/utils'
import { useEditorGuard } from '@/lib/useEditorGuard'
import type { Block, Faq, Location, LocationDefaults, Site } from '@/types'
import { Badge, Button, buttonClass, Card, Field, Input, Select, Switch, Textarea } from '@/components/ui'
import { BlockEditor } from '@/components/BlockEditor'
import { FaqList } from '@/components/FaqList'
import { ImageInput } from '@/components/ImageInput'
import { SlugInput } from '@/components/SlugInput'
import { ask } from '@/lib/confirm'

type Form = {
  parent_id: number | null
  name: string
  slug: string
  heading: string
  subheading: string
  banner_image: string
  image: string
  summary: string
  blocks: Block[]
  faqs: Faq[] | null
  meta_title: string
  meta_description: string
  is_active: boolean
}

type ServicePage = { service_id: number; name: string; service_active: boolean; path: string | null; hidden: boolean; custom: boolean }

type Props = {
  site: Site
  location: Location | null
  parent_id: number | null
  states: { id: number; name: string; slug: string }[]
  defaults: LocationDefaults
  pages?: ServicePage[]
}

const toForm = (l: Location | null, parentId: number | null): Form => ({
  parent_id: parentId,
  name: l?.name ?? '',
  slug: l?.slug ?? '',
  heading: l?.heading ?? '',
  subheading: l?.subheading ?? '',
  banner_image: l?.banner_image ?? '',
  image: l?.image ?? '',
  summary: l?.summary ?? '',
  blocks: (l?.blocks ?? []).map((b) => ({ ...b, id: b.id || uid() })),
  faqs: l?.faqs ?? null,
  meta_title: l?.meta_title ?? '',
  meta_description: l?.meta_description ?? '',
  is_active: l?.is_active ?? true,
})

export default function LocationEditorPage(props: Props) {
  return <Editor key={props.location?.id ?? `new-${props.parent_id}`} {...props} />
}

function Editor({ site, location, parent_id, states, defaults, pages = [] }: Props) {
  const form = useForm<Form>(toForm(location, parent_id))
  const { data, errors } = form
  const [slugTouched, setSlugTouched] = useState(Boolean(location))
  const set = <K extends keyof Form>(key: K, value: Form[K]) => form.setData((d) => ({ ...d, [key]: value }))
  const base = `/s/${site.slug}/locations`

  const state = states.find((s) => s.id === data.parent_id)
  const isCity = Boolean(state)
  const place = data.name || (isCity ? 'City' : 'State')
  const fill = (text: string) =>
    text
      .replaceAll('{location}', place)
      .replaceAll('{state}', state?.name ?? place)
      .replaceAll('{city}', isCity ? place : '')
      .replaceAll('{service}', '')
      .replaceAll('{brand}', site.name)
  const liveUrl = location?.path ? `${site.url}/${location.path}` : null

  // Texts from Page defaults show as the real, editable text for this place. Saved only when changed,
  // so a page that keeps the default text follows later changes to the defaults.
  const inherited = {
    heading: fill(defaults.heading),
    subheading: fill(defaults.subheading),
    meta_title: fill(defaults.meta_title),
    // Like the website: the default description, else this page's banner text.
    meta_description: defaults.meta_description ? fill(defaults.meta_description) : data.subheading || fill(defaults.subheading),
  }
  type TextKey = keyof typeof inherited
  const shown = (key: TextKey) => data[key] || inherited[key]
  const defaultFaqs = defaults.faqs.map((f) => ({ q: fill(f.q), a: fill(f.a) }))

  const submit = () => {
    if (form.processing) return
    form.transform((d) => {
      const own = (key: TextKey) => (d[key] === inherited[key] ? '' : d[key])
      return {
        ...d,
        heading: own('heading'),
        subheading: own('subheading'),
        meta_title: own('meta_title'),
        meta_description: own('meta_description'),
        faqs: d.faqs && JSON.stringify(d.faqs) !== JSON.stringify(defaultFaqs) ? d.faqs : null,
      }
    })
    if (!location) return form.post(base)
    form.put(`${base}/${location.id}`, {
      preserveScroll: true,
      onSuccess: (page) => {
        const next = toForm((page.props as unknown as Props).location, (page.props as unknown as Props).parent_id)
        form.setDefaults(next)
        form.setData(next)
      },
    })
  }
  useEditorGuard(submit, form.isDirty)

  return (
    <form
      onSubmit={(e) => {
        e.preventDefault()
        submit()
      }}
    >
      <Head title={location ? location.name : isCity ? 'New city' : 'New state'} />

      <div className="sticky top-[58px] z-10 -mx-4 mb-6 flex flex-wrap items-center justify-between gap-3 border-b-2 border-ink/10 bg-paper/85 px-4 py-3 backdrop-blur sm:-mx-6 sm:px-6 lg:top-0 lg:-mx-10 lg:px-10">
        <div className="flex min-w-0 items-center gap-3">
          <Link href={base} className={buttonClass({ variant: 'secondary', size: 'sm' })}>
            ← Locations
          </Link>
          <Badge tone={isCity ? 'cyan' : 'violet'}>{isCity ? `🏙️ City in ${state?.name}` : '🗺️ State'}</Badge>
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
          <Button type="submit" variant="pop" disabled={form.processing || (!form.isDirty && Boolean(location))} title="Ctrl+S">
            {form.processing ? 'Saving…' : location ? '💾 Save' : isCity ? '➕ Add city' : '➕ Add state'}
          </Button>
        </div>
      </div>

      <div className="grid gap-8 lg:grid-cols-[1fr_21rem]">
        <div className="min-w-0 space-y-8">
          <div>
            <input
              required
              placeholder={isCity ? 'City name, e.g. Los Angeles' : 'State name, e.g. California'}
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
                prefix={state ? `/${state.slug}/` : '/'}
                placeholder={isCity ? 'los-angeles' : 'california'}
                value={data.slug}
                onChange={(v) => set('slug', v)}
                onTouch={() => setSlugTouched(true)}
              />
            </div>
            {errors.slug ? (
              <p className="mt-1.5 text-sm font-semibold text-pop-red">⚠ {errors.slug}</p>
            ) : (
              location &&
              data.slug !== location.slug && <p className="mt-1.5 text-xs font-semibold text-ink/60">↪️ The old URLs will redirect (301) to the new ones.</p>
            )}
          </div>

          <Card title="Banner" icon="🌅" tone="yellow">
            <p className="text-xs text-ink/55">✍️ This is the text on the page now. Change anything; leave it as is and it follows Page defaults.</p>
            <Field label="Heading" error={errors.heading}>
              <Input value={shown('heading')} onChange={(e) => set('heading', e.target.value)} />
            </Field>
            <Field label="Text under the heading" error={errors.subheading}>
              <Textarea value={shown('subheading')} onChange={(e) => set('subheading', e.target.value)} />
            </Field>
            <ImageInput
              label="Background image"
              hint={defaults.banner_image ? 'Empty = the default banner from Page defaults.' : 'Optional. A wide photo (1920×800) looks best.'}
              value={data.banner_image}
              onChange={(v) => set('banner_image', v)}
            />
            <p className="text-xs text-ink/55">The website&apos;s own contact form shows next to the banner text.</p>
          </Card>

          <section>
            <div className="mb-2 flex items-center gap-2">
              <h2 className="text-xl font-extrabold tracking-tight">🧱 Extra content</h2>
              <Badge tone="violet">{data.blocks.length} blocks</Badge>
            </div>
            <p className="mb-4 text-sm text-ink/60">Optional. Shown under the service boxes, e.g. a few paragraphs about working with you in {place}.</p>
            <BlockEditor blocks={data.blocks} onChange={(blocks) => set('blocks', blocks)} />
          </section>

          <Card
            title="On the Locations page"
            icon="🗺️"
            tone="lime"
            actions={<span className="text-xs font-bold">{site.url.replace(/^https?:\/\//, '')}/locations</span>}
          >
            <ImageInput
              label={isCity ? 'City map picture' : 'State map picture'}
              hint="A picture of the map (not a live map). Shown in this place's box on /locations and on the state page."
              value={data.image}
              onChange={(v) => set('image', v)}
            />
            {!isCity && (
              <Field label="Short write-up" error={errors.summary} hint="Shown under the state name on /locations, above its cities.">
                <Textarea value={data.summary} placeholder={`Authors across ${place} trust ${site.name}…`} onChange={(e) => set('summary', e.target.value)} />
              </Field>
            )}
          </Card>

          <Card
            title="FAQs"
            icon="❓"
            tone="pink"
            actions={
              data.faqs !== null && (
                <Button size="sm" variant="ghost" onClick={() => set('faqs', null)}>
                  ↺ Back to the defaults
                </Button>
              )
            }
          >
            {data.faqs === null && (
              <p className="text-xs text-ink/55">
                ✍️ These come from{' '}
                <Link href={`${base}/defaults`} className="font-bold underline">
                  Page defaults
                </Link>
                . Edit them here to give {place} its own.
              </p>
            )}
            <FaqList items={data.faqs ?? defaultFaqs} onChange={(faqs) => set('faqs', faqs)} />
          </Card>
        </div>

        <aside className="space-y-6">
          <Card title="Status" icon="🚦" tone="lime">
            <Switch checked={data.is_active} onChange={(v) => set('is_active', v)} label="Live on the website" />
            {!location && (
              <Field label="Type">
                <Select value={data.parent_id ?? ''} onChange={(e) => set('parent_id', e.target.value ? Number(e.target.value) : null)}>
                  <option value="">🗺️ A state</option>
                  {states.map((s) => (
                    <option key={s.id} value={s.id}>
                      🏙️ A city in {s.name}
                    </option>
                  ))}
                </Select>
              </Field>
            )}
            {location && isCity && (
              <Field label="State" error={errors.parent_id}>
                <Select value={data.parent_id ?? ''} onChange={(e) => set('parent_id', Number(e.target.value))}>
                  {states.map((s) => (
                    <option key={s.id} value={s.id}>
                      {s.name}
                    </option>
                  ))}
                </Select>
              </Field>
            )}
          </Card>

          {location && (
            <Card title="Service pages" icon="🧰" tone="cyan">
              {pages.length === 0 ? (
                <p className="text-sm text-ink/60">
                  No services yet.{' '}
                  <Link href={`/s/${site.slug}/services/new`} className="font-bold underline">
                    Add one
                  </Link>{' '}
                  and it gets a page here.
                </p>
              ) : (
                <ul className="-my-1 divide-y-2 divide-ink/5">
                  {pages.map((p) => (
                    <li key={p.service_id} className="py-2">
                      <div className="flex items-center justify-between gap-2">
                        <span className="min-w-0 truncate font-bold">{p.name}</span>
                        <Link href={`${base}/${location.id}/services/${p.service_id}`} className={buttonClass({ variant: 'secondary', size: 'sm' })}>
                          ✏️
                        </Link>
                      </div>
                      <div className="mt-1 flex flex-wrap items-center gap-1.5">
                        {p.hidden ? (
                          <Badge tone="yellow">🙈 Hidden</Badge>
                        ) : !p.service_active ? (
                          <Badge tone="yellow">💤 Service off</Badge>
                        ) : (
                          p.custom && <Badge tone="pink">✍️ Custom</Badge>
                        )}
                        {p.path && (
                          <a href={`${site.url}/${p.path}`} target="_blank" rel="noreferrer" className="truncate text-xs font-semibold text-ink/50 hover:underline">
                            /{p.path} ↗
                          </a>
                        )}
                      </div>
                    </li>
                  ))}
                </ul>
              )}
            </Card>
          )}

          <Card title="SEO" icon="🔍" tone="violet">
            <Field label="Google title" error={errors.meta_title}>
              <Input value={shown('meta_title')} onChange={(e) => set('meta_title', e.target.value)} />
            </Field>
            <Field label="Google description" error={errors.meta_description}>
              <Textarea value={shown('meta_description')} onChange={(e) => set('meta_description', e.target.value)} />
            </Field>
            <div className="rounded-xl border-2 border-ink bg-paper p-3">
              <p className="mb-1.5 text-[11px] font-bold tracking-widest text-ink/45 uppercase">Google preview</p>
              <p className="truncate text-xs text-emerald-700">
                {site.url}/{state ? `${state.slug}/` : ''}
                {data.slug}
              </p>
              <p className="truncate text-base font-semibold text-blue-700">{shown('meta_title')}</p>
              <p className="line-clamp-2 text-sm text-ink/65">{shown('meta_description')}</p>
            </div>
          </Card>

          {location && (
            <Button
              variant="secondary"
              className="w-full"
              onClick={async () =>
                (await ask({
                  title: `Duplicate ${location.name}?`,
                  message: isCity ? 'A switched-off copy in the same state, ready to reword.' : 'A switched-off copy of the state (without its cities), ready to reword.',
                  yes: 'Yes, duplicate',
                  emoji: '📋',
                })) && router.post(`${base}/${location.id}/duplicate`)
              }
            >
              📋 Duplicate {isCity ? 'city' : 'state'}
            </Button>
          )}

          {location && (
            <Button
              variant="danger"
              className="w-full"
              onClick={async () =>
                (await ask({
                  title: `Delete ${location.name}?`,
                  message: isCity ? 'Its page and its service pages leave the website.' : 'All its cities and their pages leave the website too.',
                  yes: 'Yes, delete',
                  danger: true,
                })) &&
                router.delete(`${base}/${location.id}`)
              }
            >
              🗑️ Delete {isCity ? 'city' : 'state'}
            </Button>
          )}
        </aside>
      </div>
    </form>
  )
}
