<?php
// app/Providers/AuthServiceProvider.php

namespace App\Providers;

use App\Models\Mission;
use App\Models\Consultant;
use App\Models\Client;
use App\Models\Fournisseur;
use App\Policies\MissionPolicy;
use App\Policies\ConsultantPolicy;
use App\Policies\ClientPolicy;
use App\Policies\FournisseurPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Mission::class => MissionPolicy::class,
        Consultant::class => ConsultantPolicy::class,
        Client::class => ClientPolicy::class,
        Fournisseur::class => FournisseurPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}