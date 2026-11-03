import { useEffect } from 'react'
import { answer, useQuestion } from '@/lib/confirm'
import { Button } from '@/components/ui'

/** The panel's one "Are you sure?" popup (see lib/confirm.ts). Esc or a click outside means no. */
export function ConfirmDialog() {
  const q = useQuestion()

  useEffect(() => {
    if (!q) return
    const onKey = (e: KeyboardEvent) => {
      if (e.key === 'Escape') answer(false)
    }
    document.addEventListener('keydown', onKey)
    return () => document.removeEventListener('keydown', onKey)
  }, [q])

  if (!q) return null

  return (
    <div className="fixed inset-0 z-[60] grid place-items-center bg-ink/60 p-4 backdrop-blur-sm" onClick={() => answer(false)}>
      <div
        role="alertdialog"
        aria-modal="true"
        aria-labelledby="confirm-title"
        className="w-full max-w-sm animate-pop-in rounded-2xl border-2 border-ink bg-white p-6 text-center text-ink shadow-hard-lg"
        onClick={(e) => e.stopPropagation()}
      >
        <div className="mb-2 text-4xl">{q.emoji ?? (q.danger ? '🗑️' : '🤔')}</div>
        <h2 id="confirm-title" className="text-xl font-extrabold">
          {q.title}
        </h2>
        {q.message && <p className="mt-1 text-sm text-ink/60">{q.message}</p>}
        <div className="mt-5 flex justify-center gap-2">
          <Button variant="secondary" autoFocus onClick={() => answer(false)}>
            {q.no ?? 'No'}
          </Button>
          <Button variant={q.danger ? 'danger' : 'pop'} onClick={() => answer(true)}>
            {q.yes ?? 'Yes'}
          </Button>
        </div>
      </div>
    </div>
  )
}
