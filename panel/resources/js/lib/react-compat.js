// The app is written against React but runs on Preact (≈4 kB instead of ≈55 kB gzipped); vite.config.ts points
// `react` here. preact/compat covers everything except React 19's use(), which Inertia only calls with a context.
import { useContext } from 'preact/compat'

export * from 'preact/compat'
export { default } from 'preact/compat'
export const use = useContext
