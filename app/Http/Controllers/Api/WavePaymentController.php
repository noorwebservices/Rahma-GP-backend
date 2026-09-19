<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Paiement;
use App\Models\Reservation;
use App\Models\Revenus_voyageur;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WavePaymentController extends Controller
{
    /**
     * Initialiser une session de paiement Wave Checkout
     * POST /api/reservations/{reservation}/pay-wave
     */
    public function initiatePayment(Request $request, Reservation $reservation): JsonResponse
    {
        $user = $request->user();

        // 1. Vérifier si l'utilisateur est autorisé
        if (! $this->userIsParticipant($user, $reservation)) {
            return response()->json([
                'message' => 'Accès non autorisé à cette réservation.',
            ], 403);
        }

        // 2. Vérifier si un paiement réussi existe déjà
        $existingPaiement = Paiement::where('reservation_id', $reservation->id)->where('statut', 'reussi')->first();
        if ($existingPaiement) {
            return response()->json([
                'message' => 'Un paiement réussi existe déjà pour cette réservation.',
                'statut' => 'reussi',
            ], 422);
        }

        $montant = (float) ($reservation->montant_total ?? 0);
        if ($montant <= 0) {
            return response()->json([
                'message' => 'Le montant de la réservation doit être supérieur à zéro.',
            ], 422);
        }

        $apiKey = config('services.wave.api_key');
        $baseUrl = config('services.wave.base_url', 'https://api.wave.com/v1');
        $currency = config('services.wave.currency', 'XOF');
        $frontendUrl = config('app.frontend_url');
        $host = parse_url($frontendUrl, PHP_URL_HOST);
        if (! $host || str_contains($host, 'localhost') || str_contains($host, '127.0.0.1')) {
            $frontendUrl = 'https://rahmadelivery.com';
        }

        $errorUrl = $frontendUrl.'/client/booking/step-4?error=wave&reservation='.$reservation->id;
        $successUrl = $frontendUrl.'/client/messages?success=wave&reservation='.$reservation->id;

        if (str_starts_with($errorUrl, 'http://')) {
            $errorUrl = 'https://'.substr($errorUrl, 7);
        }
        if (str_starts_with($successUrl, 'http://')) {
            $successUrl = 'https://'.substr($successUrl, 7);
        }

        // 3. Appel à l'API Wave Checkout Session
        try {
            $response = Http::withToken($apiKey)
                ->acceptJson()
                ->post($baseUrl.'/checkout/sessions', [
                    'amount' => (string) (int) round($montant),
                    'currency' => $currency,
                    'error_url' => $errorUrl,
                    'success_url' => $successUrl,
                    'client_reference' => (string) $reservation->id,
                ]);

            if ($response->failed()) {
                Log::error('Erreur Wave API Checkout:', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return response()->json([
                    'message' => 'Échec de la communication avec l\'API Wave.',
                    'details' => $response->json() ?? $response->body(),
                ], 502);
            }

            $responseData = $response->json();
            $waveLaunchUrl = $responseData['wave_launch_url'] ?? null;
            $sessionId = $responseData['id'] ?? null;

            if (! $waveLaunchUrl) {
                return response()->json([
                    'message' => 'Réponse Wave invalide : URL de lancement absente.',
                ], 500);
            }

            // 4. Enregistrer ou mettre à jour la tentative de paiement en base
            Paiement::updateOrCreate(
                ['reservation_id' => $reservation->id],
                [
                    'montant' => $montant,
                    'reference' => $sessionId ?? ('WAVE-'.strtoupper(Str::random(10))),
                    'mode_paiement' => 'wave',
                    'statut' => 'en_attente',
                    'confirme_par' => $user->id,
                ]
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Session de paiement Wave créée avec succès.',
                'wave_launch_url' => $waveLaunchUrl,
                'session_id' => $sessionId,
            ]);
        } catch (\Exception $e) {
            Log::error('Exception lors de l\'initialisation Wave:', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Une erreur est survenue lors du démarrage du paiement Wave.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Vérifier l'état du paiement Wave pour une réservation
     * GET /api/reservations/{reservation}/wave-status
     */
    public function checkStatus(Request $request, Reservation $reservation): JsonResponse
    {
        $user = $request->user();

        if (! $this->userIsParticipant($user, $reservation)) {
            return response()->json(['message' => 'Accès non autorisé.'], 403);
        }

        $paiement = Paiement::where('reservation_id', $reservation->id)->first();

        if (! $paiement) {
            return response()->json([
                'statut' => 'aucun',
                'message' => 'Aucun paiement trouvé pour cette réservation.',
            ]);
        }

        if ($paiement->statut === 'reussi') {
            return response()->json([
                'statut' => 'reussi',
                'paiement' => $paiement,
            ]);
        }

        // Si en attente et qu'on a une référence de session Wave
        if ($paiement->reference && ! str_starts_with($paiement->reference, 'PAY-')) {
            $apiKey = config('services.wave.api_key');
            $baseUrl = config('services.wave.base_url', 'https://api.wave.com/v1');

            try {
                $response = Http::withToken($apiKey)
                    ->get($baseUrl.'/checkout/sessions/'.$paiement->reference);

                if ($response->successful()) {
                    $session = $response->json();
                    $paymentStatus = $session['payment_status'] ?? $session['checkout_status'] ?? $session['status'] ?? '';

                    if (in_array(strtolower((string) $paymentStatus), ['succeeded', 'complete', 'successful', 'paid'], true)) {
                        $this->markReservationPaid($reservation, $paiement, (float) ($session['amount'] ?? $paiement->montant), $paiement->reference);

                        return response()->json([
                            'statut' => 'reussi',
                            'paiement' => $paiement->fresh(),
                        ]);
                    }
                }
            } catch (\Exception $e) {
                Log::warning('Erreur de vérification du statut Wave:', ['error' => $e->getMessage()]);
            }
        }

        return response()->json([
            'statut' => $paiement->statut,
            'paiement' => $paiement,
        ]);
    }

    /**
     * Gérer les Webhooks envoyés par Wave
     * POST /api/wave/webhook
     */
    public function handleWebhook(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $signatureHeader = $request->header('Wave-Signature');
        $webhookSecret = config('services.wave.webhook_secret');

        if (! $this->verifySignature($payload, $signatureHeader, $webhookSecret)) {
            Log::warning('Signature de Webhook Wave invalide.');

            return response()->json(['message' => 'Signature invalide.'], 400);
        }

        $event = json_decode($payload, true);
        $eventType = $event['type'] ?? '';

        Log::info('Webhook Wave reçu :', ['event' => $eventType]);

        if ($eventType === 'checkout.session.completed') {
            $session = $event['data'] ?? [];
            $reservationId = $session['client_reference'] ?? null;
            $sessionId = $session['id'] ?? null;
            $montant = (float) ($session['amount'] ?? 0);

            if ($reservationId) {
                $reservation = Reservation::find($reservationId);
                if ($reservation) {
                    $paiement = Paiement::where('reservation_id', $reservation->id)->first();
                    if (! $paiement) {
                        $paiement = Paiement::create([
                            'reservation_id' => $reservation->id,
                            'montant' => $montant,
                            'reference' => $sessionId ?? ('WAVE-'.strtoupper(Str::random(10))),
                            'mode_paiement' => 'wave',
                            'statut' => 'en_attente',
                        ]);
                    }

                    $this->markReservationPaid($reservation, $paiement, $montant > 0 ? $montant : (float) $reservation->montant_total, $sessionId);
                }
            }
        }

        return response()->json(['status' => 'success'], 200);
    }

    /**
     * Marquer la réservation comme payée et créditer le voyageur
     */
    private function markReservationPaid(Reservation $reservation, Paiement $paiement, float $montant, ?string $sessionId = null): void
    {
        DB::transaction(function () use ($reservation, $paiement, $montant, $sessionId) {
            $paiement->update([
                'montant' => $montant,
                'reference' => $sessionId ?? $paiement->reference,
                'mode_paiement' => 'wave',
                'statut' => 'reussi',
                'date_paiement' => now(),
            ]);

            // Valider la réservation si elle était en attente
            if (in_array($reservation->statut, ['en_attente', 'demande_envoyee'], true)) {
                $reservation->update(['statut' => 'acceptee']);
            }

            // Créditer et effectuer le transfert automatique Wave vers le voyageur
            if ($reservation->voyage && $reservation->voyage->voyageur) {
                $this->executeAutomaticWavePayoutToVoyageur($reservation, $montant);
            }

            if ($reservation->client) {
                NotificationService::send(
                    $reservation->client->user_id,
                    'Paiement Wave reçu',
                    sprintf('Votre paiement Wave de %.0f %s pour la réservation %s est confirmé.', $montant, $reservation->voyage->devise ?? 'FCFA', $reservation->numero),
                    'paiement'
                );
            }
        });
    }

    /**
     * Effectuer un transfert automatique Wave (Payout) vers le numéro du voyageur (sans commission)
     */
    private function executeAutomaticWavePayoutToVoyageur(Reservation $reservation, float $montant): void
    {
        $voyageur = $reservation->voyage->voyageur ?? null;
        if (! $voyageur) {
            return;
        }

        $user = $voyageur->user ?? null;
        $phone = $user->telephone ?? $voyageur->telephone ?? null;

        if (! $phone) {
            Log::warning('Wave Payout impossible : Numéro de téléphone du voyageur manquant pour la réservation '.$reservation->id);

            return;
        }

        // Normaliser le numéro au format E.164 (ex: +221770000000)
        $cleanPhone = preg_replace('/[^0-9+]/', '', $phone);
        if (! str_starts_with($cleanPhone, '+')) {
            if (str_starts_with($cleanPhone, '221') || str_starts_with($cleanPhone, '225')) {
                $cleanPhone = '+'.$cleanPhone;
            } else {
                $cleanPhone = '+221'.ltrim($cleanPhone, '0');
            }
        }

        $apiKey = config('services.wave.api_key');
        $baseUrl = config('services.wave.base_url', 'https://api.wave.com/v1');

        try {
            $response = Http::withToken($apiKey)
                ->acceptJson()
                ->post($baseUrl.'/transfers', [
                    'amount' => (string) $montant,
                    'currency' => config('services.wave.currency', 'XOF'),
                    'mobile' => $cleanPhone,
                    'client_reference' => 'PAYOUT-'.$reservation->id,
                ]);

            if ($response->successful()) {
                Log::info('Transfert Wave Payout réussi vers le voyageur:', [
                    'reservation_id' => $reservation->id,
                    'voyageur_phone' => $cleanPhone,
                    'montant' => $montant,
                    'transfer_data' => $response->json(),
                ]);

                Revenus_voyageur::updateOrCreate(
                    ['reservation_id' => $reservation->id],
                    [
                        'voyageur_id' => $voyageur->id,
                        'montant' => $montant,
                        'statut' => 'paye',
                    ]
                );

                NotificationService::send(
                    $voyageur->user_id,
                    'Transfert Wave reçu !',
                    sprintf('Le paiement de %.0f %s pour la réservation %s a été directement transféré sur votre compte Wave (%s).', $montant, $reservation->voyage->devise ?? 'FCFA', $reservation->numero, $cleanPhone),
                    'paiement'
                );
            } else {
                Log::error('Échec du transfert Wave Payout au voyageur:', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                Revenus_voyageur::updateOrCreate(
                    ['reservation_id' => $reservation->id],
                    [
                        'voyageur_id' => $voyageur->id,
                        'montant' => $montant,
                        'statut' => 'disponible',
                    ]
                );

                NotificationService::send(
                    $voyageur->user_id,
                    'Paiement Client disponible',
                    sprintf('Un paiement de %.0f %s pour la réservation %s a été reçu. Il est disponible dans votre solde.', $montant, $reservation->voyage->devise ?? 'FCFA', $reservation->numero),
                    'paiement'
                );
            }
        } catch (\Exception $e) {
            Log::error('Exception lors du Wave Payout:', ['error' => $e->getMessage()]);

            Revenus_voyageur::updateOrCreate(
                ['reservation_id' => $reservation->id],
                [
                    'voyageur_id' => $voyageur->id,
                    'montant' => $montant,
                    'statut' => 'disponible',
                ]
            );
        }
    }

    /**
     * Vérifier si l'utilisateur est participant à la réservation
     */
    private function userIsParticipant($user, Reservation $reservation): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->client && $reservation->client_id === $user->client->id) {
            return true;
        }

        if ($user->voyageur && $reservation->voyage && $reservation->voyage->voyageur_id === $user->voyageur->id) {
            return true;
        }

        return false;
    }

    /**
     * Valider la signature HMAC SHA256 du Webhook Wave
     */
    private function verifySignature(string $payload, ?string $signatureHeader, ?string $secret): bool
    {
        if (! $signatureHeader || ! $secret) {
            return false;
        }

        $parts = explode(',', $signatureHeader);
        $timestamp = null;
        $signature = null;

        foreach ($parts as $part) {
            if (str_starts_with($part, 't=')) {
                $timestamp = substr($part, 2);
            } elseif (str_starts_with($part, 'v1=')) {
                $signature = substr($part, 3);
            }
        }

        if (! $timestamp || ! $signature) {
            return false;
        }

        $signedPayload = $timestamp.'.'.$payload;
        $expectedSignature = hash_hmac('sha256', $signedPayload, $secret);

        return hash_equals($expectedSignature, $signature);
    }
}
