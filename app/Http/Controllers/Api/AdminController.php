<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DemandePartenariat;
use App\Models\Message;
use App\Models\Paiement;
use App\Models\Reservation;
use App\Models\Signalement;
use App\Models\User;
use App\Models\Voyage;
use App\Models\Voyageur;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    /**
     * Obtenir les statistiques globales du tableau de bord Admin.
     */
    public function dashboardStats(): JsonResponse
    {
        // Exclure les administrateurs du décompte des utilisateurs
        $totalUsers = User::whereDoesntHave('roles', function ($q) {
            $q->where('name', 'admin');
        })->count();

        $totalClients = User::role('client')->whereDoesntHave('roles', function ($q) {
            $q->whereIn('name', ['admin', 'voyageur']);
        })->count();
        $totalVoyageurs = User::role('voyageur')->count();
        $totalAdmins = User::role('admin')->count();

        $usersActifs = User::whereDoesntHave('roles', function ($q) {
            $q->where('name', 'admin');
        })->where('statut', 'actif')->count();

        $usersSuspendus = User::whereDoesntHave('roles', function ($q) {
            $q->where('name', 'admin');
        })->where('statut', 'suspendu')->count();

        $voyageursEnAttente = Voyageur::where('statut', 'en_attente')->count();
        $voyageursVerifies = Voyageur::where('statut', 'verifie')->count();

        $totalSignalements = Signalement::count();
        $signalementsEnAttente = Signalement::where('statut', 'en_attente')->count();

        $totalPartenariats = DemandePartenariat::count();
        $partenariatsEnAttente = DemandePartenariat::where('statut', 'en_attente')->count();

        $totalVoyages = Voyage::count();
        $totalReservations = Reservation::count();
        $totalPaiementsPassees = Paiement::whereIn('statut', ['reussi', 'paye', 'disponible'])->sum('montant');

        // Estimation de la taille globale de la BD
        $messagesCount = Message::count();
        $estimatedGlobalBytes = ($totalUsers * 1200) + ($totalVoyages * 800) + ($totalReservations * 900) + ($messagesCount * 400);

        return response()->json([
            'status' => 'success',
            'data' => [
                'users' => [
                    'total' => $totalUsers,
                    'clients' => $totalClients,
                    'voyageurs' => $totalVoyageurs,
                    'admins' => $totalAdmins,
                    'actifs' => $usersActifs,
                    'suspendus' => $usersSuspendus,
                ],
                'voyageurs' => [
                    'en_attente' => $voyageursEnAttente,
                    'verifies' => $voyageursVerifies,
                ],
                'signalements' => [
                    'total' => $totalSignalements,
                    'en_attente' => $signalementsEnAttente,
                ],
                'partenariats' => [
                    'total' => $totalPartenariats,
                    'en_attente' => $partenariatsEnAttente,
                ],
                'activite' => [
                    'voyages' => $totalVoyages,
                    'reservations' => $totalReservations,
                    'messages' => $messagesCount,
                    'volume_paiements' => (float) $totalPaiementsPassees,
                ],
                'base_de_donnees' => [
                    'taille_estimee_octets' => $estimatedGlobalBytes,
                    'taille_estimee_mo' => round($estimatedGlobalBytes / (1024 * 1024), 2),
                ],
            ],
        ]);
    }

    /**
     * Obtenir la liste paginée de tous les utilisateurs (Réservé à l'Admin).
     */
    public function users(Request $request): JsonResponse
    {
        $query = User::with(['client', 'voyageur', 'roles']);

        // Recherche par mot-clé
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('nom', 'like', "%{$search}%")
                    ->orWhere('prenom', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('telephone', 'like', "%{$search}%");
            });
        }

        // Filtre par statut
        if ($statut = $request->input('statut')) {
            $query->where('statut', $statut);
        }

        // Filtre par rôle
        if ($role = $request->input('role')) {
            $query->role($role);
        }

        $perPage = (int) $request->input('per_page', 15);
        $users = $query->orderBy('created_at', 'desc')->paginate($perPage);

        // Enrichir chaque utilisateur avec le calcul de sa capacité de données dans la BD
        $users->getCollection()->transform(function ($user) {
            $userArray = $user->toArray();
            $dataSize = $this->calculateUserDataSize($user);
            $userArray['capacite_donnees'] = $dataSize;
            return $userArray;
        });

        return response()->json([
            'status' => 'success',
            'data' => $users,
        ]);
    }

    /**
     * Obtenir toutes les informations détaillées d'un utilisateur pour la fiche complète Admin.
     */
    public function showUser(User $user): JsonResponse
    {
        $user->load([
            'client',
            'voyageur.voyages.reservations.colis',
            'voyageur.voyages.reservations.messages',
            'voyageur.voyages.reservations.client.user',
            'roles',
            'notifications',
            'signalementsFaits.signale',
            'signalementsRecus.signaleur',
        ]);

        // Obtenir toutes les réservations passées par cet utilisateur (en tant que client)
        $reservationsClient = [];
        if ($user->client) {
            $reservationsClient = Reservation::where('client_id', $user->client->id)
                ->with(['voyage.voyageur.user', 'colis', 'messages'])
                ->orderBy('created_at', 'desc')
                ->get();
        }

        // Obtenir toutes les évaluations / avis reçus par cet utilisateur
        $evaluationsRecues = \App\Models\Evaluation::where('evalue_id', $user->id)
            ->with(['evaluateur', 'reservation.voyage'])
            ->orderBy('created_at', 'desc')
            ->get();

        $avgNote = $evaluationsRecues->avg('note');
        $noteMoyenne = $avgNote ? round((float)$avgNote, 1) : 5.0;

        // Calculer l'empreinte de stockage BD
        $dataSize = $this->calculateUserDataSize($user);

        $userData = $user->toArray();
        $userData['reservations_client'] = $reservationsClient;
        $userData['evaluations_recues'] = $evaluationsRecues;
        $userData['note_moyenne'] = $noteMoyenne;
        $userData['total_evaluations'] = $evaluationsRecues->count();
        $userData['capacite_donnees'] = $dataSize;

        if (isset($userData['voyageur']) && $userData['voyageur']) {
            $userData['voyageur']['note_moyenne'] = $noteMoyenne;
            $userData['voyageur']['total_evaluations'] = $evaluationsRecues->count();
        }

        return response()->json([
            'status' => 'success',
            'data' => $userData,
        ]);
    }

    /**
     * Bloquer ou débloquer un utilisateur.
     */
    public function toggleBlockUser(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'statut' => 'required|string|in:actif,suspendu,inactif',
            'motif' => 'nullable|string|max:500',
        ]);

        $newStatut = $validated['statut'];
        $user->update([
            'statut' => $newStatut,
        ]);

        $message = $newStatut === 'suspendu' 
            ? 'Votre compte a été temporairement suspendu par un administrateur.' 
            : 'Votre compte a été réactivé avec succès.';

        NotificationService::send(
            $user->id,
            'Statut de votre compte modifié',
            $message,
            'systeme'
        );

        return response()->json([
            'status' => 'success',
            'message' => "Le statut de l'utilisateur a été mis à jour en '{$newStatut}'.",
            'data' => $user->fresh(['roles', 'client', 'voyageur']),
        ]);
    }

    /**
     * Modifier le statut de vérification d'un voyageur (en_attente, verifie, refuse).
     */
    public function updateStatutVoyageur(Request $request, Voyageur $voyageur): JsonResponse
    {
        $validated = $request->validate([
            'statut' => 'required|string|in:en_attente,verifie,refuse',
            'motif_refus' => 'nullable|string|max:500',
        ]);

        $voyageur->update([
            'statut' => $validated['statut'],
        ]);

        $messageNotification = match ($validated['statut']) {
            'verifie' => 'Félicitations, votre compte voyageur a été vérifié avec succès par l\'administration.',
            'refuse' => 'Votre demande de vérification de compte voyageur a été refusée.' . ($validated['motif_refus'] ? ' Motif : ' . $validated['motif_refus'] : ''),
            default => 'Le statut de votre compte voyageur est en attente de vérification.',
        };

        if ($voyageur->user_id) {
            NotificationService::send(
                $voyageur->user_id,
                'Statut compte voyageur mis à jour',
                $messageNotification,
                'systeme'
            );
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Statut de vérification du voyageur mis à jour avec succès.',
            'data' => $voyageur->fresh(['user']),
        ]);
    }

    /**
     * Obtenir la liste des signalements de comptes.
     */
    public function signalements(Request $request): JsonResponse
    {
        $query = Signalement::with([
            'signaleur:id,nom,prenom,email,telephone,avatar,statut',
            'signale:id,nom,prenom,email,telephone,avatar,statut',
            'agentAdmin:id,nom,prenom',
        ]);

        if ($statut = $request->input('statut')) {
            $query->where('statut', $statut);
        }

        $perPage = (int) $request->input('per_page', 15);
        $signalements = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => $signalements,
        ]);
    }

    /**
     * Traiter un signalement (changer statut et prendre une décision comme bloquer le compte).
     */
    public function updateSignalementStatut(Request $request, Signalement $signalement): JsonResponse
    {
        $validated = $request->validate([
            'statut' => 'required|string|in:en_attente,traite,rejete',
            'decision' => 'nullable|string|in:bloque,avertissement,sans_suite',
        ]);

        $adminUser = auth('api')->user();

        $signalement->update([
            'statut' => $validated['statut'],
            'decision' => $validated['decision'] ?? $signalement->decision,
            'traite_par' => $adminUser->id,
        ]);

        // Si la décision est de bloquer le compte signalé
        if (($validated['decision'] ?? null) === 'bloque') {
            $userSignale = User::find($signalement->signale_id);
            if ($userSignale) {
                $userSignale->update(['statut' => 'suspendu']);
                NotificationService::send(
                    $userSignale->id,
                    'Compte suspendu',
                    'Votre compte a été suspendu suite à plusieurs signalements d\'utilisateurs.',
                    'systeme'
                );
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Signalement mis à jour avec succès.',
            'data' => $signalement->fresh(['signaleur', 'signale', 'agentAdmin']),
        ]);
    }

    /**
     * Obtenir la liste des demandes de partenariat du portail.
     */
    public function demandesPartenariat(Request $request): JsonResponse
    {
        $query = DemandePartenariat::query();

        if ($statut = $request->input('statut')) {
            $query->where('statut', $statut);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('nom_complet', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('entreprise', 'like', "%{$search}%")
                    ->orWhere('telephone', 'like', "%{$search}%");
            });
        }

        $perPage = (int) $request->input('per_page', 15);
        $demandes = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => $demandes,
        ]);
    }

    /**
     * Mettre à jour le statut d'une demande de partenariat.
     */
    public function updateDemandePartenariatStatut(Request $request, DemandePartenariat $demande): JsonResponse
    {
        $validated = $request->validate([
            'statut' => 'required|string|in:en_attente,contacte,traite,archive',
            'notes_admin' => 'nullable|string|max:1000',
        ]);

        $demande->update([
            'statut' => $validated['statut'],
            'notes_admin' => $validated['notes_admin'] ?? $demande->notes_admin,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Statut de la demande de partenariat mis à jour avec succès.',
            'data' => $demande,
        ]);
    }

    /**
     * Obtenir les statistiques détaillées des voyages par voyageur,
     * incluant le nombre de réservations et de messages par réservation,
     * ainsi que la capacité de données utilisée dans la BD par voyageur.
     */
    public function voyageursStats(Request $request): JsonResponse
    {
        $query = Voyageur::with([
            'user:id,nom,prenom,email,telephone,avatar,statut,dernier_connexion,created_at',
            'voyages' => function ($q) {
                $q->with(['reservations' => function ($rq) {
                    $rq->with(['colis', 'client.user'])->withCount('messages');
                }]);
            },
        ]);

        if ($search = $request->input('search')) {
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('nom', 'like', "%{$search}%")
                    ->orWhere('prenom', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('telephone', 'like', "%{$search}%");
            });
        }

        $perPage = (int) $request->input('per_page', 15);
        $voyageurs = $query->paginate($perPage);

        // Transformation pour ajouter la capacité de données et résumer les statistiques
        $voyageurs->getCollection()->transform(function ($voyageur) {
            $vArray = $voyageur->toArray();

            $totalVoyages = count($voyageur->voyages);
            $totalReservations = 0;
            $totalMessages = 0;

            $voyagesFormatted = [];
            foreach ($voyageur->voyages as $voyage) {
                $resCount = count($voyage->reservations);
                $totalReservations += $resCount;

                $poidsReserveVoyage = 0;
                $reservationsDetail = [];
                foreach ($voyage->reservations as $res) {
                    $msgCount = $res->messages_count ?? 0;
                    $totalMessages += $msgCount;
                    $clientUser = $res->client ? $res->client->user : null;
                    $poidsColis = (float) ($res->colis->poids ?? $res->poids_kg ?? $res->poids ?? 0);

                    if ($res->statut !== 'annulee') {
                        $poidsReserveVoyage += $poidsColis;
                    }

                    $reservationsDetail[] = [
                        'id' => $res->id,
                        'code_suivi' => $res->code_suivi ?? $res->numero ?? substr($res->id, 0, 8),
                        'client_nom' => $clientUser ? ($clientUser->prenom . ' ' . $clientUser->nom) : 'Client',
                        'statut' => $res->statut,
                        'poids_kg' => $poidsColis,
                        'messages_count' => $msgCount,
                    ];
                }

                $capaciteTotale = (float) ($voyage->capacite_totale ?? 0);
                $kilosDispo = max(0, $capaciteTotale - $poidsReserveVoyage);

                $voyagesFormatted[] = [
                    'id' => $voyage->id,
                    'ville_depart' => $voyage->ville_depart ?? 'Départ',
                    'ville_destination' => $voyage->ville_destination ?? $voyage->ville_arrivee ?? 'Destination',
                    'date_depart' => $voyage->date_depart,
                    'date_arrivee' => $voyage->date_arrivee,
                    'statut' => $voyage->statut,
                    'capacite_totale' => $capaciteTotale,
                    'poids_reserve' => $poidsReserveVoyage,
                    'kilos_disponibles' => $kilosDispo,
                    'prix_kg' => $voyage->prix_kg,
                    'reservations_count' => $resCount,
                    'reservations' => $reservationsDetail,
                ];
            }

            $user = $voyageur->user;
            $dataSize = $user ? $this->calculateUserDataSize($user) : ['octets' => 0, 'ko' => 0, 'mo' => 0, 'formatted' => '0 Ko'];

            $vArray['statistiques'] = [
                'total_voyages' => $totalVoyages,
                'total_reservations' => $totalReservations,
                'total_messages' => $totalMessages,
                'capacite_donnees_bd' => $dataSize,
            ];
            $vArray['voyages_details'] = $voyagesFormatted;

            return $vArray;
        });

        return response()->json([
            'status' => 'success',
            'data' => $voyageurs,
        ]);
    }

    /**
     * Calculer l'empreinte de stockage (capacité de données) dans la BD pour un utilisateur donné.
     */
    protected function calculateUserDataSize(User $user): array
    {
        $userId = $user->id;

        // Estimation de taille des lignes d'enregistrements en octets
        $userBaseBytes = 1200; // Infos profil user, auth tokens

        // Voyages
        $voyagesCount = Voyage::where('voyageur_id', function ($q) use ($userId) {
            $q->select('id')->from('voyageurs')->where('user_id', $userId)->limit(1);
        })->count();
        $voyagesBytes = $voyagesCount * 800;

        // Réservations faites ou reçues
        $reservationsCount = Reservation::where('client_id', function ($q) use ($userId) {
            $q->select('id')->from('clients')->where('user_id', $userId)->limit(1);
        })->orWhereIn('voyage_id', function ($q) use ($userId) {
            $q->select('id')->from('voyages')->where('voyageur_id', function ($vq) use ($userId) {
                $vq->select('id')->from('voyageurs')->where('user_id', $userId)->limit(1);
            });
        })->count();
        $reservationsBytes = $reservationsCount * 900;

        // Messages envoyés / reçus
        $messagesCount = Message::where('expediteur_id', $userId)->orWhere('destinataire_id', $userId)->count();
        $messagesBytes = $messagesCount * 450;

        // Signalements et notifications
        $notifsCount = DB::table('notifications')->where('user_id', $userId)->count();
        $notifsBytes = $notifsCount * 300;

        $totalOctets = $userBaseBytes + $voyagesBytes + $reservationsBytes + $messagesBytes + $notifsBytes;
        $ko = round($totalOctets / 1024, 2);
        $mo = round($totalOctets / (1024 * 1024), 3);

        $formatted = $mo >= 1 ? "{$mo} Mo" : "{$ko} Ko";

        return [
            'octets' => $totalOctets,
            'ko' => $ko,
            'mo' => $mo,
            'formatted' => $formatted,
            'details' => [
                'voyages_count' => $voyagesCount,
                'reservations_count' => $reservationsCount,
                'messages_count' => $messagesCount,
                'notifications_count' => $notifsCount,
            ],
        ];
    }
}
