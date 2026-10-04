@if (session('status'))
    <div class="rounded-md bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-900 print:hidden" role="status">
        ✓ {{ session('status') }}
    </div>
@endif

@if ($errors->any())
    <div class="rounded-md bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-900 print:hidden" role="alert">
        <p class="font-semibold">Revise lo siguiente:</p>
        <ul class="list-disc ms-5 mt-1">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
