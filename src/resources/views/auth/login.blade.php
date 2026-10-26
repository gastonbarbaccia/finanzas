@extends('layouts.guest', ['title' => 'Ingresar'])
@section('content')
<h1 class="font-display text-2xl font-bold">Ingresar</h1>
<form method="POST" action="{{ route('login') }}" class="mt-6 space-y-4">
  @csrf
  <div>
    <label class="label" for="email">Email</label>
    <input class="field" id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email">
  </div>
  <div>
    <label class="label" for="password">Contraseña</label>
    <input class="field" id="password" type="password" name="password" required autocomplete="current-password">
  </div>
  <label class="flex items-center gap-2 text-sm text-muted">
    <input type="checkbox" name="remember" class="rounded border-line text-pine focus:ring-pine"> Recordarme
  </label>
  <button class="btn-primary w-full">Ingresar</button>
</form>
<p class="mt-6 text-sm text-muted">¿No tenés cuenta? <a class="font-semibold text-pine hover:underline" href="{{ route('register') }}">Creala acá</a></p>
@endsection
