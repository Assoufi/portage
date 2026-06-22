<?php
// app/Policies/ConsultantPolicy.php

namespace App\Policies;

use App\Models\User;
use App\Models\Consultant;

class ConsultantPolicy
{
    public function viewAny(User $user): bool
    {
        return true; // Tous les utilisateurs authentifiés peuvent voir la liste
    }

    public function view(User $user, Consultant $consultant): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Consultant $consultant): bool
    {
        return true;
    }

    public function delete(User $user, Consultant $consultant): bool
    {
        // Seuls les admins peuvent supprimer
        return $user->email === 'admin@example.com';
    }
}