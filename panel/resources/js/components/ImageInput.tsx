import { useRef, useState } from 'react'
import { usePage } from '@inertiajs/react'
import { toast } from '@/lib/toast'
import { uploadImage } from '@/lib/upload'
import type { Site } from '@/types'
import { Button, Field, Input } from '@/components/ui'

/** Image URL field with upload to the panel's storage (per brand). Only used on brand pages. */
export function ImageInput({
  label,
  hint,
  value,
  onChange,
}: {
  label: string
  hint?: string
  value: string
  onChange: (url: string) => void
}) {
  const { site } = usePage<{ site: Site }>().props
  const fileRef = useRef<HTMLInputElement>(null)
  const [uploading, setUploading] = useState(false)

  const upload = async (file: File) => {
    setUploading(true)
    try {
      onChange(await uploadImage(site.slug, file))
      toast('Image uploaded')
    } catch (error) {
      toast((error as Error).message, 'error')
    } finally {
      setUploading(false)
    }
  }

  return (
    <Field label={label} hint={hint ?? 'JPG, PNG, WebP, GIF or AVIF, up to 5 MB.'} group>
      <div className="flex items-start gap-3">
        <button
          type="button"
          title="Upload an image"
          onClick={() => fileRef.current?.click()}
          className="shrink-0 transition hover:-rotate-3"
        >
          {value ? (
            <img src={value} alt="" className="size-20 rounded-xl border-2 border-ink bg-paper object-cover shadow-hard-sm" />
          ) : (
            <span className="grid size-20 place-items-center rounded-xl border-2 border-dashed border-ink/40 bg-paper text-2xl">
              {uploading ? '⏳' : '🖼️'}
            </span>
          )}
        </button>
        <div className="min-w-0 flex-1 space-y-2">
          <Input value={value} onChange={(e) => onChange(e.target.value)} placeholder="https://… or upload a file" />
          <div className="flex gap-2">
            <Button size="sm" variant="secondary" disabled={uploading} onClick={() => fileRef.current?.click()}>
              {uploading ? 'Uploading…' : '⬆️ Upload'}
            </Button>
            {value && (
              <Button size="sm" variant="ghost" onClick={() => onChange('')}>
                Remove
              </Button>
            )}
          </div>
        </div>
        <input
          ref={fileRef}
          type="file"
          accept="image/jpeg,image/png,image/webp,image/gif,image/avif"
          hidden
          onChange={(e) => {
            const file = e.target.files?.[0]
            if (file) upload(file)
            e.target.value = ''
          }}
        />
      </div>
    </Field>
  )
}
