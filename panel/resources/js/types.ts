import type { ToastAction, ToastTone } from '@/lib/toast'

export type Role = 'admin' | 'editor'

export type AuthUser = { id: number; name: string; email: string; role: Role }

/** A brand as listed in the sidebar switcher (shared on every page). */
export type SiteSummary = { id: number; name: string; slug: string; url: string; is_active: boolean }

export type Site = SiteSummary & {
  cache_clear_url: string | null
  available_layouts: string[]
  posts_count?: number
  users_count?: number
}

export type PostStatus = 'draft' | 'published'

export type BlockType = 'heading' | 'paragraph' | 'image' | 'image_text' | 'quote' | 'cta' | 'two_column' | 'gallery' | 'faq'

// Block fields vary per type (see components/BlockEditor.tsx); `any` keeps them storable in an Inertia form.
// eslint-disable-next-line @typescript-eslint/no-explicit-any
export type Block = { id: string; type: BlockType; [field: string]: any }

export type PostSummary = {
  id: number
  title: string
  slug: string
  layout: string
  status: PostStatus
  cover_image: string | null
  published_at: string | null
  created_at?: string
  updated_at: string
  /** Set while the post is in the trash. */
  deleted_at?: string | null
}

export type Post = PostSummary & {
  author_id: number | null
  show_author: boolean
  category: string | null
  excerpt: string | null
  blocks: Block[] | null
  meta_title: string | null
  meta_description: string | null
  og_image: string | null
  /** Pretty-printed JSON-LD, '' when none. */
  schema: string
}

/** A writer profile; the default one is used by posts that don't pick their own. */
export type Author = {
  id: number
  name: string
  role: string | null
  photo: string | null
  bio: string | null
  is_default: boolean
  posts_count?: number
}

export type Faq = { q: string; a: string }

/** A service page's "text left, image right" section above the FAQs. */
export type Feature = { title: string; html: string; image: string; alt: string }

/** A brand service; its texts are templates with {location}, {service}… filled in per state and city. */
export type Service = {
  id: number
  name: string
  slug: string
  icon: string | null
  summary: string | null
  title: string | null
  blocks: Block[] | null
  feature: Feature | null
  faqs: Faq[] | null
  meta_title: string | null
  meta_description: string | null
  is_active: boolean
}

/** A state (parent_id null) or a city in that state. */
export type LocationSummary = { id: number; parent_id: number | null; name: string; slug: string; is_active: boolean }

export type Location = LocationSummary & {
  heading: string | null
  subheading: string | null
  banner_image: string | null
  /** Map picture on /locations and the state page. */
  image: string | null
  /** Short write-up under the state on /locations. */
  summary: string | null
  blocks: Block[] | null
  /** null = the brand's default FAQs. */
  faqs: Faq[] | null
  meta_title: string | null
  meta_description: string | null
  /** Its URL on the website; null while it (or its state) is switched off. */
  path: string | null
}

export type LocationDefaults = {
  heading: string
  subheading: string
  banner_image: string
  services_title: string
  cities_title: string
  faqs: Faq[]
  contact_title: string
  contact_text: string
  meta_title: string
  meta_description: string
  hub_heading: string
  hub_text: string
  hub_intro_title: string
  hub_intro: string
  hub_image: string
}

/** A page of the brand website itself and its SEO; found_* is what the page file says. */
export type SitePage = {
  id: number
  path: string
  found_title: string | null
  found_description: string | null
  meta_title: string | null
  meta_description: string | null
  og_image: string | null
  noindex: boolean
}

export type PostCounts = { total: number; published: number; draft: number; scheduled: number; trash: number }

export type Toast = { message: string; tone?: ToastTone; action?: ToastAction }

declare module '@inertiajs/core' {
  export interface InertiaConfig {
    sharedPageProps: {
      auth: { user: AuthUser | null }
      sites: SiteSummary[]
    }
    flashDataType: {
      toast?: Toast
      /** Shown once after a brand is created or its key is rotated. */
      api_key?: string
    }
  }
}
