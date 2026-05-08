@extends('wizard.layout')

@section('content')
<div class="space-y-6">
    <div class="space-y-2">
        <h2 class="text-xl font-medium text-gray-900">Tu email</h2>
        <p class="text-sm text-gray-500">Para notificarte antes de que expire el certificado</p>
    </div>

    <form action="{{ route('wizard.save-step', ['step' => 2]) }}" method="POST" class="space-y-6">
        @csrf
        <div class="space-y-2">
            <input type="email"
                   name="email"
                   id="email"
                   value="{{ old('email', $request->email) }}"
                   placeholder="tu@email.com"
                   autocomplete="email"
                   autofocus
                   class="w-full border border-gray-200 rounded-lg px-4 py-3 text-gray-900
                          placeholder-gray-400 focus:border-gray-900 transition-colors
                          @error('email') border-red-500 @enderror">
            @error('email')
                <p class="text-red-500 text-sm">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex gap-3">
            <a href="{{ route('wizard.step', ['step' => 1]) }}"
               class="flex-1 text-center border border-gray-200 text-gray-600 py-3 px-6 rounded-lg font-medium
                      hover:bg-gray-50 transition-colors">
                Atrás
            </a>
            <button type="submit"
                    class="flex-1 bg-gray-900 text-white py-3 px-6 rounded-lg font-medium
                           hover:bg-gray-800 transition-colors cursor-pointer">
                Continuar
            </button>
        </div>
    </form>
</div>
@endsection
