<nav class="mb-6 flex gap-1 border-b border-line" aria-label="Secciones del asesor">
  @foreach ([['ai.index', 'ai.chat.*', 'Chat'], ['ai.reports', 'ai.reports', 'Informes']] as [$route, $match, $label])
    @php $active = request()->routeIs($route) || request()->routeIs($match); @endphp
    <a href="{{ route($route) }}" @if ($active) aria-current="page" @endif
       class="-mb-px border-b-2 px-4 py-2 text-sm font-semibold {{ $active ? 'border-pine text-pine' : 'border-transparent text-muted hover:text-ink' }}">{{ $label }}</a>
  @endforeach
</nav>
