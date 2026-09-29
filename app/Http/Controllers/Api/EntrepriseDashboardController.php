<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AgentGp;
use App\Models\Colis;
use App\Models\Paiement;
use App\Models\Reservation;
use App\Models\User;
use App\Models\Voyage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EntrepriseDashboardController extends Controller
{
    /**
     * Vue d'ensemble du Tableau de Bord de l'Entreprise GP (Section 13 du cahier des charges).
     */
    public function overview(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = auth('api')->user();

        if (! $user->isGerantEntreprise()) {
            return response()->json(['message' => 'Accès réservé au gérant.'], 403);
        }

        $entrepriseId = $user->getEntrepriseId();

        // 1. Statistiques Voyages
        $voyagesQuery = Voyage::where('entreprise_id', $entrepriseId);
        $totalVoyages = (clone $voyagesQuery)->count();
        $voyagesAvenir = (clone $voyagesQuery)->whereIn('statut', ['publie', 'brouillon'])->count();
        $voyagesEnCours = (clone $voyagesQuery)->where('statut', 'en_cours')->count();
        $voyagesTermines = (clone $voyagesQuery)->where('statut', 'termine')->count();
        $voyagesAnnules = (clone $voyagesQuery)->where('statut', 'annule')->count();

        // 2. Statistiques Colis
        $colisQuery = Colis::whereHas('reservation', function ($q) use ($entrepriseId) {
            $q->where('entreprise_id', $entrepriseId);
        });
        $totalColis = (clone $colisQuery)->count();
        $colisEnAttente = (clone $colisQuery)->whereIn('statut', ['demande_envoyee', 'enregistre', 'en_attente', 'reservation_acceptee'])->count();
        $colisEnTransit = (clone $colisQuery)->whereIn('statut', ['receptionne', 'colis_depose', 'colis_pris_en_charge', 'en_transit', 'arrive'])->count();
        $colisLivres = (clone $colisQuery)->whereIn('statut', ['livre', 'livree'])->count();
        $colisAnnules = (clone $colisQuery)->where('statut', 'annule')->count();

        // 3. Statistiques Agents
        $agentsQuery = AgentGp::where('entreprise_id', $entrepriseId);
        $totalAgents = (clone $agentsQuery)->count();
        $agentsActifs = (clone $agentsQuery)->where('statut', 'actif')->count();
        $agentsEnVoyage = (clone $agentsQuery)->where('statut', 'en_voyage')->count();
        $agentsIndisponibles = (clone $agentsQuery)->where('statut', 'indisponible')->count();

        // 4. Statistiques Réservations
        $reservationsQuery = Reservation::where(function ($q) use ($entrepriseId) {
            $q->where('entreprise_id', $entrepriseId)
              ->orWhereHas('voyage', fn($vq) => $vq->where('entreprise_id', $entrepriseId));
        });

        $totalReservations = (clone $reservationsQuery)->count();
        $reservationsEnAttente = (clone $reservationsQuery)->where('statut', 'en_attente')->count();
        $reservationsConfirmees = (clone $reservationsQuery)->whereIn('statut', ['acceptee', 'colis_depose', 'en_transit', 'livre', 'livree'])->count();
        $reservationsAnnulees = (clone $reservationsQuery)->whereIn('statut', ['refusee', 'annulee', 'annule'])->count();

        // 5. Statistiques Financières Résumées
        $paiementsQuery = Paiement::whereHas('reservation', function ($q) use ($entrepriseId) {
            $q->where('entreprise_id', $entrepriseId)
              ->orWhereHas('voyage', fn($vq) => $vq->where('entreprise_id', $entrepriseId));
        });

        $revenusJour = (clone $paiementsQuery)->whereIn('statut', ['reussi', 'succes', 'paye'])
            ->whereDate('date_paiement', now()->today())
            ->sum('montant');

        $revenusMois = (clone $paiementsQuery)->whereIn('statut', ['reussi', 'succes', 'paye'])
            ->whereMonth('date_paiement', now()->month)
            ->whereYear('date_paiement', now()->year)
            ->sum('montant');

        $totalEncaisse = (clone $paiementsQuery)->whereIn('statut', ['reussi', 'succes', 'paye'])->sum('montant');
        $paiementsEnAttente = (clone $paiementsQuery)->whereIn('statut', ['en_attente', 'attente'])->sum('montant');

        // Fallback financier si les objets Paiement ne sont pas explicites
        $acceptedReservationsSum = (float) (clone $reservationsQuery)->whereIn('statut', ['acceptee', 'colis_depose', 'en_transit', 'livre', 'livree'])->sum('montant_total');
        if ((float) $totalEncaisse === 0.0 && $acceptedReservationsSum > 0) {
            $totalEncaisse = $acceptedReservationsSum;
            $revenusMois = $acceptedReservationsSum;
        }

        return response()->json([
            'status' => 'success',
            'dashboard' => [
                'activite' => [
                    'total_voyages' => $totalVoyages,
                    'a_venir' => $voyagesAvenir,
                    'en_cours' => $voyagesEnCours,
                    'termines' => $voyagesTermines,
                    'annules' => $voyagesAnnules,
                ],
                'colis' => [
                    'total_colis' => $totalColis,
                    'en_attente' => $colisEnAttente,
                    'en_transit' => $colisEnTransit,
                    'livres' => $colisLivres,
                    'annules' => $colisAnnules,
                ],
                'agents' => [
                    'total_agents' => $totalAgents,
                    'actifs' => $agentsActifs,
                    'en_voyage' => $agentsEnVoyage,
                    'indisponibles' => $agentsIndisponibles,
                ],
                'reservations' => [
                    'total' => $totalReservations,
                    'en_attente' => $reservationsEnAttente,
                    'confirmees' => $reservationsConfirmees,
                    'annulees' => $reservationsAnnulees,
                ],
                'finances' => [
                    'revenus_jour' => (float) $revenusJour,
                    'revenus_mois' => (float) $revenusMois,
                    'total_encaisse' => (float) $totalEncaisse,
                    'paiements_en_attente' => (float) $paiementsEnAttente,
                ],
            ],
        ]);
    }

    /**
     * Analyse détaillée des revenus de l'entreprise GP (Section 12 du cahier des charges).
     */
    public function revenus(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = auth('api')->user();

        if (! $user->isGerantEntreprise()) {
            return response()->json(['message' => 'Accès réservé au gérant.'], 403);
        }

        $entrepriseId = $user->getEntrepriseId();

        $periode = $request->query('periode', 'mois');
        $dateDebut = $request->query('date_debut');
        $dateFin = $request->query('date_fin');

        $acceptedResQuery = Reservation::where(function ($q) use ($entrepriseId) {
            $q->where('entreprise_id', $entrepriseId)
              ->orWhereHas('voyage', fn($vq) => $vq->where('entreprise_id', $entrepriseId));
        })->whereIn('statut', ['acceptee', 'colis_depose', 'en_transit', 'livre', 'livree']);

        // Application du filtre temporel
        if ($periode === 'aujourdhui') {
            $acceptedResQuery->whereDate('created_at', now()->today());
        } elseif ($periode === 'hier') {
            $acceptedResQuery->whereDate('created_at', now()->yesterday());
        } elseif ($periode === 'semaine') {
            $acceptedResQuery->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
        } elseif ($periode === 'mois') {
            $acceptedResQuery->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year);
        } elseif ($periode === 'annee') {
            $acceptedResQuery->whereYear('created_at', now()->year);
        } elseif ($periode === 'personnalise' && $dateDebut && $dateFin) {
            $acceptedResQuery->whereBetween('created_at', [$dateDebut.' 00:00:00', $dateFin.' 23:59:59']);
        }

        $totalReservations = (clone $acceptedResQuery)->count();
        $totalVoyages = Voyage::where('entreprise_id', $entrepriseId)->count();

        $paiementsSum = (float) Paiement::whereHas('reservation', function ($q) use ($entrepriseId) {
            $q->where('entreprise_id', $entrepriseId)
              ->orWhereHas('voyage', fn($vq) => $vq->where('entreprise_id', $entrepriseId));
        })->whereIn('statut', ['reussi', 'succes', 'paye'])->sum('montant');

        $resSum = (float) (clone $acceptedResQuery)->sum('montant_total');

        $totalChiffreAffaires = $resSum > 0 ? $resSum : $paiementsSum;
        $panierMoyen = $totalReservations > 0 ? (float) ($totalChiffreAffaires / $totalReservations) : 0.0;

        // Breakdown par Agent - Trié par chiffre d'affaires Décroissant
        $agents = AgentGp::with('user')->where('entreprise_id', $entrepriseId)->get();
        $parAgent = $agents->map(function ($agent) use ($periode, $dateDebut, $dateFin) {
            $vQuery = Voyage::where('agent_gp_id', $agent->id);
            $resQuery = Reservation::whereHas('voyage', fn($q) => $q->where('agent_gp_id', $agent->id))
                ->whereIn('statut', ['acceptee', 'colis_depose', 'en_transit', 'livre', 'livree']);

            if ($periode === 'aujourdhui') {
                $resQuery->whereDate('created_at', now()->today());
            } elseif ($periode === 'hier') {
                $resQuery->whereDate('created_at', now()->yesterday());
            } elseif ($periode === 'semaine') {
                $resQuery->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
            } elseif ($periode === 'mois') {
                $resQuery->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year);
            } elseif ($periode === 'annee') {
                $resQuery->whereYear('created_at', now()->year);
            } elseif ($periode === 'personnalise' && $dateDebut && $dateFin) {
                $resQuery->whereBetween('created_at', [$dateDebut.' 00:00:00', $dateFin.' 23:59:59']);
            }

            $vCount = $vQuery->count();
            $resCount = $resQuery->count();
            $ca = (float) $resQuery->sum('montant_total');

            return [
                'id' => $agent->id,
                'nom' => $agent->user?->nom ?? 'Agent',
                'prenom' => $agent->user?->prenom ?? 'GP',
                'nombre_voyages' => $vCount,
                'nombre_reservations' => $resCount,
                'chiffre_affaires' => $ca,
                'commission' => round($ca * 0.10, 2),
            ];
        })->sortByDesc('chiffre_affaires')->values();

        // Breakdown par Trajet
        $voyages = Voyage::where('entreprise_id', $entrepriseId)->get();
        $trajetsMap = [];
        foreach ($voyages as $v) {
            $trajetKey = $v->ville_depart.' ➔ '.$v->ville_destination;
            $rQuery = Reservation::where('voyage_id', $v->id)->whereIn('statut', ['acceptee', 'colis_depose', 'en_transit', 'livre', 'livree']);
            if ($periode === 'aujourdhui') {
                $rQuery->whereDate('created_at', now()->today());
            } elseif ($periode === 'hier') {
                $rQuery->whereDate('created_at', now()->yesterday());
            } elseif ($periode === 'semaine') {
                $rQuery->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
            } elseif ($periode === 'mois') {
                $rQuery->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year);
            } elseif ($periode === 'annee') {
                $rQuery->whereYear('created_at', now()->year);
            } elseif ($periode === 'personnalise' && $dateDebut && $dateFin) {
                $rQuery->whereBetween('created_at', [$dateDebut.' 00:00:00', $dateFin.' 23:59:59']);
            }

            $m = (float) $rQuery->sum('montant_total');
            if (! isset($trajetsMap[$trajetKey])) {
                $trajetsMap[$trajetKey] = 0.0;
            }
            $trajetsMap[$trajetKey] += $m;
        }

        $parTrajet = [];
        foreach ($trajetsMap as $trajet => $montant) {
            $pourcentage = $totalChiffreAffaires > 0 ? round(($montant / $totalChiffreAffaires) * 100, 1) : 0;
            $parTrajet[] = [
                'trajet' => $trajet,
                'montant' => $montant,
                'pourcentage' => $pourcentage,
            ];
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'total_chiffre_affaires' => (float) $totalChiffreAffaires,
                'total_reservations' => $totalReservations,
                'total_voyages' => $totalVoyages,
                'panier_moyen' => (float) $panierMoyen,
                'par_agent' => $parAgent,
                'par_trajet' => $parTrajet,
            ],
            'revenus' => [
                'total_chiffre_affaires' => (float) $totalChiffreAffaires,
                'total_reservations' => $totalReservations,
                'total_voyages' => $totalVoyages,
                'panier_moyen' => (float) $panierMoyen,
                'par_agent' => $parAgent,
                'par_trajet' => $parTrajet,
                'jour' => (float) $totalChiffreAffaires,
                'semaine' => (float) $totalChiffreAffaires,
                'mois' => (float) $totalChiffreAffaires,
            ],
        ]);
    }
}
