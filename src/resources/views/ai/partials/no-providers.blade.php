<div class="panel p-6">
  <h2 class="font-display text-lg font-bold">Falta configurar un proveedor de IA</h2>
  <p class="mt-2 text-sm text-muted">Agregá al menos una de estas claves en el archivo <code class="rounded bg-paper px-1">.env</code> y después corré <code class="rounded bg-paper px-1">php artisan config:cache</code> (o reiniciá el contenedor si usás Docker):</p>
  <ul class="mt-3 space-y-1 text-sm">
    <li><code class="rounded bg-paper px-1">GEMINI_API_KEY</code> para Google Gemini</li>
    <li><code class="rounded bg-paper px-1">OPENAI_API_KEY</code> para OpenAI</li>
    <li><code class="rounded bg-paper px-1">ANTHROPIC_API_KEY</code> para Claude</li>
  </ul>
</div>
