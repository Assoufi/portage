<x-guest-layout>
    <!-- Titre -->
    <div style="text-align:center;margin-bottom:1.5rem">
        <h1 style="font-size:1.5rem;font-weight:700;color:#1f2937">Connexion</h1>
        <p style="margin-top:0.25rem;font-size:0.875rem;color:#6b7280">Connectez-vous à votre compte</p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <!-- Email -->
        <div>
            <x-input-label for="email" value="Adresse e-mail" />
            <x-text-input
                id="email"
                class="block mt-1 w-full"
                type="email"
                name="email"
                :value="old('email')"
                required
                autofocus
                autocomplete="username"
                placeholder="votre@email.com"
            />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Mot de passe -->
        <div style="margin-top:1rem">
            <x-input-label for="password" value="Mot de passe" />
            <x-text-input
                id="password"
                class="block mt-1 w-full"
                type="password"
                name="password"
                required
                autocomplete="current-password"
                placeholder="••••••••"
            />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Mot de passe oublié -->
        <div style="margin-top:0.5rem;text-align:right">
            @if (Route::has('password.request'))
                <a
                    style="font-size:0.875rem;color:#4f46e5;text-decoration:none"
                    href="{{ route('password.request') }}"
                    onmouseover="this.style.textDecoration='underline'"
                    onmouseout="this.style.textDecoration='none'"
                >
                    Mot de passe oublié ?
                </a>
            @endif
        </div>

        <!-- Se souvenir de moi -->
        <div style="margin-top:1rem">
            <label for="remember_me" style="display:inline-flex;align-items:center;cursor:pointer">
                <input
                    id="remember_me"
                    type="checkbox"
                    name="remember"
                    style="border-radius:0.25rem;border-color:#d1d5db;accent-color:#4f46e5"
                >
                <span style="margin-left:0.5rem;font-size:0.875rem;color:#4b5563">Se souvenir de moi</span>
            </label>
        </div>

        <!-- Bouton Connexion -->
        <div style="margin-top:1.5rem">
            <button type="submit" style="
                width:100%;
                padding:0.75rem 1rem;
                background-color:#4f46e5;
                color:#fff;
                font-weight:600;
                font-size:0.875rem;
                border:none;
                border-radius:0.5rem;
                cursor:pointer;
                box-shadow:0 4px 6px -1px rgba(0,0,0,0.1);
                transition:background-color 0.15s;
            " onmouseover="this.style.backgroundColor='#4338ca'" onmouseout="this.style.backgroundColor='#4f46e5'">
                Connexion
            </button>
        </div>

        <!-- Lien inscription -->
        @if (Route::has('register'))
            <div style="margin-top:1rem;text-align:center">
                <span style="font-size:0.875rem;color:#6b7280">Pas encore de compte ?</span>
                <a
                    href="{{ route('register') }}"
                    style="font-size:0.875rem;font-weight:600;color:#4f46e5;text-decoration:none"
                    onmouseover="this.style.textDecoration='underline'"
                    onmouseout="this.style.textDecoration='none'"
                >
                    Créer un compte
                </a>
            </div>
        @endif
    </form>
</x-guest-layout>
