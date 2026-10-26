{{-- Moneda y cotización. Variables: $model (gasto o deuda), $usdRate (cotización actual o null) --}}
@php
  $currency = old('currency', $model->currency ?: 'ARS');
  $rate = old('exchange_rate', $model->exists && $model->currency === 'USD' ? (float) $model->exchange_rate : $usdRate);
@endphp
<div class="grid gap-5 sm:grid-cols-2" data-currency-fields>
  <div>
    <label class="label" for="currency">Moneda</label>
    <select class="field" id="currency" name="currency" data-currency-select>
      @foreach (config('finance.currencies') as $code => $label)
        <option value="{{ $code }}" @selected($currency === $code)>{{ $label }}</option>
      @endforeach
    </select>
  </div>
  <div data-rate-field class="{{ $currency === 'USD' ? '' : 'hidden' }}">
    <label class="label" for="exchange_rate">Cotización (pesos por dólar)</label>
    <input class="field num" id="exchange_rate" name="exchange_rate" type="number" step="0.0001" min="0.0001"
           value="{{ $rate }}" placeholder="Ej.: 1450">
    <p class="mt-1 text-xs text-muted">
      @if ($usdRate)
        Cotización actual: @money($usdRate). Podés cambiarla si pagaste a otro valor.
      @else
        No hay cotización cargada. Podés configurarla en <a class="font-semibold text-pine hover:underline" href="{{ route('settings.edit') }}">Ajustes</a>.
      @endif
    </p>
  </div>
</div>
<script>
(() => {
  const box = document.currentScript.previousElementSibling;
  const select = box.querySelector('[data-currency-select]');
  const rate = box.querySelector('[data-rate-field]');
  select.addEventListener('change', () => rate.classList.toggle('hidden', select.value !== 'USD'));
})();
</script>
