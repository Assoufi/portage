@extends('layouts.app')

@section('title', 'Modifier un Avoir')

@section('header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Modifier l'avoir {{ $avoir->numero_avoir }}
        </h2>
        <a href="{{ route('avoirs.show', $avoir) }}"
           class="inline-flex items-center justify-center rounded-lg bg-gray-600 hover:bg-gray-700 text-white text-sm font-semibold py-2 px-4 transition">
            Retour à l'avoir
        </a>
    </div>
@endsection

@section('content')
    <div class="bg-white shadow-sm sm:rounded-lg">
        <div class="p-6">
            <form method="POST" action="{{ route('avoirs.update', $avoir) }}">
                @csrf
                @method('PUT')

                @include('avoirs.partials.form')

                <div class="mt-8 flex flex-col-reverse sm:flex-row sm:justify-end gap-3">
                    <a href="{{ route('avoirs.show', $avoir) }}"
                       class="inline-flex justify-center rounded-lg bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 transition">
                        Annuler
                    </a>
                    <button type="submit"
                            class="inline-flex justify-center rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 transition">
                        Mettre à jour l'avoir
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
