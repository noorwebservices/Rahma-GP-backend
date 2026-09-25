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
        $reservationsQuery = Reservation::where('entreprise_id', $entrepriseId);
        $totalReservations = (clone $reservationsQuery)->count();
        $reservationsEnAttente = (clone $reservationsQuery)->where('statut', 'en_attente')->count();
        $reservationsConfirmees = (clone $reservationsQuery)->where('statut', 'acceptee')->count();
        $reservationsAnnulees = (clone $reservationsQuery)->where('statut', 'annulee')->count();

        // 5. Statistiques Financières Résumées
        $paiementsQuery = Paiement::whereHas('reservation', function ($q) use ($entrepriseId) {
            $q->where('entreprise_id', $entrepriseId);
        });

        $revenusJour = (clone $paiementsQuery)->where('statut', 'succes')
            ->whereDate('date_paiement', now()->today())
            ->sum('montant');

        $revenusMois = (clone $paiementsQuery)->where('statut', 'succes')
            ->whereMonth('date_paiement', now()->month)
            ->whereYear('date_paiement', now()->year)
            ->sum('montant');

        $totalEncaisse = (clone $paiementsQuery)->where('statut', 'succes')->sum('montant');
        $paiementsEnAttente = (clone $paiementsQuery)->where('statut', 'en_attente')->sum('montant');

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

        $basePaiements = Paiement::whereHas('reservation', function ($q) use ($entrepriseId) {
            $q->where('entreprise_id', $entrepriseId);
        })->where('statut', 'succes');

        // Revenus du jour, semaine, mois
        $revenusJour = (clone $basePaiements)->whereDate('date_paiement', now()->today())->sum('montant');
        $revenusSemaine = (clone $basePaiements)->whereBetween('date_paiement', [now()->startOfWeek(), now()->endOfWeek()])->sum('montant');
        $revenusMois = (clone $basePaiements)->whereMonth('date_paiement', now()->month)->sum('montant');

        // Revenus par destination
        $revenusParDestination = DB::table('paiements')
            ->join('reservations', 'paiements.reservation_id', '=', 'reservations.id')
            ->join('voyages', 'reservations.voyage_id', '=', 'voyages.id')
            ->where('reservations.entreprise_id', $entrepriseId)
            ->where('paiements.statut', 'succes')
            ->select('voyages.ville_destination', DB::raw('SUM(paiements.montant) as total_revenus'), DB::raw('COUNT(reservations.id) as nombre_reservations'))
            ->groupBy('voyages.ville_destination')
            ->get();

        return response()->json([
            'status' => 'success',
            'revenus' => [
                'jour' => (float) $revenusJour,
                'semaine' => (float) $revenusSemaine,
                'mois' => (float) $revenusMois,
                'par_destination' => $revenusParDestination,
            ],
        ]);
    }
}
