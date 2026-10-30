import { useEffect, useRef } from 'react'
import { router } from '@inertiajs/react'

/** Ctrl/Cmd+S saves; leaving with unsaved changes asks first (browser close and in-app links). */
export function useEditorGuard(submit: () => void, dirty: boolean) {
  const latest = useRef({ submit, dirty })
  useEffect(() => {
    latest.current = { submit, dirty }
  })
  useEffect(() => {
    const onKey = (e: KeyboardEvent) => {
      if ((e.ctrlKey || e.metaKey) && e.key === 's') {
        e.preventDefault()
        latest.current.submit()
      }
    }
    const onUnload = (e: BeforeUnloadEvent) => {
      if (latest.current.dirty) e.preventDefault()
    }
    window.addEventListener('keydown', onKey)
    window.addEventListener('beforeunload', onUnload)
    const offBefore = router.on('before', (event) => {
      const leaving = event.detail.visit.method === 'get'
      if (leaving && latest.current.dirty && !confirm('You have unsaved changes. Leave without saving?')) event.preventDefault()
    })
    return () => {
      window.removeEventListener('keydown', onKey)
      window.removeEventListener('beforeunload', onUnload)
      offBefore()
    }
  }, [])
}
