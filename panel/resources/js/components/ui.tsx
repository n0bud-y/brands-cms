import type { ComponentProps, ReactNode } from 'react'
import { Link } from '@inertiajs/react'
import { brandTone, initials, tones, type Tone } from '@/lib/theme'
import { dismiss, useToasts } from '@/lib/toast'
import { cx } from '@/lib/utils'

const variants = {
  primary: tones.violet,
  pop: tones.lime,
  secondary: tones.white,
  danger: tones.red,
}

type ButtonStyle = { variant?: keyof typeof variants | 'ghost'; size?: 'sm' | 'md' | 'lg' }

export const buttonClass = ({ variant = 'primary', size = 'md' }: ButtonStyle = {}) =>
  cx(
    'inline-flex items-center justify-center gap-1.5 whitespace-nowrap rounded-xl font-bold transition-all duration-150 select-none',
    'disabled:pointer-events-none disabled:opacity-45',
    size === 'sm' ? 'h-8 px-3 text-sm' : size === 'lg' ? 'h-12 px-6 text-base' : 'h-10 px-4 text-sm',
    variant === 'ghost'
      ? 'opacity-75 hover:bg-ink/8 hover:opacity-100'
      : cx(
          'border-2 border-ink shadow-hard-sm hover:-translate-x-0.5 hover:-translate-y-0.5 hover:shadow-hard',
          'active:translate-x-0.5 active:translate-y-0.5 active:shadow-none',
          variants[variant],
        ),
  )

export function Button({ variant, size, className, ...props }: ComponentProps<'button'> & ButtonStyle) {
  return <button type="button" {...props} className={cx(buttonClass({ variant, size }), className)} />
}

const control =
  'block w-full rounded-xl border-2 border-ink bg-white px-3.5 py-2 text-sm text-ink outline-none transition placeholder:text-ink/35 focus:border-pop-violet focus:ring-4 focus:ring-pop-violet/25 disabled:bg-ink/5'

export const Input = ({ className, ...props }: ComponentProps<'input'>) => (
  <input {...props} className={cx(control, className)} />
)

export const Textarea = ({ className, rows = 3, ...props }: ComponentProps<'textarea'>) => (
  <textarea rows={rows} {...props} className={cx(control, 'resize-y', className)} />
)

export const Select = ({ className, ...props }: ComponentProps<'select'>) => (
  <div className="relative">
    <select {...props} className={cx(control, 'appearance-none pr-9 font-semibold', className)} />
    <span className="pointer-events-none absolute top-1/2 right-3.5 -translate-y-1/2 text-xs">▼</span>
  </div>
)

/** Label + control + hint/error. Use `group` when the content holds several controls. */
export function Field({
  label,
  hint,
  error,
  group,
  className,
  children,
}: {
  label: ReactNode
  hint?: ReactNode
  error?: string
  group?: boolean
  className?: string
  children: ReactNode
}) {
  const Tag = group ? 'div' : 'label'
  return (
    <Tag className={cx('block', className)}>
      <span className="mb-1.5 block text-sm font-bold">{label}</span>
      {children}
      {error ? (
        <span className="mt-1.5 block text-sm font-semibold text-pop-red">⚠ {error}</span>
      ) : (
        hint && <span className="mt-1.5 block text-xs text-ink/55">{hint}</span>
      )}
    </Tag>
  )
}

export function Card({
  title,
  icon,
  tone = 'white',
  actions,
  className,
  children,
}: {
  title?: ReactNode
  icon?: string
  tone?: Tone
  actions?: ReactNode
  className?: string
  children: ReactNode
}) {
  return (
    <section className={cx('rounded-2xl border-2 border-ink bg-white shadow-hard', className)}>
      {(title || actions) && (
        <header
          className={cx('flex items-center justify-between gap-3 rounded-t-[14px] border-b-2 border-ink px-4 py-3', tones[tone])}
        >
          <h2 className="flex items-center gap-2 font-extrabold tracking-tight">
            {icon && <span className="text-lg leading-none">{icon}</span>}
            {title}
          </h2>
          {actions}
        </header>
      )}
      <div className="space-y-4 p-4 sm:p-5">{children}</div>
    </section>
  )
}

export const Badge = ({ tone = 'white', className, children }: { tone?: Tone; className?: string; children: ReactNode }) => (
  <span
    className={cx(
      'inline-flex items-center gap-1 whitespace-nowrap rounded-full border-2 border-ink px-2.5 py-0.5 text-xs font-bold',
      tones[tone],
      className,
    )}
  >
    {children}
  </span>
)

/** Two-or-more option toggle, e.g. Draft / Publish. */
export function Segmented<T extends string>({
  value,
  options,
  onChange,
}: {
  value: T
  options: { value: T; label: ReactNode; tone?: Tone }[]
  onChange: (value: T) => void
}) {
  return (
    <div className="grid auto-cols-fr grid-flow-col gap-1 rounded-xl border-2 border-ink bg-paper p-1">
      {options.map((option) => (
        <button
          key={option.value}
          type="button"
          onClick={() => onChange(option.value)}
          className={cx(
            'rounded-lg border-2 px-3 py-1.5 text-sm font-bold transition',
            value === option.value
              ? cx('border-ink shadow-hard-sm', tones[option.tone ?? 'violet'])
              : 'border-transparent text-ink/55 hover:text-ink',
          )}
        >
          {option.label}
        </button>
      ))}
    </div>
  )
}

export function Switch({ checked, onChange, label }: { checked: boolean; onChange: (v: boolean) => void; label: ReactNode }) {
  return (
    <label className="flex cursor-pointer items-center gap-3 text-sm font-semibold">
      <button
        type="button"
        role="switch"
        aria-checked={checked}
        onClick={() => onChange(!checked)}
        className={cx('relative h-7 w-12 shrink-0 rounded-full border-2 border-ink transition-colors', checked ? 'bg-pop-lime' : 'bg-ink/10')}
      >
        <span
          className={cx(
            'absolute top-0.5 left-0.5 size-5 rounded-full border-2 border-ink bg-white transition-transform',
            checked && 'translate-x-5',
          )}
        />
      </button>
      {label}
    </label>
  )
}

export function BrandAvatar({ name, slug, size = 'md', className }: { name: string; slug: string; size?: 'sm' | 'md' | 'lg'; className?: string }) {
  return (
    <span
      className={cx(
        'grid shrink-0 place-items-center border-2 border-ink font-extrabold',
        size === 'lg' ? 'size-14 rounded-2xl text-xl' : size === 'sm' ? 'size-7 rounded-lg text-[11px]' : 'size-10 rounded-xl text-sm',
        brandTone(slug),
        className,
      )}
    >
      {initials(name)}
    </span>
  )
}

export const Spinner = () => (
  <span className="inline-flex gap-1.5" role="status" aria-label="Loading">
    {['bg-pop-pink', 'bg-pop-yellow', 'bg-pop-cyan'].map((color, i) => (
      <span
        key={color}
        className={cx('size-3 animate-bounce rounded-full border-2 border-ink', color)}
        style={{ animationDelay: `${i * 0.12}s` }}
      />
    ))}
  </span>
)

export const PageLoader = () => (
  <div className="grid min-h-60 place-items-center">
    <Spinner />
  </div>
)

export const PageHeader = ({
  eyebrow,
  title,
  emoji,
  children,
}: {
  eyebrow?: ReactNode
  title: ReactNode
  emoji?: string
  children?: ReactNode
}) => (
  <div className="mb-8 flex flex-wrap items-end justify-between gap-4">
    <div className="min-w-0">
      {eyebrow && <div className="mb-2 flex items-center gap-2">{eyebrow}</div>}
      <h1 className="text-3xl font-extrabold tracking-tight sm:text-4xl">
        {title}
        {emoji && <span className="ml-2 inline-block origin-bottom animate-wiggle">{emoji}</span>}
      </h1>
    </div>
    {children && <div className="flex flex-wrap items-center gap-2">{children}</div>}
  </div>
)

export const EmptyState = ({ emoji = '✨', title, children }: { emoji?: string; title: string; children?: ReactNode }) => (
  <div className="rounded-2xl border-2 border-dashed border-ink/40 bg-white/70 px-6 py-14 text-center">
    <div className="mb-3 inline-block animate-float text-5xl">{emoji}</div>
    <p className="text-lg font-extrabold">{title}</p>
    {children && <div className="mt-2 text-sm text-ink/60">{children}</div>}
  </div>
)

const TOAST_STYLE = {
  success: { emoji: '✅', bg: 'bg-pop-lime', ms: 4500 },
  info: { emoji: '✨', bg: 'bg-pop-cyan', ms: 4500 },
  error: { emoji: '⚠️', bg: 'bg-pop-pink', ms: 7000 },
}

/** Snackbars. Each one's countdown bar is a CSS animation: hovering pauses it, and it dismisses the snackbar when it ends. */
export function Toaster() {
  const toasts = useToasts()
  return (
    <div
      className="pointer-events-none fixed inset-x-4 bottom-4 z-[60] flex flex-col items-center gap-2 sm:left-auto sm:items-end"
      role="status"
      aria-live="polite"
    >
      {toasts.map((t) => {
        const style = TOAST_STYLE[t.tone]
        const external = /^https?:/.test(t.action?.href ?? '')
        return (
          <div
            key={t.id}
            className="group pointer-events-auto relative flex w-full max-w-sm animate-pop-in items-center gap-3 overflow-hidden rounded-2xl border-2 border-ink bg-white py-2.5 pr-2 pl-2.5 text-sm font-bold shadow-hard"
          >
            <span className={cx('grid size-9 shrink-0 place-items-center rounded-xl border-2 border-ink text-base', style.bg)}>{style.emoji}</span>
            <span className="min-w-0 flex-1 leading-snug">{t.message}</span>
            {t.action &&
              (external ? (
                <a href={t.action.href} target="_blank" rel="noreferrer" className={buttonClass({ variant: 'secondary', size: 'sm' })}>
                  {t.action.label}
                </a>
              ) : (
                <Link href={t.action.href} onClick={() => dismiss(t.id)} className={buttonClass({ variant: 'secondary', size: 'sm' })}>
                  {t.action.label}
                </Link>
              ))}
            <button
              type="button"
              aria-label="Dismiss"
              onClick={() => dismiss(t.id)}
              className="grid size-7 shrink-0 place-items-center rounded-lg text-ink/50 transition hover:bg-ink/8 hover:text-ink"
            >
              ✕
            </button>
            <span
              className={cx('absolute inset-x-0 bottom-0 h-1 origin-left animate-countdown group-hover:[animation-play-state:paused]', style.bg)}
              style={{ animationDuration: `${style.ms}ms` }}
              onAnimationEnd={() => dismiss(t.id)}
            />
          </div>
        )
      })}
    </div>
  )
}
