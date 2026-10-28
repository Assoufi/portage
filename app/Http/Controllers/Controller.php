<?php
// app/Http/Controllers/Controller.php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

abstract class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    /**
     * Critères de recherche courants (paramètres de requête non vides),
     * destinés à être repassés à route(...index) après une action
     * (ex. suppression) afin que l'utilisateur retrouve la liste
     * filtrée telle qu'il l'avait laissée.
     *
     * Le paramètre 'page' est retiré : la ligne supprimée peut vider
     * la page courante.
     *
     * Attention : le formulaire concerné (index.blade.php) doit transmettre
     * la query string courante dans son action, ex.
     *   route('xxx.destroy', $item) . (request()->getQueryString() ? '?' . request()->getQueryString() : '')
     */
    protected function filtresCourants(Request $request): array
    {
        $filters = array_filter($request->query(), fn ($v) => $v !== null && $v !== '');
        unset($filters['page']);

        return $filters;
    }
}