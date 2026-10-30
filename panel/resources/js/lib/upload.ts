import { http } from '@inertiajs/react'

/** Uploads an image to the brand's storage and returns its public URL. Uses Inertia's HTTP client, which sends the CSRF token. */
export async function uploadImage(siteSlug: string, file: File): Promise<string> {
  const body = new FormData()
  body.append('file', file)

  try {
    const res = await http.getClient().request({
      method: 'post',
      url: `/s/${siteSlug}/uploads`,
      data: body,
      headers: { Accept: 'application/json' },
    })
    return (JSON.parse(res.data) as { url: string }).url
  } catch (error) {
    throw new Error(uploadErrorMessage(error))
  }
}

function uploadErrorMessage(error: unknown): string {
  const data = (error as { response?: { data?: unknown } }).response?.data
  try {
    const json = (typeof data === 'string' ? JSON.parse(data) : data) as { message?: string; errors?: { file?: string[] } } | undefined
    return json?.errors?.file?.[0] ?? json?.message ?? 'Upload failed'
  } catch {
    return 'Upload failed. Is the panel running?'
  }
}
