@extends('wizard.layout')

@section('content')
<div class="text-center space-y-8">
    <div class="space-y-3">
        <h1 class="text-2xl font-medium text-gray-900">MapacheSSL</h1>
        <p class="text-gray-500">Certificados SSL gratuitos con Let's Encrypt</p>
    </div>

    <form action="{{ route('wizard.start') }}" method="POST">
        @csrf
        <button type="submit"
                class="w-full bg-gray-900 text-white py-3 px-6 rounded-lg font-medium
                       hover:bg-gray-800 transition-colors cursor-pointer">
            Comenzar
        </button>
    </form>

    <p class="text-xs text-gray-400">Sin registro. Sin costo. Sin complicaciones.</p>
</div>
@endsection
