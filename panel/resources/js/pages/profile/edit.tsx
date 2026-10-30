import { Head, useForm, usePage } from '@inertiajs/react'
import { brandTone, initials } from '@/lib/theme'
import { cx } from '@/lib/utils'
import { Button, Card, Field, Input, PageHeader } from '@/components/ui'
import { RoleBadge } from '@/components/StatusBadge'

/** Every user's own account. */
export default function Profile() {
  const user = usePage().props.auth.user!
  const details = useForm({ name: user.name, email: user.email })
  const password = useForm({ current_password: '', password: '', password_confirmation: '' })

  return (
    <>
      <Head title="Your profile" />
      <PageHeader title="Your profile" emoji="😎" eyebrow={<RoleBadge role={user.role} />} />

      <div className="grid gap-8 lg:grid-cols-[1fr_20rem]">
        <div className="space-y-6">
          <form
            onSubmit={(e) => {
              e.preventDefault()
              details.put('/profile', { preserveScroll: true })
            }}
          >
            <Card title="Details" icon="🙋" tone="yellow">
              <div className="grid gap-4 sm:grid-cols-2">
                <Field label="Name" error={details.errors.name}>
                  <Input required value={details.data.name} onChange={(e) => details.setData('name', e.target.value)} />
                </Field>
                <Field label="Email" error={details.errors.email} hint="Used to sign in.">
                  <Input required type="email" value={details.data.email} onChange={(e) => details.setData('email', e.target.value)} />
                </Field>
              </div>
              <div className="flex justify-end">
                <Button type="submit" variant="pop" disabled={details.processing || !details.isDirty}>
                  💾 Save details
                </Button>
              </div>
            </Card>
          </form>

          <form
            onSubmit={(e) => {
              e.preventDefault()
              password.put('/profile/password', { preserveScroll: true, onSuccess: () => password.reset() })
            }}
          >
            <Card title="Password" icon="🔒" tone="violet">
              <Field label="Current password" error={password.errors.current_password}>
                <Input
                  type="password"
                  autoComplete="current-password"
                  required
                  value={password.data.current_password}
                  onChange={(e) => password.setData('current_password', e.target.value)}
                />
              </Field>
              <div className="grid gap-4 sm:grid-cols-2">
                <Field label="New password" error={password.errors.password} hint="At least 8 characters.">
                  <Input
                    type="password"
                    autoComplete="new-password"
                    required
                    value={password.data.password}
                    onChange={(e) => password.setData('password', e.target.value)}
                  />
                </Field>
                <Field label="Repeat new password">
                  <Input
                    type="password"
                    autoComplete="new-password"
                    required
                    value={password.data.password_confirmation}
                    onChange={(e) => password.setData('password_confirmation', e.target.value)}
                  />
                </Field>
              </div>
              <div className="flex justify-end">
                <Button type="submit" disabled={password.processing}>
                  🔒 Change password
                </Button>
              </div>
            </Card>
          </form>
        </div>

        <aside className="h-fit rounded-2xl border-2 border-ink bg-white p-6 text-center shadow-hard">
          <span className={cx('mx-auto grid size-20 place-items-center rounded-full border-2 border-ink text-2xl font-extrabold', brandTone(user.email))}>
            {initials(details.data.name || user.name)}
          </span>
          <p className="mt-3 text-lg font-extrabold">{details.data.name || user.name}</p>
          <p className="truncate text-sm text-ink/55">{details.data.email}</p>
          <div className="mt-3">
            <RoleBadge role={user.role} />
          </div>
        </aside>
      </div>
    </>
  )
}
