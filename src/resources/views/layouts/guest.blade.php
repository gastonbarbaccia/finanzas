<!DOCTYPE html>
<html lang="es">
<head>
  @include('partials.head')
</head>
<body class="min-h-screen bg-pine-dark font-sans text-ink antialiased">
  <div class="mx-auto grid min-h-screen max-w-5xl items-center gap-10 px-6 py-10 lg:grid-cols-2">
    <div class="text-white">
      <p class="font-display text-4xl font-bold leading-tight sm:text-5xl">Sabé a dónde va cada peso.</p>
      <p class="mt-4 max-w-md text-white/75">Registrá gastos, seguí tus deudas hasta cancelarlas y pedile a una IA que revise tus números.</p>
    </div>
    <div class="panel p-6 sm:p-8">
      @include('partials.flash')
      @yield('content')
    </div>
  </div>
</body>
</html>
