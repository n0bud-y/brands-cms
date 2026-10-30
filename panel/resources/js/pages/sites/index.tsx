import { Head, Link } from '@inertiajs/react'
import { brandTone } from '@/lib/theme'
import { cx, plural } from '@/lib/utils'
import type { Site } from '@/types'
import { Badge, BrandAvatar, buttonClass, EmptyState, PageHeader } from '@/components/ui'

export default function Sites({ brands }: { brands: Site[] }) {
  return (
    <>
      <Head title="All brands" />
      <PageHeader title="All brands" emoji="🌈" eyebrow={<Badge tone="pink">{plural(brands.length, 'brand')}</Badge>}>
        <Link href="/sites/new" className={buttonClass({ variant: 'pop' })}>
          ✨ New brand
        </Link>
      </PageHeader>

      {brands.length === 0 ? (
        <EmptyState emoji="🪐" title="No brands yet">
          Create your first brand to start managing its blog and settings.
        </EmptyState>
      ) : (
        <div className="grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
          {brands.map((site) => (
            <article
              key={site.id}
              className="group relative animate-pop-in rounded-2xl border-2 border-ink bg-white shadow-hard transition hover:-translate-y-1 hover:shadow-hard-lg"
            >
              <div className={cx('relative h-24 overflow-hidden rounded-t-[14px] border-b-2 border-ink', brandTone(site.slug))}>
                <div className="absolute -top-6 -right-6 size-24 rounded-full border-2 border-ink/30 bg-white/25" />
                <div className="absolute right-16 -bottom-8 size-16 rotate-12 rounded-xl border-2 border-ink/30 bg-white/20" />
              </div>
              <BrandAvatar name={site.name} slug={site.slug} size="lg" className="absolute top-16 left-5 shadow-hard-sm" />
              <div className="p-5 pt-10">
                <h2 className="text-lg font-extrabold">
                  <Link href={`/s/${site.slug}/posts`} className="after:absolute after:inset-0">
                    {site.name}
                  </Link>
                </h2>
                <p className="truncate text-sm text-ink/55">{site.url.replace(/^https?:\/\//, '')}</p>
                <div className="mt-4 flex flex-wrap items-center gap-2">
                  <Badge tone="violet">📝 {plural(site.posts_count ?? 0, 'post')}</Badge>
                  <Badge tone="cyan">👥 {plural(site.users_count ?? 0, 'editor')}</Badge>
                  {site.is_active ? <Badge tone="lime">● Active</Badge> : <Badge>○ Paused</Badge>}
                  <Link
                    href={`/s/${site.slug}/brand`}
                    className={cx(buttonClass({ variant: 'secondary', size: 'sm' }), 'relative z-10 ml-auto')}
                  >
                    Edit
                  </Link>
                </div>
              </div>
            </article>
          ))}

          <Link
            href="/sites/new"
            className="grid min-h-56 place-items-center rounded-2xl border-2 border-dashed border-ink/40 bg-white/50 text-center transition hover:border-ink hover:bg-pop-lime/30"
          >
            <span>
              <span className="mb-2 block text-4xl">➕</span>
              <span className="font-extrabold">Add a brand</span>
            </span>
          </Link>
        </div>
      )}
    </>
  )
}
