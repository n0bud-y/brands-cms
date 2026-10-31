import type { Feature } from '@/types'
import { Field, Input } from '@/components/ui'
import { ImageInput } from '@/components/ImageInput'
import { RichText } from '@/components/RichText'

export const emptyFeature: Feature = { title: '', html: '', image: '', alt: '' }

/** The "text left, image right" section: heading, rich text and an image. */
export function FeatureInput({ value, onChange }: { value: Feature; onChange: (v: Feature) => void }) {
  const set = (patch: Partial<Feature>) => onChange({ ...value, ...patch })

  return (
    <div className="space-y-4">
      <Field label="Heading">
        <Input value={value.title} placeholder="Why authors choose us" onChange={(e) => set({ title: e.target.value })} />
      </Field>
      <Field label="Text" group>
        <RichText value={value.html} onChange={(html) => set({ html })} />
      </Field>
      <ImageInput label="Image (right side)" hint="A tall or square photo looks best." value={value.image} onChange={(image) => set({ image })} />
      {value.image && (
        <Field label="Image description" hint="For Google and screen readers.">
          <Input value={value.alt} onChange={(e) => set({ alt: e.target.value })} />
        </Field>
      )}
    </div>
  )
}
