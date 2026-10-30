import { useLayoutEffect, useRef } from 'react'

// A deliberately tiny rich-text editor (contenteditable + execCommand) instead of a
// 100 kB editor library. The panel sanitizes the HTML on save, so the toolbar only
// needs to offer what the sanitizer allows.

const TOOLS: { label: string; title: string; className?: string; run: (exec: (cmd: string, arg?: string) => void) => void }[] = [
  { label: 'B', title: 'Bold', className: 'font-extrabold', run: (exec) => exec('bold') },
  { label: 'I', title: 'Italic', className: 'italic', run: (exec) => exec('italic') },
  { label: 'U', title: 'Underline', className: 'underline', run: (exec) => exec('underline') },
  { label: 'H3', title: 'Subheading', run: (exec) => exec('formatBlock', '<h3>') },
  { label: '¶', title: 'Normal paragraph', run: (exec) => exec('formatBlock', '<p>') },
  { label: '• List', title: 'Bullet list', run: (exec) => exec('insertUnorderedList') },
  { label: '1. List', title: 'Numbered list', run: (exec) => exec('insertOrderedList') },
  {
    label: '🔗 Link',
    title: 'Add link',
    run: (exec) => {
      const url = window.prompt('Link URL (https://…, /page, mailto:…)')
      if (url) exec('createLink', url)
    },
  },
  { label: 'Unlink', title: 'Remove link', run: (exec) => exec('unlink') },
  { label: '🧽', title: 'Clear formatting', run: (exec) => exec('removeFormat') },
]

export function RichText({ value, onChange }: { value: string; onChange: (html: string) => void }) {
  const ref = useRef<HTMLDivElement>(null)

  // Only overwrite the DOM when the value changed from outside (load, save), so the caret stays put while typing.
  useLayoutEffect(() => {
    if (ref.current && ref.current.innerHTML !== value) ref.current.innerHTML = value
  }, [value])

  const emit = () => onChange(ref.current?.innerHTML ?? '')

  const exec = (command: string, arg?: string) => {
    ref.current?.focus()
    document.execCommand(command, false, arg)
    emit()
  }

  return (
    <div className="overflow-hidden rounded-xl border-2 border-ink bg-white transition focus-within:border-pop-violet focus-within:ring-4 focus-within:ring-pop-violet/25">
      <div className="flex flex-wrap gap-1 border-b-2 border-ink/10 bg-paper p-1.5">
        {TOOLS.map((tool) => (
          <button
            key={tool.label}
            type="button"
            title={tool.title}
            className={`rounded-lg px-2 py-1 text-xs font-bold transition hover:bg-pop-yellow ${tool.className ?? ''}`}
            onMouseDown={(e) => e.preventDefault()} // keep the text selection
            onClick={() => tool.run(exec)}
          >
            {tool.label}
          </button>
        ))}
      </div>
      <div
        ref={ref}
        className="rich-content min-h-28 px-3.5 py-2.5 text-sm leading-relaxed outline-none"
        contentEditable
        suppressContentEditableWarning
        onFocus={() => document.execCommand('defaultParagraphSeparator', false, 'p')}
        onInput={emit}
        onBlur={emit}
        onPaste={(e) => {
          // Paste as plain text: formatting from Word/Docs is noise the sanitizer would strip anyway.
          e.preventDefault()
          document.execCommand('insertText', false, e.clipboardData.getData('text/plain'))
        }}
      />
    </div>
  )
}
