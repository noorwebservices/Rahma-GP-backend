<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Tymon\JWTAuth\Contracts\JWTSubject; // Import du contrat JWT

#[Fillable(['nom', 'prenom', 'telephone', 'email', 'avatar', 'adresse', 'statut', 'mot_de_passe', 'dernier_connexion'])]
#[Hidden(['mot_de_passe', 'remember_token'])]

// Correction : Il faut ajouter "implements JWTSubject" ici
class User extends Authenticatable implements JWTSubject
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    /**
     * Requis par Laravel Auth car votre champ s'appelle "mot_de_passe" et non "password"
     */
    public function getAuthPassword()
    {
        return $this->mot_de_passe;
    }

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            // Correction : "password" et "email_verified_at" n'existent plus dans votre migration
            'mot_de_passe' => 'hashed', 
            'dernier_connexion' => 'datetime',
        ];
    }

    /**
     * Méthodes JWT de Tymon
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        // Optionnel : Vous pouvez ajouter des données dans le token (ex: ['statut' => $this->statut])
        return []; 
    }
}
