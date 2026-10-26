@extends('layouts.guest', ['title' => 'Crear cuenta'])
@section('content')
<h1 class="font-display text-2xl font-bold">Crear cuenta</h1>
<form method="POST" action="{{ route('register') }}" class="mt-6 space-y-4">
  @csrf
  <div>
    <label class="label" for="name">Nombre</label>
    <input class="field" id="name" name="name" value="{{ old('name') }}" required maxlength="100" autocomplete="name">
  </div>
  <div>
    <label class="label" for="email">Email</label>
    <input class="field" id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="email">
  </div>
  <div>
    <label class="label" for="password">Contraseña</label>
    <input class="field" id="password" type="password" name="password" required minlength="8" autocomplete="new-password">
    <p class="mt-1 text-xs text-muted">Mínimo 8 caracteres, con letras y números.</p>
  </div>
  <div>
    <label class="label" for="password_confirmation">Repetir contraseña</label>
    <input class="field" id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password">
  </div>
  <button class="btn-primary w-full">Crear cuenta</button>
</form>
<p class="mt-6 text-sm text-muted">¿Ya tenés cuenta? <a class="font-semibold text-pine hover:underline" href="{{ route('login') }}">Ingresá</a></p>
@endsection
