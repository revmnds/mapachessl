@extends('wizard.layout')

@section('content')
<div class="space-y-6">
    <div class="space-y-2">
        <h2 class="text-xl font-medium text-gray-900">Verificación</h2>
        <p class="text-sm text-gray-500">
            @if($request->isHttpChallenge())
                Sube este archivo a tu servidor
            @else
                Agrega este registro DNS
            @endif
        </p>
    </div>

    @if($request->isHttpChallenge())
        <div class="space-y-4">
            <div class="space-y-2">
                <label class="text-xs text-gray-500 uppercase tracking-wide">Ruta</label>
                <div class="flex items-center gap-2 bg-gray-50 rounded-lg p-3 font-mono text-sm">
                    <span class="flex-1 text-gray-900 break-all">.well-known/acme-challenge/</span>
                    <button type="button" onclick="copyToClipboard('.well-known/acme-challenge/')"
                            class="text-gray-400 hover:text-gray-600 cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                        </svg>
                    </button>
                </div>
            </div>

            <div class="space-y-2">
                <label class="text-xs text-gray-500 uppercase tracking-wide">Nombre del archivo</label>
                <div class="flex items-center gap-2 bg-gray-50 rounded-lg p-3 font-mono text-sm">
                    <span class="flex-1 text-gray-900 break-all">{{ $request->challenge_filename }}</span>
                    <button type="button" onclick="copyToClipboard('{{ $request->challenge_filename }}')"
                            class="text-gray-400 hover:text-gray-600 cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                        </svg>
                    </button>
                </div>
            </div>

            <div class="space-y-2">
                <label class="text-xs text-gray-500 uppercase tracking-wide">Contenido</label>
                <div class="flex items-center gap-2 bg-gray-50 rounded-lg p-3 font-mono text-sm">
                    <span class="flex-1 text-gray-900 break-all">{{ $request->challenge_token }}</span>
                    <button type="button" onclick="copyToClipboard('{{ $request->challenge_token }}')"
                            class="text-gray-400 hover:text-gray-600 cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    @else
        <div class="space-y-4">
            <div class="space-y-2">
                <label class="text-xs text-gray-500 uppercase tracking-wide">Nombre / Host</label>
                <div class="flex items-center gap-2 bg-gray-50 rounded-lg p-3 font-mono text-sm">
                    <span class="flex-1 text-gray-900 break-all">{{ $request->getDnsRecordName() }}</span>
                    <button type="button" onclick="copyToClipboard('{{ $request->getDnsRecordName() }}')"
                            class="text-gray-400 hover:text-gray-600 cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                        </svg>
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="space-y-2">
                    <label class="text-xs text-gray-500 uppercase tracking-wide">Tipo</label>
                    <div class="bg-gray-50 rounded-lg p-3 font-mono text-sm text-gray-900">TXT</div>
                </div>
                <div class="space-y-2">
                    <label class="text-xs text-gray-500 uppercase tracking-wide">TTL</label>
                    <div class="bg-gray-50 rounded-lg p-3 font-mono text-sm text-gray-900">300</div>
                </div>
            </div>

            <div class="space-y-2">
                <label class="text-xs text-gray-500 uppercase tracking-wide">Valor</label>
                <div class="flex items-center gap-2 bg-gray-50 rounded-lg p-3 font-mono text-sm">
                    <span class="flex-1 text-gray-900 break-all">{{ $request->challenge_token }}</span>
                    <button type="button" onclick="copyToClipboard('{{ $request->challenge_token }}')"
                            class="text-gray-400 hover:text-gray-600 cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    @endif

    @error('verification')
        <div class="bg-red-50 border border-red-200 rounded-lg p-3 text-sm text-red-600">
            {{ $message }}
        </div>
    @enderror

    <form action="{{ route('wizard.generate') }}" method="POST">
        @csrf
        <div class="flex gap-3">
            <a href="{{ route('wizard.step', ['step' => 3]) }}"
               class="flex-1 text-center border border-gray-200 text-gray-600 py-3 px-6 rounded-lg font-medium
                      hover:bg-gray-50 transition-colors">
                Atrás
            </a>
            <button type="submit"
                    class="flex-1 bg-gray-900 text-white py-3 px-6 rounded-lg font-medium
                           hover:bg-gray-800 transition-colors cursor-pointer">
                Generar certificado
            </button>
        </div>
    </form>
</div>

<script>
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        const btn = event.currentTarget;
        btn.innerHTML = '<svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>';
        setTimeout(() => {
            btn.innerHTML = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>';
        }, 1500);
    });
}
</script>
@endsection
