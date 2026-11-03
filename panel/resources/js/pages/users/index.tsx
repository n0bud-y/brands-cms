import { Head, Link, router, usePage } from '@inertiajs/react'
import { brandTone, initials } from '@/lib/theme'
import { cx, formatDate, plural } from '@/lib/utils'
import type { Role } from '@/types'
import { Badge, Button, buttonClass, PageHeader } from '@/components/ui'
import { RoleBadge } from '@/components/StatusBadge'
import { ask } from '@/lib/confirm'

type TeamMember = {
  id: number
  name: string
  email: string
  role: Role
  sites: { id: number; name: string; slug: string }[]
  created_at: string | null
}

export default function Users({ users }: { users: TeamMember[] }) {
  const me = usePage().props.auth.user

  return (
    <>
      <Head title="Team" />
      <PageHeader title="Team" emoji="👥" eyebrow={<Badge tone="pink">{plural(users.length, 'person')}</Badge>}>
        <Link href="/users/new" className={buttonClass({ variant: 'pop' })}>
          ➕ Add person
        </Link>
      </PageHeader>

      <div className="mb-6 grid gap-4 sm:grid-cols-2">
        <div className="rounded-2xl border-2 border-ink bg-pop-violet p-4 text-white shadow-hard-sm">
          <p className="font-extrabold">👑 Admin</p>
          <p className="text-sm text-white/80">Everything: all brands, website connections, brand settings, deleting, and the team.</p>
        </div>
        <div className="rounded-2xl border-2 border-ink bg-pop-cyan p-4 shadow-hard-sm">
          <p className="font-extrabold">✍️ Editor</p>
          <p className="text-sm text-ink/75">Writes and publishes posts, edits SEO, schema and settings, only on the brands assigned to them.</p>
        </div>
      </div>

      <ul className="divide-y-2 divide-ink/10 overflow-hidden rounded-2xl border-2 border-ink bg-white shadow-hard">
        {users.map((user) => (
          <li key={user.id} className="flex flex-wrap items-center gap-4 px-4 py-3.5 transition hover:bg-pop-yellow/15">
            <span className={cx('grid size-11 shrink-0 place-items-center rounded-full border-2 border-ink font-extrabold', brandTone(user.email))}>
              {initials(user.name)}
            </span>
            <div className="min-w-0 flex-1">
              <p className="flex items-center gap-2 font-bold">
                {user.name}
                {user.id === me?.id && <span className="text-xs font-semibold text-ink/45">(you)</span>}
              </p>
              <p className="truncate text-xs text-ink/55">
                {user.email} · joined {formatDate(user.created_at)}
              </p>
            </div>
            <RoleBadge role={user.role} />
            <div className="flex w-full flex-wrap gap-1.5 sm:w-64">
              {user.role === 'admin' ? (
                <span className="text-xs font-semibold text-ink/50">All brands</span>
              ) : user.sites.length ? (
                user.sites.map((s) => (
                  <span key={s.id} className={cx('rounded-full border-2 border-ink px-2 py-0.5 text-[11px] font-bold', brandTone(s.slug))}>
                    {s.name}
                  </span>
                ))
              ) : (
                <span className="text-xs font-semibold text-pop-red">No brands assigned</span>
              )}
            </div>
            <div className="flex shrink-0 items-center gap-1">
              <Link href={`/users/${user.id}`} className={buttonClass({ variant: 'secondary', size: 'sm' })}>
                Edit
              </Link>
              {user.id !== me?.id && (
                <Button
                  size="sm"
                  variant="ghost"
                  title="Remove"
                  onClick={async () =>
                    (await ask({ title: `Remove ${user.name} from the team?`, message: 'They can no longer log in.', yes: 'Yes, remove', danger: true })) &&
                    router.delete(`/users/${user.id}`, { preserveScroll: true })
                  }
                >
                  🗑️
                </Button>
              )}
            </div>
          </li>
        ))}
      </ul>
    </>
  )
}
