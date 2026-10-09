@extends('layouts.app')

@section('title', 'Modifier le Devis')

@section('header')
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        Modifier le devis {{ $devis->numero_devis }}
    </h2>
@endsection

@section('content')
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg"
         x-data="devisForm(window.devisConfig)">
        <div class="p-6">
            <form method="POST" action="{{ route('devis.update', $devis) }}">
                @csrf
                @method('PUT')

                @include('devis.form')

                <div class="mt-6 flex justify-end space-x-3">
                    <a href="{{ route('devis.index') }}"
                       class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded-lg transition duration-300">
                        Annuler
                    </a>
                    <button type="submit"
                            class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg transition duration-300">
                        Mettre à jour
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
