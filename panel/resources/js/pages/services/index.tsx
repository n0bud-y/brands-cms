import { Head, Link, router } from '@inertiajs/react'
import { plural } from '@/lib/utils'
import type { Service, Site } from '@/types'
import { Badge, BrandAvatar, Button, buttonClass, EmptyState, PageHeader } from '@/components/ui'

type Row = Pick<Service, 'id' | 'name' | 'slug' | 'icon' | 'summary' | 'is_active'>

export default function Services({ site, services, places }: { site: Site; services: Row[]; places: number }) {
  const move = (i: number, by: number) => {
    const ids = services.map((s) => s.id)
    ;[ids[i], ids[i + by]] = [ids[i + by], ids[i]]
    router.post(`/s/${site.slug}/services/reorder`, { ids }, { preserveScroll: true })
  }

  return (
    <>
      <Head title={`Services · ${site.name}`} />
      <PageHeader
        title="Services"
        emoji="🧰"
        eyebrow={
          <>
            <BrandAvatar name={site.name} slug={site.slug} size="sm" />
            <span className="font-bold text-ink/60">{site.name}</span>
          </>
        }
      >
        <Link href={`/s/${site.slug}/services/new`} className={buttonClass({ variant: 'pop' })}>
          ➕ New service
        </Link>
      </PageHeader>

      <p className="mb-6 max-w-2xl text-sm text-ink/65">
        Write a service once and it gets a page in every state and city, e.g. <b>/california-ghostwriting</b> and{' '}
        <b>/california/los-angeles-ghostwriting</b>. They show as boxes on each location page, in this order.
        {places > 0 && <> Right now that&apos;s {plural(places, 'location')}.</>}
      </p>

      {services.length === 0 ? (
        <EmptyState emoji="🧰" title="No services yet">
          Add your first service, like Ghostwriting or Book Editing.
        </EmptyState>
      ) : (
        <div className="space-y-3">
          {services.map((s, i) => (
            <article key={s.id} className="flex items-center gap-4 rounded-2xl border-2 border-ink bg-white p-4 shadow-hard-sm">
              {s.icon ? (
                <img src={s.icon} alt="" className="size-12 shrink-0 rounded-xl border-2 border-ink object-cover" />
              ) : (
                <span className="grid size-12 shrink-0 place-items-center rounded-xl border-2 border-ink bg-pop-cyan text-xl">🧰</span>
              )}
              <div className="min-w-0 flex-1">
                <div className="flex flex-wrap items-center gap-2">
                  <Link href={`/s/${site.slug}/services/${s.id}`} className="font-extrabold hover:underline">
                    {s.name}
                  </Link>
                  {!s.is_active && <Badge tone="yellow">💤 Off</Badge>}
                </div>
                <p className="text-xs font-semibold text-ink/50">/…-{s.slug}</p>
                {s.summary && <p className="mt-1 line-clamp-1 text-sm text-ink/70">{s.summary}</p>}
              </div>
              <div className="flex shrink-0 items-center gap-1">
                <Button size="sm" variant="ghost" disabled={i === 0} title="Move up" onClick={() => move(i, -1)}>
                  ↑
                </Button>
                <Button size="sm" variant="ghost" disabled={i === services.length - 1} title="Move down" onClick={() => move(i, 1)}>
                  ↓
                </Button>
                <Link href={`/s/${site.slug}/services/${s.id}`} className={buttonClass({ variant: 'secondary', size: 'sm' })}>
                  ✏️ Edit
                </Link>
              </div>
            </article>
          ))}
        </div>
      )}
    </>
  )
}
