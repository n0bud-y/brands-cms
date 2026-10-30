import { useEffect, useState } from 'react'
import { Head, Link, router } from '@inertiajs/react'
import { brandTone, tones, type Tone } from '@/lib/theme'
import { cx, formatDate } from '@/lib/utils'
import type { PostCounts, PostSummary, Site } from '@/types'
import { Badge, BrandAvatar, Button, buttonClass, EmptyState, PageHeader } from '@/components/ui'
import { StatusBadge } from '@/components/StatusBadge'

type Filters = { status: string; q: string }

type Props = {
  site: Site
  posts: { data: PostSummary[]; meta: { current_page: number; last_page: number; total: number } }
  counts: PostCounts
  filters: Filters
}

const FILTERS: { value: string; label: string; emoji: string; tone: Tone; count: keyof PostCounts }[] = [
  { value: '', label: 'All posts', emoji: '🗂️', tone: 'violet', count: 'total' },
  { value: 'published', label: 'Live', emoji: '🚀', tone: 'lime', count: 'published' },
  { value: 'draft', label: 'Drafts', emoji: '✏️', tone: 'yellow', count: 'draft' },
  { value: 'scheduled', label: 'Scheduled', emoji: '⏰', tone: 'cyan', count: 'scheduled' },
]

export default function Posts({ site, posts, counts, filters }: Props) {
  const [search, setSearch] = useState(filters.q)

  // Filters live in the URL; the server does the filtering.
  const visit = (next: Partial<Filters> & { page?: number }) => {
    const params = { ...filters, ...next }
    const query = Object.fromEntries(Object.entries(params).filter(([, v]) => v !== '' && v !== 1 && v != null))
    router.get(`/s/${site.slug}/posts`, query, { preserveState: true, preserveScroll: true, replace: true })
  }

  useEffect(() => {
    if (search.trim() === filters.q) return
    const t = setTimeout(() => visit({ q: search.trim(), page: 1 }), 300)
    return () => clearTimeout(t)
    // eslint-disable-next-line react-hooks/exhaustive-deps -- only react to typing
  }, [search])

  const { meta } = posts

  return (
    <>
      <Head title={`Posts · ${site.name}`} />
      <PageHeader
        title="Posts"
        emoji="📝"
        eyebrow={
          <>
            <BrandAvatar name={site.name} slug={site.slug} size="sm" />
            <span className="font-bold text-ink/60">{site.name}</span>
          </>
        }
      >
        <Link href={`/s/${site.slug}/posts/new`} className={buttonClass({ variant: 'pop' })}>
          ✍️ New post
        </Link>
      </PageHeader>

      <div className="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
        {FILTERS.map((f) => {
          const active = filters.status === f.value
          return (
            <button
              key={f.value}
              type="button"
              onClick={() => visit({ status: f.value, page: 1 })}
              className={cx(
                'rounded-2xl border-2 border-ink p-4 text-left transition',
                tones[f.tone],
                active ? '-translate-x-0.5 -translate-y-0.5 shadow-hard-lg' : 'shadow-hard-sm opacity-85 hover:opacity-100 hover:shadow-hard',
              )}
            >
              <span className="flex items-center justify-between text-sm font-bold">
                {f.label}
                <span className="text-xl">{f.emoji}</span>
              </span>
              <span className="mt-2 block text-4xl font-extrabold tabular-nums">{counts[f.count]}</span>
            </button>
          )
        })}
      </div>

      <div className="relative mb-4 max-w-sm">
        <span className="pointer-events-none absolute top-1/2 left-3.5 -translate-y-1/2">🔎</span>
        <input
          type="search"
          placeholder="Search titles…"
          value={search}
          onChange={(e) => setSearch(e.target.value)}
          className="w-full rounded-full border-2 border-ink bg-white py-2.5 pr-4 pl-10 text-sm font-medium outline-none focus:ring-4 focus:ring-pop-violet/25"
        />
      </div>

      {posts.data.length === 0 ? (
        <EmptyState emoji={filters.q || filters.status ? '🔍' : '🌱'} title={filters.q || filters.status ? 'Nothing matches that' : 'No posts yet'}>
          {!filters.q && !filters.status && 'Write the first post for this brand. Make it iconic.'}
        </EmptyState>
      ) : (
        <>
          <ul className="divide-y-2 divide-ink/10 overflow-hidden rounded-2xl border-2 border-ink bg-white shadow-hard">
            {posts.data.map((post) => (
              <li key={post.id} className="group flex items-center gap-3 px-4 py-3 transition hover:bg-pop-yellow/15 sm:gap-4">
                <Link href={`/s/${site.slug}/posts/${post.id}`} className="flex min-w-0 flex-1 items-center gap-4">
                  {post.cover_image ? (
                    <img src={post.cover_image} alt="" className="size-12 shrink-0 rounded-xl border-2 border-ink object-cover" />
                  ) : (
                    <span className={cx('grid size-12 shrink-0 place-items-center rounded-xl border-2 border-ink text-lg', brandTone(post.slug))}>
                      📄
                    </span>
                  )}
                  <span className="min-w-0">
                    <span className="block truncate font-bold decoration-2 underline-offset-4 group-hover:underline">{post.title}</span>
                    <span className="block truncate text-xs text-ink/50">
                      /blogs/{post.slug} · {post.layout}
                    </span>
                    <span className="mt-1 block sm:hidden">
                      <StatusBadge post={post} />
                    </span>
                  </span>
                </Link>
                <span className="hidden sm:block">
                  <StatusBadge post={post} />
                </span>
                <span className="hidden w-32 text-right text-xs text-ink/55 md:block">
                  {post.published_at ? formatDate(post.published_at) : `edited ${formatDate(post.updated_at)}`}
                </span>
                <div className="flex shrink-0 items-center">
                  {post.status === 'published' && (
                    <a
                      href={`${site.url}/blogs/${post.slug}`}
                      target="_blank"
                      rel="noreferrer"
                      title="View on website"
                      className={buttonClass({ variant: 'ghost', size: 'sm' })}
                    >
                      ↗
                    </a>
                  )}
                  <Button
                    size="sm"
                    variant="ghost"
                    title="Duplicate as a draft"
                    aria-label="Duplicate"
                    onClick={() => router.post(`/s/${site.slug}/posts/${post.id}/duplicate`)}
                  >
                    📋
                  </Button>
                  <Button
                    size="sm"
                    variant="ghost"
                    title="Delete"
                    onClick={() =>
                      confirm(`Delete "${post.title}"? This cannot be undone.`) &&
                      router.delete(`/s/${site.slug}/posts/${post.id}`, { preserveScroll: true })
                    }
                  >
                    🗑️
                  </Button>
                </div>
              </li>
            ))}
          </ul>

          {meta.last_page > 1 && (
            <div className="mt-5 flex items-center justify-between gap-3 text-sm">
              <Badge>
                Page {meta.current_page} / {meta.last_page} · {meta.total} posts
              </Badge>
              <div className="flex gap-2">
                <Button size="sm" variant="secondary" disabled={meta.current_page <= 1} onClick={() => visit({ page: meta.current_page - 1 })}>
                  ← Prev
                </Button>
                <Button
                  size="sm"
                  variant="secondary"
                  disabled={meta.current_page >= meta.last_page}
                  onClick={() => visit({ page: meta.current_page + 1 })}
                >
                  Next →
                </Button>
              </div>
            </div>
          )}
        </>
      )}
    </>
  )
}
