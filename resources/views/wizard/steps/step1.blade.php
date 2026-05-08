@extends('wizard.layout')

@section('content')
<div class="space-y-6">
    <div class="space-y-2">
        <h2 class="text-xl font-medium text-gray-900">Tu dominio</h2>
        <p class="text-sm text-gray-500">El dominio para el certificado SSL</p>
    </div>

    <form action="{{ route('wizard.save-step', ['step' => 1]) }}" method="POST" class="space-y-6">
        @csrf
        <div class="space-y-2">
            <input type="text"
                   name="domain"
                   id="domain"
                   value="{{ old('domain', $request->domain) }}"
                   placeholder="ejemplo.com"
                   autocomplete="off"
                   autofocus
                   class="w-full border border-gray-200 rounded-lg px-4 py-3 text-gray-900
                          placeholder-gray-400 focus:border-gray-900 transition-colors
                          @error('domain') border-red-500 @enderror">
            @error('domain')
                <p class="text-red-500 text-sm">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit"
                class="w-full bg-gray-900 text-white py-3 px-6 rounded-lg font-medium
                       hover:bg-gray-800 transition-colors cursor-pointer">
            Continuar
        </button>
    </form>
</div>
@endsection
