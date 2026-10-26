<!DOCTYPE html>
<html lang="es">
<head>
  @include('partials.head')
</head>
<body class="min-h-screen bg-paper font-sans text-ink antialiased">
@php
  $nav = [
    ['route' => 'dashboard',        'match' => 'dashboard',    'label' => 'Resumen'],
    ['route' => 'incomes.index',    'match' => 'incomes.*',    'label' => 'Ingresos'],
    ['route' => 'expenses.index',   'match' => 'expenses.*',   'label' => 'Gastos'],
    ['route' => 'debts.index',      'match' => 'debts.*',      'label' => 'Deudas'],
    ['route' => 'categories.index', 'match' => 'categories.*', 'label' => 'Categorías y presupuestos'],
    ['route' => 'reports.index',    'match' => 'reports.*',    'label' => 'Reportes'],
    ['route' => 'ai.index',         'match' => 'ai.*',         'label' => 'Asesor IA'],
    ['route' => 'settings.edit',    'match' => 'settings.*',   'label' => 'Ajustes'],
  ];
@endphp
<div class="lg:flex">
  <aside class="bg-pine-dark text-white lg:fixed lg:inset-y-0 lg:flex lg:w-64 lg:flex-col">
    <div class="flex items-center justify-between px-5 py-4 lg:py-6">
      <a href="{{ route('dashboard') }}" class="font-display text-xl font-bold tracking-tight">{{ config('app.name') }}</a>
      <form method="POST" action="{{ route('logout') }}" class="lg:hidden">@csrf
        <button class="text-sm text-white/75 hover:text-white">Salir</button>
      </form>
    </div>
    <nav class="flex gap-1 overflow-x-auto px-3 pb-3 lg:flex-1 lg:flex-col lg:overflow-visible" aria-label="Principal">
      @foreach ($nav as $item)
        <a href="{{ route($item['route']) }}"
           class="nav-link whitespace-nowrap {{ request()->routeIs($item['match']) ? 'active' : '' }}"
           @if (request()->routeIs($item['match'])) aria-current="page" @endif>{{ $item['label'] }}</a>
      @endforeach
    </nav>
    <div class="hidden border-t border-white/10 px-5 py-4 lg:block">
      <p class="truncate text-sm text-white/75">{{ auth()->user()->name }}</p>
      <form method="POST" action="{{ route('logout') }}">@csrf
        <button class="mt-1 text-sm font-semibold text-white hover:underline">Cerrar sesión</button>
      </form>
    </div>
  </aside>

  <main class="flex-1 lg:ml-64">
    <div class="mx-auto max-w-6xl px-4 py-6 sm:px-6 lg:py-10">
      @include('partials.flash')
      @yield('content')
    </div>
  </main>
</div>
@stack('scripts')
</body>
</html>
