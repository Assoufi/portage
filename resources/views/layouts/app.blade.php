{{-- resources/views/layouts/app.blade.php --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>@yield('title', config('app.name', 'Gestion Interne'))</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
    
    <!-- Styles -->
    @vite(['resources/css/app.css'])
    
    @stack('styles')
</head>
<body class="font-sans antialiased min-h-screen bg-gray-100">
    <script>
        // Au chargement de la page, restaurer l'état du menu mobile depuis localStorage
        document.addEventListener('alpine:init', () => {
            // Attendre un petit délai pour que Alpine soit complètement initialisé
            setTimeout(() => {
                const storedState = localStorage.getItem('navOpenState');
                if (storedState) {
                    // Sélectionner le composant navigation par son attribut x-data
                    const navElements = document.querySelectorAll('nav[x-data]');
                    navElements.forEach(navElement => {
                        if (navElement.__x) {
                            navElement.__x.data.open = storedState === 'true';
                        }
                    });
                }
            }, 100);
        });
        
        // Sauvegarder l'état du menu au clic sur le hamburger
        document.addEventListener('click', (e) => {
            const target = e.target;
            // Chercher un bouton qui toggle l'état open dans un ancêtre nav
            let button = target;
            while (button && button.tagName !== 'NAV') {
                if (button.tagName === 'BUTTON' && button.getAttribute('onclick') && button.getAttribute('onclick').includes('open')) {
                    break;
                }
                button = button.parentElement;
            }
            if (button && button.tagName === 'BUTTON' && button.getAttribute('onclick') && button.getAttribute('onclick').includes('open')) {
                const nav = button.closest('nav');
                if (nav && nav.__x) {
                    localStorage.setItem('navOpenState', nav.__x.data.open.toString());
                }
            }
        });
    </script>
    
    @include('layouts.navigation')
        
        <!-- Composant de notification global -->
        <div x-data="notify()" x-init="window.notify = this"></div>
        
        <!-- Page Heading -->
        @if (View::hasSection('header') || isset($header))
            <header class="bg-white shadow">
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    @if(View::hasSection('header'))
                        @yield('header')
                    @else
                        {{ $header }}
                    @endif
                </div>
            </header>
        @endif
        
        <!-- Page Content -->
        <main>
            <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
                @if(session('success'))
                    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)" 
                         class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                        <span class="block sm:inline">{{ session('success') }}</span>
                        <button @click="show = false" class="absolute top-0 bottom-0 right-0 px-4 py-3">
                            <svg class="fill-current h-6 w-6 text-green-500" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                <title>Fermer</title>
                                <path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z"/>
                            </svg>
                        </button>
                    </div>
                @endif
                
                @if(session('error'))
                    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)" 
                         class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                        <span class="block sm:inline">{{ session('error') }}</span>
                        <button @click="show = false" class="absolute top-0 bottom-0 right-0 px-4 py-3">
                            <svg class="fill-current h-6 w-6 text-red-500" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                <title>Fermer</title>
                                <path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z"/>
                            </svg>
                        </button>
                    </div>
                @endif
                
                @yield('content')
            </div>
        </main>
    </div>
    
    <!-- Scripts -->
    @vite(['resources/js/app.js'])
    @stack('scripts')
</body>
</html>