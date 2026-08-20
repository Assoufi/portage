<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Styles / Scripts -->
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif

        <style>
            body {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .home-card {
                backdrop-filter: blur(10px);
                background: rgba(255, 255, 255, 0.95);
            }
        </style>
    </head>
    <body class="font-sans antialiased">
        @auth
            <!-- Utilisateur connecté -->
            <div class="text-center px-4">
                <div class="home-card max-w-lg w-full mx-auto px-8 py-10 shadow-2xl rounded-2xl">
                    <h1 class="text-3xl font-bold text-gray-800 mb-2">{{ config('app.name', 'Laravel') }}</h1>
                    <p class="text-gray-500 mb-8">Bienvenue, {{ Auth::user()->name }}</p>

                    <a href="{{ url('/dashboard') }}"
                       class="inline-flex items-center px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg shadow-md hover:shadow-lg transition-all duration-200">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                        </svg>
                        Accéder au tableau de bord
                    </a>

                    <div class="mt-6">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="text-sm text-gray-500 hover:text-gray-700 underline transition-colors">
                                Déconnexion
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @else
            <!-- Utilisateur non connecté -->
            <div class="text-center px-4">
                <div class="home-card max-w-md w-full mx-auto px-8 py-10 shadow-2xl rounded-2xl">
                    <h1 class="text-3xl font-bold text-gray-800 mb-2">{{ config('app.name', 'Laravel') }}</h1>
                    <p class="text-gray-500 mb-8">Connectez-vous pour accéder à l'application</p>

                    <a href="{{ route('login') }}"
                       class="inline-flex items-center justify-center w-full px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg shadow-md hover:shadow-lg transition-all duration-200">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                        </svg>
                        Connexion
                    </a>

                    @if (Route::has('register'))
                        <p class="mt-4 text-sm text-gray-500">
                            Pas encore de compte ?
                            <a href="{{ route('register') }}" class="text-indigo-600 hover:text-indigo-800 font-semibold hover:underline">
                                Créer un compte
                            </a>
                        </p>
                    @endif
                </div>
            </div>
        @endauth
    </body>
</html>
