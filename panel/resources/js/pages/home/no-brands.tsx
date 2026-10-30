import { Head } from '@inertiajs/react'
import { EmptyState } from '@/components/ui'

/** Shown to editors who haven't been assigned to any brand yet. */
export default function NoBrands() {
  return (
    <>
      <Head title="No brands yet" />
      <EmptyState emoji="🪐" title="No brands assigned to you yet">
        Ask an admin to give you access to the brands you write for.
      </EmptyState>
    </>
  )
}
