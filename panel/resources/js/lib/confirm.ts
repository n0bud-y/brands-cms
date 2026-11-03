import { useSyncExternalStore } from 'react'

export type Question = {
  title: string
  message?: string
  /** Label of the "yes" button. */
  yes?: string
  no?: string
  emoji?: string
  /** Red "yes" button for things that remove something. */
  danger?: boolean
}

type Open = Question & { resolve: (yes: boolean) => void }

let open: Open | null = null
const listeners = new Set<() => void>()
const emit = () => listeners.forEach((listener) => listener())

/** Shows the "Are you sure?" popup; resolves true for yes, false for no / Esc / click outside. */
export function ask(question: Question): Promise<boolean> {
  open?.resolve(false)
  return new Promise((resolve) => {
    open = { ...question, resolve }
    emit()
  })
}

export function answer(yes: boolean) {
  open?.resolve(yes)
  open = null
  emit()
}

export function useQuestion() {
  return useSyncExternalStore(
    (listener) => {
      listeners.add(listener)
      return () => {
        listeners.delete(listener)
      }
    },
    () => open,
  )
}
