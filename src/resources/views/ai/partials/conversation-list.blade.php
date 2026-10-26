<ul class="divide-y divide-line">
  @forelse ($conversations as $c)
    <li>
      <a href="{{ route('ai.chat.show', $c) }}" @if ($conversation?->is($c)) aria-current="page" @endif
         class="block px-4 py-2.5 text-sm hover:bg-paper {{ $conversation?->is($c) ? 'bg-pine-soft font-semibold text-pine-dark' : '' }}">
        <span class="block truncate">{{ $c->title }}</span>
        <span class="text-xs font-normal text-muted">{{ $c->updated_at->diffForHumans() }}</span>
      </a>
    </li>
  @empty
    <li class="px-4 py-3 text-sm text-muted">Todavía no tenés conversaciones.</li>
  @endforelse
</ul>
