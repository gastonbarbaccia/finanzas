@if (session('success'))
  <div role="status" class="mb-6 rounded-lg border border-pine/30 bg-pine-soft px-4 py-3 text-sm text-pine-dark">{{ session('success') }}</div>
@endif
@if (session('error'))
  <div role="alert" class="mb-6 rounded-lg border border-brick/30 bg-brick-soft px-4 py-3 text-sm text-brick">{{ session('error') }}</div>
@endif
@if ($errors->any())
  <div role="alert" class="mb-6 rounded-lg border border-brick/30 bg-brick-soft px-4 py-3 text-sm text-brick">
    <p class="font-semibold">Revisá estos datos:</p>
    <ul class="mt-1 list-disc pl-5">
      @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
    </ul>
  </div>
@endif
