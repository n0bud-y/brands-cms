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
  updated_at: string
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

export type PostCounts = { total: number; published: number; draft: number; scheduled: number }

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
