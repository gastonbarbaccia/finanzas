@extends('layouts.app', ['title' => $conversation?->title ?? 'Asesor IA'])
@section('content')
<h1 class="font-display text-3xl font-bold">Asesor IA</h1>
<p class="mb-4 mt-1 max-w-2xl text-sm text-muted">Preguntale lo que quieras sobre tus gastos y deudas. En cada mensaje recibe tus totales actualizados, sin tu nombre, tu email ni las descripciones de los gastos.</p>
@include('ai.partials.tabs')

@if (empty($providers))
  @include('ai.partials.no-providers')
@else
<div class="grid gap-4 lg:grid-cols-[15rem_1fr]">

  {{-- Historial de conversaciones --}}
  <aside>
    <a href="{{ route('ai.index') }}" class="btn-primary mb-3 w-full">Nueva conversación</a>
    <details class="panel lg:hidden" @if (! $conversation) open @endif>
      <summary class="cursor-pointer px-4 py-3 text-sm font-semibold">Conversaciones ({{ $conversations->count() }})</summary>
      @include('ai.partials.conversation-list')
    </details>
    <div class="panel hidden max-h-[calc(100dvh-16rem)] overflow-y-auto lg:block">
      @include('ai.partials.conversation-list')
    </div>
  </aside>

  {{-- Chat --}}
  <section class="panel flex h-[calc(100dvh-15rem)] min-h-[26rem] flex-col overflow-hidden lg:h-[calc(100dvh-14rem)]" aria-label="Conversación">
    @if ($conversation)
      <header class="flex items-center justify-between gap-3 border-b border-line px-4 py-2.5">
        <h2 class="truncate text-sm font-semibold">{{ $conversation->title }}</h2>
        <form method="POST" action="{{ route('ai.chat.destroy', $conversation) }}" onsubmit="return confirm('¿Eliminar esta conversación?')">
          @csrf @method('DELETE')
          <button class="text-sm font-semibold text-brick hover:underline">Eliminar</button>
        </form>
      </header>
    @endif

    <div id="chat-scroll" class="flex-1 space-y-4 overflow-y-auto bg-paper/50 p-4" aria-live="polite">
      @forelse ($messages as $message)
        @include('ai.partials.message', ['message' => $message])
      @empty
        <div id="chat-empty" class="mx-auto max-w-lg py-6 text-center">
          <p class="font-display text-xl font-bold">¿En qué te ayudo?</p>
          <p class="mt-1 text-sm text-muted">Algunas ideas para empezar:</p>
          <div class="mt-4 flex flex-col gap-2">
            @foreach ([
              '¿En qué categorías estoy gastando de más este mes?',
              '¿Qué deuda me conviene pagar primero y por qué?',
              'Armame un presupuesto para el mes que viene.',
              '¿Cuánto podría ahorrar por mes si ajusto mis gastos?',
            ] as $suggestion)
              <button type="button" data-prompt="{{ $suggestion }}" class="rounded-lg border border-line bg-white px-4 py-2.5 text-left text-sm hover:border-pine">{{ $suggestion }}</button>
            @endforeach
          </div>
        </div>
      @endforelse
    </div>

    <form id="chat-form" method="POST" class="border-t border-line bg-white p-3"
          action="{{ $conversation ? route('ai.chat.message', $conversation) : route('ai.chat.store') }}">
      @csrf
      <p id="chat-error" role="alert" class="mb-2 hidden rounded-lg bg-brick-soft px-3 py-2 text-sm text-brick"></p>
      <label for="chat-message" class="sr-only">Tu mensaje</label>
      <textarea id="chat-message" name="message" rows="2" maxlength="2000" required
                class="field resize-none" placeholder="Escribí tu pregunta. Enter para enviar, Shift + Enter para un salto de línea.">{{ old('message') }}</textarea>
      <div class="mt-2 flex items-center justify-between gap-3">
        <div class="flex items-center gap-2">
          <label for="chat-provider" class="text-xs text-muted">IA</label>
          <select id="chat-provider" name="provider" class="field w-auto py-1 text-xs">
            @foreach ($providers as $key => $label)
              <option value="{{ $key }}" @selected($selected === $key)>{{ $label }}</option>
            @endforeach
          </select>
        </div>
        <button id="chat-send" class="btn-primary disabled:opacity-60">Enviar</button>
      </div>
    </form>
  </section>
</div>
@endif
@endsection

@push('scripts')
<script>
(() => {
  const form = document.getElementById('chat-form');
  if (!form) return;
  const input = document.getElementById('chat-message');
  const send = document.getElementById('chat-send');
  const scroll = document.getElementById('chat-scroll');
  const errorBox = document.getElementById('chat-error');
  const toBottom = () => { scroll.scrollTop = scroll.scrollHeight; };
  toBottom();

  input.addEventListener('keydown', e => {
    if (e.key === 'Enter' && !e.shiftKey && !e.isComposing) { e.preventDefault(); form.requestSubmit(); }
  });

  document.querySelectorAll('[data-prompt]').forEach(btn => btn.addEventListener('click', () => {
    input.value = btn.dataset.prompt;
    form.requestSubmit();
  }));

  const bubble = (cls, inner, text) => {
    const row = document.createElement('div');
    row.className = cls;
    const b = document.createElement('div');
    b.className = inner;
    b.textContent = text;
    row.appendChild(b);
    scroll.appendChild(row);
    toBottom();
    return row;
  };

  form.addEventListener('submit', async e => {
    e.preventDefault();
    const text = input.value.trim();
    if (!text || send.disabled) return;

    errorBox.classList.add('hidden');
    document.getElementById('chat-empty')?.remove();
    const data = new FormData(form);
    const mine = bubble('flex justify-end', 'max-w-[85%] whitespace-pre-line rounded-2xl rounded-br-md bg-pine px-4 py-2.5 text-[15px] text-white', text);
    const typing = bubble('flex justify-start', 'rounded-2xl rounded-bl-md border border-line bg-white px-4 py-3 text-sm text-muted', 'Pensando…');
    input.value = '';
    send.disabled = true;

    try {
      const res = await fetch(form.action, {
        method: 'POST',
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        body: data,
      });
      const json = await res.json().catch(() => ({}));
      if (!res.ok) throw new Error(json.error || json.message || 'No se pudo enviar el mensaje. Probá de nuevo.');
      if (json.redirect) { window.location = json.redirect; return; }
      typing.outerHTML = json.html;
      toBottom();
    } catch (err) {
      typing.remove();
      mine.remove();
      input.value = text;
      errorBox.textContent = err.message;
      errorBox.classList.remove('hidden');
    } finally {
      send.disabled = false;
      input.focus();
    }
  });
})();
</script>
@endpush
