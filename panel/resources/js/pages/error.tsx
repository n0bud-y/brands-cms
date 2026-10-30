import { Head, Link } from '@inertiajs/react'
import { buttonClass } from '@/components/ui'

const MESSAGES: Record<number, { emoji: string; title: string; text: string }> = {
  403: { emoji: '🚧', title: "You can't go here", text: "Your account doesn't have access to this page. Ask an admin if you need it." },
  404: { emoji: '🛸', title: 'This page left the chat', text: "It doesn't exist, or it was moved or deleted." },
  500: { emoji: '💥', title: 'Something broke', text: 'An unexpected error happened on the panel. Try again in a moment.' },
  503: { emoji: '🛠️', title: 'Back in a bit', text: 'The panel is under maintenance.' },
}

export default function ErrorPage({ status }: { status: number }) {
  const msg = MESSAGES[status] ?? MESSAGES[500]

  return (
    <main className="grid min-h-screen place-items-center px-4">
      <Head title={msg.title} />
      <div className="w-full max-w-md animate-pop-in rounded-3xl border-2 border-ink bg-white p-8 text-center shadow-hard-lg">
        <div className="mb-2 inline-block animate-float text-6xl">{msg.emoji}</div>
        <p className="text-sm font-extrabold tracking-widest text-ink/40">ERROR {status}</p>
        <h1 className="mt-1 text-3xl font-extrabold tracking-tight">{msg.title}</h1>
        <p className="mt-2 text-sm text-ink/65">{msg.text}</p>
        <Link href="/" className={`${buttonClass({ variant: 'pop' })} mt-6`}>
          ← Take me home
        </Link>
      </div>
    </main>
  )
}
