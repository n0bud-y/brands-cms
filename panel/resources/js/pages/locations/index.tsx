import { Head, Link, router } from '@inertiajs/react'
import { cx, plural } from '@/lib/utils'
import type { LocationSummary, Site } from '@/types'
import { Badge, BrandAvatar, Button, buttonClass, EmptyState, PageHeader } from '@/components/ui'

type State = LocationSummary & { cities: LocationSummary[] }

const cities = (n: number) => `${n} ${n === 1 ? 'city' : 'cities'}`

export default function Locations({ site, states, services }: { site: Site; states: State[]; services: number }) {
  const base = `/s/${site.slug}/locations`
  const move = (list: LocationSummary[], i: number, by: number) => {
    const ids = list.map((l) => l.id)
    ;[ids[i], ids[i + by]] = [ids[i + by], ids[i]]
    router.post(`${base}/reorder`, { ids }, { preserveScroll: true })
  }
  const cityCount = states.reduce((n, s) => n + s.cities.length, 0)

  return (
    <>
      <Head title={`Locations · ${site.name}`} />
      <PageHeader
        title="Locations"
        emoji="📍"
        eyebrow={
          <>
            <BrandAvatar name={site.name} slug={site.slug} size="sm" />
            <span className="font-bold text-ink/60">{site.name}</span>
          </>
        }
      >
        <Link href={`${base}/defaults`} className={buttonClass({ variant: 'secondary' })}>
          ⚙️ Page defaults
        </Link>
        <Link href={`${base}/new`} className={buttonClass({ variant: 'pop' })}>
          ➕ New state
        </Link>
      </PageHeader>

      <p className="mb-6 max-w-2xl text-sm text-ink/65">
        Each state and city gets a page (<b>/california</b>, <b>/california/los-angeles</b>) with a banner and form, your service boxes,
        its cities, FAQs and contact. Plus one page per service. That&apos;s{' '}
        <b>
          {plural((states.length + cityCount) * (services + 1), 'page')}
        </b>{' '}
        from {plural(states.length, 'state')}, {cities(cityCount)} and{' '}
        <Link href={`/s/${site.slug}/services`} className="font-bold underline">
          {plural(services, 'service')}
        </Link>
        .
      </p>

      {services === 0 && states.length > 0 && (
        <p className="mb-6 rounded-xl border-2 border-ink bg-pop-yellow px-4 py-3 text-sm font-semibold">
          💡 No services yet, so location pages have no service boxes.{' '}
          <Link href={`/s/${site.slug}/services/new`} className="underline">
            Add your first service
          </Link>
        </p>
      )}

      {states.length === 0 ? (
        <EmptyState emoji="🗺️" title="No locations yet">
          Add your first state, then its cities.
        </EmptyState>
      ) : (
        <div className="space-y-4">
          {states.map((state, i) => (
            <article key={state.id} className="rounded-2xl border-2 border-ink bg-white shadow-hard-sm">
              <header className="flex flex-wrap items-center gap-3 border-b-2 border-ink/10 p-4">
                <span className="text-2xl">🗺️</span>
                <div className="min-w-0 flex-1">
                  <div className="flex flex-wrap items-center gap-2">
                    <Link href={`${base}/${state.id}`} className="text-lg font-extrabold hover:underline">
                      {state.name}
                    </Link>
                    <Badge>{cities(state.cities.length)}</Badge>
                    {!state.is_active && <Badge tone="yellow">💤 Off</Badge>}
                  </div>
                  <p className="text-xs font-semibold text-ink/50">/{state.slug}</p>
                </div>
                <div className="flex items-center gap-1">
                  <Button size="sm" variant="ghost" disabled={i === 0} title="Move up" onClick={() => move(states, i, -1)}>
                    ↑
                  </Button>
                  <Button size="sm" variant="ghost" disabled={i === states.length - 1} title="Move down" onClick={() => move(states, i, 1)}>
                    ↓
                  </Button>
                  <Link href={`${base}/new?parent=${state.id}`} className={buttonClass({ variant: 'secondary', size: 'sm' })}>
                    ➕ City
                  </Link>
                  <Link href={`${base}/${state.id}`} className={buttonClass({ variant: 'secondary', size: 'sm' })}>
                    ✏️ Edit
                  </Link>
                </div>
              </header>
              {state.cities.length > 0 && (
                <ul className="divide-y-2 divide-ink/5 px-4 py-1">
                  {state.cities.map((city, j) => (
                    <li key={city.id} className="flex items-center gap-3 py-2">
                      <span>🏙️</span>
                      <Link href={`${base}/${city.id}`} className={cx('min-w-0 flex-1 truncate font-bold hover:underline', !city.is_active && 'opacity-50')}>
                        {city.name}
                        <span className="ml-2 text-xs font-semibold text-ink/45">
                          /{state.slug}/{city.slug}
                        </span>
                      </Link>
                      {!city.is_active && <Badge tone="yellow">💤 Off</Badge>}
                      <Button size="sm" variant="ghost" disabled={j === 0} title="Move up" onClick={() => move(state.cities, j, -1)}>
                        ↑
                      </Button>
                      <Button
                        size="sm"
                        variant="ghost"
                        disabled={j === state.cities.length - 1}
                        title="Move down"
                        onClick={() => move(state.cities, j, 1)}
                      >
                        ↓
                      </Button>
                    </li>
                  ))}
                </ul>
              )}
            </article>
          ))}
        </div>
      )}
    </>
  )
}
