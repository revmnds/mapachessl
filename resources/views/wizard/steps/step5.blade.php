@extends('wizard.layout')

@section('content')
<div class="space-y-6">
    @if($request->isComplete())
        <div class="text-center space-y-2">
            <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center mx-auto">
                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <h2 class="text-xl font-medium text-gray-900">Certificado listo</h2>
            <p class="text-sm text-gray-500">{{ $request->domain }}</p>
        </div>

        <div class="space-y-3 text-sm">
            @if($request->expires_at)
            <div class="flex justify-between py-2 border-b border-gray-100">
                <span class="text-gray-500">Expira</span>
                <span class="text-gray-900">{{ $request->expires_at->format('d/m/Y') }}</span>
            </div>
            @endif
            <div class="flex justify-between py-2 border-b border-gray-100">
                <span class="text-gray-500">Incluye</span>
                <span class="text-gray-900">Certificado, llave, cadena</span>
            </div>
        </div>

        <a href="{{ route('wizard.download') }}"
           class="block w-full bg-gray-900 text-white py-3 px-6 rounded-lg font-medium text-center
                  hover:bg-gray-800 transition-colors">
            Descargar ZIP
        </a>

        <form action="{{ route('wizard.start-fresh') }}" method="POST">
            @csrf
            <button type="submit"
                    class="w-full text-center text-gray-500 hover:text-gray-700 text-sm cursor-pointer">
                Generar otro certificado
            </button>
        </form>

    @else
        <div class="text-center space-y-2">
            <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center mx-auto">
                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </div>
            <h2 class="text-xl font-medium text-gray-900">Error</h2>
            <p class="text-sm text-gray-500">No se pudo generar el certificado</p>
        </div>

        @if($request->error_message)
        <div class="bg-red-50 border border-red-200 rounded-lg p-3 text-sm text-red-600">
            {{ $request->error_message }}
        </div>
        @endif

        <div class="flex gap-3">
            <a href="{{ route('wizard.step', ['step' => 4]) }}"
               class="flex-1 bg-gray-900 text-white py-3 px-6 rounded-lg font-medium text-center
                      hover:bg-gray-800 transition-colors">
                Reintentar
            </a>
            <form action="{{ route('wizard.start-fresh') }}" method="POST" class="flex-1">
                @csrf
                <button type="submit"
                        class="w-full border border-gray-200 text-gray-600 py-3 px-6 rounded-lg font-medium
                               hover:bg-gray-50 transition-colors cursor-pointer">
                    Nuevo
                </button>
            </form>
        </div>
    @endif
</div>
@endsection
