import { useState } from 'react'
import { Head, router, useForm } from '@inertiajs/react'
import { brandTone, initials } from '@/lib/theme'
import { cx, plural } from '@/lib/utils'
import type { Author, Site } from '@/types'
import { Badge, BrandAvatar, Button, Card, EmptyState, Field, Input, PageHeader, Switch, Textarea } from '@/components/ui'
import { ImageInput } from '@/components/ImageInput'

type Form = { name: string; role: string; photo: string; bio: string; is_default: boolean }

const toForm = (author: Author | null, isFirst: boolean): Form => ({
  name: author?.name ?? '',
  role: author?.role ?? '',
  photo: author?.photo ?? '',
  bio: author?.bio ?? '',
  is_default: author?.is_default ?? isFirst,
})

function Avatar({ author, size = 'size-14' }: { author: Pick<Author, 'name' | 'photo'>; size?: string }) {
  return author.photo ? (
    <img src={author.photo} alt="" className={cx(size, 'shrink-0 rounded-full border-2 border-ink object-cover')} />
  ) : (
    <span className={cx(size, 'grid shrink-0 place-items-center rounded-full border-2 border-ink font-extrabold', brandTone(author.name || '?'))}>
      {initials(author.name || '?')}
    </span>
  )
}

export default function Authors({ site, authors }: { site: Site; authors: Author[] }) {
  // null = closed, 'new' = creating, number = editing that author
  const [editing, setEditing] = useState<number | 'new' | null>(authors.length ? null : 'new')
  const current = typeof editing === 'number' ? (authors.find((a) => a.id === editing) ?? null) : null

  return (
    <>
      <Head title={`Authors · ${site.name}`} />
      <PageHeader
        title="Authors"
        emoji="🖋️"
        eyebrow={
          <>
            <BrandAvatar name={site.name} slug={site.slug} size="sm" />
            <span className="font-bold text-ink/60">{site.name}</span>
          </>
        }
      >
        <Button variant="pop" onClick={() => setEditing('new')}>
          ➕ New author
        </Button>
      </PageHeader>

      <p className="mb-6 max-w-2xl text-sm text-ink/65">
        Write a writer&apos;s profile once and it shows in a box under their posts. The <b>⭐ default</b> writer appears on every post
        that doesn&apos;t pick someone else. Edit a profile here and every post updates; hide the box per post in the editor.
      </p>

      <div className="grid items-start gap-6 lg:grid-cols-[1fr_24rem]">
        <div className="space-y-3">
          {authors.length === 0 && (
            <EmptyState emoji="🖋️" title="No authors yet">
              Add your first writer. They become the default for every post.
            </EmptyState>
          )}
          {authors.map((a) => (
            <article
              key={a.id}
              className={cx(
                'flex gap-4 rounded-2xl border-2 border-ink bg-white p-4 shadow-hard-sm transition',
                editing === a.id && 'ring-4 ring-pop-violet/30',
              )}
            >
              <Avatar author={a} />
              <div className="min-w-0 flex-1">
                <div className="flex flex-wrap items-center gap-2">
                  <h2 className="font-extrabold">{a.name}</h2>
                  {/* The default writer also covers every post without a writer, so a count would undersell it. */}
                  {a.is_default ? <Badge tone="yellow">⭐ Default</Badge> : <Badge>{plural(a.posts_count ?? 0, 'post')}</Badge>}
                </div>
                {a.role && <p className="text-sm font-semibold text-ink/60">{a.role}</p>}
                {a.bio && <p className="mt-1 line-clamp-2 text-sm text-ink/70">{a.bio}</p>}
                <div className="mt-3 flex flex-wrap gap-2">
                  <Button size="sm" variant="secondary" onClick={() => setEditing(a.id)}>
                    ✏️ Edit
                  </Button>
                  {!a.is_default && (
                    <Button
                      size="sm"
                      variant="ghost"
                      onClick={() => router.put(`/s/${site.slug}/authors/${a.id}`, { ...toForm(a, false), is_default: true }, { preserveScroll: true })}
                    >
                      ⭐ Make default
                    </Button>
                  )}
                  <Button
                    size="sm"
                    variant="ghost"
                    onClick={() =>
                      confirm(`Remove ${a.name}? Their posts will show the default writer instead.`) &&
                      router.delete(`/s/${site.slug}/authors/${a.id}`, {
                        preserveScroll: true,
                        onSuccess: () => editing === a.id && setEditing(null),
                      })
                    }
                  >
                    🗑️ Remove
                  </Button>
                </div>
              </div>
            </article>
          ))}
        </div>

        {editing !== null && (
          <AuthorForm
            // Fresh form per author.
            key={editing}
            site={site}
            author={current}
            isFirst={authors.length === 0}
            onDone={() => setEditing(null)}
          />
        )}
      </div>
    </>
  )
}

function AuthorForm({ site, author, isFirst, onDone }: { site: Site; author: Author | null; isFirst: boolean; onDone: () => void }) {
  const form = useForm<Form>(toForm(author, isFirst))
  const { data, errors } = form

  const submit = () => {
    const options = { preserveScroll: true, onSuccess: onDone }
    if (author) form.put(`/s/${site.slug}/authors/${author.id}`, options)
    else form.post(`/s/${site.slug}/authors`, options)
  }

  return (
    <form
      className="space-y-4 lg:sticky lg:top-6"
      onSubmit={(e) => {
        e.preventDefault()
        submit()
      }}
    >
      <Card title={author ? `Edit ${author.name}` : 'New author'} icon={author ? '✏️' : '✨'} tone="violet">
        <Field label="Name" error={errors.name}>
          <Input required value={data.name} placeholder="Neale Editorial" onChange={(e) => form.setData('name', e.target.value)} />
        </Field>
        <Field label="Title" error={errors.role} hint="Optional, e.g. Senior Editor.">
          <Input value={data.role} onChange={(e) => form.setData('role', e.target.value)} />
        </Field>
        <ImageInput label="Photo" hint="A square photo looks best." value={data.photo} onChange={(v) => form.setData('photo', v)} />
        <Field label="About the writer" error={errors.bio}>
          <Textarea rows={5} value={data.bio} placeholder="A few lines about who they are…" onChange={(e) => form.setData('bio', e.target.value)} />
        </Field>
        <Switch
          checked={data.is_default}
          onChange={(v) => form.setData('is_default', v)}
          label={<span>⭐ Default writer, shown on every post that doesn&apos;t pick one</span>}
        />
        <div className="flex gap-2">
          <Button type="submit" variant="pop" disabled={form.processing}>
            {form.processing ? 'Saving…' : author ? '💾 Save' : '➕ Add author'}
          </Button>
          <Button variant="ghost" onClick={onDone}>
            Cancel
          </Button>
        </div>
      </Card>

      {/* Roughly how the box looks under a post. */}
      <div className="rounded-2xl border-2 border-dashed border-ink/30 bg-white/70 p-4">
        <p className="mb-3 text-[11px] font-bold tracking-widest text-ink/45 uppercase">Preview under each post</p>
        <div className="flex gap-3">
          <Avatar author={data} size="size-12" />
          <div className="min-w-0">
            <p className="text-xs font-bold tracking-wider text-ink/45 uppercase">Written by</p>
            <p className="font-extrabold">{data.name || 'Writer name'}</p>
            {data.role && <p className="text-xs font-semibold text-ink/60">{data.role}</p>}
            <p className="mt-1 text-sm whitespace-pre-line text-ink/70">{data.bio || 'About the writer…'}</p>
          </div>
        </div>
      </div>
    </form>
  )
}
