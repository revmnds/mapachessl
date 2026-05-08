@extends('wizard.layout')

@section('content')
<div class="space-y-6">
    <div class="text-center space-y-2">
        <h2 class="text-xl font-medium text-gray-900">Continuar</h2>
        <p class="text-sm text-gray-500">Tienes un certificado pendiente</p>
    </div>

    <div class="space-y-3 text-sm">
        @if($request->domain)
        <div class="flex justify-between py-2 border-b border-gray-100">
            <span class="text-gray-500">Dominio</span>
            <span class="text-gray-900">{{ $request->domain }}</span>
        </div>
        @endif
        @if($request->email)
        <div class="flex justify-between py-2 border-b border-gray-100">
            <span class="text-gray-500">Email</span>
            <span class="text-gray-900">{{ $request->email }}</span>
        </div>
        @endif
        <div class="flex justify-between py-2 border-b border-gray-100">
            <span class="text-gray-500">Paso</span>
            <span class="text-gray-900">{{ $request->current_step }} de 5</span>
        </div>
    </div>

    <a href="{{ route('wizard.resume') }}"
       class="block w-full bg-gray-900 text-white py-3 px-6 rounded-lg font-medium text-center
              hover:bg-gray-800 transition-colors">
        Continuar
    </a>

    <form action="{{ route('wizard.start-fresh') }}" method="POST">
        @csrf
        <button type="submit"
                class="w-full text-center text-gray-500 hover:text-gray-700 text-sm cursor-pointer">
            Empezar de nuevo
        </button>
    </form>
</div>
@endsection
