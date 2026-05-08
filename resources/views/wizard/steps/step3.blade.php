@extends('wizard.layout')

@section('content')
<div class="space-y-6" x-data="{ method: '{{ old('challenge_type', $request->challenge_type ?? 'http') }}' }">
    <div class="space-y-2">
        <h2 class="text-xl font-medium text-gray-900">Método de verificación</h2>
        <p class="text-sm text-gray-500">Elige cómo verificar que el dominio es tuyo</p>
    </div>

    <form action="{{ route('wizard.save-step', ['step' => 3]) }}" method="POST" class="space-y-6">
        @csrf

        <div class="space-y-3">
            <label class="block cursor-pointer">
                <input type="radio" name="challenge_type" value="http" x-model="method" class="sr-only peer">
                <div class="border border-gray-200 rounded-lg p-4 transition-colors
                            peer-checked:border-gray-900 peer-checked:bg-gray-50
                            hover:border-gray-300">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="font-medium text-gray-900">Archivo HTTP</p>
                            <p class="text-sm text-gray-500">Sube un archivo a tu servidor</p>
                        </div>
                        <div class="w-4 h-4 rounded-full border-2 border-gray-300 flex items-center justify-center
                                    peer-checked:border-gray-900"
                             :class="method === 'http' ? 'border-gray-900 bg-gray-900' : ''">
                            <div class="w-2 h-2 rounded-full bg-white" x-show="method === 'http'"></div>
                        </div>
                    </div>
                </div>
            </label>

            <label class="block cursor-pointer">
                <input type="radio" name="challenge_type" value="dns" x-model="method" class="sr-only peer">
                <div class="border border-gray-200 rounded-lg p-4 transition-colors
                            peer-checked:border-gray-900 peer-checked:bg-gray-50
                            hover:border-gray-300">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="font-medium text-gray-900">Registro DNS</p>
                            <p class="text-sm text-gray-500">Agrega un registro TXT</p>
                        </div>
                        <div class="w-4 h-4 rounded-full border-2 border-gray-300 flex items-center justify-center"
                             :class="method === 'dns' ? 'border-gray-900 bg-gray-900' : ''">
                            <div class="w-2 h-2 rounded-full bg-white" x-show="method === 'dns'"></div>
                        </div>
                    </div>
                </div>
            </label>
        </div>

        @error('challenge_type')
            <p class="text-red-500 text-sm">{{ $message }}</p>
        @enderror

        <div class="flex gap-3">
            <a href="{{ route('wizard.step', ['step' => 2]) }}"
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

<script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
@endsection
