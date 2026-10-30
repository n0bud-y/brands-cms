import { Head, Link, useForm, usePage } from '@inertiajs/react'
import { brandTone, initials } from '@/lib/theme'
import { cx, firstError } from '@/lib/utils'
import type { Role } from '@/types'
import { Button, buttonClass, Card, Field, Input, PageHeader, Segmented } from '@/components/ui'

type EditableUser = { id: number; name: string; email: string; role: Role; site_ids: number[] }
type SiteOption = { id: number; name: string; slug: string }

export default function UserForm({ user, allSites }: { user: EditableUser | null; allSites: SiteOption[] }) {
  const me = usePage().props.auth.user
  const isMe = user?.id === me?.id
  const form = useForm({
    name: user?.name ?? '',
    email: user?.email ?? '',
    password: '',
    role: user?.role ?? ('editor' as Role),
    site_ids: user?.site_ids ?? ([] as number[]),
  })
  const { data, errors } = form

  const toggleSite = (id: number) =>
    form.setData('site_ids', data.site_ids.includes(id) ? data.site_ids.filter((s) => s !== id) : [...data.site_ids, id])

  return (
    <>
      <Head title={user ? `Edit ${user.name}` : 'Add person'} />
      <PageHeader title={user ? 'Edit person' : 'Add person'} emoji={user ? '✏️' : '👋'}>
        <Link href="/users" className={buttonClass({ variant: 'secondary' })}>
          ← Team
        </Link>
      </PageHeader>

      <form
        className="grid gap-8 lg:grid-cols-[1fr_22rem]"
        onSubmit={(e) => {
          e.preventDefault()
          if (user) form.put(`/users/${user.id}`)
          else form.post('/users')
        }}
      >
        <div className="space-y-6">
          <Card title="Account" icon="🙋" tone="yellow">
            <div className="grid gap-4 sm:grid-cols-2">
              <Field label="Name" error={errors.name}>
                <Input required value={data.name} onChange={(e) => form.setData('name', e.target.value)} />
              </Field>
              <Field label="Email" error={errors.email}>
                <Input required type="email" value={data.email} onChange={(e) => form.setData('email', e.target.value)} />
              </Field>
            </div>
            <Field
              label={user ? 'New password' : 'Password'}
              error={errors.password}
              hint={user ? 'Leave empty to keep their current password.' : 'At least 8 characters. Share it with them securely.'}
            >
              <Input
                type="password"
                autoComplete="new-password"
                required={!user}
                value={data.password}
                onChange={(e) => form.setData('password', e.target.value)}
              />
            </Field>
          </Card>

          <Card title="Role" icon="🎭" tone="violet">
            <Segmented<Role>
              value={data.role}
              onChange={(role) => !isMe && form.setData('role', role)}
              options={[
                { value: 'editor', label: '✍️ Editor', tone: 'cyan' },
                { value: 'admin', label: '👑 Admin', tone: 'violet' },
              ]}
            />
            {errors.role && <p className="text-sm font-semibold text-pop-red">⚠ {errors.role}</p>}
            <p className="text-sm text-ink/60">
              {isMe
                ? "This is you. You can't change your own role."
                : data.role === 'admin'
                  ? 'Admins can do everything, on every brand, including managing the team.'
                  : 'Editors write and publish posts and edit settings, only on the brands ticked below.'}
            </p>
          </Card>

          {data.role === 'editor' && (
            <Card title="Brand access" icon="🔐" tone="cyan">
              <p className="text-sm text-ink/60">Which brands can this person work on?</p>
              <div className="grid gap-2.5 sm:grid-cols-2">
                {allSites.map((site) => {
                  const on = data.site_ids.includes(site.id)
                  return (
                    <button
                      key={site.id}
                      type="button"
                      aria-pressed={on}
                      onClick={() => toggleSite(site.id)}
                      className={cx(
                        'flex items-center gap-3 rounded-xl border-2 p-2.5 text-left transition',
                        on ? 'border-ink bg-pop-lime shadow-hard-sm' : 'border-ink/20 bg-white hover:border-ink',
                      )}
                    >
                      <span className={cx('grid size-8 shrink-0 place-items-center rounded-lg border-2 border-ink text-[11px] font-extrabold', brandTone(site.slug))}>
                        {initials(site.name)}
                      </span>
                      <span className="min-w-0 flex-1 truncate text-sm font-bold">{site.name}</span>
                      <span className={cx('grid size-6 place-items-center rounded-md border-2 border-ink text-xs font-extrabold', on ? 'bg-ink text-pop-lime' : 'bg-white')}>
                        {on ? '✓' : ''}
                      </span>
                    </button>
                  )
                })}
              </div>
              <div className="flex gap-2">
                <Button size="sm" variant="ghost" onClick={() => form.setData('site_ids', allSites.map((s) => s.id))}>
                  Select all
                </Button>
                <Button size="sm" variant="ghost" onClick={() => form.setData('site_ids', [])}>
                  Clear
                </Button>
              </div>
              {firstError(errors, 'site_ids') && <p className="text-sm font-semibold text-pop-red">⚠ {firstError(errors, 'site_ids')}</p>}
            </Card>
          )}
        </div>

        <aside className="h-fit space-y-4 lg:sticky lg:top-6">
          <div className="rounded-2xl border-2 border-ink bg-white p-5 text-center shadow-hard">
            <span className={cx('mx-auto grid size-16 place-items-center rounded-full border-2 border-ink text-xl font-extrabold', brandTone(data.email || 'new'))}>
              {initials(data.name || '?')}
            </span>
            <p className="mt-3 font-extrabold">{data.name || 'New person'}</p>
            <p className="truncate text-sm text-ink/55">{data.email || 'email@agency.com'}</p>
            <p className="mt-2 text-xs font-bold text-ink/60">
              {data.role === 'admin' ? '👑 Admin · all brands' : `✍️ Editor · ${data.site_ids.length} brand${data.site_ids.length === 1 ? '' : 's'}`}
            </p>
          </div>
          <Button type="submit" variant="pop" size="lg" className="w-full" disabled={form.processing}>
            {form.processing ? 'Saving…' : user ? '💾 Save person' : '➕ Add to team'}
          </Button>
        </aside>
      </form>
    </>
  )
}
