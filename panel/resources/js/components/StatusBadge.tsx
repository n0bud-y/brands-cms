import type { PostSummary, Role } from '@/types'
import { Badge } from '@/components/ui'

export function StatusBadge({ post }: { post: Pick<PostSummary, 'status' | 'published_at'> }) {
  if (post.status === 'draft') return <Badge tone="yellow">✏️ Draft</Badge>
  // oxlint-disable-next-line react/purity -- "scheduled" depends on the current time by definition
  if (post.published_at && Date.parse(post.published_at) > Date.now()) return <Badge tone="cyan">⏰ Scheduled</Badge>
  return <Badge tone="lime">🚀 Live</Badge>
}

export function RoleBadge({ role }: { role: Role }) {
  return role === 'admin' ? <Badge tone="violet">👑 Admin</Badge> : <Badge tone="cyan">✍️ Editor</Badge>
}
