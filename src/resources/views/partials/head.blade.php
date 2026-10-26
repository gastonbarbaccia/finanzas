<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<link rel="icon" href="{{ asset('favicon.ico') }}?v=2" sizes="any">
<link rel="icon" href="{{ asset('favicon.svg') }}?v=2" type="image/svg+xml">
<link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}?v=2">
<meta name="theme-color" content="#0B4639">
<title>{{ isset($title) ? $title . ' · ' : '' }}{{ config('app.name') }}</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,500;12..96,700&family=Instrument+Sans:wght@400;500;600&display=swap" rel="stylesheet">
{{-- Tailwind vía CDN para simplificar. En producción conviene compilarlo con Vite. --}}
<script src="https://cdn.tailwindcss.com/3.4.17"></script>
<script>
tailwind.config = {
  theme: {
    extend: {
      colors: {
        ink: '#17212B', paper: '#EEF1EC', line: '#D5DBD3',
        pine: { DEFAULT: '#12614F', dark: '#0B4639', soft: '#DCEDE5' },
        brick: { DEFAULT: '#B4442E', soft: '#F6E1DB' },
        amber: { DEFAULT: '#9A6212', soft: '#F5E9CF' },
        muted: '#5B6773',
      },
      fontFamily: {
        display: ['"Bricolage Grotesque"', 'ui-sans-serif', 'system-ui', 'sans-serif'],
        sans: ['"Instrument Sans"', 'ui-sans-serif', 'system-ui', 'sans-serif'],
      },
    },
  },
};
</script>
<style type="text/tailwindcss">
  @layer components {
    .panel { @apply bg-white border border-line rounded-xl; }
    .btn { @apply inline-flex items-center justify-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold transition focus:outline-none focus-visible:ring-2 focus-visible:ring-pine focus-visible:ring-offset-2; }
    .btn-primary { @apply btn bg-pine text-white hover:bg-pine-dark; }
    .btn-ghost { @apply btn border border-line bg-white text-ink hover:bg-paper; }
    .btn-danger { @apply btn text-brick hover:bg-brick-soft; }
    .field { @apply block w-full rounded-lg border border-line bg-white px-3 py-2 text-sm text-ink placeholder:text-muted/70 focus:border-pine focus:outline-none focus:ring-2 focus:ring-pine/20; }
    .label { @apply mb-1 block text-sm font-medium text-ink; }
    .num { font-variant-numeric: tabular-nums; }
    .nav-link { @apply flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-white/75 hover:bg-white/10 hover:text-white; }
    .nav-link.active { @apply bg-white/15 text-white; }
  }
  .ai-text h2 { @apply font-display text-lg font-bold text-ink mt-6 mb-2 first:mt-0; }
  .ai-text h3 { @apply font-semibold text-ink mt-4 mb-1; }
  .ai-text p { @apply mb-3 leading-relaxed; }
  .ai-text ul, .ai-text ol { @apply mb-3 ml-5 space-y-1; }
  .ai-text ul { list-style: disc; }
  .ai-text ol { list-style: decimal; }
  .ai-text strong { @apply font-semibold text-ink; }
  .ai-text table { @apply my-3 w-full text-sm; }
  .ai-text th, .ai-text td { @apply border-b border-line px-2 py-1 text-left; }
  @media (prefers-reduced-motion: reduce) { * { transition: none !important; } }
</style>
