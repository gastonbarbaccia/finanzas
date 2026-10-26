@if ($message->role === 'user')
  <div class="flex justify-end">
    <div class="max-w-[85%] whitespace-pre-line rounded-2xl rounded-br-md bg-pine px-4 py-2.5 text-[15px] text-white">{{ $message->content }}</div>
  </div>
@else
  <div class="flex justify-start">
    <div class="max-w-[92%] rounded-2xl rounded-bl-md border border-line bg-white px-4 py-3">
      {{-- El HTML que pueda venir en la respuesta se descarta; solo se renderiza Markdown. --}}
      <div class="ai-text text-[15px] text-ink/90">
        {!! \Illuminate\Support\Str::markdown($message->content, ['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}
      </div>
      @if ($message->provider)
        <p class="mt-1 text-xs text-muted">{{ config("ai.providers.{$message->provider}.label", $message->provider) }}</p>
      @endif
    </div>
  </div>
@endif
