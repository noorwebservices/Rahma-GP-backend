<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Tymon\JWTAuth\Contracts\JWTSubject; // Import du contrat JWT




// Correction : Il faut ajouter "implements JWTSubject" ici
class User extends Authenticatable implements JWTSubject
{

    use HasFactory, Notifiable, HasRoles, HasUuids;

    protected $guarded = [];

    protected $hidden = [
        'mot_de_passe',
        'remember_token',
    ];

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

    // --- Relations 1-1 vers les profils métier ---

    //relation avec le profil Client.
    public function client(): HasOne
    {
        return $this->hasOne(Client::class);
    }
 
    //relation avec le profil Voyageur; 
    public function voyageur(): HasOne
    {
        return $this->hasOne(Voyageur::class);
    }

     // --- Relations vers les tables qui référencent un utilisateur ---
 
     //une notification appartient à un utilisateur. un utilisateur peut avoir plusieurs notifications.
     public function notifications(): HasMany
     {
         return $this->hasMany(Notification::class);
     }
  
     //un message appartient à un utilisateur. un utilisateur peut avoir plusieurs messages envoyés et reçus.
     public function messagesEnvoyes(): HasMany
     {
         return $this->hasMany(Message::class, 'expediteur_id');
     }
  
     //un message appartient à un utilisateur. un utilisateur peut avoir plusieurs messages envoyés et reçus.
     public function messagesRecus(): HasMany
     {
         return $this->hasMany(Message::class, 'destinataire_id');
     }
  
     //une evaluation appartient à un utilisateur. un utilisateur peut avoir plusieurs evaluations.
     public function evaluationsDonnees(): HasMany
     {
         return $this->hasMany(Evaluation::class, 'evaluateur_id');
     }
  
     //une evaluation appartient à un utilisateur. un utilisateur peut avoir plusieurs evaluations.
     public function evaluationsRecues(): HasMany
     {
         return $this->hasMany(Evaluation::class, 'evalue_id');
     }
  
     // un utitilisateur peut mettre à jour plusieurs suivis de colis.
     public function suiviColisEffectues(): HasMany
     {
         return $this->hasMany(Suivi_colis::class, 'mis_a_jour_par');
     }
  
     // un utitilisateur peut confirmer plusieurs paiements.
     public function paiementsConfirmes(): HasMany
     {
         return $this->hasMany(Paiement::class, 'confirme_par');
     }

     public function signalementsFaits(): HasMany
     {
         return $this->hasMany(Signalement::class, 'signaleur_id');
     }

     public function signalementsRecus(): HasMany
     {
         return $this->hasMany(Signalement::class, 'signale_id');
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
