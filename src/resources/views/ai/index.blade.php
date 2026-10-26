@extends('layouts.app', ['title' => 'Asesor IA'])
@section('content')
<h1 class="font-display text-3xl font-bold">Asesor IA</h1>
<p class="mt-1 max-w-2xl text-muted">La IA recibe solo totales agregados: gasto por categoría y mes, presupuestos, ingreso y el estado de tus deudas. No se envían tu nombre, tu email ni las descripciones de los gastos.</p>

@if (empty($providers))
  <div class="panel mt-6 p-6">
    <h2 class="font-display text-lg font-bold">Falta configurar un proveedor</h2>
    <p class="mt-2 text-sm text-muted">Agregá al menos una de estas claves en el archivo <code class="rounded bg-paper px-1">.env</code> y reiniciá el contenedor con <code class="rounded bg-paper px-1">docker compose restart app</code>:</p>
    <ul class="mt-3 space-y-1 text-sm">
      <li><code class="rounded bg-paper px-1">GEMINI_API_KEY</code> para Google Gemini</li>
      <li><code class="rounded bg-paper px-1">OPENAI_API_KEY</code> para OpenAI</li>
      <li><code class="rounded bg-paper px-1">ANTHROPIC_API_KEY</code> para Claude</li>
    </ul>
  </div>
@else
  <form method="POST" action="{{ route('ai.generate') }}" class="panel mt-6 flex flex-col gap-3 p-5 sm:flex-row sm:items-end"
        onsubmit="const b=this.querySelector('button'); b.disabled=true; b.textContent='Analizando tus números…';">
    @csrf
    <div class="flex-1">
      <label class="label" for="provider">Proveedor</label>
      <select class="field" id="provider" name="provider">
        @foreach ($providers as $key => $label)
          <option value="{{ $key }}" @selected($selected === $key)>{{ $label }}</option>
        @endforeach
      </select>
    </div>
    <button class="btn-primary disabled:opacity-60">Analizar mis finanzas</button>
  </form>
@endif

<section class="mt-8 space-y-6">
  @forelse ($recommendations as $r)
    <article class="panel p-6">
      <header class="mb-4 flex flex-wrap items-baseline justify-between gap-2 border-b border-line pb-3">
        <p class="text-sm text-muted">
          {{ $r->created_at->translatedFormat('j \d\e F Y, H:i') }}, con {{ config("ai.providers.{$r->provider}.label", $r->provider) }} ({{ $r->model }})
        </p>
        <form method="POST" action="{{ route('ai.destroy', $r) }}" onsubmit="return confirm('¿Eliminar este análisis?')">
          @csrf @method('DELETE')
          <button class="text-sm font-semibold text-brick hover:underline">Eliminar</button>
        </form>
      </header>
      {{-- El HTML que pueda venir en la respuesta se descarta; solo se renderiza Markdown. --}}
      <div class="ai-text max-w-prose text-[15px] text-ink/90">
        {!! \Illuminate\Support\Str::markdown($r->content, ['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}
      </div>
    </article>
  @empty
    @unless (empty($providers))
      <p class="text-muted">Todavía no pediste ningún análisis. Cuantos más meses de gastos tengas cargados, más útiles van a ser las recomendaciones.</p>
    @endunless
  @endforelse
  {{ $recommendations->links() }}
</section>
@endsection
