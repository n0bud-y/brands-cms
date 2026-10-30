import { useEffect, useLayoutEffect, useRef, useState } from 'react'
import { Head, Link, router, useForm } from '@inertiajs/react'
import { cx, fromLocalInput, slugify, toLocalInput, uid } from '@/lib/utils'
import type { Author, Block, Post, PostStatus, Site } from '@/types'
import { Badge, Button, buttonClass, Card, Field, Input, Segmented, Select, Switch, Textarea } from '@/components/ui'
import { BlockEditor } from '@/components/BlockEditor'
import { ImageInput } from '@/components/ImageInput'
import { SchemaInput } from '@/components/SchemaInput'
import { StatusBadge } from '@/components/StatusBadge'

type Form = {
  title: string
  slug: string
  layout: string
  author_id: number | null
  show_author: boolean
  category: string
  cover_image: string
  excerpt: string
  blocks: Block[]
  meta_title: string
  meta_description: string
  og_image: string
  schema: string
  status: PostStatus
  published_at: string | null
}

const LAYOUTS: Record<string, { emoji: string; label: string }> = {
  classic: { emoji: '🖼️', label: 'Classic' },
  hero: { emoji: '🌅', label: 'Hero' },
  sidebar: { emoji: '📑', label: 'Sidebar' },
  minimal: { emoji: '📄', label: 'Minimal' },
}

const toForm = (site: Site, post: Post | null): Form => ({
  title: post?.title ?? '',
  slug: post?.slug ?? '',
  layout: post?.layout ?? site.available_layouts[0] ?? 'classic',
  author_id: post?.author_id ?? null,
  show_author: post?.show_author ?? true,
  category: post?.category ?? '',
  cover_image: post?.cover_image ?? '',
  excerpt: post?.excerpt ?? '',
  blocks: (post?.blocks ?? []).map((b) => ({ ...b, id: b.id || uid() })),
  meta_title: post?.meta_title ?? '',
  meta_description: post?.meta_description ?? '',
  og_image: post?.og_image ?? '',
  schema: post?.schema ?? '',
  status: post?.status ?? 'draft',
  published_at: post?.published_at ?? null,
})

type Props = { site: Site; post: Post | null; authors: Pick<Author, 'id' | 'name' | 'is_default'>[]; categories: string[] }

export default function PostEditorPage(props: Props) {
  // Keyed so creating a post (new -> /posts/:id) starts a fresh form from the saved data.
  return <Editor key={props.post?.id ?? 'new'} {...props} />
}

function Counter({ value, max }: { value: string; max: number }) {
  return (
    <span className={cx('rounded-full px-2 text-xs tabular-nums', value.length > max ? 'bg-pop-orange font-bold' : 'bg-ink/8 text-ink/60')}>
      {value.length}/{max}
    </span>
  )
}

function Editor({ site, post, authors, categories }: Props) {
  const form = useForm<Form>(toForm(site, post))
  const { data, errors } = form
  const [slugTouched, setSlugTouched] = useState(Boolean(post))
  const set = <K extends keyof Form>(key: K, value: Form[K]) => form.setData((d) => ({ ...d, [key]: value }))

  const submit = () => {
    if (form.processing) return
    if (!post) return form.post(`/s/${site.slug}/posts`)
    form.put(`/s/${site.slug}/posts/${post.id}`, {
      preserveScroll: true,
      // The server normalizes blocks/schema; make the saved version the new baseline.
      onSuccess: (page) => {
        const next = toForm(site, (page.props as unknown as { post: Post }).post)
        form.setDefaults(next)
        form.setData(next)
      },
    })
  }

  // Ctrl/Cmd+S saves; leaving with unsaved changes asks first (browser close and in-app links).
  const latest = useRef({ submit, dirty: form.isDirty })
  useEffect(() => {
    latest.current = { submit, dirty: form.isDirty }
  })
  useEffect(() => {
    const onKey = (e: KeyboardEvent) => {
      if ((e.ctrlKey || e.metaKey) && e.key === 's') {
        e.preventDefault()
        latest.current.submit()
      }
    }
    const onUnload = (e: BeforeUnloadEvent) => {
      if (latest.current.dirty) e.preventDefault()
    }
    window.addEventListener('keydown', onKey)
    window.addEventListener('beforeunload', onUnload)
    const offBefore = router.on('before', (event) => {
      const leaving = event.detail.visit.method === 'get'
      if (leaving && latest.current.dirty && !confirm('You have unsaved changes. Leave without saving?')) event.preventDefault()
    })
    return () => {
      window.removeEventListener('keydown', onKey)
      window.removeEventListener('beforeunload', onUnload)
      offBefore()
    }
  }, [])

  // Grow the title box with its text.
  const titleRef = useRef<HTMLTextAreaElement>(null)
  useLayoutEffect(() => {
    const el = titleRef.current
    if (!el) return
    el.style.height = 'auto'
    el.style.height = `${el.scrollHeight}px`
  }, [data.title])

  const layouts = site.available_layouts.includes(data.layout) ? site.available_layouts : [...site.available_layouts, data.layout]
  const liveUrl = `${site.url}/blogs/${data.slug}`
  const isLive = post?.status === 'published'
  const publishing = data.status === 'published'

  return (
    <form
      onSubmit={(e) => {
        e.preventDefault()
        submit()
      }}
    >
      <Head title={post ? post.title : 'New post'} />

      {/* Sticky action bar */}
      <div className="sticky top-[58px] z-10 -mx-4 mb-6 flex flex-wrap items-center justify-between gap-3 border-b-2 border-ink/10 bg-paper/85 px-4 py-3 backdrop-blur sm:-mx-6 sm:px-6 lg:top-0 lg:-mx-10 lg:px-10">
        <div className="flex min-w-0 items-center gap-3">
          <Link href={`/s/${site.slug}/posts`} className={buttonClass({ variant: 'secondary', size: 'sm' })}>
            ← Posts
          </Link>
          {post ? <StatusBadge post={post} /> : <Badge tone="pink">✨ New</Badge>}
          {form.isDirty && (
            <span className="flex items-center gap-1.5 text-xs font-bold text-pop-orange">
              <span className="size-2 animate-pulse rounded-full bg-pop-orange" /> Unsaved
            </span>
          )}
        </div>
        <div className="flex items-center gap-2">
          {isLive && (
            <a href={liveUrl} target="_blank" rel="noreferrer" className={buttonClass({ variant: 'secondary' })}>
              View live ↗
            </a>
          )}
          <Button type="submit" variant={publishing ? 'pop' : 'primary'} disabled={form.processing || (!form.isDirty && Boolean(post))} title="Ctrl+S">
            {form.processing ? 'Saving…' : publishing ? (isLive ? '💾 Update' : '🚀 Publish') : '💾 Save draft'}
          </Button>
        </div>
      </div>

      <div className="grid gap-8 lg:grid-cols-[1fr_21rem]">
        <div className="min-w-0 space-y-8">
          <div>
            <textarea
              ref={titleRef}
              required
              rows={1}
              placeholder="Your post title…"
              value={data.title}
              onChange={(e) => {
                const title = e.target.value.replace(/\n/g, ' ')
                form.setData((d) => ({ ...d, title, slug: slugTouched ? d.slug : slugify(title) }))
              }}
              className="w-full resize-none overflow-hidden bg-transparent text-4xl leading-tight font-extrabold tracking-tight outline-none placeholder:text-ink/25 sm:text-5xl"
            />
            {errors.title && <p className="text-sm font-semibold text-pop-red">⚠ {errors.title}</p>}

            <div className="mt-3 inline-flex max-w-full items-center rounded-full border-2 border-ink bg-white pl-3 text-sm shadow-hard-sm focus-within:ring-4 focus-within:ring-pop-violet/25">
              <span className="shrink-0 font-semibold text-ink/45">/blogs/</span>
              <input
                required
                aria-label="URL slug"
                placeholder="your-post-url"
                className="min-w-0 bg-transparent py-1.5 pr-3 font-bold outline-none"
                size={Math.max(data.slug.length, 14)}
                value={data.slug}
                onChange={(e) => {
                  setSlugTouched(true)
                  set('slug', e.target.value.toLowerCase().replace(/[^a-z0-9-]/g, '-'))
                }}
                onBlur={() => set('slug', slugify(data.slug))}
              />
            </div>
            {errors.slug ? (
              <p className="mt-1.5 text-sm font-semibold text-pop-red">⚠ {errors.slug}</p>
            ) : (
              post &&
              data.slug !== post.slug && <p className="mt-1.5 text-xs font-semibold text-ink/60">↪️ /blogs/{post.slug} will redirect (301) here.</p>
            )}
          </div>

          <Card title="Basics" icon="📌" tone="yellow">
            <Field label="Excerpt" error={errors.excerpt} hint="Shown on the blog listing, and used as the meta description if you leave that empty.">
              <Textarea value={data.excerpt} placeholder="One or two lines that make people click…" onChange={(e) => set('excerpt', e.target.value)} />
            </Field>
            <Field label="Category" error={errors.category} hint="Optional. Shown as a badge on the blog card, e.g. Publishing Tips.">
              <Input list="post-categories" value={data.category} placeholder="Publishing Tips" onChange={(e) => set('category', e.target.value)} />
              <datalist id="post-categories">
                {categories.map((c) => (
                  <option key={c} value={c} />
                ))}
              </datalist>
            </Field>
            <ImageInput label="Cover image" value={data.cover_image} onChange={(v) => set('cover_image', v)} />
          </Card>

          <section>
            <div className="mb-4 flex items-center gap-2">
              <h2 className="text-xl font-extrabold tracking-tight">🧱 Content</h2>
              <Badge tone="violet">{data.blocks.length} blocks</Badge>
            </div>
            <BlockEditor blocks={data.blocks} onChange={(blocks) => set('blocks', blocks)} />
          </section>
        </div>

        <aside className="space-y-6">
          <Card title="Publishing" icon="🚀" tone="lime">
            <Segmented<PostStatus>
              value={data.status}
              onChange={(v) => set('status', v)}
              options={[
                { value: 'draft', label: '✏️ Draft', tone: 'yellow' },
                { value: 'published', label: '🚀 Publish', tone: 'lime' },
              ]}
            />
            <Field label="Publish date" error={errors.published_at} hint="Empty = the moment you publish. A future date schedules it ⏰">
              <Input type="datetime-local" value={toLocalInput(data.published_at)} onChange={(e) => set('published_at', fromLocalInput(e.target.value))} />
            </Field>
          </Card>

          <Card title="Layout" icon="🎨" tone="pink">
            <div className="grid grid-cols-2 gap-2">
              {layouts.map((l) => {
                const info = LAYOUTS[l] ?? { emoji: '🧩', label: l }
                return (
                  <button
                    key={l}
                    type="button"
                    onClick={() => set('layout', l)}
                    className={cx(
                      'rounded-xl border-2 px-2 py-3 text-center text-sm font-bold transition',
                      data.layout === l ? 'border-ink bg-pop-violet text-white shadow-hard-sm' : 'border-ink/20 bg-white hover:border-ink',
                      !site.available_layouts.includes(l) && 'opacity-60',
                    )}
                  >
                    <span className="block text-2xl">{info.emoji}</span>
                    {info.label}
                  </button>
                )
              })}
            </div>
            {errors.layout && <p className="text-sm font-semibold text-pop-red">⚠ {errors.layout}</p>}
          </Card>

          <Card title="Author" icon="🖋️" tone="yellow">
            {authors.length === 0 ? (
              <p className="text-sm text-ink/65">
                No writers yet.{' '}
                <Link href={`/s/${site.slug}/authors`} className="font-bold underline">
                  Add one in Authors
                </Link>{' '}
                and it shows under every post.
              </p>
            ) : (
              <>
                <Switch checked={data.show_author} onChange={(v) => set('show_author', v)} label="Show the author box on this post" />
                {data.show_author && (
                  <Field label="Writer" error={errors.author_id}>
                    <Select value={data.author_id ?? ''} onChange={(e) => set('author_id', e.target.value ? Number(e.target.value) : null)}>
                      <option value="">⭐ Default ({authors.find((a) => a.is_default)?.name ?? 'none set'})</option>
                      {authors.map((a) => (
                        <option key={a.id} value={a.id}>
                          {a.name}
                        </option>
                      ))}
                    </Select>
                  </Field>
                )}
              </>
            )}
          </Card>

          <Card title="SEO" icon="🔍" tone="cyan">
            <Field label={<span className="flex items-center justify-between">Meta title <Counter value={data.meta_title} max={60} /></span>} error={errors.meta_title}>
              <Input value={data.meta_title} placeholder={data.title || 'Defaults to the title'} onChange={(e) => set('meta_title', e.target.value)} />
            </Field>
            <Field
              label={<span className="flex items-center justify-between">Meta description <Counter value={data.meta_description} max={160} /></span>}
              error={errors.meta_description}
            >
              <Textarea value={data.meta_description} placeholder={data.excerpt || 'Defaults to the excerpt'} onChange={(e) => set('meta_description', e.target.value)} />
            </Field>
            <ImageInput label="Social share image" hint="Defaults to the cover. 1200×630 looks best." value={data.og_image} onChange={(v) => set('og_image', v)} />
            <div className="rounded-xl border-2 border-ink bg-paper p-3">
              <p className="mb-1.5 text-[11px] font-bold tracking-widest text-ink/45 uppercase">Google preview</p>
              <p className="truncate text-xs text-emerald-700">{liveUrl}</p>
              <p className="truncate text-base font-semibold text-blue-700">{data.meta_title || data.title || 'Post title'}</p>
              <p className="line-clamp-2 text-sm text-ink/65">{data.meta_description || data.excerpt || 'Description…'}</p>
            </div>
          </Card>

          <Card title="Schema" icon="🧬" tone="violet">
            <SchemaInput
              label="Custom schema (JSON-LD)"
              hint="Added to this post's <head>. A BlogPosting schema is generated automatically; use this for extras like FAQPage, HowTo, Book or Product."
              value={data.schema}
              error={errors.schema}
              onChange={(v) => set('schema', v)}
            />
          </Card>

          {post && (
            <Button
              variant="secondary"
              className="w-full"
              title="Make a draft copy of the saved post"
              onClick={() =>
                (!form.isDirty || confirm("Unsaved changes are not copied. Duplicate the last saved version?")) &&
                router.post(`/s/${site.slug}/posts/${post.id}/duplicate`)
              }
            >
              📋 Duplicate as draft
            </Button>
          )}

          {post && (
            <Button
              variant="danger"
              className="w-full"
              onClick={() => confirm('Delete this post? This cannot be undone.') && router.delete(`/s/${site.slug}/posts/${post.id}`)}
            >
              🗑️ Delete post
            </Button>
          )}
        </aside>
      </div>
    </form>
  )
}
