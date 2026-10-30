import { Head, Link, useForm } from '@inertiajs/react'
import { useEditorGuard } from '@/lib/useEditorGuard'
import type { LocationDefaults, Site } from '@/types'
import { Button, buttonClass, Card, Field, Input, PageHeader, Textarea } from '@/components/ui'
import { FaqList, PlaceholderHint } from '@/components/FaqList'
import { ImageInput } from '@/components/ImageInput'

type Props = { site: Site; defaults: LocationDefaults; fallbacks: LocationDefaults }
type TextKey = Exclude<keyof LocationDefaults, 'faqs' | 'banner_image'>

/** Brand-wide texts for every state and city page; a page can still set its own. */
export default function LocationDefaultsPage({ site, defaults, fallbacks }: Props) {
  const form = useForm<LocationDefaults>(defaults)
  const { data, errors } = form

  const submit = () => {
    if (form.processing) return
    form.put(`/s/${site.slug}/locations/defaults`, { preserveScroll: true, onSuccess: () => form.setDefaults() })
  }
  useEditorGuard(submit, form.isDirty)

  const text = (key: TextKey, label: string, long = false) => {
    const Control = long ? Textarea : Input
    return (
      <Field label={label} error={errors[key]}>
        <Control value={data[key]} placeholder={fallbacks[key]} onChange={(e) => form.setData(key, e.target.value)} />
      </Field>
    )
  }

  return (
    <form
      onSubmit={(e) => {
        e.preventDefault()
        submit()
      }}
    >
      <Head title={`Location page defaults · ${site.name}`} />
      <PageHeader title="Page defaults" emoji="⚙️">
        <Link href={`/s/${site.slug}/locations`} className={buttonClass({ variant: 'secondary' })}>
          ← Locations
        </Link>
        <Button type="submit" variant="pop" disabled={form.processing || !form.isDirty} title="Ctrl+S">
          {form.processing ? 'Saving…' : '💾 Save'}
        </Button>
      </PageHeader>

      <div className="mb-6 max-w-2xl space-y-2 text-sm text-ink/65">
        <p>Every state and city page uses these texts, unless that page sets its own. Empty fields use the grey example text.</p>
        <PlaceholderHint />
      </div>

      <div className="grid items-start gap-6 lg:grid-cols-2">
        <Card title="Banner" icon="🌅" tone="yellow">
          {text('heading', 'Heading')}
          {text('subheading', 'Text under the heading', true)}
          <ImageInput
            label="Default background image"
            hint="Used by every page without its own banner image."
            value={data.banner_image}
            onChange={(v) => form.setData('banner_image', v)}
          />
        </Card>

        <Card title="Sections" icon="🧩" tone="cyan">
          {text('services_title', 'Services heading')}
          {text('cities_title', 'Cities heading')}
          {text('contact_title', 'Contact heading')}
          {text('contact_text', 'Contact text', true)}
        </Card>

        <Card title="FAQs" icon="❓" tone="pink">
          <p className="text-sm text-ink/60">Shown on every state and city page without its own FAQs.</p>
          <FaqList items={data.faqs} onChange={(faqs) => form.setData('faqs', faqs)} />
        </Card>

        <Card title="SEO" icon="🔍" tone="violet">
          {text('meta_title', 'Meta title')}
          <Field label="Meta description" error={errors.meta_description}>
            <Textarea
              value={data.meta_description}
              placeholder="Defaults to the text under the heading"
              onChange={(e) => form.setData('meta_description', e.target.value)}
            />
          </Field>
        </Card>
      </div>
    </form>
  )
}
