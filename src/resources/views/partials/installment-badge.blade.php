{{-- Estado de la cuota del mes de una deuda. Variables: $debt, $status --}}
@php $due = $debt->dueDateThisMonth(); @endphp
@if ($status === 'paid')
  <span class="rounded-full bg-pine-soft px-2 py-0.5 text-xs font-semibold text-pine-dark">Cuota del mes pagada</span>
@elseif ($status === 'overdue')
  <span class="rounded-full bg-brick-soft px-2 py-0.5 text-xs font-semibold text-brick">Cuota vencida el {{ $due->format('d/m') }}</span>
@elseif ($status === 'pending')
  <span class="rounded-full bg-amber-soft px-2 py-0.5 text-xs font-semibold text-amber">
    Cuota pendiente{{ $due ? ($due->isToday() ? ', vence hoy' : ', vence el ' . $due->format('d/m')) : '' }}
  </span>
@elseif ($status === 'not_started')
  @php $first = $debt->firstDueDate(); @endphp
  <span class="rounded-full bg-paper px-2 py-0.5 text-xs font-semibold text-muted">
    {{ $first ? 'Primera cuota el ' . $first->format('d/m/Y') : 'Empieza el ' . $debt->started_on->format('d/m/Y') }}
  </span>
@endif
