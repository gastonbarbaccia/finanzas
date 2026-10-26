@extends('layouts.app', ['title' => 'Informes de IA'])
@section('content')
<h1 class="font-display text-3xl font-bold">Asesor IA</h1>
<p class="mb-4 mt-1 max-w-2xl text-sm text-muted">Un informe completo de tu situación: diagnóstico, alertas, recomendaciones con montos, estrategia de deudas y un plan para los próximos 30 días. Queda guardado para consultarlo después.</p>
@include('ai.partials.tabs')

@if (empty($providers))
  @include('ai.partials.no-providers')
@else
  <form method="POST" action="{{ route('ai.generate') }}" class="panel flex flex-col gap-3 p-5 sm:flex-row sm:items-end"
        onsubmit="const b=this.querySelector('button'); b.disabled=true; b.textContent='Generando el informe…';">
    @csrf
    <div class="flex-1">
      <label class="label" for="provider">Proveedor</label>
      <select class="field" id="provider" name="provider">
        @foreach ($providers as $key => $label)
          <option value="{{ $key }}" @selected($selected === $key)>{{ $label }}</option>
        @endforeach
      </select>
    </div>
    <button class="btn-primary disabled:opacity-60">Generar informe</button>
  </form>
@endif

<section class="mt-8 space-y-6">
  @forelse ($recommendations as $r)
    <article class="panel p-6">
      <header class="mb-4 flex flex-wrap items-baseline justify-between gap-2 border-b border-line pb-3">
        <p class="text-sm text-muted">
          {{ $r->created_at->translatedFormat('j \d\e F Y, H:i') }}, con {{ config("ai.providers.{$r->provider}.label", $r->provider) }} ({{ $r->model }})
        </p>
        <form method="POST" action="{{ route('ai.destroy', $r) }}" onsubmit="return confirm('¿Eliminar este informe?')">
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
      <p class="text-muted">Todavía no generaste ningún informe. Cuantos más meses de gastos tengas cargados, más útiles van a ser las recomendaciones.</p>
    @endunless
  @endforelse
  {{ $recommendations->links() }}
</section>
@endsection
