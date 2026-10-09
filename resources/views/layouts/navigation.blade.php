{{-- resources/views/layouts/navigation.blade.php --}}
<nav x-data="{ open: false }" class="bg-white border-b border-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}">
                        <x-application-logo class="block h-9 w-auto fill-current text-gray-800" />
                    </a>
                </div>
                
                <!-- Navigation Links -->
                <div class="hidden space-x-8 sm:-my-px sm:ml-10 sm:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        Dashboard
                    </x-nav-link>
                    
                    <!-- Menu Missions avec sous-menu -->
                    <div x-data="{ missionsOpen: false }" class="relative flex items-center"
                         @click.outside="missionsOpen = false" @keydown.escape.stop="missionsOpen = false">
                        <button @click="missionsOpen = !missionsOpen"
                                class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium leading-5 transition duration-150 ease-in-out focus:outline-none {{ request()->routeIs('missions.*') ? 'border-indigo-400 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                            <span>Missions</span>
                            <svg class="ml-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <div x-show="missionsOpen" x-transition:enter="transition ease-out duration-100"
                             x-transition:enter-start="transform opacity-0 scale-95" x-transition:enter-end="transform opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-75"
                             x-transition:leave-start="transform opacity-100 scale-100" x-transition:leave-end="transform opacity-0 scale-95"
                             class="absolute left-0 top-full z-50 mt-2 w-56 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 py-1"
                             style="display: none;">
                            <a href="{{ route('missions.index') }}"
                               class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 {{ request()->routeIs('missions.index') ? 'bg-gray-100' : '' }}">
                                Gestion
                            </a>
                            <a href="{{ route('stats.prestations-non-declarees') }}"
                               class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 {{ request()->routeIs('stats.prestations-non-declarees') ? 'bg-gray-100' : '' }}">
                                Missions en cours
                            </a>
                        </div>
                    </div>
                    
                    <x-nav-link :href="route('consultants.index')" :active="request()->routeIs('consultants.*')">
                        Consultants
                    </x-nav-link>
                    
                    <x-nav-link :href="route('clients.index')" :active="request()->routeIs('clients.*')">
                        Clients
                    </x-nav-link>
                    
                    <x-nav-link :href="route('fournisseurs.index')" :active="request()->routeIs('fournisseurs.*')">
                        Fournisseurs
                    </x-nav-link>
                    
                    <x-nav-link :href="route('paiements.index')" :active="request()->routeIs('paiements.*')">
                        Paiements
                    </x-nav-link>
                    
                    <x-nav-link :href="route('repartitions.index')" :active="request()->routeIs('repartitions.*')">
                        Répartitions
                    </x-nav-link>

                    <x-nav-link :href="route('factures.index')" :active="request()->routeIs('factures.*')">
                        Factures
                    </x-nav-link>

                    <!-- Menu Documents avec sous-menu -->
                    <div x-data="{ documentsOpen: false }" class="relative flex items-center"
                         @click.outside="documentsOpen = false" @keydown.escape.stop="documentsOpen = false">
                        <button @click="documentsOpen = !documentsOpen"
                                class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium leading-5 transition duration-150 ease-in-out focus:outline-none {{ request()->routeIs('attestations.*') || request()->routeIs('devis.*') ? 'border-indigo-400 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                            <span>Documents</span>
                            <svg class="ml-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <div x-show="documentsOpen" x-transition:enter="transition ease-out duration-100"
                             x-transition:enter-start="transform opacity-0 scale-95" x-transition:enter-end="transform opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-75"
                             x-transition:leave-start="transform opacity-100 scale-100" x-transition:leave-end="transform opacity-0 scale-95"
                             class="absolute left-0 top-full z-50 mt-2 w-56 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 py-1"
                             style="display: none;">
                            <a href="{{ route('attestations.index') }}"
                               class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 {{ request()->routeIs('attestations.*') ? 'bg-gray-100' : '' }}">
                                Attestation de mission
                            </a>
                            <a href="{{ route('devis.index') }}"
                               class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 {{ request()->routeIs('devis.*') ? 'bg-gray-100' : '' }}">
                                Devis
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ml-6">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                            <div>{{ Auth::user()->name }}</div>
                            <div class="ml-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>
                    
                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">
                            Mon Profil
                        </x-dropdown-link>
                        
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">
                                Déconnexion
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>
            
            <!-- Hamburger -->
            <div class="-mr-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>
    
    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                Dashboard
            </x-responsive-nav-link>
            <!-- Sous-menu Missions responsive -->
            <div x-data="{ missionsOpen: false }" @click.outside="missionsOpen = false" @keydown.escape.stop="missionsOpen = false">
                <button @click="missionsOpen = !missionsOpen"
                        class="w-full flex items-center justify-between px-4 py-2 text-base font-medium text-gray-500 hover:text-gray-700 hover:bg-gray-100 focus:outline-none transition duration-150">
                    <span>Missions</span>
                    <svg class="w-4 h-4 transition-transform" :class="{'rotate-180': missionsOpen}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div x-show="missionsOpen" x-transition class="pl-4" style="display: none;">
                    <x-responsive-nav-link :href="route('missions.index')" :active="request()->routeIs('missions.index')">
                        Gestion
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('stats.prestations-non-declarees')" :active="request()->routeIs('stats.prestations-non-declarees')">
                        Missions en cours
                    </x-responsive-nav-link>
                </div>
            </div>

            <x-responsive-nav-link :href="route('consultants.index')" :active="request()->routeIs('consultants.*')">
                Consultants
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('clients.index')" :active="request()->routeIs('clients.*')">
                Clients
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('fournisseurs.index')" :active="request()->routeIs('fournisseurs.*')">
                Fournisseurs
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('paiements.index')" :active="request()->routeIs('paiements.*')">
                Paiements
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('repartitions.index')" :active="request()->routeIs('repartitions.*')">
                Répartitions
            </x-responsive-nav-link>

            <x-responsive-nav-link :href="route('factures.index')" :active="request()->routeIs('factures.*')">
                Factures
            </x-responsive-nav-link>

            <!-- Sous-menu Documents responsive -->
            <div x-data="{ documentsOpen: false }" @click.outside="documentsOpen = false" @keydown.escape.stop="documentsOpen = false">
                <button @click="documentsOpen = !documentsOpen"
                        class="w-full flex items-center justify-between px-4 py-2 text-base font-medium {{ request()->routeIs('attestations.*') || request()->routeIs('devis.*') ? 'text-gray-900' : 'text-gray-500 hover:text-gray-700' }} hover:bg-gray-100 focus:outline-none transition duration-150">
                    <span>Documents</span>
                    <svg class="w-4 h-4 transition-transform" :class="{'rotate-180': documentsOpen}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div x-show="documentsOpen" x-transition class="pl-4" style="display: none;">
                    <x-responsive-nav-link :href="route('attestations.index')" :active="request()->routeIs('attestations.*')">
                        Attestation de mission
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('devis.index')" :active="request()->routeIs('devis.*')">
                        Devis
                    </x-responsive-nav-link>
                </div>
            </div>
        </div>
        
        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
            </div>
            
            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    Mon Profil
                </x-responsive-nav-link>
                
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-responsive-nav-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">
                        Déconnexion
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>