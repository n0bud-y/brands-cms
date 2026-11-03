import { useState } from 'react'
import { router, usePage } from '@inertiajs/react'
import type { Site } from '@/types'
import { BrandAvatar, Button } from '@/components/ui'

/** 🔀 button + a popup to pick the brand a post really belongs to (only brands this user can work on). */
export function MoveToBrand({ site, postId, title, wide }: { site: Site; postId: number; title: string; wide?: boolean }) {
  const { sites } = usePage().props
  const others = sites.filter((s) => s.id !== site.id)
  const [open, setOpen] = useState(false)
  const [target, setTarget] = useState<number | null>(null)
  const [moving, setMoving] = useState(false)

  if (others.length === 0) return null

  const close = () => {
    setOpen(false)
    setTarget(null)
  }

  return (
    <>
      <Button
        size={wide ? 'md' : 'sm'}
        variant={wide ? 'secondary' : 'ghost'}
        className={wide ? 'w-full' : undefined}
        title="Move to another brand"
        aria-label="Move to another brand"
        onClick={() => setOpen(true)}
      >
        🔀{wide && ' Move to another brand'}
      </Button>
      {open && (
        <div className="fixed inset-0 z-[60] grid place-items-center bg-ink/60 p-4 backdrop-blur-sm" onClick={close}>
          <div
            role="dialog"
            aria-modal="true"
            aria-labelledby="move-title"
            className="w-full max-w-md animate-pop-in rounded-2xl border-2 border-ink bg-white p-6 text-ink shadow-hard-lg"
            onClick={(e) => e.stopPropagation()}
            onKeyDown={(e) => e.key === 'Escape' && close()}
          >
            <div className="mb-2 text-center text-4xl">🔀</div>
            <h2 id="move-title" className="text-center text-xl font-extrabold">
              Move to another brand
            </h2>
            <p className="mt-1 text-center text-sm text-ink/60">
              “{title}” leaves {site.name} and goes to the brand you pick, with its content, images and SEO. The writer becomes that
              brand&apos;s default writer.
            </p>
            <div className="mt-4 max-h-64 space-y-1.5 overflow-y-auto">
              {others.map((s) => (
                <button
                  key={s.id}
                  type="button"
                  onClick={() => setTarget(s.id)}
                  className={`flex w-full items-center gap-3 rounded-xl border-2 px-3 py-2 text-left transition ${
                    target === s.id ? 'border-ink bg-pop-lime shadow-hard-sm' : 'border-ink/15 hover:border-ink'
                  }`}
                >
                  <BrandAvatar name={s.name} slug={s.slug} size="sm" />
                  <span className="min-w-0 flex-1">
                    <span className="block truncate text-sm font-bold">{s.name}</span>
                    <span className="block truncate text-xs text-ink/50">{s.url.replace(/^https?:\/\//, '')}</span>
                  </span>
                  {target === s.id && <span className="font-extrabold">✓</span>}
                </button>
              ))}
            </div>
            <div className="mt-5 flex justify-center gap-2">
              <Button variant="secondary" onClick={close}>
                Cancel
              </Button>
              <Button
                variant="pop"
                disabled={target === null || moving}
                onClick={() =>
                  router.post(
                    `/s/${site.slug}/posts/${postId}/move`,
                    { site_id: target },
                    { onStart: () => setMoving(true), onFinish: () => setMoving(false), onSuccess: close },
                  )
                }
              >
                {moving ? 'Moving…' : '🔀 Move post'}
              </Button>
            </div>
          </div>
        </div>
      )}
    </>
  )
}
