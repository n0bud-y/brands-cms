import { useEffect, useRef, useState, type ReactNode } from 'react'
import { Link, router, usePage } from '@inertiajs/react'
import { initials } from '@/lib/theme'
import { cx, plural } from '@/lib/utils'
import type { Site } from '@/types'
import { BrandAvatar, Button } from '@/components/ui'

type Section = { path: string; label: string; emoji: string; adminOnly?: boolean }

const SECTIONS: Section[] = [
  { path: 'posts', label: 'Posts', emoji: '📝' },
  { path: 'authors', label: 'Authors', emoji: '🖋️' },
  { path: 'locations', label: 'Locations', emoji: '📍' },
  { path: 'services', label: 'Services', emoji: '🧰' },
  { path: 'pages', label: 'Pages & SEO', emoji: '📄' },
  { path: 'settings', label: 'Settings', emoji: '🎛️' },
  { path: 'brand', label: 'Brand', emoji: '🏷️', adminOnly: true },
]

export function Logo() {
  return (
    <Link href="/" className="flex items-center gap-2.5 text-xl font-extrabold tracking-tight">
      <span className="grid size-9 -rotate-6 place-items-center rounded-xl border-2 border-current bg-pop-lime text-ink transition-transform hover:rotate-6">
        ✦
      </span>
      <span>
        brand<span className="text-pop-pink">.</span>cms
      </span>
    </Link>
  )
}

export default function AppLayout({ children }: { children: ReactNode }) {
  const [menuOpen, setMenuOpen] = useState(false)
  // Close the mobile menu whenever a visit starts.
  useEffect(() => router.on('start', () => setMenuOpen(false)), [])

  return (
    <div className="min-h-screen lg:pl-72">
      <aside
        className={cx(
          'fixed inset-y-0 left-0 z-40 flex w-72 flex-col bg-ink text-white transition-transform duration-200 lg:translate-x-0',
          menuOpen ? 'translate-x-0' : '-translate-x-full',
        )}
      >
        <Sidebar />
      </aside>
      {menuOpen && (
        <button
          type="button"
          aria-label="Close menu"
          className="fixed inset-0 z-30 bg-ink/50 backdrop-blur-sm lg:hidden"
          onClick={() => setMenuOpen(false)}
        />
      )}

      <header className="sticky top-0 z-20 flex items-center justify-between border-b-2 border-ink bg-paper/90 px-4 py-3 backdrop-blur lg:hidden">
        <Logo />
        <Button size="sm" variant="secondary" onClick={() => setMenuOpen(true)}>
          ☰ Menu
        </Button>
      </header>

      <main className="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-10 lg:py-10">{children}</main>
    </div>
  )
}

function NavItem({ href, emoji, children }: { href: string; emoji: string; children: ReactNode }) {
  const { url } = usePage()
  const path = url.split('?')[0]
  // Brand sections stay lit on their sub-pages (a post, a location…).
  const active = path === href || (href.startsWith('/s/') && path.startsWith(`${href}/`))
  return (
    <Link
      href={href}
      className={cx(
        'flex items-center gap-3 rounded-xl border-2 px-3 py-2 text-sm font-bold transition',
        active
          ? 'border-ink bg-pop-lime text-ink shadow-[3px_3px_0_0_#fff]'
          : 'border-transparent text-white/70 hover:bg-white/10 hover:text-white',
      )}
    >
      <span className="text-base">{emoji}</span>
      {children}
    </Link>
  )
}

const sectionLabel = 'mb-2 px-3 text-[11px] font-bold tracking-[0.18em] text-white/40 uppercase'

function Sidebar() {
  const { props, url } = usePage<{ site?: Site }>()
  const user = props.auth.user!
  const site = props.site
  const isAdmin = user.role === 'admin'
  const sections = SECTIONS.filter((s) => isAdmin || !s.adminOnly)

  // Stay on the same section (posts/settings/brand) when switching brands.
  const switchSite = (slug: string) => {
    const current = url.split('?')[0].split('/')[3]
    const section = sections.find((s) => s.path === current)?.path ?? 'posts'
    router.visit(`/s/${slug}/${section}`)
  }

  return (
    <>
      <div className="p-5">
        <Logo />
      </div>

      <div className="px-4">
        <BrandSwitcher current={site?.slug} onPick={switchSite} />
      </div>

      <nav className="mt-6 flex-1 space-y-6 overflow-y-auto px-4">
        {site && (
          <div>
            <p className={sectionLabel}>Workspace</p>
            <div className="space-y-1">
              {sections.map((s) => (
                <NavItem key={s.path} href={`/s/${site.slug}/${s.path}`} emoji={s.emoji}>
                  {s.label}
                </NavItem>
              ))}
            </div>
          </div>
        )}
        {isAdmin && (
          <div>
            <p className={sectionLabel}>Agency</p>
            <div className="space-y-1">
              <NavItem href="/sites" emoji="🌈">
                All brands
              </NavItem>
              <NavItem href="/sites/new" emoji="✨">
                New brand
              </NavItem>
              <NavItem href="/users" emoji="👥">
                Team
              </NavItem>
            </div>
          </div>
        )}
      </nav>

      <div className="m-4 flex items-center gap-3 rounded-2xl border-2 border-white/15 bg-white/5 p-3">
        <Link href="/profile" className="flex min-w-0 flex-1 items-center gap-3" title="Your profile">
          <span className="grid size-10 shrink-0 place-items-center rounded-full border-2 border-white bg-pop-pink font-extrabold text-ink">
            {initials(user.name)}
          </span>
          <span className="min-w-0 flex-1">
            <span className="block truncate text-sm font-bold">{user.name}</span>
            <span className="block truncate text-xs text-white/50">{isAdmin ? '👑 Admin' : '✍️ Editor'} · Profile</span>
          </span>
        </Link>
        <LogoutButton />
      </div>
    </>
  )
}

/** 👋 opens an "Are you sure?" box; only Yes logs out. Esc or a click outside closes it. */
function LogoutButton() {
  const [asking, setAsking] = useState(false)
  const [leaving, setLeaving] = useState(false)

  useEffect(() => {
    if (!asking) return
    const onKey = (e: KeyboardEvent) => e.key === 'Escape' && setAsking(false)
    document.addEventListener('keydown', onKey)
    return () => document.removeEventListener('keydown', onKey)
  }, [asking])

  return (
    <>
      <button type="button" title="Log out" aria-label="Log out" className="rounded-lg px-2 py-1 text-lg transition hover:bg-white/10" onClick={() => setAsking(true)}>
        👋
      </button>
      {asking && (
        <div className="fixed inset-0 z-50 grid place-items-center bg-ink/60 p-4 backdrop-blur-sm" onClick={() => setAsking(false)}>
          <div
            role="alertdialog"
            aria-modal="true"
            aria-labelledby="logout-title"
            className="w-full max-w-sm animate-pop-in rounded-2xl border-2 border-ink bg-white p-6 text-center text-ink shadow-hard-lg"
            onClick={(e) => e.stopPropagation()}
          >
            <div className="mb-2 text-4xl">👋</div>
            <h2 id="logout-title" className="text-xl font-extrabold">
              Log out?
            </h2>
            <p className="mt-1 text-sm text-ink/60">Unsaved changes on this page will be lost.</p>
            <div className="mt-5 flex justify-center gap-2">
              <Button variant="secondary" autoFocus onClick={() => setAsking(false)}>
                No, stay
              </Button>
              <Button
                variant="danger"
                disabled={leaving}
                onClick={() => router.post('/logout', {}, { onStart: () => setLeaving(true), onFinish: () => setLeaving(false) })}
              >
                {leaving ? 'Logging out…' : 'Yes, log out'}
              </Button>
            </div>
          </div>
        </div>
      )}
    </>
  )
}

function BrandSwitcher({ current, onPick }: { current?: string; onPick: (slug: string) => void }) {
  const { sites } = usePage().props
  const [open, setOpen] = useState(false)
  const ref = useRef<HTMLDivElement>(null)
  const site = sites.find((s) => s.slug === current)

  useEffect(() => {
    if (!open) return
    const onClick = (e: MouseEvent) => {
      if (!ref.current?.contains(e.target as Node)) setOpen(false)
    }
    const onKey = (e: KeyboardEvent) => {
      if (e.key === 'Escape') setOpen(false)
    }
    document.addEventListener('mousedown', onClick)
    document.addEventListener('keydown', onKey)
    return () => {
      document.removeEventListener('mousedown', onClick)
      document.removeEventListener('keydown', onKey)
    }
  }, [open])

  return (
    <div ref={ref} className="relative">
      <button
        type="button"
        aria-haspopup="listbox"
        aria-expanded={open}
        onClick={() => setOpen((o) => !o)}
        className="flex w-full items-center gap-3 rounded-2xl border-2 border-white/15 bg-white/5 p-2.5 text-left transition hover:border-white/40"
      >
        {site ? (
          <BrandAvatar name={site.name} slug={site.slug} />
        ) : (
          <span className="grid size-10 place-items-center rounded-xl border-2 border-dashed border-white/40 text-lg">🏷️</span>
        )}
        <span className="min-w-0 flex-1">
          <span className="block truncate font-bold">{site?.name ?? 'Pick a brand'}</span>
          <span className="block text-xs text-white/50">{site ? 'Switch brand' : plural(sites.length, 'brand')}</span>
        </span>
        <span className={cx('text-xs transition-transform', open && 'rotate-180')}>▼</span>
      </button>

      {open && (
        <div
          role="listbox"
          className="absolute inset-x-0 top-full z-50 mt-2 max-h-80 animate-pop-in overflow-y-auto rounded-2xl border-2 border-ink bg-white p-1.5 text-ink shadow-[5px_5px_0_0_var(--color-pop-lime)]"
        >
          {sites.map((s) => (
            <button
              key={s.slug}
              type="button"
              role="option"
              aria-selected={s.slug === current}
              onClick={() => {
                setOpen(false)
                onPick(s.slug)
              }}
              className={cx(
                'flex w-full items-center gap-3 rounded-xl px-2 py-2 text-left transition hover:bg-pop-yellow/40',
                s.slug === current && 'bg-pop-lime/40',
              )}
            >
              <BrandAvatar name={s.name} slug={s.slug} size="sm" />
              <span className="min-w-0 flex-1">
                <span className="block truncate text-sm font-bold">{s.name}</span>
                <span className="block truncate text-xs text-ink/50">{s.url.replace(/^https?:\/\//, '')}</span>
              </span>
              {!s.is_active && <span className="text-xs text-ink/40">off</span>}
              {s.slug === current && <span className="font-extrabold">✓</span>}
            </button>
          ))}
          {sites.length === 0 && <p className="px-3 py-2 text-sm text-ink/50">No brands assigned yet</p>}
        </div>
      )}
    </div>
  )
}
