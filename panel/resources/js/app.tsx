import type { ReactNode } from 'react'
import { createRoot } from 'react-dom/client'
import { createInertiaApp, router, type ResolvedComponent } from '@inertiajs/react'
import AppLayout from '@/components/AppLayout'
import { Toaster } from '@/components/ui'
import { toast } from '@/lib/toast'

// Every page is its own chunk; the login screen never downloads the editor.
const pages = import.meta.glob<{ default: ResolvedComponent }>('./pages/**/*.tsx')

// Pages rendered without the sidebar shell.
const BARE_PAGES = ['auth/login', 'error']

// Snackbars for everything that happens: server messages (Inertia::flash), failed validation, lost connection.
router.on('flash', ({ detail }) => {
  const t = detail.flash.toast
  if (t) toast(t.message, t.tone, t.action)
})
router.on('error', ({ detail }) => {
  // The login form explains its own errors.
  if (detail.page?.component !== 'auth/login') toast('Some fields need a fix. They are highlighted on the page.', 'error')
})
router.on('networkError', () => toast("Can't reach the server. Check your connection and try again.", 'error'))
router.on('httpException', (event) => {
  const { status } = event.detail.response
  if (status === 413) toast('That file is too big for the server.', 'error')
  // In development, keep Inertia's error overlay; it shows the stack trace.
  else if (import.meta.env.DEV) return
  else toast(`Something went wrong (error ${status}). Please try again.`, 'error')
  event.preventDefault()
})

createInertiaApp({
  title: (title) => (title ? `${title} · brand.cms` : 'brand.cms'),
  resolve: async (name) => {
    const page = (await pages[`./pages/${name}.tsx`]()).default
    if (!BARE_PAGES.includes(name)) page.layout ??= (child: ReactNode) => <AppLayout>{child}</AppLayout>
    return page
  },
  setup({ el, App, props }) {
    createRoot(el).render(
      <>
        <App {...props} />
        <Toaster />
      </>,
    )
  },
  progress: { color: '#c6f432', showSpinner: false },
})
