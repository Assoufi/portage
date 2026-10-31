@extends('layouts.app')

@section('title', 'Créer une Commande')

@section('header')
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        Créer une nouvelle commande
    </h2>
@endsection

@section('content')
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg"
         x-data="commandeForm(window.commandeConfig)">
        <div class="p-6">
            <form method="POST" action="{{ route('commandes.store') }}">
                @csrf

                @include('commandes.form')

                <div class="mt-6 flex justify-end space-x-3">
                    <a href="{{ route('commandes.index') }}"
                       class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded-lg transition duration-300">
                        Annuler
                    </a>
                    <button type="submit"
                            class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg transition duration-300">
                        Créer la commande
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
