import { useEffect, useState } from 'react'
import { Head, Link, router } from '@inertiajs/react'
import { ask } from '@/lib/confirm'
import { brandTone, tones, type Tone } from '@/lib/theme'
import { cx, formatDate } from '@/lib/utils'
import type { PostCounts, PostSummary, Site } from '@/types'
import { Badge, BrandAvatar, Button, buttonClass, EmptyState, PageHeader } from '@/components/ui'
import { StatusBadge } from '@/components/StatusBadge'

type Filters = { status: string; q: string; month: string; day: string }

type Props = {
  site: Site
  posts: { data: PostSummary[]; meta: { current_page: number; last_page: number; total: number } }
  counts: PostCounts
  /** Months that have posts, newest first. */
  months: { value: string; label: string; count: number }[]
  filters: Filters
}

const FILTERS: { value: string; label: string; emoji: string; tone: Tone; count: keyof PostCounts }[] = [
  { value: '', label: 'All posts', emoji: '🗂️', tone: 'violet', count: 'total' },
  { value: 'published', label: 'Live', emoji: '🚀', tone: 'lime', count: 'published' },
  { value: 'draft', label: 'Drafts', emoji: '✏️', tone: 'yellow', count: 'draft' },
  { value: 'scheduled', label: 'Scheduled', emoji: '⏰', tone: 'cyan', count: 'scheduled' },
  { value: 'trash', label: 'Trash', emoji: '🗑️', tone: 'white', count: 'trash' },
]

const filterControl =
  'rounded-full border-2 border-ink bg-white py-2.5 pr-4 pl-4 text-sm font-semibold outline-none focus:ring-4 focus:ring-pop-violet/25'

export default function Posts({ site, posts, counts, months, filters }: Props) {
  const [search, setSearch] = useState(filters.q)
  const base = `/s/${site.slug}/posts`
  const inTrash = filters.status === 'trash'
  const filtered = Boolean(filters.q || filters.month || filters.day)

  // Filters live in the URL; the server does the filtering.
  const visit = (next: Partial<Filters> & { page?: number }) => {
    const params = { ...filters, ...next }
    const query = Object.fromEntries(Object.entries(params).filter(([, v]) => v !== '' && v !== 1 && v != null))
    router.get(base, query, { preserveState: true, preserveScroll: true, replace: true })
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
        <Link href={`${base}/new`} className={buttonClass({ variant: 'pop' })}>
          ✍️ New post
        </Link>
      </PageHeader>

      <div className="mb-6 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
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

      {/* Search, month and day */}
      <div className="mb-4 flex flex-wrap items-center gap-2">
        <div className="relative w-full max-w-xs">
          <span className="pointer-events-none absolute top-1/2 left-3.5 -translate-y-1/2">🔎</span>
          <input
            type="search"
            placeholder="Search titles…"
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            className={cx(filterControl, 'w-full pl-10 font-medium')}
          />
        </div>
        <select
          aria-label="Month"
          value={filters.month}
          onChange={(e) => visit({ month: e.target.value, day: '', page: 1 })}
          className={cx(filterControl, 'cursor-pointer')}
        >
          <option value="">📅 All months</option>
          {months.map((m) => (
            <option key={m.value} value={m.value}>
              {m.label} ({m.count})
            </option>
          ))}
        </select>
        <label className={cx(filterControl, 'flex cursor-pointer items-center gap-2 py-1.5')}>
          <span className="text-ink/60">Day</span>
          <input
            type="date"
            aria-label="Day"
            value={filters.day}
            onChange={(e) => visit({ day: e.target.value, month: '', page: 1 })}
            className="cursor-pointer bg-transparent outline-none"
          />
        </label>
        {filtered && (
          <Button
            size="sm"
            variant="ghost"
            onClick={() => {
              setSearch('')
              visit({ q: '', month: '', day: '', page: 1 })
            }}
          >
            ✕ Clear filters
          </Button>
        )}
      </div>

      {inTrash && posts.data.length > 0 && (
        <p className="mb-4 rounded-xl border-2 border-ink bg-white px-4 py-3 text-sm font-semibold">
          🗑️ Posts in the trash are not on the website. <b>Restore</b> brings one back; <b>Delete forever</b> removes it for good.
        </p>
      )}

      {posts.data.length === 0 ? (
        <EmptyState
          emoji={inTrash ? '🗑️' : filtered || filters.status ? '🔍' : '🌱'}
          title={inTrash ? 'The trash is empty' : filtered || filters.status ? 'Nothing matches that' : 'No posts yet'}
        >
          {!inTrash && !filtered && !filters.status && 'Write the first post for this brand. Make it iconic.'}
        </EmptyState>
      ) : (
        <>
          <ul className="divide-y-2 divide-ink/10 overflow-hidden rounded-2xl border-2 border-ink bg-white shadow-hard">
            {posts.data.map((post) => (
              <li key={post.id} className="group flex items-center gap-3 px-4 py-3 transition hover:bg-pop-yellow/15 sm:gap-4">
                <PostRow site={site} post={post} inTrash={inTrash} />
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

function PostRow({ site, post, inTrash }: { site: Site; post: PostSummary; inTrash: boolean }) {
  const url = `/s/${site.slug}/posts/${post.id}`
  const thumb = post.cover_image ? (
    <img src={post.cover_image} alt="" loading="lazy" className="size-12 shrink-0 rounded-xl border-2 border-ink object-cover" />
  ) : (
    <span className={cx('grid size-12 shrink-0 place-items-center rounded-xl border-2 border-ink text-lg', brandTone(post.slug))}>📄</span>
  )
  const info = (
    <span className="min-w-0">
      <span className="block truncate font-bold decoration-2 underline-offset-4 group-hover:underline">{post.title}</span>
      <span className="block truncate text-xs text-ink/50">
        /blogs/{post.slug} · {post.layout}
      </span>
    </span>
  )

  if (inTrash) {
    return (
      <>
        <span className="flex min-w-0 flex-1 items-center gap-4 opacity-70">
          {thumb}
          {info}
        </span>
        <span className="hidden w-36 text-right text-xs text-ink/55 md:block">deleted {formatDate(post.deleted_at ?? null)}</span>
        <div className="flex shrink-0 items-center gap-1">
          <Button size="sm" variant="secondary" onClick={() => router.post(`${url}/restore`, {}, { preserveScroll: true })}>
            ♻️ Restore
          </Button>
          <Button
            size="sm"
            variant="ghost"
            title="Delete forever"
            onClick={async () =>
              (await ask({ title: 'Delete forever?', message: `"${post.title}" will be gone for good. This cannot be undone.`, yes: 'Yes, delete forever', danger: true })) &&
              router.delete(`${url}/forever`, { preserveScroll: true })
            }
          >
            ❌
          </Button>
        </div>
      </>
    )
  }

  return (
    <>
      <Link href={url} className="flex min-w-0 flex-1 items-center gap-4">
        {thumb}
        <span className="min-w-0">
          {info}
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
          <a href={`${site.url}/blogs/${post.slug}`} target="_blank" rel="noreferrer" title="View on website" className={buttonClass({ variant: 'ghost', size: 'sm' })}>
            ↗
          </a>
        )}
        <Button
          size="sm"
          variant="ghost"
          title="Duplicate as a draft"
          aria-label="Duplicate"
          onClick={async () =>
            (await ask({ title: 'Duplicate this post?', message: `A copy of "${post.title}" is saved as a new draft.`, yes: 'Yes, duplicate', emoji: '📋' })) &&
            router.post(`${url}/duplicate`)
          }
        >
          📋
        </Button>
        <Button
          size="sm"
          variant="ghost"
          title="Move to the trash"
          aria-label="Delete"
          onClick={async () =>
            (await ask({ title: 'Move to the trash?', message: `"${post.title}" leaves the website. You can restore it from the trash.`, yes: 'Yes, delete', danger: true })) &&
            router.delete(url, { preserveScroll: true })
          }
        >
          🗑️
        </Button>
      </div>
    </>
  )
}
