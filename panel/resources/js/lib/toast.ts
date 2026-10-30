import { useSyncExternalStore } from 'react'

export type ToastTone = 'success' | 'error' | 'info'
/** A link button inside the snackbar: external URLs open in a new tab, app paths visit in place. */
export type ToastAction = { label: string; href: string }
export type Toast = { id: number; message: string; tone: ToastTone; action?: ToastAction }

let toasts: Toast[] = []
let nextId = 1
const listeners = new Set<() => void>()
const emit = () => listeners.forEach((listener) => listener())

/** Show a snackbar. It hides itself (see Toaster), so there is no timer here. */
export function toast(message: string, tone: ToastTone = 'success', action?: ToastAction) {
  // The same message twice in a row (double click, repeated error) shows once.
  if (toasts.at(-1)?.message === message) return
  toasts = [...toasts, { id: nextId++, message, tone, action }].slice(-4)
  emit()
}

export function dismiss(id: number) {
  toasts = toasts.filter((t) => t.id !== id)
  emit()
}

export function useToasts() {
  return useSyncExternalStore(
    (listener) => {
      listeners.add(listener)
      return () => {
        listeners.delete(listener)
      }
    },
    () => toasts,
  )
}
